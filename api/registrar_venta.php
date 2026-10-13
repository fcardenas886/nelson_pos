<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/precios_helper.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
verifyCsrfApi();

$user = currentUser();
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['items']) || !is_array($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'El carrito está vacío.']);
    exit;
}

$tipoDocumento = $input['tipo_documento'] ?? 'Boleta';
$clienteID = !empty($input['cliente_id']) ? (int)$input['cliente_id'] : null;
$cotizacionID = !empty($input['cotizacion_id']) ? (int)$input['cotizacion_id'] : null;
$descuentoGlobal = (int)($input['descuento_global'] ?? 0);
$montoPagado = (int)($input['monto_pagado'] ?? 0);
$vuelto = (int)($input['vuelto'] ?? 0);

// Pagos Mixtos o Pago Único
$pagosList = $input['pagos'] ?? [];
if (empty($pagosList)) {
    $metodoUnico = $input['metodo_pago'] ?? 'Efectivo';
    $pagosList[] = ['metodo' => $metodoUnico, 'monto' => 0];
}

try {
    $pdo = getDB();
    
    // Obtener turno abierto para el usuario
    $stmtTurno = $pdo->prepare("SELECT TurnoID FROM turnos WHERE UsuarioID = :uid AND Estado = 'Abierto' ORDER BY TurnoID DESC LIMIT 1");
    $stmtTurno->execute([':uid' => $user['id']]);
    $turno = $stmtTurno->fetch();
    
    if (!$turno) {
        echo json_encode(['success' => false, 'error' => 'No hay un turno de caja abierto para este usuario. Por favor, abre tu caja antes de vender.']);
        exit;
    }
    $turnoID = $turno['TurnoID'];

    $cfgStmt = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('PERMITIR_STOCK_NEGATIVO', 'CREDITO_INTERNO_ACTIVO', 'LOYALTY_ACTIVO', 'LOYALTY_PORCENTAJE')");
    $configApp = [];
    while ($cr = $cfgStmt->fetch(PDO::FETCH_ASSOC)) {
        $configApp[$cr['Clave']] = $cr['Valor'];
    }
    $permitirStockNegativo = ($configApp['PERMITIR_STOCK_NEGATIVO'] ?? 'false') === 'true';
    $creditoHabilitado = ($configApp['CREDITO_INTERNO_ACTIVO'] ?? 'true') === 'true';
    $loyaltyHabilitado = ($configApp['LOYALTY_ACTIVO'] ?? 'true') === 'true';
    $loyaltyPorcentaje = (float)($configApp['LOYALTY_PORCENTAJE'] ?? 1.0);

    $pdo->beginTransaction();

    // 1. Verificar stock y procesar ítems del carrito (aplicando promociones activas en backend)
    $subtotalBruto = 0;
    $itemsProcesados = [];
    $unidadesPorProducto = []; // unidades pedidas por producto sumando todas sus líneas (unidad + packs)

    foreach ($input['items'] as $item) {
        $pid = (int)$item['producto_id'];
        $cant = (float)$item['cantidad'];
        $factor = isset($item['factor']) && (float)$item['factor'] > 0 ? (float)$item['factor'] : 1.0;
        $nombreItem = !empty($item['nombre_item']) ? trim($item['nombre_item']) : null;
        $unidadesFisicas = $cant * $factor;

        if ($pid <= 0 || $cant <= 0) {
            throw new Exception("Cantidad inválida para el producto ID $pid.");
        }

        $stmtP = $pdo->prepare("SELECT ProductoID, Nombre, PrecioVenta, CostoCompra, Stock, EsAfecto, EsPrecioVariable FROM productos WHERE ProductoID = :pid FOR UPDATE");
        $stmtP->execute([':pid' => $pid]);
        $prod = $stmtP->fetch();

        if (!$prod) {
            throw new Exception("El producto ID $pid no fue encontrado.");
        }

        $unidadesPorProducto[$pid] = ($unidadesPorProducto[$pid] ?? 0) + $unidadesFisicas;
        if (!$permitirStockNegativo && $prod['Stock'] < $unidadesPorProducto[$pid]) {
            throw new Exception("Stock insuficiente para '{$prod['Nombre']}'. Disponible: {$prod['Stock']} unidades (requeridas: {$unidadesPorProducto[$pid]}).");
        }

        // Promoción activa y precio de esta presentación (unidad, pack o precio variable)
        $promo = obtenerPromoActiva($pdo, $pid);
        $precioUnitario = resolverPrecioUnitario($pdo, $prod, $factor, $item['precio_unitario'] ?? 0, $promo);

        $descItem = 0;

        if ($promo && $factor <= 1) {
            if ($promo['Tipo'] === 'DESCUENTO_UNIT') {
                $descUnit = (int)round($precioUnitario * ((float)$promo['DescuentoPorcentaje'] / 100));
                $descItem = (int)round($cant * $descUnit);
            } elseif ($promo['Tipo'] === 'MULTIBUY') {
                $cantMin = (int)$promo['CantidadMinima'];
                $precioOf = (int)$promo['PrecioOferta'];
                if ($cant >= $cantMin) {
                    $packs = (int)floor($cant / $cantMin);
                    $resto = $cant % $cantMin;
                    $subtotalConPromo = ($packs * $precioOf) + ($resto * $precioUnitario);
                    $subtotalNormal = $cant * $precioUnitario;
                    $descItem = max(0, $subtotalNormal - $subtotalConPromo);
                }
            }
        }

        $subtotalItem = max(0, (int)round(($cant * $precioUnitario) - $descItem));
        $subtotalBruto += $subtotalItem;

        $itemsProcesados[] = [
            'prod' => $prod,
            'cant' => $cant,
            'factor' => $factor,
            'nombre_item' => $nombreItem,
            'precio' => $precioUnitario,
            'costo' => (int)round(($prod['CostoCompra'] ?? 0) * $factor),
            'descuento' => $descItem,
            'subtotal' => $subtotalItem
        ];
    }

    $montoTotal = max(0, $subtotalBruto - $descuentoGlobal);

    // Un Vale de Devolución (Nota de Crédito / Cambio de Mercadería) reduce el total
    // de ESTA venta, en vez de figurar como un pago aparte. Ese monto ya se declaró
    // como venta la primera vez; si se cobrara de nuevo al precio lleno, el IVA de
    // ese monto quedaría declarado dos veces.
    $vale = null;
    $valeAplicado = 0;
    $codigoVale = trim($input['vale_codigo'] ?? '');
    if (!empty($codigoVale)) {
        if (is_numeric($codigoVale)) {
            $stmtVale = $pdo->prepare("
                SELECT ValeID, CodigoVale, MontoDisponible, Estado 
                FROM valesdevolucion 
                WHERE VentaID = :codigo AND Estado = 'Activo' AND MontoDisponible > 0
                FOR UPDATE
            ");
            $stmtVale->execute([':codigo' => (int)$codigoVale]);
        } else {
            $stmtVale = $pdo->prepare("
                SELECT ValeID, CodigoVale, MontoDisponible, Estado 
                FROM valesdevolucion 
                WHERE CodigoVale = :codigo 
                FOR UPDATE
            ");
            $stmtVale->execute([':codigo' => $codigoVale]);
        }
        $vale = $stmtVale->fetch();
        if (!$vale || $vale['Estado'] !== 'Activo') {
            throw new Exception("El vale o número de boleta '$codigoVale' no es válido, ya fue utilizado o no existe.");
        }
        
        $codigoValeReal = $vale['CodigoVale'];
        
        // Restricción para Ticket de Cambio (TC-)
        if (strpos($codigoValeReal, 'TC-') === 0) {
            if ($montoTotal < $vale['MontoDisponible']) {
                throw new Exception("Para cambios de mercadería, el total de la compra (" . formatCLP($montoTotal) . ") debe ser igual o mayor al valor del Ticket de Cambio (" . formatCLP($vale['MontoDisponible']) . ").");
            }
        }
        
        $valeAplicado = min((int)$vale['MontoDisponible'], $montoTotal);
    }

    // Descuentos manuales configurables según porcentaje permitido para cajeros
    $descuentoTotal = $descuentoGlobal;
    if ($user['rol'] === 'Cajero' && $descuentoTotal > 0) {
        // Cargar porcentaje máximo de descuento permitido sin clave de supervisor
        $stmtCfgPorc = $pdo->prepare("SELECT Valor FROM configuraciones WHERE Clave = 'POS_DESCUENTO_MAX_PORC'");
        $stmtCfgPorc->execute();
        $cfgVal = $stmtCfgPorc->fetchColumn();
        $maxPorcPermitido = ($cfgVal !== false) ? (float)$cfgVal : 5.0;

        $porcEfectivo = $subtotalBruto > 0 ? ($descuentoTotal / $subtotalBruto) * 100 : 0;

        // Si supera el porcentaje configurado o si es 0 (exigir siempre supervisor)
        if ($porcEfectivo > $maxPorcPermitido || $maxPorcPermitido <= 0) {
            $supervisorPass = trim($input['supervisor_pass'] ?? '');
            if (empty($supervisorPass)) {
                $porcFmt = number_format($porcEfectivo, 1, ',', '.');
                $maxFmt = number_format($maxPorcPermitido, 1, ',', '.');
                throw new Exception("El descuento aplicado ($porcFmt%) supera el límite permitido sin supervisión ($maxFmt%). Requiere clave de Administrador o Supervisor.");
            }

            $supId = verificarClaveSupervisor($pdo, $supervisorPass);
            if (!$supId) {
                throw new Exception("Clave de supervisor incorrecta para autorizar el descuento.");
            }
        }
    }

    // Los pagos declarados deben cubrir el monto restante (Total - Vale)
    $montoRestante = max(0, $montoTotal - $valeAplicado);
    $sumaPagos = 0;
    foreach ($pagosList as $pago) {
        $sumaPagos += (int)(($pago['monto'] ?? 0) > 0 ? $pago['monto'] : $montoRestante);
    }
    if (($sumaPagos + $valeAplicado) < $montoTotal) {
        throw new Exception("Los pagos declarados (" . formatCLP($sumaPagos + $valeAplicado) . ") no cubren el total de la venta (" . formatCLP($montoTotal) . ").");
    }

    // Calcular IVA (19%)
    $montoNeto = (int)round($montoTotal / 1.19);
    $montoIva = $montoTotal - $montoNeto;

    // Calcular puntos ganados (solo si el Club de Puntos está habilitado)
    $puntosGanados = 0;
    if ($loyaltyHabilitado && $loyaltyPorcentaje > 0) {
        $puntosGanados = (int)floor($montoTotal * ($loyaltyPorcentaje / 100));
    }

    // 2. Insertar Venta
    $stmtVenta = $pdo->prepare("
        INSERT INTO ventas (TurnoID, ClienteID, TipoDocumento, MontoNeto, MontoIva, DescuentoGlobal, MontoTotal, MontoPagado, Vuelto, PuntosGanados, Estado)
        VALUES (:turno, :cliente, :tipo, :neto, :iva, :desc, :total, :pagado, :vuelto, :puntos, 'Completada')
    ");
    $stmtVenta->execute([
        ':turno' => $turnoID,
        ':cliente' => $clienteID,
        ':tipo' => $tipoDocumento,
        ':neto' => $montoNeto,
        ':iva' => $montoIva,
        ':desc' => $descuentoGlobal,
        ':total' => $montoTotal,
        ':pagado' => $montoPagado > 0 ? $montoPagado : $montoTotal,
        ':vuelto' => $vuelto,
        ':puntos' => $puntosGanados
    ]);
    $ventaID = $pdo->lastInsertId();

    // Consumir el vale aplicado y dejar registro de en qué venta se canjeó
    if ($vale && $valeAplicado > 0) {
        $nuevoDisponible = (int)$vale['MontoDisponible'] - $valeAplicado;
        $stmtUpdVale = $pdo->prepare("UPDATE valesdevolucion SET MontoDisponible = :disp, Estado = :estado WHERE ValeID = :vale");
        $stmtUpdVale->execute([
            ':disp' => $nuevoDisponible,
            ':estado' => $nuevoDisponible <= 0 ? 'Usado' : 'Activo',
            ':vale' => $vale['ValeID']
        ]);
        $stmtCanje = $pdo->prepare("INSERT INTO valescanjes (ValeID, VentaID, Monto) VALUES (:vale, :vid, :monto)");
        $stmtCanje->execute([':vale' => $vale['ValeID'], ':vid' => $ventaID, ':monto' => $valeAplicado]);
    }

    // 3. Insertar DetalleVentas, Actualizar Stock y Kardex
    $stmtDetalle = $pdo->prepare("
        INSERT INTO detalleventas (VentaID, ProductoID, NombreItem, Cantidad, FactorConversion, PrecioUnitario, CostoUnitario, Descuento, EsAfecto, Subtotal)
        VALUES (:vid, :pid, :nombre_item, :cant, :factor, :precio, :costo, :desc, :afecto, :subtotal)
    ");
    
    $stmtUpdStock = $pdo->prepare("UPDATE productos SET Stock = Stock - :cant_fisica WHERE ProductoID = :pid");
    
    $stmtKardex = $pdo->prepare("
        INSERT INTO kardex (ProductoID, TipoTransaccion, VentaID, CantidadSalida, StockSaldo, ValorUnitario)
        VALUES (:pid, 'VENTA', :vid, :cant_fisica, :saldo, :val)
    ");

    $stockCorriente = [];
    foreach ($itemsProcesados as $item) {
        $p = $item['prod'];
        $cant = $item['cant'];
        $factor = $item['factor'];
        $unidadesFisicas = $cant * $factor;
        
        $stmtDetalle->execute([
            ':vid' => $ventaID,
            ':pid' => $p['ProductoID'],
            ':nombre_item' => $item['nombre_item'],
            ':cant' => $cant,
            ':factor' => $factor,
            ':precio' => $item['precio'],
            ':costo' => $item['costo'],
            ':desc' => $item['descuento'],
            ':afecto' => $p['EsAfecto'],
            ':subtotal' => $item['subtotal']
        ]);

        $stmtUpdStock->execute([':cant_fisica' => $unidadesFisicas, ':pid' => $p['ProductoID']]);

        // Saldo encadenado: si el producto aparece en varias líneas, cada una parte del saldo de la anterior
        $stockCorriente[$p['ProductoID']] = ($stockCorriente[$p['ProductoID']] ?? (float)$p['Stock']) - $unidadesFisicas;
        $nuevoStock = $stockCorriente[$p['ProductoID']];
        $stmtKardex->execute([
            ':pid' => $p['ProductoID'],
            ':vid' => $ventaID,
            ':cant_fisica' => $unidadesFisicas,
            ':saldo' => $nuevoStock,
            ':val' => $item['precio']
        ]);
    }

    // 4. Procesar Pagos (Múltiples / Pagos Mixtos / Crédito / Puntos)
    $stmtPago = $pdo->prepare("INSERT INTO pagosventa (VentaID, MetodoPago, Monto) VALUES (:vid, :metodo, :monto)");

    // Registrar pago por Vale de Devolución/Nota de Crédito si se aplicó
    if ($vale && $valeAplicado > 0) {
        $stmtPago->execute([
            ':vid' => $ventaID,
            ':metodo' => 'Vale Devolucion',
            ':monto' => $valeAplicado
        ]);
    }

    // Cargar cupo/puntos reales del cliente si algún pago los necesita, antes de aplicar nada.
    $cliente = null;
    $necesitaCliente = false;
    foreach ($pagosList as $pago) {
        $pMetodo = $pago['metodo'] ?? '';
        if (in_array($pMetodo, ['Credito', 'Fiado', 'Credito Interno'], true)) {
            if (!$creditoHabilitado) {
                throw new Exception("Las ventas a crédito/fiado están deshabilitadas en la configuración del sistema.");
            }
            $necesitaCliente = true;
        } elseif ($pMetodo === 'Puntos') {
            if (!$loyaltyHabilitado) {
                throw new Exception("El canje de puntos está deshabilitado en la configuración del sistema.");
            }
            $necesitaCliente = true;
        }
    }
    if ($necesitaCliente) {
        if (!$clienteID) {
            throw new Exception("Debes seleccionar un cliente para pagos a crédito o con puntos.");
        }
        $stmtCli = $pdo->prepare("SELECT LimiteCredito, SaldoDeudor, PuntosAcumulados FROM clientes WHERE ClienteID = :cid FOR UPDATE");
        $stmtCli->execute([':cid' => $clienteID]);
        $cliente = $stmtCli->fetch();
        if (!$cliente) {
            throw new Exception("El cliente seleccionado no existe.");
        }
    }
    $creditoUsado = 0;
    $puntosUsados = 0;

    foreach ($pagosList as $pago) {
        $metodo = $pago['metodo'] ?? 'Efectivo';
        if (in_array($metodo, ['Credito', 'Fiado'], true)) {
            $metodo = 'Credito Interno';
        }
        $montoRestante = max(0, $montoTotal - $valeAplicado);
        $montoPago = (int)(($pago['monto'] ?? 0) > 0 ? $pago['monto'] : $montoRestante);

        // Si el vale ya cubrió todo, el pago adicional es $0 y no se inserta
        if ($montoPago <= 0) {
            continue;
        }

        if ($metodo === 'Credito Interno') {
            if (!$creditoHabilitado) {
                throw new Exception("Las ventas a crédito/fiado están deshabilitadas en la configuración del sistema.");
            }
            $creditoUsado += $montoPago;
            $limite = (int)($cliente['LimiteCredito'] ?? 0);
            $saldoActual = (int)($cliente['SaldoDeudor'] ?? 0);
            if ($saldoActual + $creditoUsado > $limite) {
                throw new Exception("El cliente no tiene cupo de crédito suficiente (disponible: " . formatCLP(max(0, $limite - $saldoActual)) . ").");
            }
        } elseif ($metodo === 'Puntos') {
            if (!$loyaltyHabilitado) {
                throw new Exception("El canje de puntos está deshabilitado en la configuración del sistema.");
            }
            $puntosUsados += $montoPago;
            $puntosDisponibles = (int)($cliente['PuntosAcumulados'] ?? 0);
            if ($puntosUsados > $puntosDisponibles) {
                throw new Exception("El cliente no tiene suficientes puntos acumulados (disponibles: $puntosDisponibles).");
            }
        }

        $stmtPago->execute([
            ':vid' => $ventaID,
            ':metodo' => $metodo,
            ':monto' => $montoPago
        ]);

        // Manejar pago a Crédito/Fiado o Puntos para Clientes
        if ($clienteID) {
            if ($metodo === 'Credito Interno') {
                $stmtCred = $pdo->prepare("UPDATE clientes SET SaldoDeudor = COALESCE(SaldoDeudor, 0) + :monto WHERE ClienteID = :cid");
                $stmtCred->execute([':monto' => $montoPago, ':cid' => $clienteID]);
            } elseif ($metodo === 'Puntos') {
                $stmtPts = $pdo->prepare("UPDATE clientes SET PuntosAcumulados = GREATEST(0, COALESCE(PuntosAcumulados, 0) - :pts) WHERE ClienteID = :cid");
                $stmtPts->execute([':pts' => $montoPago, ':cid' => $clienteID]);
            }
        }
    }

    // Sumar Puntos Ganados al Cliente si aplica
    if ($clienteID && $puntosGanados > 0) {
        $stmtAddPts = $pdo->prepare("UPDATE clientes SET PuntosAcumulados = COALESCE(PuntosAcumulados, 0) + :pts WHERE ClienteID = :cid");
        $stmtAddPts->execute([':pts' => $puntosGanados, ':cid' => $clienteID]);
    }

    // Si la venta salió de una cotización, marcarla como convertida
    if ($cotizacionID) {
        $pdo->prepare("
            UPDATE cotizaciones SET Estado = 'Convertida'
            WHERE CotizacionID = :id AND Estado IN ('Pendiente', 'Restaurada')
        ")->execute([':id' => $cotizacionID]);
    }

    // 5. Emisión automática de cupones para Sorteo Activo (si aplica)
    $cuponesSorteo = [];
    $nombreParticipante = trim($input['sorteo_nombre_participante'] ?? '');
    $telefonoParticipante = trim($input['sorteo_telefono_participante'] ?? '');
    $sorteoActivoInfo = null;

    try {
        $stmtSorteo = $pdo->query("
            SELECT SorteoID, Titulo, Descripcion, TipoRegla, MontoMinimoCupon, RequiereCliente, ImprimirCuponEnTicket
            FROM sorteos
            WHERE Estado = 'Activo' 
              AND FechaInicio <= CURDATE() 
              AND FechaFin >= CURDATE()
            ORDER BY SorteoID DESC
            LIMIT 1
        ");
        $sorteoActivo = $stmtSorteo ? $stmtSorteo->fetch(PDO::FETCH_ASSOC) : null;

        if ($sorteoActivo) {
            $sorteoActivoInfo = $sorteoActivo;
            $puedeParticipar = true;
            if ($sorteoActivo['RequiereCliente'] && empty($clienteID)) {
                $puedeParticipar = false;
            }

            if ($puedeParticipar) {
                $montoBase = (float)($sorteoActivo['MontoMinimoCupon'] ?? 5000);
                $cantCupones = 0;

                if ($sorteoActivo['TipoRegla'] === 'por_venta') {
                    $cantCupones = ($montoTotal >= $montoBase || $montoBase <= 0) ? 1 : 0;
                } else {
                    if ($montoBase > 0) {
                        $cantCupones = (int)floor($montoTotal / $montoBase);
                    }
                }

                if ($cantCupones > 0) {
                    $stmtMaxNum = $pdo->prepare("SELECT COALESCE(MAX(NumeroCupon), 0) FROM sorteos_cupones WHERE SorteoID = :sid FOR UPDATE");
                    $stmtMaxNum->execute([':sid' => $sorteoActivo['SorteoID']]);
                    $ultimoNum = (int)$stmtMaxNum->fetchColumn();

                    // Si no se indicaron datos y hay cliente registrado, usar los del cliente
                    if (empty($nombreParticipante) && $clienteID) {
                        $stCliInfo = $pdo->prepare("SELECT Nombre, Telefono FROM clientes WHERE ClienteID = :cid");
                        $stCliInfo->execute([':cid' => $clienteID]);
                        $cliInfo = $stCliInfo->fetch(PDO::FETCH_ASSOC);
                        if ($cliInfo) {
                            $nombreParticipante = $cliInfo['Nombre'] ?? '';
                            $telefonoParticipante = $cliInfo['Telefono'] ?? '';
                        }
                    }

                    $stmtInsCupon = $pdo->prepare("
                        INSERT INTO sorteos_cupones (
                            SorteoID, VentaID, NumeroCupon, CodigoCupon, ClienteID,
                            NombreParticipante, TelefonoParticipante, MontoVenta
                        ) VALUES (
                            :sid, :vid, :num, :cod, :cid, :nom, :tel, :monto
                        )
                    ");

                    for ($i = 1; $i <= $cantCupones; $i++) {
                        $numCupon = $ultimoNum + $i;
                        $codCupon = sprintf("SOR-%05d", $numCupon);
                        $stmtInsCupon->execute([
                            ':sid' => $sorteoActivo['SorteoID'],
                            ':vid' => $ventaID,
                            ':num' => $numCupon,
                            ':cod' => $codCupon,
                            ':cid' => $clienteID ?: null,
                            ':nom' => $nombreParticipante ?: null,
                            ':tel' => $telefonoParticipante ?: null,
                            ':monto' => $montoTotal,
                        ]);
                        $cuponesSorteo[] = [
                            'cupon_id' => (int)$pdo->lastInsertId(),
                            'numero' => $numCupon,
                            'codigo' => $codCupon,
                            'titulo_sorteo' => $sorteoActivo['Titulo'],
                            'imprimir' => (bool)$sorteoActivo['ImprimirCuponEnTicket']
                        ];
                    }
                }
            }
        }
    } catch (Exception $eSorteo) {
        error_log("[Sorteos] Error al emitir cupones en venta $ventaID: " . $eSorteo->getMessage());
    }

    $pdo->commit();

    // Emisión de DTE (Boleta o Factura electrónica) si está configurado un proveedor
    $dteResponse = null;
    try {
        $provider = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave = 'DTE_PROVEEDOR'")->fetchColumn();
        if ($provider && $provider !== 'ninguno') {
            require_once __DIR__ . '/../includes/sii/SiiFacturacion.php';
            $dteResponse = SiiFacturacion::emitirDesdeVenta($ventaID);
        }
    } catch (Exception $dteEx) {
        $dteResponse = ['success' => false, 'error' => $dteEx->getMessage()];
    }

    // Comprobante de crédito interno: datos del cliente y su cuenta tras esta compra
    $creditoInfo = null;
    if ($creditoUsado > 0 && $clienteID) {
        $stmtCC = $pdo->prepare("SELECT Nombre, RutCuerpo, RutDv, LimiteCredito, SaldoDeudor FROM clientes WHERE ClienteID = :cid");
        $stmtCC->execute([':cid' => $clienteID]);
        $cc = $stmtCC->fetch();
        if ($cc) {
            $rut = null;
            if (!empty($cc['RutCuerpo'])) {
                $rut = number_format((int)$cc['RutCuerpo'], 0, '', '.') . '-' . $cc['RutDv'];
            }
            $creditoInfo = [
                'cliente' => $cc['Nombre'],
                'rut' => $rut,
                'monto' => $creditoUsado,
                'saldo_deudor' => (int)$cc['SaldoDeudor'],
                'cupo_disponible' => max(0, (int)$cc['LimiteCredito'] - (int)$cc['SaldoDeudor']),
            ];
        }
    }

    $resPayload = [
        'success' => true,
        'venta_id' => $ventaID,
        'total' => $montoTotal,
        'vuelto' => $vuelto,
        'puntos_ganados' => $puntosGanados,
        'fecha' => date('d/m/Y H:i:s')
    ];

    if (!empty($cuponesSorteo)) {
        $resPayload['sorteo'] = [
            'titulo' => $sorteoActivoInfo['Titulo'] ?? 'Sorteo',
            'cupones' => $cuponesSorteo,
            'imprimir' => !empty($sorteoActivoInfo['ImprimirCuponEnTicket'])
        ];
    }

    if ($dteResponse) {
        $resPayload['dte'] = $dteResponse;
    }

    if ($creditoInfo) {
        $resPayload['credito'] = $creditoInfo;
    }

    echo json_encode($resPayload);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
/**
 * =========================================================================================
 * 🔄 API DE SINCRONIZACIÓN DE VENTAS OFFLINE (PWA)
 * =========================================================================================
 * Recibe un lote de ventas procesadas localmente en el navegador durante un corte de luz o
 * internet, las valida, inserta en MySQL preservando su fecha/hora original, descuenta el
 * stock oficial en productos y registra los pagos y el Kardex correspondiente.
 */

ob_start();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/precios_helper.php';

if (empty($_SESSION['usuario'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user = currentUser();
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['ventas']) || !is_array($input['ventas'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'No se recibieron ventas para sincronizar.']);
    exit;
}

try {
    $pdo = getDB();

    // Obtener turno abierto para el usuario o el más reciente
    $stmtTurno = $pdo->prepare("SELECT TurnoID FROM turnos WHERE UsuarioID = :uid AND Estado = 'Abierto' ORDER BY TurnoID DESC LIMIT 1");
    $stmtTurno->execute([':uid' => $user['id']]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        // Si la luz se cortó antes de abrir turno o se cerró, buscar el último turno o crear uno de contingencia
        $stmtUltimo = $pdo->prepare("SELECT TurnoID FROM turnos WHERE UsuarioID = :uid ORDER BY TurnoID DESC LIMIT 1");
        $stmtUltimo->execute([':uid' => $user['id']]);
        $turno = $stmtUltimo->fetch(PDO::FETCH_ASSOC);

        if (!$turno) {
            // Crear turno de contingencia si el usuario no tenía ninguno
            $stmtNuevoTurno = $pdo->prepare("
                INSERT INTO turnos (CajaID, UsuarioID, FechaApertura, MontoApertura, Estado, Observaciones)
                VALUES (1, :uid, NOW(), 0, 'Abierto', 'Apertura automática por sincronización de contingencia offline')
            ");
            $stmtNuevoTurno->execute([':uid' => $user['id']]);
            $turnoID = (int)$pdo->lastInsertId();
        } else {
            $turnoID = (int)$turno['TurnoID'];
        }
    } else {
        $turnoID = (int)$turno['TurnoID'];
    }

    $cfgStmt = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('PERMITIR_STOCK_NEGATIVO', 'CREDITO_INTERNO_ACTIVO', 'LOYALTY_ACTIVO', 'LOYALTY_PORCENTAJE')");
    $configApp = [];
    while ($cr = $cfgStmt->fetch(PDO::FETCH_ASSOC)) {
        $configApp[$cr['Clave']] = $cr['Valor'];
    }
    $permitirStockNegativo = ($configApp['PERMITIR_STOCK_NEGATIVO'] ?? 'false') === 'true';
    $creditoHabilitado = ($configApp['CREDITO_INTERNO_ACTIVO'] ?? 'true') === 'true';
    $loyaltyHabilitado = ($configApp['LOYALTY_ACTIVO'] ?? 'true') === 'true';
    $loyaltyPorcentaje = (float)($configApp['LOYALTY_PORCENTAJE'] ?? 1.0);

    $sincronizadas = [];
    $errores = [];

    // Sentencias preparadas para reutilización eficiente en el bucle
    $stmtVenta = $pdo->prepare("
        INSERT INTO ventas (TurnoID, ClienteID, TipoDocumento, FechaVenta, MontoNeto, MontoIva, DescuentoGlobal, MontoTotal, MontoPagado, Vuelto, PuntosGanados, Estado)
        VALUES (:turno, :cliente, :tipo, :fecha, :neto, :iva, :desc, :total, :pagado, :vuelto, :puntos, 'Completada')
    ");

    $stmtDetalle = $pdo->prepare("
        INSERT INTO detalleventas (VentaID, ProductoID, NombreItem, Cantidad, FactorConversion, PrecioUnitario, CostoUnitario, Descuento, EsAfecto, Subtotal)
        VALUES (:vid, :pid, :nombre_item, :cant, :factor, :precio, :costo, :desc, :afecto, :subtotal)
    ");

    $stmtUpdStock = $pdo->prepare("UPDATE productos SET Stock = Stock - :cant_fisica WHERE ProductoID = :pid");

    $stmtKardex = $pdo->prepare("
        INSERT INTO kardex (ProductoID, TipoTransaccion, VentaID, CantidadSalida, StockSaldo, ValorUnitario)
        VALUES (:pid, 'VENTA', :vid, :cant_fisica, :saldo, :val)
    ");

    $stmtPago = $pdo->prepare("INSERT INTO pagosventa (VentaID, MetodoPago, Monto) VALUES (:vid, :metodo, :monto)");
    $stmtAddPts = $pdo->prepare("UPDATE clientes SET PuntosAcumulados = COALESCE(PuntosAcumulados, 0) + :pts WHERE ClienteID = :cid");
    $stmtAddCred = $pdo->prepare("UPDATE clientes SET SaldoDeudor = COALESCE(SaldoDeudor, 0) + :monto WHERE ClienteID = :cid");

    foreach ($input['ventas'] as $v) {
        $tempId = $v['id_temporal'] ?? '';
        if (empty($tempId)) continue;

        try {
            $pdo->beginTransaction();

            $fechaVenta = !empty($v['fecha_creacion']) ? $v['fecha_creacion'] : date('Y-m-d H:i:s');
            $tipoDoc = $v['tipo_documento'] ?? 'Boleta';
            $clienteID = !empty($v['cliente_id']) ? (int)$v['cliente_id'] : null;
            $descGlobal = (int)($v['descuento_global'] ?? 0);
            $montoPagado = (int)($v['monto_pagado'] ?? 0);
            $vuelto = (int)($v['vuelto'] ?? 0);
            $items = $v['items'] ?? [];
            $pagos = $v['pagos'] ?? [];

            // Calcular montos acumulados
            $subtotalBruto = 0;
            $itemsProcesados = [];

            foreach ($items as $it) {
                $pid = (int)$it['producto_id'];
                $cant = (float)$it['cantidad'];
                $factor = isset($it['factor']) && (float)$it['factor'] > 0 ? (float)$it['factor'] : 1.0;
                $unidadesFisicas = $cant * $factor;

                if ($pid <= 0 || $cant <= 0) continue;

                $stmtP = $pdo->prepare("SELECT ProductoID, Nombre, PrecioVenta, CostoCompra, Stock, EsAfecto, EsPrecioVariable FROM productos WHERE ProductoID = :pid FOR UPDATE");
                $stmtP->execute([':pid' => $pid]);
                $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new Exception("El producto ID $pid no fue encontrado en el catálogo del servidor.");
                }

                // Misma regla de precio que una venta online: el precio de la caja solo vale para Precio Variable
                $precioUnitario = resolverPrecioUnitario($pdo, $prod, $factor, $it['precio_unitario'] ?? 0, obtenerPromoActiva($pdo, $pid));
                $subItem = (int)round($cant * $precioUnitario);
                $subtotalBruto += $subItem;

                $itemsProcesados[] = [
                    'prod' => $prod,
                    'cant' => $cant,
                    'factor' => $factor,
                    'unidadesFisicas' => $unidadesFisicas,
                    'nombre_item' => $it['nombre_item'] ?? $prod['Nombre'],
                    'precio' => $precioUnitario,
                    'costo' => (int)$prod['CostoCompra'],
                    'descuento' => 0,
                    'subtotal' => $subItem
                ];
            }

            if (empty($itemsProcesados)) {
                throw new Exception("La venta offline no contiene ítems válidos.");
            }

            $montoTotal = max(0, $subtotalBruto - $descGlobal);
            $montoNeto = (int)round($montoTotal / 1.19);
            $montoIva = $montoTotal - $montoNeto;
            $puntosGanados = 0;
            if ($loyaltyHabilitado && $loyaltyPorcentaje > 0) {
                $puntosGanados = (int)floor($montoTotal * ($loyaltyPorcentaje / 100));
            }

            // 1. Insertar cabecera de venta
            $stmtVenta->execute([
                ':turno' => $turnoID,
                ':cliente' => $clienteID,
                ':tipo' => $tipoDoc,
                ':fecha' => $fechaVenta,
                ':neto' => $montoNeto,
                ':iva' => $montoIva,
                ':desc' => $descGlobal,
                ':total' => $montoTotal,
                ':pagado' => $montoPagado > 0 ? $montoPagado : $montoTotal,
                ':vuelto' => $vuelto,
                ':puntos' => $puntosGanados
            ]);
            $ventaID = (int)$pdo->lastInsertId();

            // 2. Insertar detalles y actualizar stock
            $stockCorriente = [];
            foreach ($itemsProcesados as $ip) {
                $stmtDetalle->execute([
                    ':vid' => $ventaID,
                    ':pid' => $ip['prod']['ProductoID'],
                    ':nombre_item' => $ip['nombre_item'],
                    ':cant' => $ip['cant'],
                    ':factor' => $ip['factor'],
                    ':precio' => $ip['precio'],
                    ':costo' => $ip['costo'],
                    ':desc' => $ip['descuento'],
                    ':afecto' => $ip['prod']['EsAfecto'],
                    ':subtotal' => $ip['subtotal']
                ]);

                $stmtUpdStock->execute([
                    ':cant_fisica' => $ip['unidadesFisicas'],
                    ':pid' => $ip['prod']['ProductoID']
                ]);

                // Saldo encadenado: si el producto aparece en varias líneas, cada una parte del saldo de la anterior
                $pidK = $ip['prod']['ProductoID'];
                $stockCorriente[$pidK] = ($stockCorriente[$pidK] ?? (float)$ip['prod']['Stock']) - $ip['unidadesFisicas'];
                $nuevoStock = $stockCorriente[$pidK];
                $stmtKardex->execute([
                    ':pid' => $ip['prod']['ProductoID'],
                    ':vid' => $ventaID,
                    ':cant_fisica' => $ip['unidadesFisicas'],
                    ':saldo' => $nuevoStock,
                    ':val' => $ip['precio']
                ]);
            }

            // 3. Insertar pagos
            if (!empty($pagos)) {
                foreach ($pagos as $p) {
                    $montoP = (int)($p['monto'] ?? $montoTotal);
                    if ($montoP > 0) {
                        $metodoP = $p['metodo'] ?? 'Efectivo';
                        if (in_array($metodoP, ['Credito', 'Fiado', 'Credito Interno'], true)) {
                            $metodoP = 'Credito Interno';
                            if (!$creditoHabilitado) {
                                throw new Exception("Las ventas a crédito/fiado están deshabilitadas en la configuración del sistema.");
                            }
                        } elseif ($metodoP === 'Puntos') {
                            if (!$loyaltyHabilitado) {
                                throw new Exception("El Club de Puntos está deshabilitado en la configuración del sistema.");
                            }
                        }
                        $stmtPago->execute([
                            ':vid' => $ventaID,
                            ':metodo' => $metodoP,
                            ':monto' => $montoP
                        ]);
                        if ($clienteID && $metodoP === 'Credito Interno') {
                            $stmtAddCred->execute([':monto' => $montoP, ':cid' => $clienteID]);
                        }
                    }
                }
            } else {
                $stmtPago->execute([
                    ':vid' => $ventaID,
                    ':metodo' => 'Efectivo',
                    ':monto' => $montoTotal
                ]);
            }

            // 4. Sumar Puntos Ganados al Cliente si aplica
            if ($clienteID && $puntosGanados > 0) {
                $stmtAddPts->execute([':pts' => $puntosGanados, ':cid' => $clienteID]);
            }

            $pdo->commit();

            $sincronizadas[] = [
                'id_temporal' => $tempId,
                'venta_id' => $ventaID,
                'total' => $montoTotal
            ];

        } catch (Exception $eSale) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = [
                'id_temporal' => $tempId,
                'error' => $eSale->getMessage()
            ];
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'sincronizadas' => $sincronizadas,
        'total_sincronizadas' => count($sincronizadas),
        'errores' => $errores
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Fallo general en sincronización: ' . $e->getMessage()
    ]);
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sii/SiiRcvClient.php';

// Control de acceso: solo Administradores y Supervisores
if (empty($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Administrador', 'Supervisor'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
verifyCsrfApi();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'consultar';

$pdo = getDB();

try {
    // -------------------------------------------------------------
    // Guardar credenciales de acceso al SII
    // -------------------------------------------------------------
    if ($action === 'guardar_credenciales') {
        $rutUsuario = trim($input['rut_usuario'] ?? '');
        $claveUsuario = trim($input['clave_usuario'] ?? '');
        $rutEmpresa = trim($input['rut_empresa'] ?? '');

        $stmt = $pdo->prepare("INSERT INTO configuraciones (Clave, Valor) VALUES (:clave, :valor) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor)");

        if (!empty($rutUsuario)) {
            $stmt->execute([':clave' => 'SII_RUT_USUARIO', ':valor' => $rutUsuario]);
        }
        if (!empty($claveUsuario)) {
            $stmt->execute([':clave' => 'SII_CLAVE_USUARIO', ':valor' => $claveUsuario]);
        }
        if (!empty($rutEmpresa)) {
            $stmt->execute([':clave' => 'SII_RUT_EMPRESA', ':valor' => $rutEmpresa]);
        }

        echo json_encode(['success' => true, 'mensaje' => 'Credenciales del SII guardadas correctamente en el sistema.']);
        exit;
    }

    // -------------------------------------------------------------
    // Consultar Compras o Ventas (Cache-First + Sincronización SII)
    // -------------------------------------------------------------
    if ($action === 'consultar') {
        $periodo = trim($input['periodo'] ?? date('Ym'));
        $operacion = strtoupper(trim($input['operacion'] ?? 'COMPRA'));
        $modo = trim($input['modo'] ?? 'sii'); // 'sii' o 'demo'
        $forzarSync = !empty($input['forzar_sync']);
        $guardarCreds = !empty($input['guardar_credenciales']);

        // 1. Si no se fuerza sincronización y es modo 'sii', revisar si ya existe en BD local
        if (!$forzarSync && $modo !== 'demo') {
            $stmtCache = $pdo->prepare("
                SELECT * FROM sii_documentos_rcv 
                WHERE Periodo = :per AND Operacion = :op 
                ORDER BY FechaEmision DESC, Folio DESC
            ");
            $stmtCache->execute([':per' => $periodo, ':op' => $operacion]);
            $cachedDocs = $stmtCache->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($cachedDocs)) {
                // Calcular totales de los documentos en caché
                $totales = [
                    'cantidad_documentos' => 0,
                    'total_neto' => 0,
                    'total_exento' => 0,
                    'total_iva' => 0,
                    'total_general' => 0
                ];

                $documentos = [];
                $fechaSync = $cachedDocs[0]['FechaSincronizacion'] ?? null;

                foreach ($cachedDocs as $cd) {
                    $esNC = ((int)$cd['TipoDTE'] === 61);
                    $signo = $esNC ? -1 : 1;

                    $totales['cantidad_documentos']++;
                    $totales['total_neto'] += ((int)$cd['MontoNeto'] * $signo);
                    $totales['total_exento'] += ((int)$cd['MontoExento'] * $signo);
                    $totales['total_iva'] += ((int)$cd['MontoIVA'] * $signo);
                    $totales['total_general'] += ((int)$cd['MontoTotal'] * $signo);

                    $documentos[] = [
                        'tipo_dte' => (int)$cd['TipoDTE'],
                        'nombre_tipo' => $cd['NombreTipo'],
                        'folio' => (int)$cd['Folio'],
                        'rut_contraparte' => $cd['RutContraparte'],
                        'rut_cuerpo' => (int)$cd['RutCuerpo'],
                        'razon_social' => $cd['RazonSocial'],
                        'fecha_emision' => $cd['FechaEmision'],
                        'monto_neto' => (int)$cd['MontoNeto'],
                        'monto_exento' => (int)$cd['MontoExento'],
                        'monto_iva' => (int)$cd['MontoIVA'],
                        'monto_total' => (int)$cd['MontoTotal'],
                        'estado' => $cd['EstadoSII'],
                        'es_nota_credito' => $esNC,
                        'conciliado' => (bool)$cd['Conciliado'],
                        'compra_local_id' => $cd['LocalID']
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'origen' => 'db',
                    'fecha_sincronizacion' => $fechaSync,
                    'periodo' => $periodo,
                    'operacion' => $operacion,
                    'totales' => $totales,
                    'documentos' => $documentos,
                    'total_documentos' => count($documentos)
                ]);
                exit;
            }
        }

        // 2. Si no estaba en BD o se forzó sincronización:
        $rutUsuario = trim($input['rut_usuario'] ?? '');
        $claveUsuario = trim($input['clave_usuario'] ?? '');
        $rutEmpresa = trim($input['rut_empresa'] ?? '');

        if (empty($rutUsuario) || empty($claveUsuario) || empty($rutEmpresa)) {
            $configs = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('SII_RUT_USUARIO', 'SII_CLAVE_USUARIO', 'SII_RUT_EMPRESA', 'MINIMARKET_RUT')")->fetchAll();
            $confMap = [];
            foreach ($configs as $c) {
                $confMap[$c['Clave']] = $c['Valor'];
            }
            if (empty($rutUsuario)) $rutUsuario = $confMap['SII_RUT_USUARIO'] ?? '';
            if (empty($claveUsuario)) $claveUsuario = $confMap['SII_CLAVE_USUARIO'] ?? '';
            if (empty($rutEmpresa)) $rutEmpresa = $confMap['SII_RUT_EMPRESA'] ?? ($confMap['MINIMARKET_RUT'] ?? '');
        }

        if ($guardarCreds && !empty($rutUsuario) && !empty($claveUsuario) && !empty($rutEmpresa)) {
            $stmt = $pdo->prepare("INSERT INTO configuraciones (Clave, Valor) VALUES (:clave, :valor) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor)");
            $stmt->execute([':clave' => 'SII_RUT_USUARIO', ':valor' => $rutUsuario]);
            $stmt->execute([':clave' => 'SII_CLAVE_USUARIO', ':valor' => $claveUsuario]);
            $stmt->execute([':clave' => 'SII_RUT_EMPRESA', ':valor' => $rutEmpresa]);
        }

        $resultado = null;

        if ($modo === 'demo') {
            $resultado = SiiRcvClient::generarDatosDemo($periodo, $operacion);
        } else {
            if (empty($rutUsuario) || empty($claveUsuario)) {
                echo json_encode(['success' => false, 'error' => 'Por favor ingresa o guarda tu RUT y Clave del SII.']);
                exit;
            }
            if (empty($rutEmpresa)) {
                echo json_encode(['success' => false, 'error' => 'RUT de empresa no configurado.']);
                exit;
            }

            $client = new SiiRcvClient();
            if (!$client->login($rutUsuario, $claveUsuario)) {
                echo json_encode(['success' => false, 'error' => $client->getLastError() ?: 'Error al iniciar sesión en el SII.']);
                exit;
            }

            $resultado = $client->obtenerDetalle($rutEmpresa, $periodo, $operacion);
        }

        if (empty($resultado['success'])) {
            echo json_encode(['success' => false, 'error' => $resultado['error'] ?? 'No se recuperaron registros.']);
            exit;
        }

        $documentos = $resultado['documentos'] ?? [];

        // 3. Conciliación con registros locales
        if ($operacion === 'COMPRA') {
            $stmtComp = $pdo->query("
                SELECT c.CompraID, c.NumeroDocumento, c.MontoTotal, c.FechaCompra, p.RutCuerpo
                FROM compras c
                JOIN proveedores p ON c.ProveedorID = p.ProveedorID
            ");
            $comprasLocales = $stmtComp->fetchAll();
            $mapCompras = [];
            foreach ($comprasLocales as $cl) {
                $f = trim($cl['NumeroDocumento']);
                $rc = (int)$cl['RutCuerpo'];
                if ($f !== '') {
                    $mapCompras["{$rc}_{$f}"] = $cl;
                    $mapCompras["folio_{$f}"] = $cl;
                }
            }

            foreach ($documentos as &$doc) {
                $folioStr = (string)$doc['folio'];
                $rutCuerpo = (int)($doc['rut_cuerpo'] ?? 0);
                $key1 = "{$rutCuerpo}_{$folioStr}";
                $key2 = "folio_{$folioStr}";

                if (isset($mapCompras[$key1])) {
                    $doc['conciliado'] = true;
                    $doc['compra_local_id'] = $mapCompras[$key1]['CompraID'];
                } elseif (isset($mapCompras[$key2])) {
                    $doc['conciliado'] = true;
                    $doc['compra_local_id'] = $mapCompras[$key2]['CompraID'];
                } else {
                    $doc['conciliado'] = false;
                    $doc['compra_local_id'] = null;
                }
            }
            unset($doc);

        } else {
            $stmtDte = $pdo->query("SELECT Folio, VentaID FROM dte_emitidos");
            $dtesLocales = $stmtDte->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($documentos as &$doc) {
                $fol = (int)$doc['folio'];
                if (isset($dtesLocales[$fol])) {
                    $doc['conciliado'] = true;
                    $doc['venta_local_id'] = $dtesLocales[$fol];
                } else {
                    $doc['conciliado'] = ($doc['tipo_dte'] === 39);
                }
            }
            unset($doc);
        }

        // 4. Guardar en Base de Datos Local (sii_documentos_rcv) si no es modo demo
        if ($modo !== 'demo' && !empty($documentos)) {
            $stmtIns = $pdo->prepare("
                INSERT INTO sii_documentos_rcv 
                (Operacion, Periodo, FechaEmision, TipoDTE, NombreTipo, Folio, RutContraparte, RutCuerpo, RazonSocial, MontoNeto, MontoExento, MontoIVA, MontoTotal, EstadoSII, Conciliado, LocalID, FechaSincronizacion)
                VALUES (:op, :per, :fch, :tipo, :ntipo, :fol, :rutc, :cuerpo, :razon, :neto, :exe, :iva, :total, :estado, :conc, :lid, NOW())
                ON DUPLICATE KEY UPDATE
                    NombreTipo = VALUES(NombreTipo),
                    RazonSocial = VALUES(RazonSocial),
                    MontoNeto = VALUES(MontoNeto),
                    MontoExento = VALUES(MontoExento),
                    MontoIVA = VALUES(MontoIVA),
                    MontoTotal = VALUES(MontoTotal),
                    EstadoSII = VALUES(EstadoSII),
                    Conciliado = VALUES(Conciliado),
                    LocalID = VALUES(LocalID),
                    FechaSincronizacion = NOW()
            ");

            foreach ($documentos as $d) {
                $fchValida = !empty($d['fecha_emision']) ? date('Y-m-d', strtotime(str_replace('/', '-', $d['fecha_emision']))) : (substr($periodo, 0, 4) . '-' . substr($periodo, 4, 2) . '-01');

                $stmtIns->execute([
                    ':op' => $operacion,
                    ':per' => $periodo,
                    ':fch' => $fchValida,
                    ':tipo' => (int)$d['tipo_dte'],
                    ':ntipo' => $d['nombre_tipo'],
                    ':fol' => (int)$d['folio'],
                    ':rutc' => $d['rut_contraparte'],
                    ':cuerpo' => (int)($d['rut_cuerpo'] ?? 0),
                    ':razon' => $d['razon_social'],
                    ':neto' => (int)$d['monto_neto'],
                    ':exe' => (int)$d['monto_exento'],
                    ':iva' => (int)$d['monto_iva'],
                    ':total' => (int)$d['monto_total'],
                    ':estado' => $d['estado'] ?? 'REGISTRO',
                    ':conc' => !empty($d['conciliado']) ? 1 : 0,
                    ':lid' => $d['compra_local_id'] ?? null
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'origen' => ($modo === 'demo' ? 'demo' : 'sii'),
            'fecha_sincronizacion' => date('Y-m-d H:i:s'),
            'periodo' => $periodo,
            'operacion' => $operacion,
            'totales' => $resultado['totales'] ?? [],
            'documentos' => $documentos,
            'total_documentos' => count($documentos)
        ]);
        exit;
    }

    // -------------------------------------------------------------
    // Cuadratura Diaria (Caja POS vs. SII)
    // -------------------------------------------------------------
    if ($action === 'cuadratura_diaria') {
        $periodo = trim($input['periodo'] ?? date('Ym'));
        $year = (int)substr($periodo, 0, 4);
        $month = (int)substr($periodo, 4, 2);

        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // 1. Obtener ventas diarias de Caja POS (tabla ventas)
        $stmtVentas = $pdo->prepare("
            SELECT 
                DATE(FechaVenta) as fecha,
                COALESCE(SUM(MontoTotal), 0) as total_caja,
                COUNT(VentaID) as cant_caja
            FROM ventas
            WHERE Estado = 'Completada' AND DATE_FORMAT(FechaVenta, '%Y%m') = :per
            GROUP BY DATE(FechaVenta)
        ");
        $stmtVentas->execute([':per' => $periodo]);
        $ventasCajaMap = [];
        foreach ($stmtVentas->fetchAll(PDO::FETCH_ASSOC) as $vc) {
            $ventasCajaMap[$vc['fecha']] = [
                'total' => (int)$vc['total_caja'],
                'cant' => (int)$vc['cant_caja']
            ];
        }

        // 2. Obtener ventas diarias registradas en SII (tabla sii_documentos_rcv)
        $stmtSii = $pdo->prepare("
            SELECT 
                FechaEmision as fecha,
                COALESCE(SUM(CASE WHEN TipoDTE = 61 THEN -MontoTotal ELSE MontoTotal END), 0) as total_sii,
                COUNT(ID) as cant_sii
            FROM sii_documentos_rcv
            WHERE Operacion = 'VENTA' AND Periodo = :per
            GROUP BY FechaEmision
        ");
        $stmtSii->execute([':per' => $periodo]);
        $ventasSiiMap = [];
        foreach ($stmtSii->fetchAll(PDO::FETCH_ASSOC) as $vs) {
            $ventasSiiMap[$vs['fecha']] = [
                'total' => (int)$vs['total_sii'],
                'cant' => (int)$vs['cant_sii']
            ];
        }

        // 3. Armar día por día
        $dias = [];
        $resumenMes = [
            'total_caja' => 0,
            'total_sii' => 0,
            'diferencia' => 0,
            'dias_cuadrados' => 0,
            'dias_con_diferencia' => 0,
            'dias_sin_ventas' => 0
        ];

        $stmtUpsertCuad = $pdo->prepare("
            INSERT INTO sii_cuadratura_diaria 
            (Fecha, Periodo, TotalCaja, CantVentasCaja, TotalSII, CantDtesSII, Diferencia, Estado, FechaActualizacion)
            VALUES (:fch, :per, :tcaja, :ccaja, :tsii, :csii, :dif, :est, NOW())
            ON DUPLICATE KEY UPDATE
                TotalCaja = VALUES(TotalCaja),
                CantVentasCaja = VALUES(CantVentasCaja),
                TotalSII = VALUES(TotalSII),
                CantDtesSII = VALUES(CantDtesSII),
                Diferencia = VALUES(Diferencia),
                Estado = VALUES(Estado),
                FechaActualizacion = NOW()
        ");

        $hoy = date('Y-m-d');

        for ($d = 1; $d <= $diasEnMes; $d++) {
            $fechaStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            
            // No evaluar días futuros del mes corriente
            if ($fechaStr > $hoy) {
                break;
            }

            $caja = $ventasCajaMap[$fechaStr] ?? ['total' => 0, 'cant' => 0];
            $sii = $ventasSiiMap[$fechaStr] ?? ['total' => 0, 'cant' => 0];

            $totCaja = $caja['total'];
            $totSii = $sii['total'];
            $dif = $totCaja - $totSii;

            $estado = 'CUADRADO';
            if ($totCaja === 0 && $totSii === 0) {
                $estado = 'SIN_VENTAS';
                $resumenMes['dias_sin_ventas']++;
            } elseif ($dif === 0) {
                $estado = 'CUADRADO';
                $resumenMes['dias_cuadrados']++;
            } elseif ($dif > 0) {
                $estado = 'FALTAN_EN_SII'; // Hay ventas en POS que no aparecen en SII
                $resumenMes['dias_con_diferencia']++;
            } else {
                $estado = 'SOBRAN_EN_SII'; // Hay cobros en SII/MP no registrados en POS
                $resumenMes['dias_con_diferencia']++;
            }

            $resumenMes['total_caja'] += $totCaja;
            $resumenMes['total_sii'] += $totSii;

            $dias[] = [
                'fecha' => $fechaStr,
                'dia_numero' => $d,
                'dia_nombre' => date('D', strtotime($fechaStr)),
                'total_caja' => $totCaja,
                'cant_caja' => $caja['cant'],
                'total_sii' => $totSii,
                'cant_sii' => $sii['cant'],
                'diferencia' => $dif,
                'estado' => $estado
            ];

            // Guardar en tabla sii_cuadratura_diaria
            $stmtUpsertCuad->execute([
                ':fch' => $fechaStr,
                ':per' => $periodo,
                ':tcaja' => $totCaja,
                ':ccaja' => $caja['cant'],
                ':tsii' => $totSii,
                ':csii' => $sii['cant'],
                ':dif' => $dif,
                ':est' => $estado
            ]);
        }

        $resumenMes['diferencia'] = $resumenMes['total_caja'] - $resumenMes['total_sii'];

        echo json_encode([
            'success' => true,
            'periodo' => $periodo,
            'resumen' => $resumenMes,
            'dias' => array_reverse($dias) // Más recientes primero
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

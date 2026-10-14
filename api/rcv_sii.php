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

        echo json_encode(['success' => true, 'mensaje' => 'Credenciales del SII guardadas correctamente.']);
        exit;
    }

    if ($action === 'consultar') {
        $periodo = trim($input['periodo'] ?? date('Ym'));
        $operacion = strtoupper(trim($input['operacion'] ?? 'COMPRA'));
        $modo = trim($input['modo'] ?? 'sii'); // 'sii' o 'demo'
        $guardarCreds = !empty($input['guardar_credenciales']);

        // Obtener credenciales de la petición o de la base de datos
        $rutUsuario = trim($input['rut_usuario'] ?? '');
        $claveUsuario = trim($input['clave_usuario'] ?? '');
        $rutEmpresa = trim($input['rut_empresa'] ?? '');

        // Si están vacías en la petición, buscar en la BD
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

        // Si se solicitó guardar credenciales
        if ($guardarCreds && !empty($rutUsuario) && !empty($claveUsuario) && !empty($rutEmpresa)) {
            $stmt = $pdo->prepare("INSERT INTO configuraciones (Clave, Valor) VALUES (:clave, :valor) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor)");
            $stmt->execute([':clave' => 'SII_RUT_USUARIO', ':valor' => $rutUsuario]);
            $stmt->execute([':clave' => 'SII_CLAVE_USUARIO', ':valor' => $claveUsuario]);
            $stmt->execute([':clave' => 'SII_RUT_EMPRESA', ':valor' => $rutEmpresa]);
        }

        $resultado = null;

        if ($modo === 'demo') {
            // Generar datos simulados para prueba inmediata
            $resultado = SiiRcvClient::generarDatosDemo($periodo, $operacion);
        } else {
            // Conexión real al SII
            if (empty($rutUsuario) || empty($claveUsuario)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Por favor ingresa el RUT del usuario y la Clave Tributaria del SII, o utiliza el botón "Cargar Datos Demo" para probar sin credenciales.'
                ]);
                exit;
            }
            if (empty($rutEmpresa)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Por favor especifica el RUT de la empresa o contribuyente a consultar.'
                ]);
                exit;
            }

            $client = new SiiRcvClient();
            $logueado = $client->login($rutUsuario, $claveUsuario);

            if (!$logueado) {
                echo json_encode([
                    'success' => false,
                    'error' => $client->getLastError() ?: 'No fue posible iniciar sesión en el SII con las credenciales provistas.'
                ]);
                exit;
            }

            $resultado = $client->obtenerDetalle($rutEmpresa, $periodo, $operacion);
        }

        if (empty($resultado['success'])) {
            echo json_encode([
                'success' => false,
                'error' => $resultado['error'] ?? 'No se pudieron recuperar los registros del RCV para este período.'
            ]);
            exit;
        }

        // Realizar conciliación con los registros locales del Minimarket
        $documentos = $resultado['documentos'] ?? [];

        if ($operacion === 'COMPRA') {
            // Obtener todas las compras registradas en el sistema para contrastar
            $stmtComp = $pdo->query("
                SELECT c.CompraID, c.NumeroDocumento, c.MontoTotal, c.FechaCompra, p.RutCuerpo, p.RazonSocial
                FROM compras c
                JOIN proveedores p ON c.ProveedorID = p.ProveedorID
            ");
            $comprasLocales = $stmtComp->fetchAll();

            // Indexar por folio y por rut proveedor para búsqueda O(1)
            $mapCompras = [];
            foreach ($comprasLocales as $cl) {
                $folioNum = trim($cl['NumeroDocumento']);
                $rutCuerpo = (int)$cl['RutCuerpo'];
                if ($folioNum !== '') {
                    $mapCompras["{$rutCuerpo}_{$folioNum}"] = $cl;
                    // También indexar solo por folio como fallback
                    $mapCompras["folio_{$folioNum}"] = $cl;
                }
            }

            foreach ($documentos as &$doc) {
                $folioStr = (string)$doc['folio'];
                $rutCuerpo = (int)($doc['rut_cuerpo'] ?? 0);
                
                $claveEspecifica = "{$rutCuerpo}_{$folioStr}";
                $claveGenerica = "folio_{$folioStr}";

                if (isset($mapCompras[$claveEspecifica])) {
                    $match = $mapCompras[$claveEspecifica];
                    $doc['conciliado'] = true;
                    $doc['compra_local_id'] = $match['CompraID'];
                    $doc['fecha_recepcion_local'] = $match['FechaCompra'];
                } elseif (isset($mapCompras[$claveGenerica])) {
                    $match = $mapCompras[$claveGenerica];
                    $doc['conciliado'] = true;
                    $doc['compra_local_id'] = $match['CompraID'];
                    $doc['fecha_recepcion_local'] = $match['FechaCompra'];
                } else {
                    $doc['conciliado'] = false;
                    $doc['compra_local_id'] = null;
                }
            }
            unset($doc);

        } else {
            // Para VENTAS: contrastar con la tabla dte_emitidos
            $stmtDte = $pdo->query("SELECT Folio, TipoDocumento, VentaID, FechaEmision FROM dte_emitidos");
            $dtesLocales = $stmtDte->fetchAll();
            $mapDte = [];
            foreach ($dtesLocales as $d) {
                $mapDte[(int)$d['Folio']] = $d;
            }

            foreach ($documentos as &$doc) {
                $folioInt = (int)$doc['folio'];
                if (isset($mapDte[$folioInt])) {
                    $doc['conciliado'] = true;
                    $doc['venta_local_id'] = $mapDte[$folioInt]['VentaID'] ?? null;
                } else {
                    // Si es el registro agregado de boletas, no es un folio individual
                    $doc['conciliado'] = ($doc['tipo_dte'] === 39) ? true : false;
                }
            }
            unset($doc);
        }

        echo json_encode([
            'success' => true,
            'es_demo' => !empty($resultado['es_demo']),
            'periodo' => $periodo,
            'operacion' => $operacion,
            'totales' => $resultado['totales'] ?? [],
            'documentos' => $documentos,
            'total_documentos' => count($documentos)
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

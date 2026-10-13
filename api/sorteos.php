<?php
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
    exit;
}

$accion = $_GET['accion'] ?? ($_POST['accion'] ?? 'listar_sorteos');
$pdo = getDB();

try {
    switch ($accion) {
        case 'listar_sorteos':
            $stmt = $pdo->query("
                SELECT s.*,
                       COUNT(c.CuponID) AS TotalCupones,
                       COUNT(DISTINCT NULLIF(COALESCE(c.TelefonoParticipante, c.NombreParticipante), '')) AS TotalParticipantesUnicos,
                       SUM(CASE WHEN c.EsGanador = 1 THEN 1 ELSE 0 END) AS TotalGanadores
                FROM sorteos s
                LEFT JOIN sorteos_cupones c ON c.SorteoID = s.SorteoID
                GROUP BY s.SorteoID
                ORDER BY s.FechaFin DESC, s.SorteoID DESC
            ");
            $sorteos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($sorteos as &$s) {
                $s['Premios'] = json_decode($s['Premios'], true) ?: [];
            }
            echo json_encode(['success' => true, 'sorteos' => $sorteos]);
            break;

        case 'sorteo_activo':
            $stmt = $pdo->query("
                SELECT * FROM sorteos
                WHERE Estado = 'Activo' 
                  AND FechaInicio <= CURDATE() 
                  AND FechaFin >= CURDATE()
                ORDER BY SorteoID DESC
                LIMIT 1
            ");
            $sorteo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($sorteo) {
                $sorteo['Premios'] = json_decode($sorteo['Premios'], true) ?: [];
                // Conteo de cupones emitidos hasta ahora
                $cStmt = $pdo->prepare("SELECT COUNT(*) FROM sorteos_cupones WHERE SorteoID = :sid");
                $cStmt->execute([':sid' => $sorteo['SorteoID']]);
                $sorteo['TotalCupones'] = (int)$cStmt->fetchColumn();
            }
            echo json_encode(['success' => true, 'sorteo' => $sorteo ?: null]);
            break;

        case 'guardar_sorteo':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sorteoId = (int)($input['sorteo_id'] ?? 0);
            $titulo = trim($input['titulo'] ?? '');
            $descripcion = trim($input['descripcion'] ?? '');
            $tipoRegla = in_array($input['tipo_regla'] ?? '', ['monto_cada_x', 'por_venta']) ? $input['tipo_regla'] : 'monto_cada_x';
            $montoMinimo = max(100, (float)($input['monto_minimo'] ?? 5000));
            $requiereCliente = !empty($input['requiere_cliente']) ? 1 : 0;
            $imprimirTicket = !empty($input['imprimir_ticket']) ? 1 : 0;
            $fechaInicio = !empty($input['fecha_inicio']) ? $input['fecha_inicio'] : date('Y-m-d');
            $fechaFin = !empty($input['fecha_fin']) ? $input['fecha_fin'] : date('Y-m-d', strtotime('+30 days'));
            $estado = in_array($input['estado'] ?? '', ['Activo', 'Pausado', 'Finalizado']) ? $input['estado'] : 'Activo';

            $premios = $input['premios'] ?? [];
            if (is_string($premios)) {
                $premios = json_decode($premios, true) ?: [['lugar' => 1, 'premio' => $premios]];
            }
            $premiosJson = json_encode($premios, JSON_UNESCAPED_UNICODE);

            if (empty($titulo)) {
                echo json_encode(['success' => false, 'error' => 'El título del sorteo es obligatorio']);
                exit;
            }

            if ($sorteoId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE sorteos SET
                        Titulo = :titulo,
                        Descripcion = :descripcion,
                        Premios = :premios,
                        TipoRegla = :tipo_regla,
                        MontoMinimoCupon = :monto_min,
                        RequiereCliente = :req_cli,
                        ImprimirCuponEnTicket = :imp_ticket,
                        FechaInicio = :f_ini,
                        FechaFin = :f_fin,
                        Estado = :estado
                    WHERE SorteoID = :sid
                ");
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':premios' => $premiosJson,
                    ':tipo_regla' => $tipoRegla,
                    ':monto_min' => $montoMinimo,
                    ':req_cli' => $requiereCliente,
                    ':imp_ticket' => $imprimirTicket,
                    ':f_ini' => $fechaInicio,
                    ':f_fin' => $fechaFin,
                    ':estado' => $estado,
                    ':sid' => $sorteoId,
                ]);
            } else {
                // Si el nuevo se crea como Activo, pausar otros activos si se desea exclusividad
                if ($estado === 'Activo') {
                    $pdo->exec("UPDATE sorteos SET Estado = 'Pausado' WHERE Estado = 'Activo'");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO sorteos (Titulo, Descripcion, Premios, TipoRegla, MontoMinimoCupon, RequiereCliente, ImprimirCuponEnTicket, FechaInicio, FechaFin, Estado)
                    VALUES (:titulo, :descripcion, :premios, :tipo_regla, :monto_min, :req_cli, :imp_ticket, :f_ini, :f_fin, :estado)
                ");
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':premios' => $premiosJson,
                    ':tipo_regla' => $tipoRegla,
                    ':monto_min' => $montoMinimo,
                    ':req_cli' => $requiereCliente,
                    ':imp_ticket' => $imprimirTicket,
                    ':f_ini' => $fechaInicio,
                    ':f_fin' => $fechaFin,
                    ':estado' => $estado,
                ]);
                $sorteoId = (int)$pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'sorteo_id' => $sorteoId, 'mensaje' => 'Sorteo guardado correctamente']);
            break;

        case 'cambiar_estado':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sorteoId = (int)($input['sorteo_id'] ?? 0);
            $nuevoEstado = $input['estado'] ?? '';

            if (!$sorteoId || !in_array($nuevoEstado, ['Activo', 'Pausado', 'Finalizado'])) {
                echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
                exit;
            }

            if ($nuevoEstado === 'Activo') {
                $pdo->exec("UPDATE sorteos SET Estado = 'Pausado' WHERE Estado = 'Activo'");
            }

            $stmt = $pdo->prepare("UPDATE sorteos SET Estado = :estado WHERE SorteoID = :sid");
            $stmt->execute([':estado' => $nuevoEstado, ':sid' => $sorteoId]);

            echo json_encode(['success' => true, 'mensaje' => "Estado actualizado a $nuevoEstado"]);
            break;

        case 'eliminar_sorteo':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sorteoId = (int)($input['sorteo_id'] ?? 0);

            if (!$sorteoId) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM sorteos WHERE SorteoID = :sid");
            $stmt->execute([':sid' => $sorteoId]);
            echo json_encode(['success' => true, 'mensaje' => 'Sorteo eliminado']);
            break;

        case 'participantes':
            $sorteoId = (int)($_GET['sorteo_id'] ?? 0);
            if (!$sorteoId) {
                echo json_encode(['success' => false, 'error' => 'Sorteo no especificado']);
                exit;
            }

            // Cupones activos que no hayan ganado aún en este sorteo
            $stmt = $pdo->prepare("
                SELECT c.CuponID, c.SorteoID, c.NumeroCupon, c.CodigoCupon, 
                       c.NombreParticipante, c.TelefonoParticipante, c.MontoVenta, 
                       c.VentaID, c.EmitidoEn, cl.Nombre AS ClienteNombreRegistrado,
                       cl.Telefono AS ClienteTelefonoRegistrado
                FROM sorteos_cupones c
                LEFT JOIN clientes cl ON cl.ClienteID = c.ClienteID
                WHERE c.SorteoID = :sid AND c.EsGanador = 0
                ORDER BY c.NumeroCupon ASC
            ");
            $stmt->execute([':sid' => $sorteoId]);
            $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalizar nombre y fono para la animación
            foreach ($participantes as &$p) {
                if (empty($p['NombreParticipante']) && !empty($p['ClienteNombreRegistrado'])) {
                    $p['NombreParticipante'] = $p['ClienteNombreRegistrado'];
                }
                if (empty($p['NombreParticipante'])) {
                    $p['NombreParticipante'] = 'Boleta #' . ($p['VentaID'] ?: $p['NumeroCupon']);
                }
                if (empty($p['TelefonoParticipante']) && !empty($p['ClienteTelefonoRegistrado'])) {
                    $p['TelefonoParticipante'] = $p['ClienteTelefonoRegistrado'];
                }
            }

            echo json_encode(['success' => true, 'total' => count($participantes), 'participantes' => $participantes]);
            break;

        case 'sortear_ganador':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $sorteoId = (int)($input['sorteo_id'] ?? 0);
            $cuponId = (int)($input['cupon_id'] ?? 0);
            $lugarPremio = (int)($input['lugar_premio'] ?? 1);
            $premio = trim($input['premio'] ?? 'Premio');
            $notas = trim($input['notas'] ?? '');

            if (!$sorteoId) {
                echo json_encode(['success' => false, 'error' => 'Sorteo no especificado']);
                exit;
            }

            // Si no se envió cupon_id específico, elegir uno al azar en la base de datos
            if (!$cuponId) {
                $stmtRandom = $pdo->prepare("
                    SELECT CuponID FROM sorteos_cupones
                    WHERE SorteoID = :sid AND EsGanador = 0
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $stmtRandom->execute([':sid' => $sorteoId]);
                $cuponId = (int)$stmtRandom->fetchColumn();
            }

            if (!$cuponId) {
                echo json_encode(['success' => false, 'error' => 'No hay cupones elegibles disponibles para sortear']);
                exit;
            }

            // Marcar como ganador
            $stmtUp = $pdo->prepare("
                UPDATE sorteos_cupones SET
                    EsGanador = 1,
                    LugarPremio = :lugar,
                    PremioGanado = :premio,
                    FechaGanador = NOW(),
                    NotasGanador = :notas
                WHERE CuponID = :cid AND SorteoID = :sid
            ");
            $stmtUp->execute([
                ':lugar' => $lugarPremio,
                ':premio' => $premio,
                ':notas' => $notas,
                ':cid' => $cuponId,
                ':sid' => $sorteoId,
            ]);

            // Obtener los datos completos del ganador
            $stmtGanador = $pdo->prepare("
                SELECT c.*, s.Titulo AS SorteoTitulo,
                       cl.Nombre AS ClienteNombreRegistrado,
                       cl.Telefono AS ClienteTelefonoRegistrado
                FROM sorteos_cupones c
                INNER JOIN sorteos s ON s.SorteoID = c.SorteoID
                LEFT JOIN clientes cl ON cl.ClienteID = c.ClienteID
                WHERE c.CuponID = :cid
            ");
            $stmtGanador->execute([':cid' => $cuponId]);
            $ganador = $stmtGanador->fetch(PDO::FETCH_ASSOC);

            if (empty($ganador['NombreParticipante']) && !empty($ganador['ClienteNombreRegistrado'])) {
                $ganador['NombreParticipante'] = $ganador['ClienteNombreRegistrado'];
            }
            if (empty($ganador['TelefonoParticipante']) && !empty($ganador['ClienteTelefonoRegistrado'])) {
                $ganador['TelefonoParticipante'] = $ganador['ClienteTelefonoRegistrado'];
            }

            echo json_encode([
                'success' => true,
                'ganador' => $ganador,
                'mensaje' => '¡Ganador registrado con éxito!'
            ]);
            break;

        case 'deshacer_ganador':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $cuponId = (int)($input['cupon_id'] ?? 0);

            if (!$cuponId) {
                echo json_encode(['success' => false, 'error' => 'Cupón no especificado']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE sorteos_cupones SET
                    EsGanador = 0,
                    LugarPremio = NULL,
                    PremioGanado = NULL,
                    FechaGanador = NULL,
                    NotasGanador = CONCAT(COALESCE(NotasGanador, ''), ' [Anulado]')
                WHERE CuponID = :cid
            ");
            $stmt->execute([':cid' => $cuponId]);
            echo json_encode(['success' => true, 'mensaje' => 'Estado de ganador anulado correctamente']);
            break;

        case 'listar_cupones':
            $sorteoId = (int)($_GET['sorteo_id'] ?? 0);
            $buscar = trim($_GET['buscar'] ?? '');
            $soloGanadores = !empty($_GET['solo_ganadores']) ? 1 : 0;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 50;
            $offset = ($page - 1) * $limit;

            $where = ["1=1"];
            $params = [];

            if ($sorteoId > 0) {
                $where[] = "c.SorteoID = :sid";
                $params[':sid'] = $sorteoId;
            }
            if ($soloGanadores) {
                $where[] = "c.EsGanador = 1";
            }
            if (!empty($buscar)) {
                $where[] = "(c.CodigoCupon LIKE :b1 OR c.NumeroCupon = :b2 OR c.NombreParticipante LIKE :b3 OR c.TelefonoParticipante LIKE :b4 OR c.VentaID = :b5)";
                $params[':b1'] = "%$buscar%";
                $params[':b2'] = is_numeric($buscar) ? (int)$buscar : -1;
                $params[':b3'] = "%$buscar%";
                $params[':b4'] = "%$buscar%";
                $params[':b5'] = is_numeric($buscar) ? (int)$buscar : -1;
            }

            $whereStr = implode(' AND ', $where);

            // Total conteo
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sorteos_cupones c WHERE $whereStr");
            $countStmt->execute($params);
            $totalFilas = (int)$countStmt->fetchColumn();

            // Filas
            $sql = "
                SELECT c.*, s.Titulo AS SorteoTitulo,
                       cl.Nombre AS ClienteNombreRegistrado,
                       cl.Telefono AS ClienteTelefonoRegistrado
                FROM sorteos_cupones c
                INNER JOIN sorteos s ON s.SorteoID = c.SorteoID
                LEFT JOIN clientes cl ON cl.ClienteID = c.ClienteID
                WHERE $whereStr
                ORDER BY c.EsGanador DESC, c.CuponID DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cupones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'total' => $totalFilas,
                'page' => $page,
                'total_paginas' => ceil($totalFilas / $limit),
                'cupones' => $cupones
            ]);
            break;

        case 'listar_ganadores':
            $sorteoId = (int)($_GET['sorteo_id'] ?? 0);
            $where = ["c.EsGanador = 1"];
            $params = [];

            if ($sorteoId > 0) {
                $where[] = "c.SorteoID = :sid";
                $params[':sid'] = $sorteoId;
            }

            $stmt = $pdo->prepare("
                SELECT c.*, s.Titulo AS SorteoTitulo,
                       cl.Nombre AS ClienteNombreRegistrado,
                       cl.Telefono AS ClienteTelefonoRegistrado
                FROM sorteos_cupones c
                INNER JOIN sorteos s ON s.SorteoID = c.SorteoID
                LEFT JOIN clientes cl ON cl.ClienteID = c.ClienteID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.FechaGanador DESC, c.LugarPremio ASC
            ");
            $stmt->execute($params);
            $ganadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'ganadores' => $ganadores]);
            break;

        case 'obtener_cupon_impresion':
            $cuponId = (int)($_GET['cupon_id'] ?? 0);
            if (!$cuponId) {
                echo json_encode(['success' => false, 'error' => 'Cupón no especificado']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT c.*, s.Titulo AS SorteoTitulo, s.Descripcion AS SorteoDesc,
                       s.Premios AS SorteoPremios, s.FechaFin AS SorteoFechaFin,
                       cl.Nombre AS ClienteNombreRegistrado, cl.Telefono AS ClienteTelefonoRegistrado
                FROM sorteos_cupones c
                INNER JOIN sorteos s ON s.SorteoID = c.SorteoID
                LEFT JOIN clientes cl ON cl.ClienteID = c.ClienteID
                WHERE c.CuponID = :cid
            ");
            $stmt->execute([':cid' => $cuponId]);
            $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cupon) {
                echo json_encode(['success' => false, 'error' => 'Cupón no encontrado']);
                exit;
            }

            $cupon['SorteoPremios'] = json_decode($cupon['SorteoPremios'], true) ?: [];
            if (empty($cupon['NombreParticipante']) && !empty($cupon['ClienteNombreRegistrado'])) {
                $cupon['NombreParticipante'] = $cupon['ClienteNombreRegistrado'];
            }
            if (empty($cupon['TelefonoParticipante']) && !empty($cupon['ClienteTelefonoRegistrado'])) {
                $cupon['TelefonoParticipante'] = $cupon['ClienteTelefonoRegistrado'];
            }

            echo json_encode(['success' => true, 'cupon' => $cupon]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/fiscal.php';

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';
$reporteCierre = null;

// Procesar Apertura / Cierre / Movimientos de Caja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'apertura') {
        $montoApertura = (int)($_POST['monto_apertura'] ?? 0);
        try {
            $stmtChk = $pdo->prepare("SELECT TurnoID FROM turnos WHERE UsuarioID = :uid AND Estado = 'Abierto' LIMIT 1");
            $stmtChk->execute([':uid' => $user['id']]);
            if ($stmtChk->fetch()) {
                throw new Exception('Ya tienes un turno de caja abierto. Ciérralo antes de abrir uno nuevo.');
            }

            // Validar o auto-crear caja si la tabla está vacía o no existe
            $cajaID = (int)($_POST['caja_id'] ?? 0);
            if ($cajaID <= 0) {
                $cajaID = (int)$pdo->query("SELECT CajaID FROM cajas WHERE Activa = 1 ORDER BY CajaID ASC LIMIT 1")->fetchColumn();
                if (!$cajaID) {
                    $pdo->query("INSERT INTO cajas (CajaID, Nombre, Activa) VALUES (1, 'Caja Principal 01', 1)");
                    $cajaID = 1;
                }
            } else {
                $chkCaja = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE CajaID = ?");
                $chkCaja->execute([$cajaID]);
                if (!$chkCaja->fetchColumn()) {
                    $cajaID = (int)$pdo->query("SELECT CajaID FROM cajas WHERE Activa = 1 ORDER BY CajaID ASC LIMIT 1")->fetchColumn() ?: 1;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO turnos (CajaID, UsuarioID, MontoApertura, Estado) VALUES (:caja, :uid, :monto, 'Abierto')");
            $stmt->execute([':caja' => $cajaID, ':uid' => $user['id'], ':monto' => $montoApertura]);
            $message = 'Caja abierta exitosamente con ' . formatCLP($montoApertura);
            $redirigirPos = true;
        } catch (Exception $e) {
            $error = 'Error al abrir caja: ' . $e->getMessage();
        }
    } elseif ($action === 'cierre') {
        $turnoID = (int)($_POST['turno_id'] ?? 0);
        $efectivoReal = (int)($_POST['efectivo_real'] ?? 0);
        $tarjetaReal = (int)($_POST['tarjeta_real'] ?? 0);
        $transferenciaReal = (int)($_POST['transferencia_real'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');
        $generarZ = !empty($_POST['generar_z']);
        $supervisorPass = trim($_POST['supervisor_pass'] ?? '');
        $zGenerado = null;

        try {
            // 1. Obtener Monto de Apertura del Turno y validar dueño/estado
            $stmtTurnInfo = $pdo->prepare("SELECT MontoApertura, UsuarioID, Estado, CajaID FROM turnos WHERE TurnoID = :tid");
            $stmtTurnInfo->execute([':tid' => $turnoID]);
            $turnoInfo = $stmtTurnInfo->fetch();

            if (!$turnoInfo) {
                throw new Exception('El turno indicado no existe.');
            }
            $esAdminCaja = in_array($user['rol'], ['Administrador', 'Supervisor'], true);
            if ((int)$turnoInfo['UsuarioID'] !== (int)$user['id'] && !$esAdminCaja) {
                throw new Exception('No puedes cerrar el turno de otro usuario.');
            }
            if ($turnoInfo['Estado'] !== 'Abierto') {
                throw new Exception('Este turno ya fue cerrado anteriormente.');
            }

            // Un Cajero necesita autorización de un Administrador/Supervisor para cerrar su turno
            $supervisorID = $esAdminCaja ? (int)$user['id'] : verificarClaveSupervisor($pdo, $supervisorPass);
            if (!$supervisorID) {
                throw new Exception('Se requiere la clave de un Administrador o Supervisor para cerrar el turno.');
            }

            $montoApertura = (int)$turnoInfo['MontoApertura'];

            // 2. Obtener Totales de Ventas del Turno
            $stmtVentas = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN p.MetodoPago = 'Efectivo' THEN p.Monto ELSE 0 END), 0) AS total_efectivo,
                    COALESCE(SUM(CASE WHEN p.MetodoPago IN ('Tarjeta Debito', 'Tarjeta Credito') THEN p.Monto ELSE 0 END), 0) AS total_tarjeta,
                    COALESCE(SUM(CASE WHEN p.MetodoPago = 'Transferencia' THEN p.Monto ELSE 0 END), 0) AS total_transferencia,
                    COALESCE(SUM(CASE WHEN p.MetodoPago = 'Vale Devolucion' THEN p.Monto ELSE 0 END), 0) AS total_vales
                FROM ventas v
                JOIN pagosventa p ON v.VentaID = p.VentaID
                WHERE v.TurnoID = :tid AND v.Estado = 'Completada'
            ");
            $stmtVentas->execute([':tid' => $turnoID]);
            $totales = $stmtVentas->fetch();

            // 3. Obtener Totales de Movimientos Manuales (Ingresos / Retiros)
            $stmtMovs = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN TipoMovimiento = 'INGRESO' THEN Monto ELSE 0 END), 0) AS total_ingresos,
                    COALESCE(SUM(CASE WHEN TipoMovimiento = 'RETIRO' THEN Monto ELSE 0 END), 0) AS total_retiros
                FROM movimientoscaja
                WHERE TurnoID = :tid
            ");
            $stmtMovs->execute([':tid' => $turnoID]);
            $movs = $stmtMovs->fetch();

            // Calcular el total del sistema: Apertura + Efectivo Ventas + Ingresos Manuales - Retiros Manuales + Tarjetas + Transferencia
            $efectivoEsperado = $montoApertura + $totales['total_efectivo'] + $movs['total_ingresos'] - $movs['total_retiros'];
            $sistemaTotal = $efectivoEsperado + $totales['total_tarjeta'] + $totales['total_transferencia'];

            $pdo->beginTransaction();

            $stmtUpd = $pdo->prepare("
                UPDATE turnos SET
                    FechaCierre = CURRENT_TIMESTAMP(),
                    MontoCierreEfectivo = :efec,
                    MontoCierreTarjeta = :tarj,
                    MontoCierreTransferencia = :trans,
                    MontoCierreSistema = :sistema,
                    Estado = 'Cerrado',
                    Observaciones = :obs
                WHERE TurnoID = :tid
            ");
            $stmtUpd->execute([
                ':efec' => $efectivoReal,
                ':tarj' => $tarjetaReal,
                ':trans' => $transferenciaReal,
                ':sistema' => $sistemaTotal,
                ':obs' => $observaciones,
                ':tid' => $turnoID
            ]);

            if ($generarZ) {
                $zGenerado = generarCierreZ($pdo, (int)$turnoInfo['CajaID'], $supervisorID);
            }

            $pdo->commit();

            // Cargar datos para el reporte de cierre inmediato
            $stmtReporte = $pdo->prepare("
                SELECT t.*, u.Nombre AS Cajero, c.Nombre AS CajaName 
                FROM turnos t 
                JOIN usuarios u ON t.UsuarioID = u.UsuarioID 
                JOIN cajas c ON t.CajaID = c.CajaID 
                WHERE t.TurnoID = :tid
            ");
            $stmtReporte->execute([':tid' => $turnoID]);
            $reporteCierre = $stmtReporte->fetch();

            if ($reporteCierre) {
                $reporteCierre['ventas_efectivo'] = (int)$totales['total_efectivo'];
                $reporteCierre['ventas_tarjeta'] = (int)$totales['total_tarjeta'];
                $reporteCierre['ventas_transferencia'] = (int)$totales['total_transferencia'];
                $reporteCierre['ventas_vales'] = (int)$totales['total_vales'];
                $reporteCierre['ingresos_manuales'] = (int)$movs['total_ingresos'];
                $reporteCierre['retiros_manuales'] = (int)$movs['total_retiros'];
                $reporteCierre['efectivo_esperado'] = $efectivoEsperado;
                
                $reporteCierre['tarjeta_real'] = $tarjetaReal;
                $reporteCierre['transferencia_real'] = $transferenciaReal;
                $reporteCierre['diferencia_efectivo'] = $efectivoReal - $efectivoEsperado;
                $reporteCierre['diferencia_tarjeta'] = $tarjetaReal - (int)$totales['total_tarjeta'];
                $reporteCierre['diferencia_transferencia'] = $transferenciaReal - (int)$totales['total_transferencia'];
                $reporteCierre['diferencia_neta'] = $reporteCierre['diferencia_efectivo'] + $reporteCierre['diferencia_tarjeta'] + $reporteCierre['diferencia_transferencia'];
            }

            $message = 'Caja cerrada y arqueo registrado correctamente.';
            if ($zGenerado) {
                $message .= " Cierre Z #{$zGenerado['numeroZ']} generado por " . formatCLP($zGenerado['total']) . ".";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Error al cerrar caja: ' . $e->getMessage();
        }
    }
}

// Consultar Turno Activo (con nombre de la caja física)
$stmtTurno = $pdo->prepare("
    SELECT t.*, c.Nombre AS NombreCaja 
    FROM turnos t 
    JOIN cajas c ON t.CajaID = c.CajaID 
    WHERE t.UsuarioID = :uid AND t.Estado = 'Abierto' 
    ORDER BY t.TurnoID DESC LIMIT 1
");
$stmtTurno->execute([':uid' => $user['id']]);
$turnoActivo = $stmtTurno->fetch();

$ventasTurno = ['total_efectivo' => 0, 'total_tarjeta' => 0, 'total_transferencia' => 0, 'total_ventas' => 0];
$movimientosTurno = ['total_ingresos' => 0, 'total_retiros' => 0];
$efectivoEsperado = 0;

if ($turnoActivo) {
    // 1. Totales de Ventas
    $stmtV = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN p.MetodoPago = 'Efectivo' THEN p.Monto ELSE 0 END), 0) AS total_efectivo,
            COALESCE(SUM(CASE WHEN p.MetodoPago IN ('Tarjeta Debito', 'Tarjeta Credito') THEN p.Monto ELSE 0 END), 0) AS total_tarjeta,
            COALESCE(SUM(CASE WHEN p.MetodoPago = 'Transferencia' THEN p.Monto ELSE 0 END), 0) AS total_transferencia,
            COALESCE(SUM(CASE WHEN p.MetodoPago = 'Vale Devolucion' THEN p.Monto ELSE 0 END), 0) AS total_vales,
            COUNT(DISTINCT v.VentaID) AS total_ventas
        FROM ventas v
        JOIN pagosventa p ON v.VentaID = p.VentaID
        WHERE v.TurnoID = :tid AND v.Estado = 'Completada'
    ");
    $stmtV->execute([':tid' => $turnoActivo['TurnoID']]);
    $ventasTurno = $stmtV->fetch();

    // 2. Totales de Movimientos Manuales (Ingresos / Retiros)
    $stmtM = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN TipoMovimiento = 'INGRESO' THEN Monto ELSE 0 END), 0) AS total_ingresos,
            COALESCE(SUM(CASE WHEN TipoMovimiento = 'RETIRO' THEN Monto ELSE 0 END), 0) AS total_retiros
        FROM movimientoscaja
        WHERE TurnoID = :tid
    ");
    $stmtM->execute([':tid' => $turnoActivo['TurnoID']]);
    $movimientosTurno = $stmtM->fetch();

    // Calcular Efectivo Esperado en Caja (Apertura + Ventas Efectivo + Ingresos Manuales - Retiros Manuales)
    $efectivoEsperado = $turnoActivo['MontoApertura'] + $ventasTurno['total_efectivo'] + $movimientosTurno['total_ingresos'] - $movimientosTurno['total_retiros'];
}

// Historial de últimos turnos
$stmtHist = $pdo->query("
    SELECT t.*, u.Nombre AS Usuario, c.Nombre AS NombreCaja 
    FROM turnos t 
    JOIN usuarios u ON t.UsuarioID = u.UsuarioID 
    LEFT JOIN cajas c ON t.CajaID = c.CajaID 
    ORDER BY t.TurnoID DESC LIMIT 15
");
$historialTurnos = $stmtHist ? $stmtHist->fetchAll() : [];
// Cargar cajas activas disponibles
$cajasDisponibles = $pdo->query("SELECT * FROM cajas WHERE Activa = 1 ORDER BY CajaID ASC")->fetchAll();
if (empty($cajasDisponibles)) {
    $pdo->query("INSERT INTO cajas (CajaID, Nombre, Activa) VALUES (1, 'Caja Principal 01', 1)");
    $cajasDisponibles = $pdo->query("SELECT * FROM cajas WHERE Activa = 1 ORDER BY CajaID ASC")->fetchAll();
}

// Cargar la vista HTML
include __DIR__ . '/views/caja.view.php';

require_once __DIR__ . '/includes/footer.php';

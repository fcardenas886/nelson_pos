<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$user = currentUser();

// 1. Total Ventas Hoy
$stmtVentasHoy = $pdo->query("
    SELECT COUNT(*) as total_ventas, COALESCE(SUM(MontoTotal), 0) as total_monto 
    FROM ventas 
    WHERE DATE(FechaVenta) = CURRENT_DATE() AND Estado = 'Completada'
");
$statsVentas = $stmtVentasHoy->fetch();

// 2. Productos bajo Stock Mínimo
$stmtLowStock = $pdo->query("
    SELECT COUNT(*) as bajo_stock 
    FROM productos 
    WHERE Stock <= StockMinimo AND Activo = TRUE
");
$lowStockCount = $stmtLowStock->fetch()['bajo_stock'];

// 3. Estado del Turno Actual del usuario
$stmtTurno = $pdo->prepare("
    SELECT TurnoID, FechaApertura, MontoApertura, Estado 
    FROM turnos 
    WHERE UsuarioID = :uid AND Estado = 'Abierto' 
    ORDER BY TurnoID DESC LIMIT 1
");
$stmtTurno->execute([':uid' => $user['id']]);
$turnoActivo = $stmtTurno->fetch();

// 4. Últimas Ventas
$stmtUltimas = $pdo->query("
    SELECT v.VentaID, v.FechaVenta, v.TipoDocumento, v.MontoTotal, v.Estado, u.Nombre AS Cajero
    FROM ventas v
    JOIN turnos t ON v.TurnoID = t.TurnoID
    JOIN usuarios u ON t.UsuarioID = u.UsuarioID
    ORDER BY v.VentaID DESC LIMIT 5
");
$ultimasVentas = $stmtUltimas->fetchAll();

// =========================================================================
// 5. INTELIGENCIA COMERCIAL: CURVA SEMANAL & RENDIMIENTO MENSUAL
// =========================================================================

// --- A. Análisis Semanal: Esta Semana vs. Semana Anterior ---
$lunesActual = (new DateTime())->modify('monday this week')->format('Y-m-d');
$domingoActual = (new DateTime())->modify('sunday this week')->format('Y-m-d');
$lunesAnterior = (new DateTime($lunesActual))->modify('-7 days')->format('Y-m-d');
$domingoAnterior = (new DateTime($domingoActual))->modify('-7 days')->format('Y-m-d');

$diasNombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$diasNombresCompletos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$fechasActual = [];
$fechasAnterior = [];
for ($i = 0; $i < 7; $i++) {
    $fechasActual[$i] = date('Y-m-d', strtotime("$lunesActual +$i days"));
    $fechasAnterior[$i] = date('Y-m-d', strtotime("$lunesAnterior +$i days"));
}

$stmtSemanaActual = $pdo->prepare("
    SELECT DATE(FechaVenta) as fecha, COALESCE(SUM(MontoTotal), 0) as total
    FROM ventas
    WHERE FechaVenta >= :inicio AND FechaVenta <= :fin AND Estado = 'Completada'
    GROUP BY DATE(FechaVenta)
");
$stmtSemanaActual->execute([':inicio' => $lunesActual . ' 00:00:00', ':fin' => $domingoActual . ' 23:59:59']);
$ventasActualPorDia = $stmtSemanaActual->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtSemanaAnterior = $pdo->prepare("
    SELECT DATE(FechaVenta) as fecha, COALESCE(SUM(MontoTotal), 0) as total
    FROM ventas
    WHERE FechaVenta >= :inicio AND FechaVenta <= :fin AND Estado = 'Completada'
    GROUP BY DATE(FechaVenta)
");
$stmtSemanaAnterior->execute([':inicio' => $lunesAnterior . ' 00:00:00', ':fin' => $domingoAnterior . ' 23:59:59']);
$ventasAnteriorPorDia = $stmtSemanaAnterior->fetchAll(PDO::FETCH_KEY_PAIR);

$hoy = date('Y-m-d');
$serieActual = [];
$serieAnterior = [];
$totalSemanaActual = 0;
$totalSemanaAnterior = 0;
$maxVentaSemana = 0;
$mejorDiaSemana = 'Sin ventas aún';

for ($i = 0; $i < 7; $i++) {
    $fAct = $fechasActual[$i];
    $fAnt = $fechasAnterior[$i];
    
    $montoAnt = isset($ventasAnteriorPorDia[$fAnt]) ? (int)$ventasAnteriorPorDia[$fAnt] : 0;
    $serieAnterior[] = $montoAnt;
    $totalSemanaAnterior += $montoAnt;

    if ($fAct <= $hoy) {
        $montoAct = isset($ventasActualPorDia[$fAct]) ? (int)$ventasActualPorDia[$fAct] : 0;
        $serieActual[] = $montoAct;
        $totalSemanaActual += $montoAct;
        if ($montoAct > $maxVentaSemana) {
            $maxVentaSemana = $montoAct;
            $mejorDiaSemana = $diasNombresCompletos[$i] . ' (' . formatCLP($montoAct) . ')';
        }
    } else {
        $serieActual[] = null; // No proyectar ceros artificiales para días que aún no transcurren
    }
}

if ($totalSemanaAnterior > 0) {
    $difSemanal = $totalSemanaActual - $totalSemanaAnterior;
    $porcSemanal = round(($difSemanal / $totalSemanaAnterior) * 100, 1);
} else {
    $difSemanal = $totalSemanaActual;
    $porcSemanal = $totalSemanaActual > 0 ? 100.0 : 0.0;
}

// --- B. Rendimiento Mensual: Mes Actual vs. Mes Anterior ---
$primerDiaMesActual = date('Y-m-01');
$ultimoDiaMesActual = date('Y-m-t');
$diaActualDelMes = (int)date('j');
$totalDiasMesActual = (int)date('t');

$primerDiaMesAnterior = date('Y-m-01', strtotime('first day of last month'));
$ultimoDiaMesAnterior = date('Y-m-t', strtotime('last month'));
$diasEnMesAnterior = (int)date('t', strtotime('last month'));
$mismoDiaMesAnterior = date('Y-m-' . sprintf('%02d', min($diaActualDelMes, $diasEnMesAnterior)), strtotime('last month'));

$mesesNombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombreMesActual = $mesesNombres[(int)date('n')];
$nombreMesAnterior = $mesesNombres[(int)date('n', strtotime('last month'))];

// Ventas acumuladas en el mes actual hasta hoy
$stmtMesActual = $pdo->prepare("
    SELECT COALESCE(SUM(MontoTotal), 0) as total, COUNT(*) as cantidad
    FROM ventas
    WHERE FechaVenta >= :inicio AND FechaVenta <= :fin AND Estado = 'Completada'
");
$stmtMesActual->execute([
    ':inicio' => $primerDiaMesActual . ' 00:00:00',
    ':fin' => date('Y-m-d 23:59:59')
]);
$resMesActual = $stmtMesActual->fetch();
$totalMesActual = (int)$resMesActual['total'];
$cantVentasMesActual = (int)$resMesActual['cantidad'];

// Ventas al mismo día de corte en el mes anterior (comparación justa día por día)
$stmtMesAntCorte = $pdo->prepare("
    SELECT COALESCE(SUM(MontoTotal), 0) as total
    FROM ventas
    WHERE FechaVenta >= :inicio AND FechaVenta <= :fin AND Estado = 'Completada'
");
$stmtMesAntCorte->execute([
    ':inicio' => $primerDiaMesAnterior . ' 00:00:00',
    ':fin' => $mismoDiaMesAnterior . ' 23:59:59'
]);
$totalMesAntCorte = (int)$stmtMesAntCorte->fetch()['total'];

// Total cerrado del mes anterior completo
$stmtMesAntCompleto = $pdo->prepare("
    SELECT COALESCE(SUM(MontoTotal), 0) as total
    FROM ventas
    WHERE FechaVenta >= :inicio AND FechaVenta <= :fin AND Estado = 'Completada'
");
$stmtMesAntCompleto->execute([
    ':inicio' => $primerDiaMesAnterior . ' 00:00:00',
    ':fin' => $ultimoDiaMesAnterior . ' 23:59:59'
]);
$totalMesAntCompleto = (int)$stmtMesAntCompleto->fetch()['total'];

// Ritmo y proyecciones
$promedioDiarioMes = $diaActualDelMes > 0 ? (int)round($totalMesActual / $diaActualDelMes) : 0;
$diasRestantesMes = max(0, $totalDiasMesActual - $diaActualDelMes);
$porcentajeMesTranscurrido = round(($diaActualDelMes / $totalDiasMesActual) * 100, 1);
$promedioDiarioMesAnt = ($diasEnMesAnterior > 0 && $totalMesAntCompleto > 0) ? (int)round($totalMesAntCompleto / $diasEnMesAnterior) : 0;

// --- C. Proyección Ponderada por Patrón Comercial (Días Hábiles vs. Fines de Semana) ---
// Analizamos hasta 90 días históricos (incluyendo meses anteriores) para detectar el patrón real del negocio
$stmtHist = $pdo->query("
    SELECT 
        DATE(FechaVenta) as fecha,
        DAYOFWEEK(DATE(FechaVenta)) as dow_mysql,
        SUM(MontoTotal) as total_dia
    FROM ventas
    WHERE Estado = 'Completada' AND FechaVenta >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)
    GROUP BY DATE(FechaVenta), DAYOFWEEK(DATE(FechaVenta))
");
$diasHistoricos = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

$ventasHabiles = [];
$ventasFinSemana = [];

foreach ($diasHistoricos as $dh) {
    $dow = (int)$dh['dow_mysql']; // 1=Dom, 6=Vie, 7=Sab
    $monto = (int)$dh['total_dia'];
    if ($dow === 1 || $dow === 6 || $dow === 7) {
        $ventasFinSemana[] = $monto;
    } else {
        $ventasHabiles[] = $monto;
    }
}

$promedioDiaHabil = !empty($ventasHabiles) ? (int)round(array_sum($ventasHabiles) / count($ventasHabiles)) : $promedioDiarioMes;
$promedioFinSemana = !empty($ventasFinSemana) ? (int)round(array_sum($ventasFinSemana) / count($ventasFinSemana)) : $promedioDiarioMes;

// Calendario exacto de días restantes en el mes actual (desde mañana hasta fin de mes)
$yearMonth = date('Y-m-');
$habilesRestantes = 0;
$finSemanaRestantes = 0;

for ($dia = $diaActualDelMes + 1; $dia <= $totalDiasMesActual; $dia++) {
    $fechaDia = $yearMonth . sprintf('%02d', $dia);
    $diaSemanaN = (int)date('N', strtotime($fechaDia)); // 1=Lun ... 5=Vie, 6=Sab, 7=Dom
    if ($diaSemanaN >= 5) {
        $finSemanaRestantes++;
    } else {
        $habilesRestantes++;
    }
}

// Proyección ponderada considerando cuántos fines de semana y días hábiles faltan
$proyeccionRestante = ($habilesRestantes * $promedioDiaHabil) + ($finSemanaRestantes * $promedioFinSemana);
$proyeccionCierrePonderada = $totalMesActual + $proyeccionRestante;

if ($totalMesAntCorte > 0) {
    $difMesCorte = $totalMesActual - $totalMesAntCorte;
    $porcMesCorte = round(($difMesCorte / $totalMesAntCorte) * 100, 1);
} else {
    $difMesCorte = $totalMesActual;
    $porcMesCorte = $totalMesActual > 0 ? 100.0 : 0.0;
}

include __DIR__ . '/views/index.view.php';
require_once __DIR__ . '/includes/footer.php';

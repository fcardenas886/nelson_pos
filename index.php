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

// --- C. Proyección Ponderada por Patrón Comercial, Ciclo de Sueldos y Feriados ---
if (!function_exists('getFeriadosChile')) {
    function getFeriadosChile(int $year): array {
        $feriados = [
            sprintf('%04d-01-01', $year), // Año Nuevo
            sprintf('%04d-05-01', $year), // Día del Trabajo
            sprintf('%04d-05-21', $year), // Glorias Navales
            sprintf('%04d-06-21', $year), // Pueblos Indígenas
            sprintf('%04d-06-29', $year), // San Pedro y San Pablo
            sprintf('%04d-07-16', $year), // Virgen del Carmen
            sprintf('%04d-08-15', $year), // Asunción de la Virgen
            sprintf('%04d-09-18', $year), // Fiestas Patrias
            sprintf('%04d-09-19', $year), // Glorias del Ejército
            sprintf('%04d-10-12', $year), // Encuentro de Dos Mundos
            sprintf('%04d-10-31', $year), // Iglesias Evangélicas
            sprintf('%04d-11-01', $year), // Todos los Santos
            sprintf('%04d-12-08', $year), // Inmaculada Concepción
            sprintf('%04d-12-25', $year), // Navidad
        ];
        if (function_exists('easter_date')) {
            $easter = easter_date($year);
            $feriados[] = date('Y-m-d', strtotime('-2 days', $easter));
            $feriados[] = date('Y-m-d', strtotime('-1 day', $easter));
        }
        return $feriados;
    }
}

// Analizamos hasta 90 días históricos para detectar el ritmo real del negocio
$stmtHist = $pdo->query("
    SELECT 
        DATE(FechaVenta) as fecha,
        DAYOFWEEK(DATE(FechaVenta)) as dow_mysql,
        DAY(DATE(FechaVenta)) as dia_mes,
        SUM(MontoTotal) as total_dia
    FROM ventas
    WHERE Estado = 'Completada' AND FechaVenta >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)
    GROUP BY DATE(FechaVenta), DAYOFWEEK(DATE(FechaVenta)), DAY(DATE(FechaVenta))
");
$diasHistoricos = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

$ventasHabiles = [];
$ventasFinSemana = [];
$ventasSueldo = [];
$ventasQuincena = [];
$ventasValle = [];

foreach ($diasHistoricos as $dh) {
    $dow = (int)$dh['dow_mysql']; // 1=Dom, 6=Vie, 7=Sab
    $diaMes = (int)$dh['dia_mes'];
    $monto = (int)$dh['total_dia'];

    if ($dow === 1 || $dow === 6 || $dow === 7) {
        $ventasFinSemana[] = $monto;
    } else {
        $ventasHabiles[] = $monto;
    }

    if ($diaMes >= 28 || $diaMes <= 5) {
        $ventasSueldo[] = $monto;
    } elseif ($diaMes >= 14 && $diaMes <= 17) {
        $ventasQuincena[] = $monto;
    } else {
        $ventasValle[] = $monto;
    }
}

$promedioDiaHabil = !empty($ventasHabiles) ? (int)round(array_sum($ventasHabiles) / count($ventasHabiles)) : $promedioDiarioMes;
$promedioFinSemana = !empty($ventasFinSemana) ? (int)round(array_sum($ventasFinSemana) / count($ventasFinSemana)) : $promedioDiarioMes;

// Factores comerciales de fase del mes (sueldo y quincena) con fallback conservador
$promValle = !empty($ventasValle) ? (array_sum($ventasValle) / count($ventasValle)) : 0;
$factorSueldo = 1.25;
if ($promValle > 0 && !empty($ventasSueldo)) {
    $calc = (array_sum($ventasSueldo) / count($ventasSueldo)) / $promValle;
    $factorSueldo = max(1.05, min(1.60, round($calc, 2)));
}
$factorQuincena = 1.12;
if ($promValle > 0 && !empty($ventasQuincena)) {
    $calc = (array_sum($ventasQuincena) / count($ventasQuincena)) / $promValle;
    $factorQuincena = max(1.02, min(1.40, round($calc, 2)));
}

// Simulación y proyección día por día de lo que resta de mes
$yearActual = (int)date('Y');
$feriadosChile = getFeriadosChile($yearActual);
$proyeccionRestante = 0;
$yearMonth = date('Y-m-');

for ($dia = $diaActualDelMes + 1; $dia <= $totalDiasMesActual; $dia++) {
    $fechaDia = $yearMonth . sprintf('%02d', $dia);
    $diaSemanaN = (int)date('N', strtotime($fechaDia)); // 1=Lun ... 7=Dom
    $esFinde = ($diaSemanaN >= 5);
    $esFeriado = in_array($fechaDia, $feriadosChile, true);

    if ($esFeriado) {
        $baseDia = max($promedioFinSemana, (int)round($promedioDiaHabil * 1.30));
    } elseif ($esFinde) {
        $baseDia = $promedioFinSemana;
    } else {
        $baseDia = $promedioDiaHabil;
    }

    if ($dia >= 28 || $dia <= 5) {
        $estimadoDia = (int)round($baseDia * $factorSueldo);
    } elseif ($dia >= 14 && $dia <= 17) {
        $estimadoDia = (int)round($baseDia * $factorQuincena);
    } else {
        $estimadoDia = $baseDia;
    }

    $proyeccionRestante += $estimadoDia;
}

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

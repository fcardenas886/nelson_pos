<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Parámetros de filtrado
$desde = trim($_GET['desde'] ?? ($_GET['fecha'] ?? date('Y-m-d')));
$hasta = trim($_GET['hasta'] ?? ($_GET['fecha'] ?? date('Y-m-d')));
$q = trim($_GET['q'] ?? '');
$tipoDoc = trim($_GET['tipo_doc'] ?? 'Todos');
$estado = trim($_GET['estado'] ?? 'Todos');
$metodoPago = trim($_GET['metodo_pago'] ?? 'Todos');

// Validación básica de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');
if ($desde > $hasta) {
    $temp = $desde;
    $desde = $hasta;
    $hasta = $temp;
}

// Construcción de consulta dinámica
$where = ["DATE(v.FechaVenta) BETWEEN :desde AND :hasta"];
$params = [
    ':desde' => $desde,
    ':hasta' => $hasta
];

if ($q !== '') {
    // Si viene con prefijo # o número limpio
    $cleanQ = ltrim($q, '#');
    $where[] = "(v.VentaID = :qid OR v.Folio = :qfolio OR dte.Folio = :qdtefolio OR c.Nombre LIKE :qname OR c.RutCuerpo LIKE :qrut)";
    $params[':qid'] = is_numeric($cleanQ) ? (int)$cleanQ : 0;
    $params[':qfolio'] = is_numeric($cleanQ) ? (int)$cleanQ : 0;
    $params[':qdtefolio'] = is_numeric($cleanQ) ? (int)$cleanQ : 0;
    $params[':qname'] = "%$q%";
    $params[':qrut'] = "%$cleanQ%";
}

if ($tipoDoc !== 'Todos' && in_array($tipoDoc, ['Boleta', 'Factura', 'Ticket'], true)) {
    $where[] = "v.TipoDocumento = :tdoc";
    $params[':tdoc'] = $tipoDoc;
}

if ($estado !== 'Todos' && in_array($estado, ['Completada', 'Anulada'], true)) {
    $where[] = "v.Estado = :est";
    $params[':est'] = $estado;
}

// Filtro por Medio de Pago
if ($metodoPago !== 'Todos') {
    switch ($metodoPago) {
        case 'Efectivo':
            $where[] = "(EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND pv.MetodoPago = 'Efectivo') OR NOT EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID))";
            break;
        case 'Tarjeta':
            $where[] = "EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND (pv.MetodoPago LIKE 'Tarjeta%' OR pv.MetodoPago = 'Transbank'))";
            break;
        case 'Transferencia':
            $where[] = "EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND pv.MetodoPago = 'Transferencia')";
            break;
        case 'Credito Interno':
            $where[] = "EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND pv.MetodoPago IN ('Credito Interno', 'Credito', 'Fiado'))";
            break;
        case 'Puntos':
            $where[] = "EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND pv.MetodoPago = 'Puntos')";
            break;
        case 'Vale Devolucion':
            $where[] = "EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID AND pv.MetodoPago LIKE 'Vale%')";
            break;
        case 'Mixto':
            $where[] = "(SELECT COUNT(*) FROM pagosventa pv WHERE pv.VentaID = v.VentaID) > 1";
            break;
    }
}

$whereSql = implode(' AND ', $where);

$query = "
    SELECT v.VentaID, v.FechaVenta, v.TipoDocumento, v.TipoDte, v.MontoNeto, v.MontoIva, 
           v.DescuentoGlobal, v.MontoTotal, v.MontoPagado, v.Vuelto, v.Estado,
           COALESCE(dte.Folio, v.Folio) AS FolioDoc,
           COALESCE(dte.PdfUrl, v.DtePdfPath) AS DtePdfUrl,
           dte.EstadoSii AS DteEstadoSii,
           u.Nombre AS Cajero,
           c.Nombre AS ClienteNombre, c.RutCuerpo, c.RutDv,
           (SELECT GROUP_CONCAT(DISTINCT pv.MetodoPago SEPARATOR ' + ') 
            FROM pagosventa pv WHERE pv.VentaID = v.VentaID) AS MetodosPago,
           (SELECT GROUP_CONCAT(CONCAT(pv.MetodoPago, ':', CAST(ROUND(pv.Monto) AS CHAR)) ORDER BY pv.PagoVentaID ASC SEPARATOR '||') 
            FROM pagosventa pv WHERE pv.VentaID = v.VentaID) AS DesglosePagos,
           (SELECT COUNT(*) FROM pagosventa pv WHERE pv.VentaID = v.VentaID) AS CantPagos,
           (SELECT GROUP_CONCAT(CONCAT(p.Nombre, ' (x', dv.Cantidad, ')') SEPARATOR ', ') 
            FROM detalleventas dv 
            JOIN productos p ON dv.ProductoID = p.ProductoID 
            WHERE dv.VentaID = v.VentaID) AS ProductosDetalle,
           (SELECT COUNT(*) FROM detalleventas dv WHERE dv.VentaID = v.VentaID) AS TotalItems
    FROM ventas v
    JOIN turnos t ON v.TurnoID = t.TurnoID
    JOIN usuarios u ON t.UsuarioID = u.UsuarioID
    LEFT JOIN clientes c ON v.ClienteID = c.ClienteID
    LEFT JOIN (
        SELECT VentaID, Folio, PdfUrl, EstadoSii 
        FROM dte_emitidos 
        WHERE DteID IN (SELECT MAX(DteID) FROM dte_emitidos GROUP BY VentaID)
    ) dte ON v.VentaID = dte.VentaID
    WHERE $whereSql
    ORDER BY v.VentaID DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculo de Métricas (KPIs)
$totalRecaudado = 0;
$totalVentasCount = 0;
$totalAnuladasCount = 0;
$totalAnuladoMonto = 0;

foreach ($ventas as $v) {
    if ($v['Estado'] === 'Completada') {
        $totalRecaudado += (int)$v['MontoTotal'];
        $totalVentasCount++;
    } else {
        $totalAnuladasCount++;
        $totalAnuladoMonto += (int)$v['MontoTotal'];
    }
}
$ticketPromedio = $totalVentasCount > 0 ? (int)round($totalRecaudado / $totalVentasCount) : 0;

// Totales de Cuadratura por Medio de Pago en el Período Consultado (Ventas Completadas)
$totalesCuadratura = [
    'Efectivo' => ['total' => 0, 'cant' => 0, 'label' => 'Efectivo', 'icon' => 'fa-money-bill-wave', 'color' => '#10b981', 'badge_class' => 'badge-success'],
    'Tarjeta' => ['total' => 0, 'cant' => 0, 'label' => 'Tarjetas (Débito/Crédito)', 'icon' => 'fa-credit-card', 'color' => '#3b82f6', 'badge_class' => 'badge-info'],
    'Transferencia' => ['total' => 0, 'cant' => 0, 'label' => 'Transferencias', 'icon' => 'fa-building-columns', 'color' => '#06b6d4', 'badge_class' => 'badge-info'],
    'Credito Interno' => ['total' => 0, 'cant' => 0, 'label' => 'Fiado / Crédito', 'icon' => 'fa-handshake', 'color' => '#f59e0b', 'badge_class' => 'badge-warning'],
    'Puntos' => ['total' => 0, 'cant' => 0, 'label' => 'Puntos Fidelidad', 'icon' => 'fa-star', 'color' => '#eab308', 'badge_class' => 'badge-warning'],
    'Vale Devolucion' => ['total' => 0, 'cant' => 0, 'label' => 'Vales Devolución', 'icon' => 'fa-ticket', 'color' => '#ec4899', 'badge_class' => 'badge-secondary'],
    'Mixto' => ['total' => 0, 'cant' => 0, 'label' => 'Pagos Mixtos', 'icon' => 'fa-arrows-split-up-and-left', 'color' => '#8b5cf6', 'badge_class' => 'badge-primary'],
];

$stmtCuad = $pdo->prepare("
    SELECT 
        pv.MetodoPago,
        COUNT(pv.PagoVentaID) AS PagosCount,
        COALESCE(SUM(pv.Monto), 0) AS TotalMonto
    FROM ventas v
    JOIN pagosventa pv ON v.VentaID = pv.VentaID
    WHERE DATE(v.FechaVenta) BETWEEN :desde AND :hasta
      AND v.Estado = 'Completada'
    GROUP BY pv.MetodoPago
");
$stmtCuad->execute([':desde' => $desde, ':hasta' => $hasta]);
$filasCuad = $stmtCuad->fetchAll(PDO::FETCH_ASSOC);

foreach ($filasCuad as $row) {
    $mp = trim($row['MetodoPago']);
    $monto = (int)$row['TotalMonto'];
    $cant = (int)$row['PagosCount'];

    if (stripos($mp, 'debito') !== false || (stripos($mp, 'credito') !== false && stripos($mp, 'tarjeta') !== false) || stripos($mp, 'tarjeta') !== false || stripos($mp, 'transbank') !== false) {
        $totalesCuadratura['Tarjeta']['total'] += $monto;
        $totalesCuadratura['Tarjeta']['cant'] += $cant;
    } elseif (stripos($mp, 'transfer') !== false) {
        $totalesCuadratura['Transferencia']['total'] += $monto;
        $totalesCuadratura['Transferencia']['cant'] += $cant;
    } elseif (stripos($mp, 'interno') !== false || stripos($mp, 'fiado') !== false || $mp === 'Credito') {
        $totalesCuadratura['Credito Interno']['total'] += $monto;
        $totalesCuadratura['Credito Interno']['cant'] += $cant;
    } elseif (stripos($mp, 'punto') !== false) {
        $totalesCuadratura['Puntos']['total'] += $monto;
        $totalesCuadratura['Puntos']['cant'] += $cant;
    } elseif (stripos($mp, 'vale') !== false) {
        $totalesCuadratura['Vale Devolucion']['total'] += $monto;
        $totalesCuadratura['Vale Devolucion']['cant'] += $cant;
    } else {
        $totalesCuadratura['Efectivo']['total'] += $monto;
        $totalesCuadratura['Efectivo']['cant'] += $cant;
    }
}

// Ventas completadas sin registro en pagosventa (históricas = Efectivo)
$stmtSinPv = $pdo->prepare("
    SELECT COUNT(*) AS cant, COALESCE(SUM(v.MontoTotal), 0) AS total
    FROM ventas v
    WHERE DATE(v.FechaVenta) BETWEEN :desde AND :hasta
      AND v.Estado = 'Completada'
      AND NOT EXISTS (SELECT 1 FROM pagosventa pv WHERE pv.VentaID = v.VentaID)
");
$stmtSinPv->execute([':desde' => $desde, ':hasta' => $hasta]);
$rowSinPv = $stmtSinPv->fetch(PDO::FETCH_ASSOC);
if ($rowSinPv && (int)$rowSinPv['cant'] > 0) {
    $totalesCuadratura['Efectivo']['total'] += (int)$rowSinPv['total'];
    $totalesCuadratura['Efectivo']['cant'] += (int)$rowSinPv['cant'];
}

// Total de ventas mixtas (más de 1 pago) en el período
$stmtMixtas = $pdo->prepare("
    SELECT COUNT(*) AS cant, COALESCE(SUM(v.MontoTotal), 0) AS total
    FROM ventas v
    WHERE DATE(v.FechaVenta) BETWEEN :desde AND :hasta
      AND v.Estado = 'Completada'
      AND (SELECT COUNT(*) FROM pagosventa pv WHERE pv.VentaID = v.VentaID) > 1
");
$stmtMixtas->execute([':desde' => $desde, ':hasta' => $hasta]);
$rowMixtas = $stmtMixtas->fetch(PDO::FETCH_ASSOC);
if ($rowMixtas) {
    $totalesCuadratura['Mixto']['total'] = (int)$rowMixtas['total'];
    $totalesCuadratura['Mixto']['cant'] = (int)$rowMixtas['cant'];
}

// Cargar datos de la empresa para impresión y encabezados
$stmtCfgLocal = $pdo->query("
    SELECT Clave, Valor FROM configuraciones
    WHERE Clave IN ('MINIMARKET_NOMBRE', 'MINIMARKET_RUT', 'MINIMARKET_DIRECCION', 'MINIMARKET_GIRO', 'MINIMARKET_TELEFONO', 'TICKET_PIE_PAGINA')
");
$cfgLocal = [];
while ($row = $stmtCfgLocal->fetch(PDO::FETCH_ASSOC)) {
    $cfgLocal[$row['Clave']] = $row['Valor'];
}

include __DIR__ . '/views/ventas.view.php';
require_once __DIR__ . '/includes/footer.php';

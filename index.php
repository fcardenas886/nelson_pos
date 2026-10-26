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
    WHERE Stock <= StockMinimo AND Activo = TRUE AND COALESCE(ControlaStock, 1) = 1
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

include __DIR__ . '/views/index.view.php';
require_once __DIR__ . '/includes/footer.php';

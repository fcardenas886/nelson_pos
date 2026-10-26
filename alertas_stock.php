<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Algoritmo de sugerencias de compra basado en ventas pasadas de los últimos 7, 15 y 30 días
$stmt = $pdo->query("
    SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.Stock, p.StockMinimo, c.Nombre AS Categoria,
           COALESCE(SUM(CASE WHEN v.FechaVenta >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) THEN dv.Cantidad ELSE 0 END), 0) AS Ventas7Dias,
           COALESCE(SUM(CASE WHEN v.FechaVenta >= DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY) THEN dv.Cantidad ELSE 0 END), 0) AS Ventas15Dias,
           COALESCE(SUM(CASE WHEN v.FechaVenta >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) THEN dv.Cantidad ELSE 0 END), 0) AS Ventas30Dias
    FROM productos p
    LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
    LEFT JOIN detalleventas dv ON p.ProductoID = dv.ProductoID
    LEFT JOIN ventas v ON dv.VentaID = v.VentaID AND v.Estado = 'Completada'
    WHERE p.Activo = TRUE AND COALESCE(p.ControlaStock, 1) = 1 AND p.Stock <= p.StockMinimo
    GROUP BY p.ProductoID
    ORDER BY p.Stock ASC
");
$alertas = $stmt->fetchAll();

include __DIR__ . '/views/alertas_stock.view.php';
require_once __DIR__ . '/includes/footer.php';

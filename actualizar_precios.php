<?php
require_once __DIR__ . '/includes/header.php';
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();

// Obtener categorías para filtros opcionales
$stmtCat = $pdo->query("SELECT CategoriaID, Nombre FROM categorias ORDER BY Nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Conteo de productos activos que no tienen costo asignado
$stmtSinCosto = $pdo->query("SELECT COUNT(*) FROM productos WHERE Activo = 1 AND (CostoCompra = 0 OR CostoCompra IS NULL)");
$totalSinCosto = (int)($stmtSinCosto ? $stmtSinCosto->fetchColumn() : 0);

include __DIR__ . '/views/actualizar_precios.view.php';
require_once __DIR__ . '/includes/footer.php';

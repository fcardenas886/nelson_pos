<?php
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Sorteos disponibles
$stmtSorteos = $pdo->query("
    SELECT s.*, 
           COUNT(c.CuponID) AS TotalCupones,
           SUM(CASE WHEN c.EsGanador = 1 THEN 1 ELSE 0 END) AS TotalGanadores
    FROM sorteos s
    LEFT JOIN sorteos_cupones c ON c.SorteoID = s.SorteoID
    GROUP BY s.SorteoID
    ORDER BY s.Estado = 'Activo' DESC, s.FechaFin DESC, s.SorteoID DESC
");
$sorteosList = $stmtSorteos->fetchAll(PDO::FETCH_ASSOC);

// Sorteo activo preferido para la tómbola
$sorteoActivoDefault = null;
foreach ($sorteosList as $s) {
    if ($s['Estado'] === 'Activo') {
        $sorteoActivoDefault = $s;
        break;
    }
}
if (!$sorteoActivoDefault && !empty($sorteosList)) {
    $sorteoActivoDefault = $sorteosList[0];
}

include __DIR__ . '/views/sorteos.view.php';
require_once __DIR__ . '/includes/footer.php';

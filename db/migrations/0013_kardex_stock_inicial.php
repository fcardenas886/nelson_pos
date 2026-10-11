<?php
/**
 * Migración 0013: Generación e inserción de Stock Inicial en Kardex para todos los productos existentes.
 * Idempotente: omite productos que ya posean un registro de tipo 'INICIAL'.
 */

require_once __DIR__ . '/../../config/database.php';

$pdo = getDB();

$productos = $pdo->query("
    SELECT ProductoID, Nombre, CodigoBarras, CodigoPLU, Stock, CostoCompra, PrecioVenta
    FROM productos
    ORDER BY ProductoID ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "Iniciando migración de Stock Inicial en Kardex...\n";
echo "Total productos en catálogo: " . count($productos) . "\n";

$insertados = 0;
$omitidos = 0;

$stmtCheck = $pdo->prepare("SELECT KardexID FROM kardex WHERE ProductoID = :pid AND TipoTransaccion = 'INICIAL' LIMIT 1");
$stmtMovs = $pdo->prepare("SELECT * FROM kardex WHERE ProductoID = :pid ORDER BY FechaMovimiento ASC, KardexID ASC");
$stmtInsert = $pdo->prepare("
    INSERT INTO kardex (ProductoID, TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo, ValorUnitario, CostoMedioPonderado, FechaMovimiento)
    VALUES (:pid, 'INICIAL', :cant_in, 0, :saldo, :costo, :cmp, :fecha)
");

foreach ($productos as $p) {
    $pid = (int)$p['ProductoID'];

    $stmtCheck->execute([':pid' => $pid]);
    if ($stmtCheck->fetch()) {
        $omitidos++;
        continue;
    }

    $stmtMovs->execute([':pid' => $pid]);
    $movs = $stmtMovs->fetchAll(PDO::FETCH_ASSOC);

    $costo = (int)($p['CostoCompra'] > 0 ? $p['CostoCompra'] : $p['PrecioVenta']);

    if (count($movs) > 0) {
        $primerMov = $movs[0];
        $stockInicial = (float)$primerMov['StockSaldo'] - (float)$primerMov['CantidadEntrada'] + (float)$primerMov['CantidadSalida'];
        $fecha = date('Y-m-d H:i:s', strtotime($primerMov['FechaMovimiento'] . ' -1 minute'));
    } else {
        $stockInicial = (float)$p['Stock'];
        $fecha = date('Y-m-d 08:00:00', strtotime('-1 day'));
    }

    // Asegurar que no sea negativo si hubo desfases
    if ($stockInicial < 0) {
        $stockInicial = 0;
    }

    $stmtInsert->execute([
        ':pid' => $pid,
        ':cant_in' => $stockInicial,
        ':saldo' => $stockInicial,
        ':costo' => $costo,
        ':cmp' => $costo,
        ':fecha' => $fecha
    ]);

    $insertados++;
}

echo "Migración completada exitosamente.\n";
echo "Registros INICIAL insertados: {$insertados}\n";
echo "Productos que ya tenían INICIAL: {$omitidos}\n";

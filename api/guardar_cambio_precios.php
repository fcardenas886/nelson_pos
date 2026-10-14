<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Control de acceso: solo Administradores y Supervisores
if (empty($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Administrador', 'Supervisor'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado. Se requiere rol de Administrador o Supervisor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

verifyCsrfApi();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['cambios']) || !is_array($input['cambios'])) {
    echo json_encode(['success' => false, 'error' => 'No se enviaron productos para actualizar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $stmtUpdProd = $pdo->prepare("
        UPDATE productos 
        SET PrecioVenta = :precio, CostoCompra = :costo 
        WHERE ProductoID = :pid
    ");

    $stmtUpdPack = $pdo->prepare("
        UPDATE productoscodigos 
        SET PrecioVenta = :precio 
        WHERE CodigoID = :cid AND ProductoID = :pid
    ");

    $stmtSyncVentas = $pdo->prepare("
        UPDATE detalleventas 
        SET CostoUnitario = :costo 
        WHERE ProductoID = :pid AND (CostoUnitario = 0 OR CostoUnitario IS NULL)
    ");

    $totalActualizados = 0;
    $totalPacksActualizados = 0;

    foreach ($input['cambios'] as $item) {
        $pid = (int)($item['producto_id'] ?? 0);
        $precioVenta = isset($item['precio_venta']) ? (int)$item['precio_venta'] : null;
        $costoCompra = isset($item['costo_compra']) ? (int)$item['costo_compra'] : null;

        if ($pid <= 0 || $precioVenta === null || $precioVenta < 0) {
            throw new Exception("Datos inválidos para el producto ID $pid (Precio Venta: $precioVenta)");
        }

        // Si costoCompra no se envió o es menor a cero, mantener el actual
        if ($costoCompra === null || $costoCompra < 0) {
            $stmtGet = $pdo->prepare("SELECT CostoCompra FROM productos WHERE ProductoID = :pid");
            $stmtGet->execute([':pid' => $pid]);
            $costoCompra = (int)($stmtGet->fetchColumn() ?: 0);
        }

        $stmtUpdProd->execute([
            ':precio' => $precioVenta,
            ':costo' => $costoCompra,
            ':pid' => $pid
        ]);
        $totalActualizados++;

        // Propagar costo actualizado a ventas históricas que quedaron con costo $0
        if ($costoCompra > 0) {
            $stmtSyncVentas->execute([
                ':costo' => $costoCompra,
                ':pid' => $pid
            ]);
        }

        // Actualizar packs/códigos alternativos vinculados si fueron provistos
        if (!empty($item['packs']) && is_array($item['packs'])) {
            foreach ($item['packs'] as $pk) {
                $cid = (int)($pk['codigo_id'] ?? 0);
                $packPrecio = ($pk['precio_venta'] !== null && $pk['precio_venta'] !== '' && (int)$pk['precio_venta'] > 0)
                    ? (int)$pk['precio_venta']
                    : null;

                if ($cid > 0) {
                    $stmtUpdPack->execute([
                        ':precio' => $packPrecio,
                        ':cid' => $cid,
                        ':pid' => $pid
                    ]);
                    $totalPacksActualizados++;
                }
            }
        }
    }

    $pdo->commit();

    $packMsg = $totalPacksActualizados > 0 ? " y $totalPacksActualizados presentaciones de pack" : "";
    echo json_encode([
        'success' => true,
        'total_productos' => $totalActualizados,
        'total_packs' => $totalPacksActualizados,
        'mensaje' => "¡Se actualizaron con éxito $totalActualizados productos$packMsg!"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

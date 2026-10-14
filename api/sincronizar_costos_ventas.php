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

try {
    $pdo = getDB();

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $productoID = isset($input['producto_id']) ? (int)$input['producto_id'] : 0;

    if ($productoID > 0) {
        $stmt = $pdo->prepare("
            UPDATE detalleventas dv
            JOIN productos p ON dv.ProductoID = p.ProductoID
            SET dv.CostoUnitario = p.CostoCompra
            WHERE dv.ProductoID = :pid
              AND (dv.CostoUnitario = 0 OR dv.CostoUnitario IS NULL)
              AND p.CostoCompra > 0
        ");
        $stmt->execute([':pid' => $productoID]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE detalleventas dv
            JOIN productos p ON dv.ProductoID = p.ProductoID
            SET dv.CostoUnitario = p.CostoCompra
            WHERE (dv.CostoUnitario = 0 OR dv.CostoUnitario IS NULL)
              AND p.CostoCompra > 0
        ");
        $stmt->execute();
    }

    $afectados = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'afectados' => $afectados,
        'mensaje' => $afectados > 0 
            ? "¡Sincronización exitosa! Se actualizaron $afectados registros de ventas con costo $0."
            : "No se encontraron registros de ventas con costo $0 pendientes de sincronizar."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

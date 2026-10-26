<?php
/**
 * Endpoint JSON: Exportación ultraliviana del catálogo de productos y configuraciones
 * para sincronizar y almacenar en el IndexedDB del navegador (Soporte Offline).
 */
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Productos activos
    $stmtProd = $pdo->query("
        SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra,
               p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU,
               p.CategoriaID, COALESCE(cat.Nombre, 'Sin Categoría') AS CategoriaNombre
        FROM productos p
        LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
        WHERE p.Activo = TRUE
        ORDER BY p.Nombre ASC
    ");
    $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    // 2. Códigos alternativos y packs
    $stmtAlt = $pdo->query("
        SELECT pc.CodigoID, pc.ProductoID, pc.CodigoBarras, pc.Descripcion, 
               COALESCE(pc.Cantidad, 1.000) AS Cantidad, pc.PrecioVenta
        FROM productoscodigos pc
        INNER JOIN productos p ON pc.ProductoID = p.ProductoID
        WHERE p.Activo = TRUE
    ");
    $codigosAlt = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Promociones activas vigentes
    $stmtPromo = $pdo->query("
        SELECT PromocionID, ProductoID, Tipo, CantidadMinima, DescuentoPorcentaje, PrecioOferta
        FROM promociones
        WHERE Activa = TRUE AND FechaInicio <= NOW() AND FechaFin >= NOW()
    ");
    $promociones = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

    // 4. Clientes activos básicos
    $stmtCli = $pdo->query("
        SELECT ClienteID, Nombre, RutCuerpo, RutDv, PuntosAcumulados
        FROM clientes
        WHERE Activo = TRUE
        ORDER BY Nombre ASC
    ");
    $clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

    // 5. Configuraciones críticas para la caja
    $stmtCfg = $pdo->query("
        SELECT Clave, Valor FROM configuraciones
        WHERE Clave IN (
            'MINIMARKET_NOMBRE', 'MINIMARKET_RUT', 'MINIMARKET_DIRECCION', 'MINIMARKET_GIRO',
            'MINIMARKET_TELEFONO', 'TICKET_PIE_PAGINA', 'BALANZA_PREFIJO_INDIVIDUAL', 
            'BALANZA_TIPO_EAN', 'BALANZA_MODO', 'BALANZA_INGRESO_MANUAL', 'POS_REQ_SUPERVISOR_CANCELAR', 'POS_REQ_SUPERVISOR_ELIMINAR_ITEM',
            'POS_DESCUENTO_MAX_PORC', 'POS_LAYOUT_MODO', 'PERMITIR_STOCK_NEGATIVO'
        )
    ");
    $configuracion = [];
    while ($r = $stmtCfg->fetch(PDO::FETCH_ASSOC)) {
        $configuracion[$r['Clave']] = $r['Valor'];
    }

    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'total_productos' => count($productos),
        'productos' => $productos,
        'codigos_alt' => $codigosAlt,
        'promociones' => $promociones,
        'clientes' => $clientes,
        'configuracion' => $configuracion
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener catálogo offline: ' . $e->getMessage()]);
}

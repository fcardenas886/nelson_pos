<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$filtro = trim($_GET['filtro'] ?? 'todos'); // 'todos' | 'mas_vendidos' | 'ofertas' | 'categoria'
$categoriaId = (int)($_GET['cat'] ?? 0);

try {
    $pdo = getDB();

    if (!empty($query)) {
        // Búsqueda directa por texto / código de barra principal / códigos alternativos / PLU
        $stmt = $pdo->prepare("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf,
                   pc_match.CodigoBarras AS CodigoAltMatch,
                   pc_match.Descripcion AS AltDescripcion,
                   COALESCE(pc_match.Cantidad, 1.000) AS AltCantidad,
                   pc_match.PrecioVenta AS AltPrecioVenta
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            LEFT JOIN productoscodigos pc_match ON pc_match.ProductoID = p.ProductoID AND pc_match.CodigoBarras = :q_match
            LEFT JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE AND (
                p.CodigoBarras = :q1 
                OR p.Nombre LIKE :like_q 
                OR p.CodigoPLU = :plu
                OR EXISTS (SELECT 1 FROM productoscodigos pc WHERE pc.ProductoID = p.ProductoID AND pc.CodigoBarras = :q_alt)
            )
            ORDER BY (p.CodigoBarras = :q2) DESC, (pc_match.CodigoBarras IS NOT NULL) DESC, (p.CodigoPLU = :plu2) DESC, p.Nombre ASC 
            LIMIT 40
        ");
        $stmt->execute([
            ':q_match' => $query,
            ':q1' => $query,
            ':like_q' => "%$query%",
            ':plu' => $query,
            ':q_alt' => $query,
            ':q2' => $query,
            ':plu2' => $query
        ]);
    } elseif ($filtro === 'sin_costo') {
        // Productos que no tienen costo asignado (CostoCompra = 0 o NULL)
        $sqlCat = ($categoriaId > 0) ? " AND p.CategoriaID = :catId" : "";
        $stmt = $pdo->prepare("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            LEFT JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE AND (p.CostoCompra = 0 OR p.CostoCompra IS NULL) $sqlCat
            ORDER BY cat.Nombre ASC, p.Nombre ASC
            LIMIT 500
        ");
        $params = ($categoriaId > 0) ? [':catId' => $categoriaId] : [];
        $stmt->execute($params);
    } elseif ($filtro === 'mas_vendidos') {
        // Ordenado por mayor volumen de venta histórico en detalleventas (compatible con ONLY_FULL_GROUP_BY)
        $stmt = $pdo->query("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf,
                   COALESCE(ventas.TotalVendido, 0) AS TotalVendido
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            LEFT JOIN (
                SELECT ProductoID, SUM(Cantidad) AS TotalVendido
                FROM detalleventas
                GROUP BY ProductoID
            ) ventas ON p.ProductoID = ventas.ProductoID
            LEFT JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE
            ORDER BY TotalVendido DESC, p.Nombre ASC
            LIMIT 40
        ");
    } elseif ($filtro === 'ofertas') {
        // Solo productos con promociones u ofertas activas
        $stmt = $pdo->query("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            INNER JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE
            ORDER BY p.Nombre ASC
            LIMIT 40
        ");
    } elseif ($filtro === 'categoria' && $categoriaId > 0) {
        // Filtrado por categoría específica
        $stmt = $pdo->prepare("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            LEFT JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE AND p.CategoriaID = :catId
            ORDER BY p.Nombre ASC
            LIMIT 300
        ");
        $stmt->execute([':catId' => $categoriaId]);
    } else {
        // Catálogo general (Todos, orden alfabético)
        $stmt = $pdo->query("
            SELECT p.ProductoID, p.CodigoBarras, p.Nombre, p.PrecioVenta, p.CostoCompra, p.Stock, p.StockMinimo, p.UnidadMedida, COALESCE(p.ControlaStock, 1) AS ControlaStock, p.EsPesable, p.EsPrecioVariable, p.CodigoPLU, p.CategoriaID,
                   cat.Nombre AS CategoriaNombre,
                   pr.PromocionID, pr.Tipo AS PromoTipo, pr.CantidadMinima AS PromoCantMin, pr.DescuentoPorcentaje AS PromoDescPorc, pr.PrecioOferta AS PromoPrecioOf
            FROM productos p
            LEFT JOIN categorias cat ON p.CategoriaID = cat.CategoriaID
            LEFT JOIN promociones pr ON p.ProductoID = pr.ProductoID AND pr.Activa = TRUE AND pr.FechaInicio <= NOW() AND pr.FechaFin >= NOW()
            WHERE p.Activo = TRUE 
            ORDER BY p.Nombre ASC 
            LIMIT 60
        ");
    }
    
    $productos = $stmt->fetchAll();
    echo json_encode(['success' => true, 'productos' => $productos, 'filtro_activo' => $filtro], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

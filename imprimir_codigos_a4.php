<?php
/**
 * Controlador de Impresión de Códigos de Barras en Hoja A4
 * Soporta dos modos:
 * 1. Pliego de Etiquetas A4 (grilla de stickers recortables para 1 o varios artículos)
 * 2. Catálogo / Carta de Mostrador A4 (hoja de consulta y escaneo rápido para caja)
 */

require_once __DIR__ . '/includes/header.php';
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();

// Obtener datos del local
$empresaInfo = [
    'nombre' => $nombreEmpresa ?? 'Minimarket POS',
    'rut' => '',
    'direccion' => ''
];

try {
    $stmtCfg = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('MINIMARKET_NOMBRE', 'MINIMARKET_RUT', 'MINIMARKET_DIRECCION')");
    while ($r = $stmtCfg->fetch(PDO::FETCH_ASSOC)) {
        if ($r['Clave'] === 'MINIMARKET_NOMBRE' && !empty($r['Valor'])) $empresaInfo['nombre'] = $r['Valor'];
        if ($r['Clave'] === 'MINIMARKET_RUT') $empresaInfo['rut'] = $r['Valor'];
        if ($r['Clave'] === 'MINIMARKET_DIRECCION') $empresaInfo['direccion'] = $r['Valor'];
    }
} catch (Exception $e) {}

// Obtener categorías
$categorias = $pdo->query("SELECT CategoriaID, Nombre FROM categorias ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los productos activos
$stmtProd = $pdo->query("
    SELECT p.ProductoID, p.Nombre, p.CodigoBarras, p.CodigoPLU, p.PrecioVenta, 
           p.EsPrecioVariable, p.EsPesable, p.CategoriaID, 
           COALESCE(c.Nombre, 'Sin Categoría') AS Categoria
    FROM productos p
    LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
    WHERE p.Activo = 1
    ORDER BY c.Nombre ASC, p.Nombre ASC
");
$todosProductos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// Parámetros iniciales desde URL
$preselectedId = (int)($_GET['producto_id'] ?? 0);
$preselectedModo = in_array($_GET['modo'] ?? '', ['pliego', 'catalogo'], true) ? $_GET['modo'] : 'pliego';
$preselectedCat = (int)($_GET['categoria_id'] ?? 0);

include __DIR__ . '/views/imprimir_codigos_a4.view.php';
require_once __DIR__ . '/includes/footer.php';

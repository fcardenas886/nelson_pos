<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Validar permisos (Solo Administrador y Supervisor)
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();

// ----------------------------------------------------
// 1. FILTROS Y PARÁMETROS
// ----------------------------------------------------
$productoID = (int)($_GET['producto_id'] ?? 0);
$tipoFiltro = trim($_GET['tipo'] ?? '');
$fechaDesde = trim($_GET['desde'] ?? '');
$fechaHasta = trim($_GET['hasta'] ?? '');
$searchQ    = trim($_GET['q'] ?? '');

// ----------------------------------------------------
// 2. CONSTRUCCIÓN DE CONSULTA DE MOVIMIENTOS
// ----------------------------------------------------
$where = [];
$params = [];

if ($productoID > 0) {
    $where[] = "k.ProductoID = :pid";
    $params[':pid'] = $productoID;
}

if ($tipoFiltro !== '' && $tipoFiltro !== 'todos') {
    if ($tipoFiltro === 'AJUSTE') {
        $where[] = "k.TipoTransaccion IN ('AJUSTE_ENTRADA', 'AJUSTE_SALIDA')";
    } else {
        $where[] = "k.TipoTransaccion = :tipo";
        $params[':tipo'] = $tipoFiltro;
    }
}

if (!empty($fechaDesde)) {
    $where[] = "DATE(k.FechaMovimiento) >= :desde";
    $params[':desde'] = $fechaDesde;
}

if (!empty($fechaHasta)) {
    $where[] = "DATE(k.FechaMovimiento) <= :hasta";
    $params[':hasta'] = $fechaHasta;
}

if (!empty($searchQ)) {
    $where[] = "(p.Nombre LIKE :q OR p.CodigoBarras LIKE :q OR p.CodigoPLU LIKE :q)";
    $params[':q'] = '%' . $searchQ . '%';
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$limitSql = ($productoID > 0 || !empty($fechaDesde) || !empty($tipoFiltro) || !empty($searchQ)) ? '' : 'LIMIT 300';

$stmt = $pdo->prepare("
    SELECT 
        k.*,
        p.Nombre AS ProductoName,
        p.CodigoBarras,
        p.CodigoPLU,
        COALESCE(c.Nombre, 'Sin Categoría') AS Categoria,
        v.TipoDocumento,
        v.Folio,
        aj.Motivo AS MotivoAjuste,
        aj.Observaciones AS ObsAjuste,
        comp.NumeroDocumento
    FROM kardex k
    JOIN productos p ON k.ProductoID = p.ProductoID
    LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
    LEFT JOIN ventas v ON k.VentaID = v.VentaID
    LEFT JOIN ajustesstock aj ON k.AjusteStockID = aj.AjusteStockID
    LEFT JOIN compras comp ON k.CompraID = comp.CompraID
    $whereSql
    ORDER BY k.FechaMovimiento DESC, k.KardexID DESC
    $limitSql
");
$stmt->execute($params);
$kardexList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------
// 3. EXPORTACIÓN A EXCEL CSV (SI SE SOLICITA)
// ----------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="kardex_' . date('Y-m-d_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    
    fputcsv($output, [
        'N° Mov', 'Fecha', 'Hora', 'Producto', 'Código', 'Categoría', 
        'Tipo Transacción', 'Entrada', 'Salida', 'Saldo Stock', 'Valor Unitario ($)', 'Referencia / Detalle'
    ], ';');

    foreach ($kardexList as $k) {
        $referencia = '';
        if ($k['TipoTransaccion'] === 'VENTA' && $k['VentaID']) {
            $referencia = 'Venta #' . $k['VentaID'] . ($k['Folio'] ? ' (' . $k['TipoDocumento'] . ' ' . $k['Folio'] . ')' : '');
        } elseif ($k['TipoTransaccion'] === 'COMPRA' && $k['CompraID']) {
            $referencia = 'Compra #' . $k['CompraID'] . ($k['NumeroDocumento'] ? ' (Doc ' . $k['NumeroDocumento'] . ')' : '');
        } elseif (str_contains($k['TipoTransaccion'], 'AJUSTE') && $k['AjusteStockID']) {
            $referencia = 'Ajuste #' . $k['AjusteStockID'] . ($k['MotivoAjuste'] ? ' - ' . $k['MotivoAjuste'] : '');
        } elseif ($k['TipoTransaccion'] === 'INICIAL') {
            $referencia = 'Stock Inicial de Apertura';
        }

        fputcsv($output, [
            $k['KardexID'],
            date('d/m/Y', strtotime($k['FechaMovimiento'])),
            date('H:i', strtotime($k['FechaMovimiento'])),
            $k['ProductoName'],
            $k['CodigoBarras'] ?: ($k['CodigoPLU'] ? 'PLU:' . $k['CodigoPLU'] : ''),
            $k['Categoria'],
            $k['TipoTransaccion'],
            (float)$k['CantidadEntrada'],
            (float)$k['CantidadSalida'],
            (float)$k['StockSaldo'],
            (int)$k['ValorUnitario'],
            $referencia
        ], ';');
    }
    fclose($output);
    exit;
}

// ----------------------------------------------------
// 4. DETALLE Y KPIS DEL PRODUCTO SELECCIONADO
// ----------------------------------------------------
$productoSeleccionado = null;
$kpiProducto = [
    'StockInicial' => 0,
    'TotalEntradas' => 0,
    'TotalSalidas' => 0,
    'StockActual' => 0,
    'CostoUnitario' => 0,
    'PrecioVenta' => 0,
    'TotalMovimientos' => 0
];

if ($productoID > 0) {
    $stmtProd = $pdo->prepare("
        SELECT p.*, COALESCE(c.Nombre, 'Sin Categoría') AS Categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID 
        WHERE p.ProductoID = :pid
    ");
    $stmtProd->execute([':pid' => $productoID]);
    $productoSeleccionado = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if ($productoSeleccionado) {
        $kpiProducto['StockActual'] = (float)$productoSeleccionado['Stock'];
        $kpiProducto['CostoUnitario'] = (int)$productoSeleccionado['CostoCompra'];
        $kpiProducto['PrecioVenta'] = (int)$productoSeleccionado['PrecioVenta'];

        $stmtTotales = $pdo->prepare("
            SELECT TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo
            FROM kardex
            WHERE ProductoID = :pid
            ORDER BY FechaMovimiento ASC, KardexID ASC
        ");
        $stmtTotales->execute([':pid' => $productoID]);
        $allMovsProd = $stmtTotales->fetchAll(PDO::FETCH_ASSOC);

        $kpiProducto['TotalMovimientos'] = count($allMovsProd);
        $foundInicial = false;
        foreach ($allMovsProd as $m) {
            if ($m['TipoTransaccion'] === 'INICIAL') {
                $kpiProducto['StockInicial'] = (float)$m['CantidadEntrada'];
                $foundInicial = true;
            } else {
                $kpiProducto['TotalEntradas'] += (float)$m['CantidadEntrada'];
                $kpiProducto['TotalSalidas'] += (float)$m['CantidadSalida'];
            }
        }
        if (!$foundInicial && count($allMovsProd) > 0) {
            $primer = $allMovsProd[0];
            $kpiProducto['StockInicial'] = (float)$primer['StockSaldo'] - (float)$primer['CantidadEntrada'] + (float)$primer['CantidadSalida'];
        }
    }
}

// ----------------------------------------------------
// 5. LISTA DE PRODUCTOS ACTIVOS PARA EL SELECTOR
// ----------------------------------------------------
$productosList = $pdo->query("
    SELECT 
        p.ProductoID, 
        p.Nombre, 
        p.CodigoBarras, 
        p.CodigoPLU, 
        p.Stock, 
        COALESCE(c.Nombre, 'Sin Categoría') AS Categoria
    FROM productos p 
    LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
    WHERE p.Activo = 1 
    ORDER BY p.Nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/kardex.view.php';
require_once __DIR__ . '/includes/footer.php';

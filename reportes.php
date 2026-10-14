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
// 1. MANEJO DE PRESETS Y RANGO DE FECHAS
// ----------------------------------------------------
$preset = $_GET['preset'] ?? '';
$hoy = date('Y-m-d');

if ($preset === 'hoy') {
    $fechaInicio = $hoy;
    $fechaFin = $hoy;
} elseif ($preset === 'ayer') {
    $fechaInicio = date('Y-m-d', strtotime('-1 day'));
    $fechaFin = $fechaInicio;
} elseif ($preset === 'semana') {
    // Últimos 7 días
    $fechaInicio = date('Y-m-d', strtotime('-6 days'));
    $fechaFin = $hoy;
} elseif ($preset === 'mes') {
    // Mes en curso
    $fechaInicio = date('Y-m-01');
    $fechaFin = $hoy;
} elseif ($preset === 'mes_anterior') {
    // Mes anterior completo
    $fechaInicio = date('Y-m-01', strtotime('first day of last month'));
    $fechaFin = date('Y-m-t', strtotime('last day of last month'));
} elseif ($preset === 'ano') {
    // Año en curso
    $fechaInicio = date('Y-01-01');
    $fechaFin = $hoy;
} else {
    // Rango personalizado o por defecto (Mes actual)
    $fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fin'] ?? $hoy;
}

// Normalizar orden si la fecha de inicio es mayor
if ($fechaInicio > $fechaFin) {
    $tmp = $fechaInicio;
    $fechaInicio = $fechaFin;
    $fechaFin = $tmp;
}

$tab = $_GET['tab'] ?? 'ventas';
$tabsValidos = ['ventas', 'productos', 'inventario', 'cajeros', 'creditos', 'compras', 'utilidades', 'mermas'];
if (!in_array($tab, $tabsValidos)) {
    $tab = 'ventas';
}

$esExport = isset($_GET['export']) && $_GET['export'] === 'csv';

// ----------------------------------------------------
// 2. CONSULTAS DE CADA PESTAÑA / REPORTE
// ----------------------------------------------------

// TAB 1: VENTAS Y MEDIOS DE PAGO
if ($tab === 'ventas') {
    // KPIs Generales
    $stmtKPI = $pdo->prepare("
        SELECT 
            COUNT(v.VentaID) AS TotalTransacciones,
            COALESCE(SUM(v.MontoTotal), 0) AS TotalVentas,
            COALESCE(SUM(v.MontoNeto), 0) AS TotalNeto,
            COALESCE(SUM(v.MontoIva), 0) AS TotalIva,
            COALESCE(SUM(v.DescuentoGlobal), 0) AS TotalDescuentosGlobales
        FROM ventas v
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
    ");
    $stmtKPI->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiVentas = $stmtKPI->fetch(PDO::FETCH_ASSOC);

    // Sumar descuentos a nivel de producto
    $stmtDescDet = $pdo->prepare("
        SELECT COALESCE(SUM(dv.Descuento), 0) AS TotalDescuentoDetalle
        FROM detalleventas dv
        JOIN ventas v ON dv.VentaID = v.VentaID
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
    ");
    $stmtDescDet->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $descDet = (int)$stmtDescDet->fetchColumn();
    $kpiVentas['TotalDescuentos'] = $kpiVentas['TotalDescuentosGlobales'] + $descDet;

    $totalVentasMonto = (int)$kpiVentas['TotalVentas'];
    $transaccionesCount = (int)$kpiVentas['TotalTransacciones'];
    $ticketPromedio = $transaccionesCount > 0 ? round($totalVentasMonto / $transaccionesCount) : 0;

    // Desglose por Medio de Pago
    $stmtPagos = $pdo->prepare("
        SELECT 
            pv.MetodoPago,
            COUNT(pv.PagoVentaID) AS Transacciones,
            COALESCE(SUM(pv.Monto), 0) AS TotalRecaudado
        FROM pagosventa pv
        JOIN ventas v ON pv.VentaID = v.VentaID
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
        GROUP BY pv.MetodoPago
        ORDER BY TotalRecaudado DESC
    ");
    $stmtPagos->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $pagosMetodos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    // Desglose por Tipo de Comprobante
    $stmtDoc = $pdo->prepare("
        SELECT 
            v.TipoDocumento,
            COUNT(v.VentaID) AS Cantidad,
            COALESCE(SUM(v.MontoTotal), 0) AS TotalMonto
        FROM ventas v
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
        GROUP BY v.TipoDocumento
        ORDER BY TotalMonto DESC
    ");
    $stmtDoc->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $docsDesglose = $stmtDoc->fetchAll(PDO::FETCH_ASSOC);

    // Ventas por Hora del Día (Horas Peak)
    $stmtHoras = $pdo->prepare("
        SELECT 
            HOUR(v.FechaVenta) AS Hora,
            COUNT(v.VentaID) AS TotalVentas,
            COALESCE(SUM(v.MontoTotal), 0) AS MontoTotal
        FROM ventas v
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
        GROUP BY HOUR(v.FechaVenta)
        ORDER BY Hora ASC
    ");
    $stmtHoras->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $ventasPorHora = [];
    $maxMontoHora = 1;
    while ($row = $stmtHoras->fetch(PDO::FETCH_ASSOC)) {
        $horaInt = (int)$row['Hora'];
        $ventasPorHora[$horaInt] = $row;
        if ($row['MontoTotal'] > $maxMontoHora) {
            $maxMontoHora = $row['MontoTotal'];
        }
    }

    // Listado de Ventas Recientes en el rango (limit 30 para visualización rápida)
    $stmtVentasList = $pdo->prepare("
        SELECT 
            v.VentaID,
            v.FechaVenta,
            v.TipoDocumento,
            v.MontoTotal,
            u.Nombre AS Cajero,
            c.Nombre AS Cliente
        FROM ventas v
        JOIN turnos t ON v.TurnoID = t.TurnoID
        JOIN usuarios u ON t.UsuarioID = u.UsuarioID
        LEFT JOIN clientes c ON v.ClienteID = c.ClienteID
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
        ORDER BY v.VentaID DESC
        LIMIT 100
    ");
    $stmtVentasList->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $ventasList = $stmtVentasList->fetchAll(PDO::FETCH_ASSOC);
}

// TAB 2: RANKING Y ROTACIÓN DE PRODUCTOS
if ($tab === 'productos') {
    // Categoría filtro opcional
    $catId = (int)($_GET['categoria_id'] ?? 0);
    $paramsRank = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
    $sqlCat = "";
    if ($catId > 0) {
        $sqlCat = " AND p.CategoriaID = :cat ";
        $paramsRank[':cat'] = $catId;
    }

    // Top 25 Más Vendidos
    $stmtRank = $pdo->prepare("
        SELECT 
            p.ProductoID,
            p.Nombre,
            p.CodigoBarras,
            c.Nombre AS Categoria,
            SUM(dv.Cantidad) AS UnidadesVendidas,
            SUM(dv.Subtotal) AS MontoTotalVentas,
            SUM(dv.Subtotal - (dv.Cantidad * COALESCE(NULLIF(dv.CostoUnitario, 0), p.CostoCompra, 0))) AS UtilidadTotal
        FROM detalleventas dv
        JOIN ventas v ON dv.VentaID = v.VentaID
        JOIN productos p ON dv.ProductoID = p.ProductoID
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE v.Estado = 'Completada' 
          AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
          $sqlCat
        GROUP BY p.ProductoID
        ORDER BY UnidadesVendidas DESC
        LIMIT 30
    ");
    $stmtRank->execute($paramsRank);
    $rankingProductos = $stmtRank->fetchAll(PDO::FETCH_ASSOC);

    // Productos Sin Rotación (Huesos / Capital Estancado)
    $stmtHuesos = $pdo->prepare("
        SELECT 
            p.ProductoID,
            p.Nombre,
            p.CodigoBarras,
            c.Nombre AS Categoria,
            p.Stock,
            p.CostoCompra,
            (p.Stock * p.CostoCompra) AS CapitalInmovilizado,
            p.PrecioVenta
        FROM productos p
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE p.Activo = 1 
          AND p.Stock > 0
          $sqlCat
          AND NOT EXISTS (
              SELECT 1 FROM detalleventas dv
              JOIN ventas v ON dv.VentaID = v.VentaID
              WHERE dv.ProductoID = p.ProductoID
                AND v.Estado = 'Completada'
                AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
          )
        ORDER BY CapitalInmovilizado DESC
        LIMIT 40
    ");
    $stmtHuesos->execute($paramsRank);
    $productosSinRotacion = $stmtHuesos->fetchAll(PDO::FETCH_ASSOC);

    $totalCapitalEstancado = 0;
    foreach ($productosSinRotacion as $h) {
        $totalCapitalEstancado += $h['CapitalInmovilizado'];
    }

    // Lista de Categorías para selector
    $categorias = $pdo->query("SELECT CategoriaID, Nombre FROM categorias ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// TAB 3: VALORIZACIÓN DE INVENTARIO
if ($tab === 'inventario') {
    // KPIs Globales de Inventario Físico
    $stmtInvGlobal = $pdo->query("
        SELECT 
            COUNT(*) AS TotalProductos,
            SUM(CASE WHEN Stock > 0 THEN 1 ELSE 0 END) AS ProductosConStock,
            SUM(CASE WHEN Stock <= 0 THEN 1 ELSE 0 END) AS ProductosAgotados,
            SUM(CASE WHEN Stock > 0 AND Stock <= StockMinimo THEN 1 ELSE 0 END) AS ProductosBajoStock,
            SUM(CASE WHEN CostoCompra = 0 OR CostoCompra IS NULL THEN 1 ELSE 0 END) AS ProductosSinCosto,
            COALESCE(SUM(Stock), 0) AS TotalUnidadesFisicas,
            COALESCE(SUM(Stock * CostoCompra), 0) AS ValorTotalCosto,
            COALESCE(SUM(Stock * PrecioVenta), 0) AS ValorTotalVenta
        FROM productos
        WHERE Activo = 1
    ");
    $kpiInventario = $stmtInvGlobal->fetch(PDO::FETCH_ASSOC);
    $margenPotencialTotal = $kpiInventario['ValorTotalVenta'] - $kpiInventario['ValorTotalCosto'];
    $margenPotencialPorc = $kpiInventario['ValorTotalCosto'] > 0 
        ? round(($margenPotencialTotal / $kpiInventario['ValorTotalCosto']) * 100, 1) 
        : 0;

    // Desglose por Categoría
    $stmtCatInv = $pdo->query("
        SELECT 
            p.CategoriaID,
            COALESCE(c.Nombre, 'Sin Categoría') AS Categoria,
            COUNT(p.ProductoID) AS TotalItems,
            COALESCE(SUM(p.Stock), 0) AS StockTotal,
            COALESCE(SUM(p.Stock * p.CostoCompra), 0) AS ValorCosto,
            COALESCE(SUM(p.Stock * p.PrecioVenta), 0) AS ValorVenta
        FROM productos p
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE p.Activo = 1
        GROUP BY p.CategoriaID, c.Nombre
        ORDER BY ValorCosto DESC
    ");
    $categoriasInventario = $stmtCatInv->fetchAll(PDO::FETCH_ASSOC);

    // Filtros opcionales para la lista de productos
    $catId = (int)($_GET['categoria_id'] ?? 0);
    $filtroStock = $_GET['filtro_stock'] ?? 'todos';
    $buscar = trim($_GET['q'] ?? '');

    $whereProd = ["p.Activo = 1"];
    $paramsProd = [];

    if ($catId > 0) {
        $whereProd[] = "p.CategoriaID = :cat";
        $paramsProd[':cat'] = $catId;
    }

    if ($filtroStock === 'con_stock') {
        $whereProd[] = "p.Stock > 0";
    } elseif ($filtroStock === 'sin_costo') {
        $whereProd[] = "(p.CostoCompra = 0 OR p.CostoCompra IS NULL)";
    } elseif ($filtroStock === 'agotados') {
        $whereProd[] = "p.Stock <= 0";
    } elseif ($filtroStock === 'bajo_stock') {
        $whereProd[] = "p.Stock > 0 AND p.Stock <= p.StockMinimo";
    }

    if ($buscar !== '') {
        $whereProd[] = "(p.Nombre LIKE :q OR p.CodigoBarras LIKE :q OR p.CodigoPLU LIKE :q)";
        $paramsProd[':q'] = "%$buscar%";
    }

    $whereProdSql = implode(' AND ', $whereProd);

    // Listado Detallado de Productos con Stock, Costo y Valorización
    $stmtProdInv = $pdo->prepare("
        SELECT 
            p.ProductoID,
            p.CodigoBarras,
            p.CodigoPLU,
            p.Nombre,
            p.CategoriaID,
            COALESCE(c.Nombre, 'Sin Categoría') AS Categoria,
            p.Stock,
            p.StockMinimo,
            p.UnidadMedida,
            p.CostoCompra,
            p.PrecioVenta,
            p.EsPesable,
            p.EsPrecioVariable,
            ROUND(p.Stock * p.CostoCompra) AS TotalCosto,
            ROUND(p.Stock * p.PrecioVenta) AS TotalVenta,
            ROUND((p.Stock * p.PrecioVenta) - (p.Stock * p.CostoCompra)) AS MargenTotal
        FROM productos p
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE $whereProdSql
        ORDER BY TotalCosto DESC, p.Nombre ASC
    ");
    $stmtProdInv->execute($paramsProd);
    $productosInventario = $stmtProdInv->fetchAll(PDO::FETCH_ASSOC);

    // Lista de categorías para el selector
    $categorias = $pdo->query("SELECT CategoriaID, Nombre FROM categorias ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// TAB 4: RENDIMIENTO DE CAJEROS Y CUADRATURAS
if ($tab === 'cajeros') {
    // Ventas y rendimiento por cajero en el período
    $stmtCajeros = $pdo->prepare("
        SELECT 
            u.UsuarioID,
            u.Nombre AS Cajero,
            u.NombreUsuario AS Usuario,
            r.Nombre AS Rol,
            COUNT(DISTINCT v.VentaID) AS TotalVentas,
            COALESCE(SUM(v.MontoTotal), 0) AS MontoTotalVentas,
            COUNT(DISTINCT t.TurnoID) AS TurnosRealizados
        FROM usuarios u
        JOIN roles r ON u.RolID = r.RolID
        LEFT JOIN turnos t ON t.UsuarioID = u.UsuarioID AND DATE(t.FechaApertura) BETWEEN :inicio AND :fin
        LEFT JOIN ventas v ON v.TurnoID = t.TurnoID AND v.Estado = 'Completada'
        WHERE u.Activo = 1
        GROUP BY u.UsuarioID
        HAVING TotalVentas > 0 OR TurnosRealizados > 0
        ORDER BY MontoTotalVentas DESC
    ");
    $stmtCajeros->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $cajerosRendimiento = $stmtCajeros->fetchAll(PDO::FETCH_ASSOC);

    // Historial de Cuadraturas de Turnos (Arqueos Cerrados)
    $stmtCuadraturas = $pdo->prepare("
        SELECT 
            t.TurnoID,
            u.Nombre AS Cajero,
            t.FechaApertura,
            t.FechaCierre,
            t.MontoApertura,
            t.MontoCierreSistema,
            (COALESCE(t.MontoCierreEfectivo, 0) + COALESCE(t.MontoCierreTarjeta, 0) + COALESCE(t.MontoCierreTransferencia, 0)) AS TotalDeclarado,
            ((COALESCE(t.MontoCierreEfectivo, 0) + COALESCE(t.MontoCierreTarjeta, 0) + COALESCE(t.MontoCierreTransferencia, 0)) - t.MontoCierreSistema) AS Diferencia
        FROM turnos t
        JOIN usuarios u ON t.UsuarioID = u.UsuarioID
        WHERE t.Estado = 'Cerrado' AND DATE(t.FechaApertura) BETWEEN :inicio AND :fin
        ORDER BY t.TurnoID DESC
        LIMIT 50
    ");
    $stmtCuadraturas->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $cuadraturasTurnos = $stmtCuadraturas->fetchAll(PDO::FETCH_ASSOC);

    // Acumulado de diferencias
    $totalSobrantes = 0;
    $totalFaltantes = 0;
    foreach ($cuadraturasTurnos as $ct) {
        $dif = (int)$ct['Diferencia'];
        if ($dif > 0) $totalSobrantes += $dif;
        if ($dif < 0) $totalFaltantes += abs($dif);
    }
}

// TAB 5: UTILIDADES Y MÁRGENES DE GANANCIA
if ($tab === 'utilidades') {
    $stmtUtil = $pdo->prepare("
        SELECT 
            p.ProductoID,
            p.Nombre AS Producto,
            p.CodigoBarras,
            p.CodigoPLU,
            COALESCE(p.CategoriaID, 0) AS CategoriaID,
            COALESCE(c.Nombre, 'Sin Categoría') AS Categoria,
            SUM(dv.Cantidad) AS CantidadVendida,
            SUM(dv.Subtotal) AS TotalVentas,
            SUM(dv.Cantidad * COALESCE(NULLIF(dv.CostoUnitario, 0), p.CostoCompra, 0)) AS TotalCosto,
            SUM(dv.Subtotal - (dv.Cantidad * COALESCE(NULLIF(dv.CostoUnitario, 0), p.CostoCompra, 0))) AS UtilidadEstimada
        FROM detalleventas dv
        JOIN ventas v ON dv.VentaID = v.VentaID
        JOIN productos p ON dv.ProductoID = p.ProductoID
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE v.Estado = 'Completada' AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
        GROUP BY p.ProductoID, c.Nombre
        ORDER BY Categoria ASC, UtilidadEstimada DESC
    ");
    $stmtUtil->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $reporteUtilidades = $stmtUtil->fetchAll(PDO::FETCH_ASSOC);

    $totalVentasUtil = 0;
    $totalCostoUtil = 0;
    $totalUtilidadMonto = 0;
    $utilidadesPorCategoria = [];

    foreach ($reporteUtilidades as $r) {
        $catId = (int)$r['CategoriaID'];
        $catNombre = $r['Categoria'] ?: 'Sin Categoría';

        if (!isset($utilidadesPorCategoria[$catId])) {
            $utilidadesPorCategoria[$catId] = [
                'CategoriaID' => $catId,
                'Categoria' => $catNombre,
                'TotalVentas' => 0,
                'TotalCosto' => 0,
                'UtilidadEstimada' => 0,
                'CantidadVendida' => 0,
                'ProductosCount' => 0,
                'Productos' => []
            ];
        }

        $utilidadesPorCategoria[$catId]['TotalVentas'] += (int)$r['TotalVentas'];
        $utilidadesPorCategoria[$catId]['TotalCosto'] += (int)$r['TotalCosto'];
        $utilidadesPorCategoria[$catId]['UtilidadEstimada'] += (int)$r['UtilidadEstimada'];
        $utilidadesPorCategoria[$catId]['CantidadVendida'] += (float)$r['CantidadVendida'];
        $utilidadesPorCategoria[$catId]['ProductosCount']++;
        $utilidadesPorCategoria[$catId]['Productos'][] = $r;

        $totalVentasUtil += (int)$r['TotalVentas'];
        $totalCostoUtil += (int)$r['TotalCosto'];
        $totalUtilidadMonto += (int)$r['UtilidadEstimada'];
    }

    // Ordenar categorías por mayor utilidad estimada
    uasort($utilidadesPorCategoria, function($a, $b) {
        return $b['UtilidadEstimada'] <=> $a['UtilidadEstimada'];
    });

    // Lista de categorías para selector
    $categoriasUtilSelector = [];
    foreach ($utilidadesPorCategoria as $c) {
        $categoriasUtilSelector[] = [
            'CategoriaID' => $c['CategoriaID'],
            'Nombre' => $c['Categoria'],
            'ItemsCount' => $c['ProductosCount']
        ];
    }

    // Pérdidas por Mermas al Costo en el período
    $stmtMermasPeriodo = $pdo->prepare("
        SELECT 
            COALESCE(SUM(da.Cantidad), 0) AS TotalUnidadesMermadas,
            COALESCE(SUM(da.Cantidad * COALESCE(NULLIF(da.CostoUnitario, 0), p.CostoCompra, 0)), 0) AS TotalMermasCosto,
            COALESCE(SUM(da.Cantidad * p.PrecioVenta), 0) AS TotalMermasVenta
        FROM ajustesstock a
        JOIN detalleajustesstock da ON a.AjusteStockID = da.AjusteStockID
        JOIN productos p ON da.ProductoID = p.ProductoID
        WHERE da.TipoMovimiento = 'SALIDA' 
          AND a.ProveedorID IS NULL
          AND DATE(a.FechaAjuste) BETWEEN :inicio AND :fin
    ");
    $stmtMermasPeriodo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiMermasUtil = $stmtMermasPeriodo->fetch(PDO::FETCH_ASSOC);

    $totalMermasCosto = (int)$kpiMermasUtil['TotalMermasCosto'];
    $totalMermasUnidades = (float)$kpiMermasUtil['TotalUnidadesMermadas'];
    $utilidadRealNeta = $totalUtilidadMonto - $totalMermasCosto;
}

// TAB 6: CARTERA DE CLIENTES Y FIADOS (CUENTAS POR COBRAR)
if ($tab === 'creditos') {
    // Total deuda actual de clientes activos
    $stmtDeuda = $pdo->query("
        SELECT 
            COALESCE(SUM(SaldoDeudor), 0) AS TotalDeudaGlobal,
            COUNT(CASE WHEN SaldoDeudor > 0 THEN 1 END) AS ClientesDeudoresCount,
            COALESCE(SUM(LimiteCredito), 0) AS TotalCupoGlobal
        FROM clientes 
        WHERE Activo = 1
    ");
    $kpiCreditos = $stmtDeuda->fetch(PDO::FETCH_ASSOC);

    // Total ventas al fiado en el período
    $stmtFiadoPeriodo = $pdo->prepare("
        SELECT COALESCE(SUM(pv.Monto), 0) AS TotalFiadoPeriodo, COUNT(DISTINCT v.VentaID) AS TransaccionesFiado
        FROM pagosventa pv
        JOIN ventas v ON pv.VentaID = v.VentaID
        WHERE v.Estado = 'Completada' 
          AND pv.MetodoPago = 'Credito_Interno' 
          AND DATE(v.FechaVenta) BETWEEN :inicio AND :fin
    ");
    $stmtFiadoPeriodo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiFiado = $stmtFiadoPeriodo->fetch(PDO::FETCH_ASSOC);

    // Total abonos recaudados en el período
    $stmtAbonosPeriodo = $pdo->prepare("
        SELECT COALESCE(SUM(Monto), 0) AS TotalAbonosPeriodo, COUNT(AbonoID) AS CantidadAbonos
        FROM abonoscredito
        WHERE DATE(FechaAbono) BETWEEN :inicio AND :fin
    ");
    $stmtAbonosPeriodo->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiAbonos = $stmtAbonosPeriodo->fetch(PDO::FETCH_ASSOC);

    // Cartera de clientes deudores (ordenada por mayor deuda)
    $stmtCartera = $pdo->query("
        SELECT 
            ClienteID,
            Nombre,
            RutCuerpo,
            RutDv,
            Telefono,
            Email,
            LimiteCredito,
            SaldoDeudor,
            PuntosAcumulados
        FROM clientes
        WHERE Activo = 1 AND SaldoDeudor > 0
        ORDER BY SaldoDeudor DESC
    ");
    $carteraDeudores = $stmtCartera->fetchAll(PDO::FETCH_ASSOC);

    // Historial de abonos recibidos en el período
    $stmtAbonosList = $pdo->prepare("
        SELECT 
            ac.AbonoID,
            ac.FechaAbono,
            ac.Monto,
            ac.MetodoPago,
            c.Nombre AS Cliente,
            u.Nombre AS Cajero
        FROM abonoscredito ac
        JOIN clientes c ON ac.ClienteID = c.ClienteID
        LEFT JOIN turnos t ON ac.TurnoID = t.TurnoID
        LEFT JOIN usuarios u ON t.UsuarioID = u.UsuarioID
        WHERE DATE(ac.FechaAbono) BETWEEN :inicio AND :fin
        ORDER BY ac.AbonoID DESC
        LIMIT 100
    ");
    $stmtAbonosList->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $abonosList = $stmtAbonosList->fetchAll(PDO::FETCH_ASSOC);
}

// TAB 7: COMPRAS Y GASTOS POR PROVEEDOR (EGRESOS Y ABASTECIMIENTO)
if ($tab === 'compras') {
    // KPIs Generales de Compras
    $stmtComprasKPI = $pdo->prepare("
        SELECT 
            COUNT(c.CompraID) AS TotalCompras,
            COALESCE(SUM(c.MontoTotal), 0) AS TotalEgresosCompras,
            COALESCE(SUM(c.MontoNeto), 0) AS TotalNetoCompras,
            COALESCE(SUM(c.MontoIva), 0) AS TotalIvaCompras,
            COUNT(DISTINCT c.ProveedorID) AS TotalProveedoresActivos
        FROM compras c
        WHERE c.Estado = 'Completada' AND DATE(c.FechaCompra) BETWEEN :inicio AND :fin
    ");
    $stmtComprasKPI->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiCompras = $stmtComprasKPI->fetch(PDO::FETCH_ASSOC);

    $totalEgresosMonto = (int)$kpiCompras['TotalEgresosCompras'];
    $comprasCount = (int)$kpiCompras['TotalCompras'];
    $compraPromedio = $comprasCount > 0 ? round($totalEgresosMonto / $comprasCount) : 0;

    // Resumen y Ranking de Compras por Proveedor
    $stmtProvRank = $pdo->prepare("
        SELECT 
            p.ProveedorID,
            p.RazonSocial,
            p.RutCuerpo,
            p.RutDv,
            p.Telefono,
            COUNT(c.CompraID) AS CantidadFacturas,
            COALESCE(SUM(c.MontoNeto), 0) AS MontoNeto,
            COALESCE(SUM(c.MontoIva), 0) AS MontoIva,
            COALESCE(SUM(c.MontoTotal), 0) AS TotalComprado
        FROM compras c
        JOIN proveedores p ON c.ProveedorID = p.ProveedorID
        WHERE c.Estado = 'Completada' AND DATE(c.FechaCompra) BETWEEN :inicio AND :fin
        GROUP BY p.ProveedorID
        ORDER BY TotalComprado DESC
    ");
    $stmtProvRank->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $rankingProveedores = $stmtProvRank->fetchAll(PDO::FETCH_ASSOC);

    // Historial de Recepciones de Compra en el período
    $stmtComprasList = $pdo->prepare("
        SELECT 
            c.CompraID,
            c.FechaCompra,
            c.NumeroDocumento,
            c.MontoNeto,
            c.MontoIva,
            c.MontoTotal,
            p.RazonSocial AS Proveedor,
            u.Nombre AS Usuario
        FROM compras c
        JOIN proveedores p ON c.ProveedorID = p.ProveedorID
        JOIN usuarios u ON c.UsuarioID = u.UsuarioID
        WHERE c.Estado = 'Completada' AND DATE(c.FechaCompra) BETWEEN :inicio AND :fin
        ORDER BY c.CompraID DESC
        LIMIT 100
    ");
    $stmtComprasList->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $comprasList = $stmtComprasList->fetchAll(PDO::FETCH_ASSOC);
}

// TAB 8: AUDITORÍA DE MERMAS Y PÉRDIDAS DE MERCADERÍA
if ($tab === 'mermas') {
    // 1. KPIs Generales de Mermas en el Período
    $stmtKpiMermas = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT a.AjusteStockID) AS TotalEventosMermas,
            COUNT(da.DetalleAjusteStockID) AS TotalLineasAfectadas,
            COALESCE(SUM(da.Cantidad), 0) AS TotalUnidadesMermadas,
            COALESCE(SUM(da.Cantidad * COALESCE(NULLIF(da.CostoUnitario, 0), p.CostoCompra, 0)), 0) AS TotalPerdidaCosto,
            COALESCE(SUM(da.Cantidad * p.PrecioVenta), 0) AS TotalPerdidaVenta
        FROM ajustesstock a
        JOIN detalleajustesstock da ON a.AjusteStockID = da.AjusteStockID
        JOIN productos p ON da.ProductoID = p.ProductoID
        WHERE da.TipoMovimiento = 'SALIDA' 
          AND a.ProveedorID IS NULL
          AND DATE(a.FechaAjuste) BETWEEN :inicio AND :fin
    ");
    $stmtKpiMermas->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $kpiMermas = $stmtKpiMermas->fetch(PDO::FETCH_ASSOC);

    // 2. Desglose y Agrupación por Motivo de Merma
    $stmtMotivos = $pdo->prepare("
        SELECT 
            CASE 
                WHEN a.Motivo LIKE 'Vencimiento%' THEN 'Vencimiento / Caducidad'
                WHEN a.Motivo LIKE 'Daño%' OR a.Motivo LIKE 'Rotura%' OR a.Motivo LIKE 'Deterioro%' THEN 'Daño / Rotura'
                WHEN a.Motivo LIKE 'Pérdida%' OR a.Motivo LIKE 'Descuadre%' OR a.Motivo LIKE 'Robo%' THEN 'Pérdida / Descuadre'
                WHEN a.Motivo LIKE 'Consumo Interno%' OR a.Motivo LIKE 'Consumo Personal%' THEN 'Consumo Interno'
                WHEN a.Motivo LIKE 'Conteo Físico%' OR a.Motivo LIKE 'Inventario%' THEN 'Conteo Físico'
                ELSE 'Otros / Personalizado'
            END AS GrupoMotivo,
            COUNT(da.DetalleAjusteStockID) AS CantidadItems,
            COALESCE(SUM(da.Cantidad), 0) AS TotalUnidades,
            COALESCE(SUM(da.Cantidad * COALESCE(NULLIF(da.CostoUnitario, 0), p.CostoCompra, 0)), 0) AS PerdidaCosto,
            COALESCE(SUM(da.Cantidad * p.PrecioVenta), 0) AS PerdidaVenta
        FROM ajustesstock a
        JOIN detalleajustesstock da ON a.AjusteStockID = da.AjusteStockID
        JOIN productos p ON da.ProductoID = p.ProductoID
        WHERE da.TipoMovimiento = 'SALIDA' 
          AND a.ProveedorID IS NULL
          AND DATE(a.FechaAjuste) BETWEEN :inicio AND :fin
        GROUP BY GrupoMotivo
        ORDER BY PerdidaCosto DESC
    ");
    $stmtMotivos->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $mermasPorMotivo = $stmtMotivos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Listado Detallado de Registros de Merma
    $stmtDetalleMermas = $pdo->prepare("
        SELECT 
            a.AjusteStockID,
            a.FechaAjuste,
            a.Motivo,
            a.DocReferencia,
            u.Nombre AS Usuario,
            p.ProductoID,
            p.CodigoBarras,
            p.CodigoPLU,
            p.Nombre AS Producto,
            COALESCE(c.Nombre, 'Sin Categoría') AS Categoria,
            da.Cantidad,
            COALESCE(NULLIF(da.CostoUnitario, 0), p.CostoCompra, 0) AS CostoUnitario,
            p.PrecioVenta,
            ROUND(da.Cantidad * COALESCE(NULLIF(da.CostoUnitario, 0), p.CostoCompra, 0)) AS PerdidaCosto,
            ROUND(da.Cantidad * p.PrecioVenta) AS PerdidaVenta
        FROM ajustesstock a
        JOIN usuarios u ON a.UsuarioID = u.UsuarioID
        JOIN detalleajustesstock da ON a.AjusteStockID = da.AjusteStockID
        JOIN productos p ON da.ProductoID = p.ProductoID
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE da.TipoMovimiento = 'SALIDA' 
          AND a.ProveedorID IS NULL
          AND DATE(a.FechaAjuste) BETWEEN :inicio AND :fin
        ORDER BY a.FechaAjuste DESC, a.AjusteStockID DESC
        LIMIT 200
    ");
    $stmtDetalleMermas->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
    $detalleMermas = $stmtDetalleMermas->fetchAll(PDO::FETCH_ASSOC);
}

// ----------------------------------------------------
// 3. EXPORTACIÓN A CSV PARA EXCEL
// ----------------------------------------------------
if ($esExport) {
    // Limpiar cualquier salida previa
    if (ob_get_level()) ob_end_clean();

    $filename = "reporte_{$tab}_{$fechaInicio}_al_{$fechaFin}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM para apertura perfecta en Excel Windows
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    $delimiter = ';';

    if ($tab === 'ventas') {
        fputcsv($out, ['REPORTE DE VENTAS Y MEDIOS DE PAGO - MINIMARKET POS'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, ['Total Ventas', '$' . number_format($kpiVentas['TotalVentas'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total Neto', '$' . number_format($kpiVentas['TotalNeto'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total IVA (19%)', '$' . number_format($kpiVentas['TotalIva'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total Descuentos', '$' . number_format($kpiVentas['TotalDescuentos'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total Transacciones', $kpiVentas['TotalTransacciones']], $delimiter);
        fputcsv($out, ['Ticket Promedio', '$' . number_format($ticketPromedio, 0, ',', '.')], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['DESGLOSE POR MEDIO DE PAGO'], $delimiter);
        fputcsv($out, ['Medio de Pago', 'Transacciones', 'Monto Total'], $delimiter);
        foreach ($pagosMetodos as $p) {
            fputcsv($out, [$p['MetodoPago'], $p['Transacciones'], $p['TotalRecaudado']], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['LISTADO DETALLADO DE VENTAS'], $delimiter);
        fputcsv($out, ['ID Venta', 'Fecha y Hora', 'Documento', 'Cajero', 'Cliente', 'Monto Total'], $delimiter);
        foreach ($ventasList as $v) {
            fputcsv($out, [
                $v['VentaID'],
                $v['FechaVenta'],
                $v['TipoDocumento'],
                $v['Cajero'],
                $v['Cliente'] ?: 'Cliente Ocasional',
                $v['MontoTotal']
            ], $delimiter);
        }
    } elseif ($tab === 'productos') {
        fputcsv($out, ['REPORTE DE RANKING Y ROTACION DE PRODUCTOS'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['TOP PRODUCTOS MAS VENDIDOS'], $delimiter);
        fputcsv($out, ['Producto', 'Codigo de Barras', 'Categoria', 'Unidades Vendidas', 'Venta Total ($)', 'Utilidad Total ($)'], $delimiter);
        foreach ($rankingProductos as $rp) {
            fputcsv($out, [
                $rp['Nombre'],
                $rp['CodigoBarras'],
                $rp['Categoria'] ?: 'General',
                $rp['UnidadesVendidas'],
                $rp['MontoTotalVentas'],
                $rp['UtilidadTotal']
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['PRODUCTOS SIN ROTACION (HUESOS O STOCK ESTANCADO)'], $delimiter);
        fputcsv($out, ['Producto', 'Codigo de Barras', 'Categoria', 'Stock Fisico', 'Costo Compra', 'Capital Inmovilizado ($)', 'Precio Venta'], $delimiter);
        foreach ($productosSinRotacion as $h) {
            fputcsv($out, [
                $h['Nombre'],
                $h['CodigoBarras'],
                $h['Categoria'] ?: 'General',
                $h['Stock'],
                $h['CostoCompra'],
                $h['CapitalInmovilizado'],
                $h['PrecioVenta']
            ], $delimiter);
        }
    } elseif ($tab === 'inventario') {
        fputcsv($out, ['REPORTE DE VALORIZACION DE INVENTARIO Y STOCK'], $delimiter);
        fputcsv($out, ['Fecha de Emision', date('Y-m-d H:i')], $delimiter);
        fputcsv($out, ['Total Productos Activos', $kpiInventario['TotalProductos']], $delimiter);
        fputcsv($out, ['Valor Total a Costo de Compra', $kpiInventario['ValorTotalCosto']], $delimiter);
        fputcsv($out, ['Valor Total a Precio de Venta', $kpiInventario['ValorTotalVenta']], $delimiter);
        fputcsv($out, ['Margen Potencial Total (s/Costo)', $margenPotencialTotal . ($kpiInventario['ValorTotalCosto'] > 0 ? " (+$margenPotencialPorc%)" : " (S/C)")], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['DESGLOSE POR CATEGORIA'], $delimiter);
        fputcsv($out, ['Categoria', 'Cantidad Items', 'Stock Total', 'Valor a Costo ($)', 'Valor a Venta ($)', 'Margen Potencial ($)', 'Margen % s/Costo'], $delimiter);
        foreach ($categoriasInventario as $ci) {
            $margenCat = $ci['ValorVenta'] - $ci['ValorCosto'];
            $pctCat = $ci['ValorCosto'] > 0 ? round(($margenCat / $ci['ValorCosto']) * 100, 1) . '%' : 'S/C';
            fputcsv($out, [
                $ci['Categoria'],
                $ci['TotalItems'],
                $ci['StockTotal'],
                $ci['ValorCosto'],
                $ci['ValorVenta'],
                $margenCat,
                $pctCat
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['DETALLE VALORIZADO POR PRODUCTO (LISTADO Y COSTOS)'], $delimiter);
        fputcsv($out, ['Codigo', 'Producto', 'Categoria', 'Stock Fisico', 'Costo Unitario ($)', 'Precio Venta ($)', 'Total Costo ($)', 'Total Venta ($)', 'Margen Potencial ($)', 'Margen % s/Costo'], $delimiter);
        foreach ($productosInventario as $pi) {
            $margenItem = $pi['TotalVenta'] - $pi['TotalCosto'];
            $pctItem = $pi['CostoCompra'] > 0 ? round((($pi['PrecioVenta'] - $pi['CostoCompra']) / $pi['CostoCompra']) * 100, 1) . '%' : 'S/C';
            $cod = $pi['CodigoBarras'] ?: ($pi['CodigoPLU'] ? 'PLU:' . $pi['CodigoPLU'] : 'Sin Codigo');
            fputcsv($out, [
                $cod,
                $pi['Nombre'],
                $pi['Categoria'],
                (float)$pi['Stock'],
                $pi['CostoCompra'],
                $pi['PrecioVenta'],
                $pi['TotalCosto'],
                $pi['TotalVenta'],
                $margenItem,
                $pctItem
            ], $delimiter);
        }
    } elseif ($tab === 'cajeros') {
        fputcsv($out, ['REPORTE DE RENDIMIENTO DE CAJEROS Y CUADRATURAS'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['RENDIMIENTO POR CAJERO'], $delimiter);
        fputcsv($out, ['Cajero', 'Usuario', 'Rol', 'Turnos Realizados', 'Total Transacciones', 'Total Recaudado ($)'], $delimiter);
        foreach ($cajerosRendimiento as $c) {
            fputcsv($out, [
                $c['Cajero'],
                $c['Usuario'],
                $c['Rol'],
                $c['TurnosRealizados'],
                $c['TotalVentas'],
                $c['MontoTotalVentas']
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['HISTORIAL DE CUADRATURAS DE CAJA (ARQUEOS)'], $delimiter);
        fputcsv($out, ['N° Turno', 'Cajero', 'Apertura', 'Cierre', 'Monto Sistema', 'Monto Declarado', 'Diferencia ($)'], $delimiter);
        foreach ($cuadraturasTurnos as $ct) {
            fputcsv($out, [
                $ct['TurnoID'],
                $ct['Cajero'],
                $ct['FechaApertura'],
                $ct['FechaCierre'],
                $ct['MontoCierreSistema'],
                $ct['TotalDeclarado'],
                $ct['Diferencia']
            ], $delimiter);
        }
    } elseif ($tab === 'utilidades') {
        fputcsv($out, ['REPORTE DE UTILIDADES Y MARGENES POR PRODUCTO'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, ['Total Ventas', $totalVentasUtil], $delimiter);
        fputcsv($out, ['Utilidad Bruta en Ventas', $totalUtilidadMonto], $delimiter);
        fputcsv($out, ['(-) Total Perdidas por Mermas', $totalMermasCosto], $delimiter);
        fputcsv($out, ['Utilidad Real Neta Final', $utilidadRealNeta], $delimiter);
        $margenPromedioCsv = $totalCostoUtil > 0 ? round(($totalUtilidadMonto / $totalCostoUtil) * 100, 1) . '%' : 'S/C';
        fputcsv($out, ['Margen Promedio Global (s/Costo)', $margenPromedioCsv], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['Producto', 'Categoria', 'Unidades Vendidas', 'Venta Total ($)', 'Costo Total ($)', 'Utilidad ($)', 'Margen % s/Costo'], $delimiter);
        foreach ($reporteUtilidades as $ru) {
            $mPorc = $ru['TotalCosto'] > 0 ? round(($ru['UtilidadEstimada'] / $ru['TotalCosto']) * 100, 1) . '%' : 'S/C';
            fputcsv($out, [
                $ru['Producto'],
                $ru['Categoria'] ?: 'General',
                $ru['CantidadVendida'],
                $ru['TotalVentas'],
                $ru['TotalCosto'],
                $ru['UtilidadEstimada'],
                $mPorc
            ], $delimiter);
        }
    } elseif ($tab === 'creditos') {
        fputcsv($out, ['REPORTE DE CARTERA DE CLIENTES Y FIADOS (CUENTAS POR COBRAR)'], $delimiter);
        fputcsv($out, ["Periodo de Abonos y Ventas Fiadas: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, ['Total Deuda Global por Cobrar', '$' . number_format($kpiCreditos['TotalDeudaGlobal'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Clientes con Deuda Activa', $kpiCreditos['ClientesDeudoresCount']], $delimiter);
        fputcsv($out, ['Total Cupo de Credito Autorizado', '$' . number_format($kpiCreditos['TotalCupoGlobal'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Ventas al Fiado en el Periodo', '$' . number_format($kpiFiado['TotalFiadoPeriodo'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Abonos Recaudados en el Periodo', '$' . number_format($kpiAbonos['TotalAbonosPeriodo'], 0, ',', '.')], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['CARTERA DE CLIENTES DEUDORES (RANKING DE DEUDA)'], $delimiter);
        fputcsv($out, ['Cliente', 'RUT', 'Telefono', 'Cupo Maximo ($)', 'Saldo Deudor ($)', 'Cupo Utilizado %'], $delimiter);
        foreach ($carteraDeudores as $cd) {
            $pctCupo = $cd['LimiteCredito'] > 0 ? round(($cd['SaldoDeudor'] / $cd['LimiteCredito']) * 100, 1) : 100;
            $rutFmt = $cd['RutCuerpo'] ? number_format($cd['RutCuerpo'], 0, '', '.') . '-' . $cd['RutDv'] : 'Sin RUT';
            fputcsv($out, [
                $cd['Nombre'],
                $rutFmt,
                $cd['Telefono'] ?: '—',
                $cd['LimiteCredito'],
                $cd['SaldoDeudor'],
                $pctCupo . '%'
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['HISTORIAL DE ABONOS RECIBIDOS EN EL PERIODO'], $delimiter);
        fputcsv($out, ['N° Abono', 'Fecha y Hora', 'Cliente', 'Cajero', 'Medio de Pago', 'Monto Abonado ($)'], $delimiter);
        foreach ($abonosList as $ab) {
            fputcsv($out, [
                $ab['AbonoID'],
                $ab['FechaAbono'],
                $ab['Cliente'],
                $ab['Cajero'] ?: '—',
                $ab['MetodoPago'],
                $ab['Monto']
            ], $delimiter);
        }
    } elseif ($tab === 'compras') {
        fputcsv($out, ['REPORTE DE COMPRAS Y GASTOS POR PROVEEDOR (EGRESOS)'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, ['Total Egresos en Compras', '$' . number_format($kpiCompras['TotalEgresosCompras'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total Monto Neto', '$' . number_format($kpiCompras['TotalNetoCompras'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Total IVA Credito Fiscal', '$' . number_format($kpiCompras['TotalIvaCompras'], 0, ',', '.')], $delimiter);
        fputcsv($out, ['Facturas/Guias Recibidas', $kpiCompras['TotalCompras']], $delimiter);
        fputcsv($out, ['Proveedores Activos Abasteciendo', $kpiCompras['TotalProveedoresActivos']], $delimiter);
        fputcsv($out, ['Gasto Promedio por Factura', '$' . number_format($compraPromedio, 0, ',', '.')], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['RESUMEN Y RANKING DE COMPRAS POR PROVEEDOR'], $delimiter);
        fputcsv($out, ['Proveedor / Razon Social', 'RUT', 'Telefono', 'Facturas Recibidas', 'Monto Neto ($)', 'IVA Credito ($)', 'Total Comprado ($)', '% Participacion'], $delimiter);
        foreach ($rankingProveedores as $pr) {
            $rutProv = $pr['RutCuerpo'] ? number_format($pr['RutCuerpo'], 0, '', '.') . '-' . $pr['RutDv'] : '—';
            $pctGasto = $kpiCompras['TotalEgresosCompras'] > 0 ? round(($pr['TotalComprado'] / $kpiCompras['TotalEgresosCompras']) * 100, 1) : 0;
            fputcsv($out, [
                $pr['RazonSocial'],
                $rutProv,
                $pr['Telefono'] ?: '—',
                $pr['CantidadFacturas'],
                $pr['MontoNeto'],
                $pr['MontoIva'],
                $pr['TotalComprado'],
                $pctGasto . '%'
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['HISTORIAL DE RECEPCIONES DE COMPRA EN EL PERIODO'], $delimiter);
        fputcsv($out, ['N° Compra', 'Fecha', 'Proveedor', 'N° Factura/Doc', 'Recepcionado Por', 'Neto ($)', 'IVA ($)', 'Total Factura ($)'], $delimiter);
        foreach ($comprasList as $comp) {
            fputcsv($out, [
                $comp['CompraID'],
                $comp['FechaCompra'],
                $comp['Proveedor'],
                $comp['NumeroDocumento'] ?: 'S/N',
                $comp['Usuario'],
                $comp['MontoNeto'],
                $comp['MontoIva'],
                $comp['MontoTotal']
            ], $delimiter);
        }
    } elseif ($tab === 'mermas') {
        fputcsv($out, ['REPORTE DE AUDITORIA DE MERMAS Y PERDIDAS DE MERCADERIA'], $delimiter);
        fputcsv($out, ["Periodo: $fechaInicio a $fechaFin"], $delimiter);
        fputcsv($out, ['Total Unidades Mermadas', $kpiMermas['TotalUnidadesMermadas']], $delimiter);
        fputcsv($out, ['Total Perdida a Costo de Compra ($)', $kpiMermas['TotalPerdidaCosto']], $delimiter);
        fputcsv($out, ['Total Venta Perdida No Percibida ($)', $kpiMermas['TotalPerdidaVenta']], $delimiter);
        fputcsv($out, ['Cantidad de Incidentes / Registros', $kpiMermas['TotalLineasAfectadas']], $delimiter);
        fputcsv($out, [], $delimiter);

        fputcsv($out, ['DESGLOSE DE PERDIDAS POR MOTIVO'], $delimiter);
        fputcsv($out, ['Motivo', 'Registros', 'Unidades Mermadas', 'Perdida al Costo ($)', 'Venta Perdida ($)'], $delimiter);
        foreach ($mermasPorMotivo as $mpm) {
            fputcsv($out, [
                $mpm['GrupoMotivo'],
                $mpm['CantidadItems'],
                $mpm['TotalUnidades'],
                $mpm['PerdidaCosto'],
                $mpm['PerdidaVenta']
            ], $delimiter);
        }

        fputcsv($out, [], $delimiter);
        fputcsv($out, ['DETALLE INDIVIDUAL DE MERMAS EN EL PERIODO'], $delimiter);
        fputcsv($out, ['Fecha', 'N° Ajuste', 'Codigo', 'Producto', 'Categoria', 'Cantidad', 'Costo Unit. ($)', 'P. Venta ($)', 'Perdida Costo ($)', 'Venta Perdida ($)', 'Motivo Detallado', 'Registrado Por'], $delimiter);
        foreach ($detalleMermas as $dm) {
            $cod = $dm['CodigoBarras'] ?: ($dm['CodigoPLU'] ? 'PLU:' . $dm['CodigoPLU'] : 'Sin Codigo');
            fputcsv($out, [
                $dm['FechaAjuste'],
                '#' . $dm['AjusteStockID'],
                $cod,
                $dm['Producto'],
                $dm['Categoria'],
                (float)$dm['Cantidad'],
                $dm['CostoUnitario'],
                $dm['PrecioVenta'],
                $dm['PerdidaCosto'],
                $dm['PerdidaVenta'],
                $dm['Motivo'],
                $dm['Usuario']
            ], $delimiter);
        }
    }

    fclose($out);
    exit;
}

// ----------------------------------------------------
// 4. RENDERIZAR VISTA
// ----------------------------------------------------
require_once __DIR__ . '/includes/header.php';
include __DIR__ . '/views/reportes.view.php';
require_once __DIR__ . '/includes/footer.php';

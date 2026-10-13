<?php
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

// Verificar si el usuario actual tiene un turno activo/abierto
$stmtTurno = $pdo->prepare("
    SELECT t.TurnoID, t.CajaID, c.Nombre AS NombreCaja 
    FROM turnos t 
    JOIN cajas c ON t.CajaID = c.CajaID 
    WHERE t.UsuarioID = :uid AND t.Estado = 'Abierto' 
    ORDER BY t.TurnoID DESC LIMIT 1
");
$stmtTurno->execute([':uid' => $user['id']]);
$turnoActivo = $stmtTurno->fetch();

// Cargar clientes con su saldo deudor y puntos acumulados
$clientes = $pdo->query("
    SELECT ClienteID, Nombre, RutCuerpo, RutDv, PuntosAcumulados, LimiteCredito, SaldoDeudor 
    FROM clientes WHERE Activo = TRUE ORDER BY Nombre ASC
")->fetchAll();

// Cargar configuraciones de balanza
$stmtCfg = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('BALANZA_PREFIJO_INDIVIDUAL', 'BALANZA_TIPO_EAN', 'BALANZA_MODO', 'BALANZA_INGRESO_MANUAL')");
$configBalanza = [];
while ($row = $stmtCfg->fetch(PDO::FETCH_ASSOC)) {
    $configBalanza[$row['Clave']] = $row['Valor'];
}

// Datos del local para el encabezado y pie del comprobante impreso
$stmtLocal = $pdo->query("
    SELECT Clave, Valor FROM configuraciones
    WHERE Clave IN ('MINIMARKET_NOMBRE', 'MINIMARKET_RUT', 'MINIMARKET_DIRECCION', 'MINIMARKET_GIRO', 'MINIMARKET_TELEFONO', 'TICKET_PIE_PAGINA')
");
$cfgLocal = [];
while ($row = $stmtLocal->fetch(PDO::FETCH_ASSOC)) {
    $cfgLocal[$row['Clave']] = $row['Valor'];
}

// Cargar configuraciones de supervisión en caja
$stmtSup = $pdo->query("
    SELECT Clave, Valor FROM configuraciones 
    WHERE Clave IN ('POS_REQ_SUPERVISOR_CANCELAR', 'POS_REQ_SUPERVISOR_ELIMINAR_ITEM', 'POS_DESCUENTO_MAX_PORC', 'PERMITIR_STOCK_NEGATIVO')
");
$configSupervision = [
    'POS_REQ_SUPERVISOR_CANCELAR' => 'SI',
    'POS_REQ_SUPERVISOR_ELIMINAR_ITEM' => 'SI',
    'POS_DESCUENTO_MAX_PORC' => '5',
    'PERMITIR_STOCK_NEGATIVO' => 'false',
];
while ($row = $stmtSup->fetch(PDO::FETCH_ASSOC)) {
    $configSupervision[$row['Clave']] = $row['Valor'];
}

// Cargar reglas de módulos: Crédito Interno / Fiado y Club de Puntos
$stmtModulos = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('CREDITO_INTERNO_ACTIVO', 'LOYALTY_ACTIVO', 'LOYALTY_PORCENTAJE')");
$configModulos = [
    'CREDITO_INTERNO_ACTIVO' => 'true',
    'LOYALTY_ACTIVO' => 'true',
    'LOYALTY_PORCENTAJE' => '1.0'
];
while ($row = $stmtModulos->fetch(PDO::FETCH_ASSOC)) {
    $configModulos[$row['Clave']] = $row['Valor'];
}
$creditoActivo = ($configModulos['CREDITO_INTERNO_ACTIVO'] ?? 'true') === 'true';
$loyaltyActivo = ($configModulos['LOYALTY_ACTIVO'] ?? 'true') === 'true';
$loyaltyPorcentaje = (float)($configModulos['LOYALTY_PORCENTAJE'] ?? 1.0);

// Cargar modo operativo del POS (supermercado, tactil, clasico)
$stmtModo = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave = 'POS_LAYOUT_MODO'");
$posLayoutModo = $stmtModo ? ($stmtModo->fetchColumn() ?: 'supermercado') : 'supermercado';

// Cargar diseño predeterminado de grilla POS
$stmtGrid = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave = 'POS_DISENO_GRID'");
$posDisenoGridDefault = $stmtGrid ? ($stmtGrid->fetchColumn() ?: 'estandar') : 'estandar';

// Cargar categorías para los filtros de catálogo y modo táctil
$categorias = $pdo->query("SELECT CategoriaID, Nombre FROM categorias ORDER BY Nombre ASC")->fetchAll();

// Cotización a cargar automáticamente en el carrito (viene de la pantalla de Cotizaciones)
$cotizacionPreload = (int)($_GET['cotizacion'] ?? 0);

include __DIR__ . '/views/pos.view.php';
require_once __DIR__ . '/includes/footer.php';

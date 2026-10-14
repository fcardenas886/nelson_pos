<?php
require_once __DIR__ . '/includes/header.php';
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();

// Cargar credenciales guardadas si existen
$configRows = $pdo->query("
    SELECT Clave, Valor FROM configuraciones 
    WHERE Clave IN ('SII_RUT_USUARIO', 'SII_CLAVE_USUARIO', 'SII_RUT_EMPRESA', 'MINIMARKET_RUT', 'MINIMARKET_NOMBRE')
")->fetchAll();

$siiConfig = [];
foreach ($configRows as $r) {
    $siiConfig[$r['Clave']] = $r['Valor'];
}

$rutUsuarioGuardado = $siiConfig['SII_RUT_USUARIO'] ?? '';
$claveGuardada = $siiConfig['SII_CLAVE_USUARIO'] ?? '';
$rutEmpresaGuardado = $siiConfig['SII_RUT_EMPRESA'] ?? ($siiConfig['MINIMARKET_RUT'] ?? '');
$nombreEmpresa = $siiConfig['MINIMARKET_NOMBRE'] ?? 'Mi Empresa';

// Período actual por defecto
$mesActual = (int)date('m');
$anioActual = (int)date('Y');

include __DIR__ . '/views/rcv_sii.view.php';
require_once __DIR__ . '/includes/footer.php';

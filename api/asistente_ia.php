<?php
/**
 * Endpoint de Asistente de IA para Soporte Operativo y Manual del Sistema
 * Versión 4.9.0 - Minimarket POS Web
 * Restringido exclusivamente al uso, procedimientos y funciones reales del sistema.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Validar que el usuario esté autenticado en el sistema
if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Debes iniciar sesión en el sistema.']);
    exit;
}

$user = $_SESSION['usuario'];
$userRol = $user['rol'] ?? 'Cajero';
$userNombre = $user['nombre'] ?? 'Operador';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];
$mensaje = trim($data['mensaje'] ?? $_POST['mensaje'] ?? $GLOBALS['test_mensaje'] ?? '');
$historial = is_array($data['historial'] ?? null) ? $data['historial'] : ($GLOBALS['test_historial'] ?? []);

if (empty($mensaje)) {
    echo json_encode(['success' => false, 'error' => 'Mensaje vacío.']);
    exit;
}

// --------------------------------------------------------------------
// 1. OBTENER CONFIGURACIONES DE IA
// --------------------------------------------------------------------
$pdo = getDB();
$apiKeyGemini = '';
$iaActiva = 'true';

try {
    $stmtCfg = $pdo->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('ASISTENTE_IA_ACTIVO', 'GEMINI_API_KEY')");
    while ($row = $stmtCfg->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Clave'] === 'ASISTENTE_IA_ACTIVO') $iaActiva = $row['Valor'];
        if ($row['Clave'] === 'GEMINI_API_KEY') $apiKeyGemini = trim($row['Valor']);
    }
} catch (Exception $e) {
    // Continuar con valores por defecto
}

if ($iaActiva === 'false') {
    echo json_encode([
        'success' => true,
        'respuesta' => 'El asistente de IA se encuentra temporalmente desactivado por el administrador.',
        'fuente' => 'sistema'
    ]);
    exit;
}

// --------------------------------------------------------------------
// 2. DETECCIÓN DE PREGUNTAS TOTALMENTE AJENAS AL SISTEMA (GUARDRAILS)
// --------------------------------------------------------------------
$mensajeLower = mb_strtolower($mensaje, 'UTF-8');

$esPreguntaExterna = false;
$temasProhibidos = [
    'receta', 'cocinar', 'ingredientes para torta', 'política', 'presidente', 'elecciones', 
    'clima hoy', 'temperatura', 'chiste', 'fútbol', 'mundial', 'película', 'serie',
    'quién eres en la vida real', 'cuéntame un cuento', 'poema', 'capital de', 'religión',
    'horóscopo', 'zodiaco', 'matemática cuántica', 'código python', 'hackear', 'programar en c'
];

foreach ($temasProhibidos as $tp) {
    if (str_contains($mensajeLower, $tp)) {
        $esPreguntaExterna = true;
        break;
    }
}

// Palabras clave que confirman que la consulta sí tiene relación con el sistema
$palabrasClaveSistema = [
    'pos', 'caja', 'turno', 'venta', 'vender', 'vend', 'cobrar', 'cobr', 'cuánto', 'cuanto', 'efectivo', 'tarjeta', 'transferencia',
    'fiado', 'crédito', 'puntos', 'vuelto', 'producto', 'código', 'barra', 'ean', 'plu', 'balanza',
    'pesable', 'kardex', 'stock', 'inventario', 'merma', 'pérdida', 'vencimiento', 'ajuste',
    'compra', 'factura', 'guía', 'proveedor', 'cliente', 'devolución', 'anular', 'vale',
    'reporte', 'utilidad', 'margen', 'costo', 'ganancia', 'cierre', 'arqueo', 'z', 'gaveta',
    'impresora', 'ticket', 'precio', 'promoción', 'oferta', 'pack', 'descuento', 'pausar',
    'pendiente', 'usuario', 'clave', 'rol', 'sueldo', 'gasto', 'servicio', 'online', 'web', 'sii',
    'boleta', 'cómo', 'donde', 'ayuda', 'manual', 'a4', 'pliego', 'hoja', 'etiqueta', 'logo',
    'dinero', 'plata', 'total'
];

$coincideSistema = false;
foreach ($palabrasClaveSistema as $pcs) {
    if (str_contains($mensajeLower, $pcs)) {
        $coincideSistema = true;
        break;
    }
}

if ($esPreguntaExterna && !$coincideSistema) {
    $respGuardrail = "👋 Hola **$userNombre**, soy el **Asistente Oficial de Minimarket POS**.\n\n"
        . "Mi función es **exclusivamente guiarte en el uso y operación de este sistema** (ventas, cobros, turnos de caja, inventario, mermas, kardex, compras, clientes, reportes y códigos A4).\n\n"
        . "📌 *No puedo responder sobre temas externos como recetas, noticias o entretenimiento.*\n\n"
        . "¿En qué función o procedimiento de la tienda necesitas ayuda?";
    
    echo json_encode([
        'success' => true,
        'respuesta' => $respGuardrail,
        'fuente' => 'guardrail'
    ]);
    exit;
}

// --------------------------------------------------------------------
// 2.1 OBTENER MÉTRICAS DEL DÍA EN TIEMPO REAL (Contexto Operativo)
// --------------------------------------------------------------------
$kpiVentasHoy = ['total_ventas' => 0, 'total_monto' => 0];
$mediosPagoHoy = [];
$turnoUsuario = null;
$alertasStockCount = 0;

try {
    // Total ventas completadas hoy
    $stmtVHoy = $pdo->query("
        SELECT COUNT(*) as total_ventas, COALESCE(SUM(MontoTotal), 0) as total_monto 
        FROM ventas 
        WHERE DATE(FechaVenta) = CURRENT_DATE() AND Estado = 'Completada'
    ");
    if ($stmtVHoy) {
        $kpiVentasHoy = $stmtVHoy->fetch(PDO::FETCH_ASSOC) ?: $kpiVentasHoy;
    }

    // Desglose medios de pago de hoy
    $stmtPagos = $pdo->query("
        SELECT pv.MetodoPago, COALESCE(SUM(pv.Monto), 0) as total 
        FROM pagosventa pv 
        JOIN ventas v ON pv.VentaID = v.VentaID 
        WHERE DATE(v.FechaVenta) = CURRENT_DATE() AND v.Estado = 'Completada' 
        GROUP BY pv.MetodoPago
    ");
    if ($stmtPagos) {
        $mediosPagoHoy = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    }

    // Turno actual del usuario
    $userId = (int)($user['id'] ?? $user['UsuarioID'] ?? 0);
    $stmtTu = $pdo->prepare("
        SELECT TurnoID, FechaApertura, MontoApertura, Estado 
        FROM turnos 
        WHERE UsuarioID = :uid AND Estado = 'Abierto' 
        ORDER BY TurnoID DESC LIMIT 1
    ");
    $stmtTu->execute([':uid' => $userId]);
    $turnoUsuario = $stmtTu->fetch(PDO::FETCH_ASSOC) ?: null;

    // Alertas de stock bajo
    $stmtStock = $pdo->query("SELECT COUNT(*) FROM productos WHERE Stock <= StockMinimo AND Activo = TRUE");
    $alertasStockCount = $stmtStock ? (int)$stmtStock->fetchColumn() : 0;
} catch (Exception $e) {
    // Continuar con valores por defecto
}

$contextoEnVivo = [
    'fecha_hoy' => date('d/m/Y'),
    'ventas_monto' => (float)$kpiVentasHoy['total_monto'],
    'ventas_cant' => (int)$kpiVentasHoy['total_ventas'],
    'medios_pago' => $mediosPagoHoy,
    'turno' => $turnoUsuario,
    'alertas_stock' => $alertasStockCount
];

// --------------------------------------------------------------------
// 3. INTENTO DE RESPUESTA MEDIANTE CLOUD LLM (GEMINI) SI ESTÁ CONFIGURADO
// --------------------------------------------------------------------
if (!empty($apiKeyGemini) && (str_starts_with($apiKeyGemini, 'AQ.') || str_starts_with($apiKeyGemini, 'AIza')) && strlen($apiKeyGemini) >= 30) {
    $respuestaGemini = consultarGemini($mensaje, $apiKeyGemini, $userRol, $userNombre, $contextoEnVivo, $historial);
    if ($respuestaGemini !== null) {
        echo json_encode([
            'success' => true,
            'respuesta' => $respuestaGemini,
            'fuente' => 'gemini'
        ]);
        exit;
    }
}

// --------------------------------------------------------------------
// 4. MOTOR EXPERTO DE INTENCIONES LOCAL (OFFLINE / 100% DISPONIBLE)
// --------------------------------------------------------------------
$respuestaLocal = resolverConocimientoLocal($mensajeLower, $userRol, $userNombre, $contextoEnVivo);

echo json_encode([
    'success' => true,
    'respuesta' => $respuestaLocal,
    'fuente' => 'motor_local'
]);
exit;


// ====================================================================
// FUNCIONES AUXILIARES DEL MOTOR LOCAL Y GEMINI
// ====================================================================

/**
 * Motor semántico de resolución local con manual completo y tratamiento riguroso de funciones ausentes
 */
function resolverConocimientoLocal($q, $rol, $nombre, $contextoEnVivo = []) {
    
    // ----------------------------------------------------------------
    // SECCIÓN 0: SALUDOS Y SOLICITUD DE AYUDA GENERAL
    // ----------------------------------------------------------------
    if (preg_match('/^(hola|buenos\s*d[ií]as|buenas\s*tardes|buenas\s*noches|qu[eé]\s*haces|qui[eé]n\s*eres|ayuda|manual|qu[eé]\s*puedes\s*hacer|men[uú]|opciones|inicio)\b/ui', $q) || $q === 'hola' || $q === 'ayuda') {
        return "👋 Hola **$nombre**, soy el Asistente Operativo de **Minimarket POS v4.9.0**.\n\n"
            . "Puedo orientarte paso a paso sobre cómo utilizar cualquiera de las funciones del sistema:\n\n"
            . "🔹 **Ventas y Caja POS:** Cobros (<kbd>F12</kbd>), pago mixto, fiados con cupo, puntos, balanza (PLU), pausar ventas.\n"
            . "🔹 **Turnos y Arqueo:** Fondo de apertura, retiros e ingresos de efectivo, arqueo ciego y Cierre Z.\n"
            . "🔹 **Impresión en Hoja A4:** Pliegos de stickers recortables, pliego mixto de internos y carta de mostrador.\n"
            . "🔹 **Inventario y Kardex:** Catálogo, códigos EAN-8, toma de inventario, stock inicial y bajas por merma.\n"
            . "🔹 **Cotizaciones:** Presupuestos previos, ver detalle en pantalla y transferir 'A la caja'.\n"
            . "🔹 **Compras y Proveedores:** Recepción de facturas en 2 fases y actualización de márgenes.\n"
            . "🔹 **Clientes y Créditos:** Cartola de clientes, abonos con algoritmo FIFO y puntos de fidelización.\n"
            . "🔹 **Reportes:** Utilidades sobre costo, categorías en acordeón y Utilidad Real Neta.\n\n"
            . "💡 *Escribe tu duda sobre cualquier procedimiento (por ejemplo: '¿cómo abrir caja?', '¿cómo registrar merma?', '¿cómo imprimir pliego A4?') y te guiaré de inmediato.*";
    }

    // ----------------------------------------------------------------
    // SECCIÓN 1: FUNCIONES NO DISPONIBLES EN EL SISTEMA (Honestidad y Alternativas)
    // ----------------------------------------------------------------

    // 1.1 WhatsApp y envío por email o PDF en cotizaciones / ventas
    if (str_contains($q, 'wsp') || str_contains($q, 'whatsapp') || str_contains($q, 'enviar por correo') || str_contains($q, 'mandar por correo') || str_contains($q, 'enviar pdf') || str_contains($q, 'descargar pdf cotizacion')) {
        return "⚠️ **Esta función NO está disponible:** El sistema actualmente **NO** cuenta con botón de envío directo por WhatsApp, ni envío por correo electrónico, ni exportador a archivo PDF.\n\n"
            . "💡 **¿Cómo se utiliza en la práctica?**\n"
            . "1. En **Operaciones → Cotizaciones**, al guardar una cotización puedes presionar el ícono de **Ojo (Ver detalle)** para revisar en pantalla el total y los artículos acordados.\n"
            . "2. Puedes comunicar ese monto directamente a tu cliente (por mensaje manual o llamada).\n"
            . "3. Cuando el cliente venga a pagar o retirar, presiona el botón azul **'A la caja'** en el listado para transferir todos los productos al POS con 1 solo clic y cobrar con <kbd>F12</kbd>.";
    }

    // 1.2 Sueldos, nóminas o pago de servicios (luz, agua, arriendo, imposiciones)
    if (str_contains($q, 'sueldo') || str_contains($q, 'pago de luz') || str_contains($q, 'pago de agua') || str_contains($q, 'arriendo') || str_contains($q, 'pagar empleado') || str_contains($q, 'nómina') || str_contains($q, 'imposiciones') || str_contains($q, 'previred')) {
        return "⚠️ **Esta función NO está disponible:** El sistema no cuenta con un módulo de remuneraciones, recursos humanos ni pago de servicios básicos.\n\n"
            . "💡 **Alternativa práctica recomendada:**\n"
            . "Para que el dinero físico de la gaveta no quede descuadrado al entregar el turno:\n"
            . "1. En la barra superior del **POS** o en **Turnos / Arqueo**, presiona el botón **'Retiro / Ingreso'**.\n"
            . "2. Selecciona **Tipo: Retiro de Efectivo**.\n"
            . "3. Ingresa el **Monto retirado** y detalla el motivo exacto (ej. *'Pago de luz del local'*, *'Adelanto sueldo cajero'*).\n"
            . "4. Presiona **Registrar**.\n\n"
            . "✅ De esta forma, la salida de dinero queda justificada en el reporte de turno y el arqueo cuadra sin faltantes.";
    }

    // 1.3 Tienda online, e-commerce, sincronización con página web o delivery
    if (str_contains($q, 'online') || str_contains($q, 'página web') || str_contains($q, 'ecommerce') || str_contains($q, 'mercadolibre') || str_contains($q, 'pedidos ya') || str_contains($q, 'uber eats') || str_contains($q, 'tienda virtual') || str_contains($q, 'shopify')) {
        return "⚠️ **Esta función NO está disponible:** El sistema está diseñado exclusivamente para la venta presencial en mostrador físico y **no incluye sincronización con tiendas online externas ni plataformas de delivery**.\n\n"
            . "💡 **Alternativa práctica:**\n"
            . "Si recibes pedidos por teléfono o redes sociales que necesitas apartar antes de cobrar:\n"
            . "1. Ve a **Operaciones → Cotizaciones**.\n"
            . "2. Registra los artículos acordados con el cliente.\n"
            . "3. Al momento de la entrega, presiona **'A la caja'** para cargarlo de inmediato al carrito del POS.";
    }

    // 1.4 Envíos masivos de correo o newsletters a clientes
    if (str_contains($q, 'correo masivo') || str_contains($q, 'email marketing') || str_contains($q, 'newsletter') || str_contains($q, 'correos masivos')) {
        return "⚠️ **Esta función NO está disponible:** El sistema no cuenta con herramientas de marketing ni envío masivo de correos electrónicos.\n\n"
            . "💡 **Alternativa:**\n"
            . "En **Maestros → Clientes y Créditos** dispones del listado de tus clientes registrados con sus números de teléfono, historial de compra y puntos acumulados para contactarlos directamente.";
    }

    // 1.5 Criptomonedas y pagos con códigos QR externos (MercadoPago / Bitcoin)
    if (str_contains($q, 'bitcoin') || str_contains($q, 'cripto') || str_contains($q, 'usdt') || str_contains($q, 'qr mercadopago') || str_contains($q, 'pagar con qr')) {
        return "⚠️ **Esta función NO está disponible:** El sistema no admite pagos en criptomonedas ni códigos QR de billeteras virtuales externas.\n\n"
            . "💡 **Formas de pago aceptadas en el POS (<kbd>F12</kbd>):**\n"
            . "1. 💵 **Efectivo** (con cálculo de vuelto).\n"
            . "2. 💳 **Tarjeta** (POS Transbank / Redelcom / Débito / Crédito).\n"
            . "3. 🏦 **Transferencia Bancaria**.\n"
            . "4. 🤝 **Fiado (Crédito Interno)** con límite de cupo.\n"
            . "5. ⭐ **Puntos de Fidelización** canjeables.\n"
            . "6. 🔀 **Pago Mixto** combinando dos o más de las anteriores.";
    }

    // 1.6 Facturación de exportación o aduanas
    if (str_contains($q, 'exportación') || str_contains($q, 'aduana') || str_contains($q, 'comercio exterior') || str_contains($q, 'factura de exportacion')) {
        return "⚠️ **Esta función NO está disponible:** El sistema está adaptado para el comercio minorista nacional chileno (Boletas y Facturas electrónicas del SII o comprobantes internos locales) y **no emite documentos aduaneros ni de exportación**.";
    }

    // 1.7 Préstamos financieros o créditos bancarios con cuotas e interés
    if (str_contains($q, 'préstamo') || str_contains($q, 'prestar plata') || str_contains($q, 'cuotas con interés') || str_contains($q, 'tasa de interés')) {
        return "⚠️ **Esta función NO está disponible:** El sistema no incluye un módulo financiero de préstamos ni cálculo de intereses bancarios.\n\n"
            . "💡 Si otorgas crédito comercial simple de la tienda (fiado), utilízalo desde **Maestros → Clientes y Créditos** fijando un cupo máximo en pesos sin intereses.";
    }

    // 1.8 Contabilidad general completa (Libro Mayor, Balance de 8 columnas, F29)
    if (str_contains($q, 'libro mayor') || str_contains($q, 'balance de 8 columnas') || str_contains($q, 'asiento contable') || str_contains($q, 'partida doble') || str_contains($q, 'f29')) {
        return "⚠️ **Esta función NO está disponible:** Minimarket POS es un software de punto de venta, control de inventario y utilidades comerciales, **no es un ERP contable de partida doble**.\n\n"
            . "💡 Para la declaración de impuestos y balance con tu contador, puedes exportar a Excel las ventas en **Operaciones → Ventas**, los **Cierres Z** en Reportes Z y el **Inventario Valorizado**.";
    }

    // 1.9 Biometría o control por huella digital
    if (str_contains($q, 'huella digital') || str_contains($q, 'biometrico') || str_contains($q, 'biométrico') || str_contains($q, 'reconocimiento facial')) {
        return "⚠️ **Esta función NO está disponible:** El sistema no cuenta con integración para lectores biométricos o huellas dactilares. El acceso y las autorizaciones operan mediante usuario, contraseña y clave PIN de supervisor.";
    }

    // 1.10 Comandas de restaurant o gestión de mesas
    if (str_contains($q, 'mesa') || str_contains($q, 'comanda') || str_contains($q, 'mozo') || str_contains($q, 'garzon') || str_contains($q, 'restaurant')) {
        return "⚠️ **Esta función NO está disponible:** El sistema está especializado en minimarkets, almacenes, panaderías y comercio minorista de mostrador, **no cuenta con mapa de mesas de restaurant ni comanderas de cocina**.";
    }


    // ----------------------------------------------------------------
    // SECCIÓN 2: FUNCIONES REALES DEL SISTEMA (Guía Paso a Paso)
    // ----------------------------------------------------------------

    // 2.0 Ventas del Día / ¿Cuánto vendí hoy? / ¿Cómo sé cuánto vendí?
    if (preg_match('/(cu[aá]nto\s*(se\s*)?(ha\s*)?(he\s*)?vend[ií]|ventas?\s*(de|del)?\s*hoy|ventas?\s*del\s*d[ií]a|total\s*vendido|lo\s*que\s*vend[ií]|c[oó]mo\s*s[eé]\s*cu[aá]nto\s*(se\s*)?vend[ií]|saber\s*cu[aá]nto\s*(se\s*)?vend[ií]|cu[aá]nto\s*llevo\s*vendido|cu[aá]nto\s*va\s*vendido)/ui', $q)) {
        $montoFmt = '$' . number_format($contextoEnVivo['ventas_monto'] ?? 0, 0, ',', '.');
        $cantVentas = (int)($contextoEnVivo['ventas_cant'] ?? 0);
        
        $desglose = "";
        if (!empty($contextoEnVivo['medios_pago'])) {
            $pItems = [];
            foreach ($contextoEnVivo['medios_pago'] as $mp) {
                $pItems[] = "• **{$mp['MetodoPago']}:** $" . number_format($mp['total'], 0, ',', '.');
            }
            $desglose = "\n\n💳 **Desglose por método de pago hoy:**\n" . implode("\n", $pItems);
        }
        
        return "📊 **Ventas de hoy (" . ($contextoEnVivo['fecha_hoy'] ?? date('d/m/Y')) . "):**\n\n"
            . "Actualmente llevas recaudado **$montoFmt CLP** en **$cantVentas venta(s) completada(s)**."
            . $desglose . "\n\n"
            . "🔍 **Dónde revisarlo en el sistema:**\n\n"
            . "• **En el Inicio:** En la tarjeta principal **'Ventas del Día'** arriba a la izquierda lo ves al instante en tiempo real.\n"
            . "• **En Caja y Ventas → Ventas y Comprobantes:** Si quieres revisar cada boleta, factura o ticket emitido hoy con su detalle de productos y cajero.\n"
            . "• **En Reportes → Ventas y Medios de Pago:** Presiona el botón rápido **'Hoy'** para ver gráficos de venta por hora y desglose por tarjetas o efectivo.\n"
            . "• **En Turnos y Arqueo:** Si solo deseas revisar lo acumulado en tu turno actual en esta caja registradora.";
    }

    // 2.1 Apertura de Gaveta de Dinero sin venta
    if (preg_match('/(gaveta|caj[oó]n)/ui', $q) && !str_contains($q, 'cerrar') && !str_contains($q, 'arqueo')) {
        return "💵 **Apertura de Gaveta de Dinero:**\n\n"
            . "La gaveta se abre automáticamente al cobrar en efectivo.\n\n"
            . "**Para abrirla manualmente sin hacer una venta (ej. dar cambio):**\n"
            . "Presiona el atajo de teclado <kbd>Ctrl + F10</kbd> en la pantalla de Caja POS.\n"
            . "*(Requiere que la impresora térmica esté conectada en Configuración → Impresora).*";
    }

    // 2.2 Abrir Caja y Turnos
    if ((preg_match('/(abr|apertur|inici)/ui', $q) && (str_contains($q, 'caja') || str_contains($q, 'turno') || str_contains($q, 'dia') || str_contains($q, 'día'))) || str_contains($q, 'fondo fijo') || str_contains($q, 'abrir caja')) {
        return "🟢 **¿Cómo abrir tu turno de caja registradora?**\n\n"
            . "1. Ve al menú superior y selecciona **Turnos / Arqueo** (`caja.php`).\n"
            . "2. Si hay más de una caja física configurada, selecciona tu **Caja Asignada**.\n"
            . "3. Ingresa el **Monto de Apertura** (efectivo inicial de cambio para la gaveta).\n"
            . "4. Presiona el botón verde **'Abrir Caja Registradora'**.\n\n"
            . "🚀 **Listo:** El sistema te redirigirá automáticamente a la pantalla de **Caja POS**, listo para escanear y vender.";
    }

    // 2.3 Cierre Z Fiscal y Reportes Z
    if (str_contains($q, 'cierre z') || str_contains($q, 'reporte z') || str_contains($q, 'reportes z') || str_contains($q, 'historial z')) {
        return "🧾 **¿Dónde ver y cómo funciona el Cierre Z Fiscal?**\n\n"
            . "El Cierre Z consolida las ventas y recaudación por caja con correlativo oficial:\n\n"
            . "1. 📊 **Para consultar el Historial de Cierres Z:**\n"
            . "   * Ve al menú superior **Reportes → Cierre Z Fiscal** (`reportes_z.php`).\n"
            . "   * Encontrarás la tabla con cada Z emitido (#Z correlativo, fecha, caja, supervisor, boletas/facturas, monto neto, IVA 19% y total acumulado).\n"
            . "   * También puedes presionar el botón **'Generar Cierre Z Diario'** para emitir un cierre consolidado de forma manual.\n\n"
            . "2. 🔒 **Para emitirlo al cerrar tu caja registradora:**\n"
            . "   * En **Caja y Ventas → Turnos y Arqueo** (`caja.php`), haz el conteo físico de dinero (arqueo ciego).\n"
            . "   * Asegúrate de mantener marcada la casilla **'Generar Cierre Z de esta caja también'** antes de presionar *Cerrar Turno y Caja*.";
    }

    // 2.4 Cerrar Caja y Arqueo Ciego
    if ((preg_match('/(cerr|cierr|arque|cuadr)/ui', $q) && (str_contains($q, 'caja') || str_contains($q, 'turno'))) || str_contains($q, 'sobrante') || str_contains($q, 'faltante') || str_contains($q, 'arqueo ciego')) {
        return "🔒 **¿Cómo cerrar tu turno de caja (Arqueo Ciego)?**\n\n"
            . "1. Ve a **Turnos / Arqueo** en el menú superior.\n"
            . "2. El sistema utiliza **Arqueo Ciego** (los montos del sistema están ocultos para asegurar un conteo honesto).\n"
            . "3. Cuenta físicamente tu dinero e ingresa los montos reales en:\n"
            . "   * **Efectivo Real Contado** ($)\n"
            . "   * **Comprobantes Tarjeta Transbank / POS** ($)\n"
            . "   * **Transferencias Bancarias** ($)\n"
            . "4. Si eres Cajero, solicita la **Clave de Supervisor** para validar el cierre.\n"
            . "5. Mantén marcado *'Generar Cierre Z de esta caja'* si es el último turno de la jornada.\n"
            . "6. Presiona **'Cerrar Turno y Caja'**.\n\n"
            . "📊 El sistema generará el informe de cuadratura indicando si hubo diferencia exacta, sobrante o faltante.";
    }

    // 2.5 Retiros e Ingresos de Efectivo en Caja
    if (str_contains($q, 'retiro') || str_contains($q, 'ingreso de dinero') || str_contains($q, 'sacar plata') || str_contains($q, 'meter sencillo') || str_contains($q, 'cambio')) {
        return "💵 **Retiros e Ingresos de Efectivo en Caja:**\n\n"
            . "Sirve para justificar movimientos de dinero sin necesidad de realizar una venta:\n\n"
            . "1. En la barra superior del **POS** o en **Turnos / Arqueo**, presiona el botón **'Retiro / Ingreso'**.\n"
            . "2. Selecciona la opción deseada:\n"
            . "   * **Retiro:** Para salidas de dinero (pagos a proveedores, adelantos, compras imprevistas).\n"
            . "   * **Ingreso:** Para entradas de dinero (ingreso de más cambio/sencillo a la gaveta).\n"
            . "3. Digita el monto y escribe el motivo obligatorio.\n"
            . "4. Presiona **Registrar Movimiento**.\n\n"
            . "✅ El arqueo de cierre tomará en cuenta este movimiento automáticamente.";
    }

    // 2.6 Devoluciones y Anulación de Ventas
    if (preg_match('/(anul(ar|o|é|aste|aci[oó]n)?|cancelar\s+venta|devoluci[oó]n|reembolso)/ui', $q)) {
        return "↩️ **Devoluciones y Anulación de Ventas:**\n\n"
            . "**Para anular una venta completa:**\n"
            . "1. Ve al menú superior **Caja y Ventas → Ventas y Comprobantes** (`ventas.php`).\n"
            . "2. Localiza la boleta, factura o ticket (puedes buscar por folio, fecha o cliente).\n"
            . "3. Presiona el botón rojo **'Anular'** en la fila de la venta.\n"
            . "4. Ingresa el motivo de anulación y la **Clave PIN de Supervisor**.\n"
            . "5. Al confirmar, todo el stock (incluyendo packs) se devuelve automáticamente al inventario y queda registrado en el Kardex.\n\n"
            . "**Para devolución parcial de artículos:**\n"
            . "1. Ve a **Caja y Ventas → Devoluciones y Vales** (`devoluciones.php`).\n"
            . "2. Ingresa el número de comprobante y selecciona solo los artículos devueltos para emitir un vale con saldo a favor o devolver el dinero.";
    }

    // 2.7 Pausar y Retomar Ventas en el POS
    if (preg_match('/(paus(ar|o|ada|adita)?|retom(ar|o)?|pendiente|en\s+espera)/ui', $q)) {
        return "⏸️ **Pausar y Retomar Ventas en el POS:**\n\n"
            . "1. Si un cliente fue a buscar dinero o un artículo adicional, presiona el botón **'Pausar'** en el carrito.\n"
            . "2. Escribe una nota de referencia rápida (ej. *'Señora abrigo rojo'*).\n"
            . "3. El carrito se vaciará de inmediato para atender al siguiente cliente en la fila.\n"
            . "4. Para recuperarla, presiona el botón **'Pendientes'** arriba en el POS, selecciona la venta y presiona **'Restaurar'**.";
    }

    // 2.8 Historial de Ventas, Comprobantes y Reimpresión
    if (preg_match('/(reimprim[a-z]*|historial\s+(de\s+)?ventas?|buscar\s+(boleta|factura|ticket|venta)|ver\s+(boleta|factura|comprobante|detalle\s+de\s+venta))/ui', $q)) {
        return "🧾 **Historial de Ventas y Comprobantes (`ventas.php`):**\n\n"
            . "1. Ve a **Caja y Ventas → Ventas y Comprobantes** en el menú superior.\n"
            . "2. Por defecto muestra todas las ventas emitidas hoy. Puedes filtrar por rango de fechas o buscar por folio, RUT o cliente.\n"
            . "3. **Ver Detalle:** Presiona el ícono del **Ojo** para ver la lista de productos y medios de pago utilizados.\n"
            . "4. **Reimprimir:** Presiona el botón de **Impresora** en la fila para reimprimir el comprobante térmico o ticket.";
    }

    // 2.9 Imprimir / Sacar Boleta o Ticket al cobrar
    if (preg_match('/(imprim[a-z]*|sacar|emitir|dar)\s+(la\s+|el\s+|un\s+|una\s+)?(boleta|ticket|factura|comprobante)/ui', $q)) {
        return "🖨️ **¿Cómo imprimir boleta o ticket de venta?**\n\n"
            . "1. En la pantalla de **Caja POS**, al tener los artículos cargados, presiona **COBRAR (<kbd>F12</kbd>)**.\n"
            . "2. Selecciona el medio de pago (Efectivo, Tarjeta, etc.).\n"
            . "3. Presiona el botón verde **'CONFIRMAR E IMPRIMIR'**.\n"
            . "4. La impresora térmica emitirá el comprobante físico de inmediato y abrirá la gaveta si pagaron en efectivo.\n\n"
            . "💡 *Si necesitas reimprimir un ticket anterior, ve a **Caja y Ventas → Ventas y Comprobantes** y presiona el ícono de impresora en la venta deseada.*";
    }

    // 2.10 Cobro con Tarjeta / Transbank / Redelcom / Débito / Crédito
    if (preg_match('/(tarjeta|transbank|redelcom|d[eé]bito|cr[eé]dito\s+bancario|posbank|voucher|v[aá]ucher)/ui', $q) && !str_contains($q, 'fiado')) {
        return "💳 **¿Cómo cobrar con Tarjeta en Caja POS?**\n\n"
            . "1. Carga los productos en el carrito del POS y presiona **COBRAR (<kbd>F12</kbd>)**.\n"
            . "2. En los métodos de pago disponibles, haz clic en la tarjeta **'Tarjeta'** (o Transferencia).\n"
            . "3. Pasa la tarjeta en tu máquina física POS (Transbank, Redelcom o MercadoPago) por el monto total indicado en pantalla.\n"
            . "4. Una vez aprobado el pago en tu terminal físico, presiona **'CONFIRMAR E IMPRIMIR'** en el sistema.\n"
            . "5. El sistema registrará el medio de pago electrónico y no descontará efectivo de la gaveta.\n\n"
            . "💡 *Si el cliente paga una parte con tarjeta y otra en efectivo, usa la opción **'Pago Mixto'**.*";
    }

    // 2.11 Pago Mixto (Efectivo + Tarjeta + Fiado)
    if (str_contains($q, 'pago mixto') || str_contains($q, 'tarjeta y efectivo') || str_contains($q, 'efectivo y tarjeta') || str_contains($q, 'dividir pago') || str_contains($q, 'dividir el monto') || str_contains($q, 'parte efectivo') || str_contains($q, 'dos formas de pago') || (str_contains($q, 'mixto') && (str_contains($q, 'pago') || str_contains($q, 'cobr')))) {
        return "🔀 **¿Cómo usar Pago Mixto en el cobro?**\n\n"
            . "1. Al presionar **COBRAR (<kbd>F12</kbd>)**, haz clic en la tarjeta **'Pago Mixto'**.\n"
            . "2. Se desplegarán los campos para ingresar los montos distribuidos:\n"
            . "   * Efectivo ($)\n"
            . "   * Tarjeta ($)\n"
            . "   * Transferencia ($)\n"
            . "   * Fiado / Crédito ($) (si el cliente tiene cupo)\n"
            . "   * Puntos canjeados\n"
            . "3. El recuadro inferior indicará en tiempo real si **'Falta \$X'** o si el efectivo entregado genera vuelto.\n"
            . "4. Cuando la suma cubra el total, presiona **Confirmar Venta**.";
    }

    // 2.12 Fiado y Crédito a Clientes
    if (str_contains($q, 'fiado') || str_contains($q, 'credito') || str_contains($q, 'crédito') || str_contains($q, 'abono') || str_contains($q, 'deuda cliente') || str_contains($q, 'cartola')) {
        return "🤝 **Gestión de Fiados y Crédito Interno:**\n\n"
            . "**Para vender a Fiado:**\n"
            . "1. En el POS, selecciona al cliente en el recuadro **'Cliente Asignado'**.\n"
            . "2. Al cobrar (<kbd>F12</kbd>), selecciona **Crédito Interno / Fiado**.\n"
            . "3. El sistema verificará que la compra no exceda su límite de cupo configurado.\n\n"
            . "**Para abonar o saldar una deuda:**\n"
            . "1. Ve a **Maestros → Clientes y Créditos** (`clientes.php`).\n"
            . "2. Busca al cliente y presiona **'Ver Cuenta / Abonar'**.\n"
            . "3. Ingresa el monto abonado en efectivo o transferencia. El sistema aplica el abono con criterio **FIFO** (pagando las compras más antiguas primero).";
    }

    // 2.13 Descuentos en Caja y Políticas de Supervisión
    if (str_contains($q, 'descuento') || str_contains($q, 'rebaja') || str_contains($q, 'clave supervisor') || str_contains($q, 'permiso cajero') || str_contains($q, 'autorización')) {
        return "🛡️ **Descuentos y Políticas de Supervisión en Caja:**\n\n"
            . "1. En el POS se pueden aplicar descuentos en porcentaje (%) o monto fijo ($).\n"
            . "2. En **Configuración → Políticas de Supervisión**, el administrador define:\n"
            . "   * Si se exige clave de supervisor al vaciar/cancelar un carrito.\n"
            . "   * Si se exige clave al eliminar un producto agregado.\n"
            . "   * El **Descuento Máximo permitido** a cajeros sin supervisor (ej. hasta 5%; si supera ese tope, exige clave PIN de supervisor).";
    }

    // 2.14 Crear / Agregar / Modificar Productos en el Catálogo
    if (preg_match('/(crear|agregar|nuevo|a[ñn]adir|alta|registrar|modificar|editar)\s+(un\s+)?(producto|art[ií]culo|mercader[ií]a)/ui', $q) || preg_match('/^(nuevo\s+producto|crear\s+producto|cat[aá]logo)/ui', $q)) {
        return "📦 **¿Cómo crear o agregar un producto al sistema?**\n\n"
            . "1. Ve al menú superior **Mantenedores → Productos y Precios** (`productos.php`).\n"
            . "2. Presiona el botón verde **'Nuevo Producto'** en la parte superior derecha.\n"
            . "3. Completa los datos en la ficha:\n"
            . "   * **Nombre del Producto:** Descripción visible en boletas y pantallas.\n"
            . "   * **Código de Barras:** Pistolea el código del envase o presiona el botón **'⚡ Generar EAN-8'** si es de elaboración propia (pan, empanadas, etc.).\n"
            . "   * **Categoría:** Selecciona la familia a la que pertenece.\n"
            . "   * **Costo de Compra y Precio de Venta:** El sistema calculará el margen comercial.\n"
            . "   * **Stock Inicial y Stock Mínimo:** Para las alertas de reposición.\n"
            . "   * Marca **'Es Pesable'** si se vende por balanza/kilos (puedes generar PLU de 4 dígitos).\n"
            . "4. Presiona **'Guardar Producto'**.\n\n"
            . "✅ El artículo quedará disponible de inmediato en la Caja POS para ser escaneado o buscado.";
    }

    // 2.15 Reportes de Ventas y Estadísticas
    if (preg_match('/reporte(s)?(\s+de)?\s*(venta|caja|ingreso|medios?\s*de\s*pago)/ui', $q) || str_contains($q, 'centro de reportes')) {
        return "📊 **Reportes de Ventas y Estadísticas (`reportes.php`):**\n\n"
            . "1. Ve al menú superior **Reportes → Ventas y Medios de Pago** (`reportes.php?tab=ventas`).\n"
            . "2. Utiliza los botones de período rápido (**Hoy**, **Ayer**, **Esta Semana**, **Este Mes**) o selecciona un rango personalizado de fechas.\n"
            . "3. Muestra gráficos de ventas por hora, total recaudado y desglose por cada medio de pago (Efectivo, Tarjetas, Fiado, etc.).\n"
            . "4. Permite exportar la nómina completa de ventas a formato Excel CSV.";
    }

    // 2.16 Vender y Cobrar en Caja POS (Flujo general de venta)
    if (preg_match('/(vender|venta|cobrar|cobro|como\s+vendo|como\s+cobro|realizar\s+venta|hacer\s+venta)/ui', $q)) {
        return "🛒 **¿Cómo realizar una venta en Caja POS?**\n\n"
            . "1. En la pantalla de **Caja POS**, escanea el código de barras o escribe el nombre del producto en el buscador superior y presiona <kbd>Enter</kbd>.\n"
            . "2. El artículo se agregará al **Carrito de Compra**. Puedes cambiar cantidades con <kbd>+</kbd> o <kbd>-</kbd>.\n"
            . "3. Presiona el botón verde **'COBRAR VENTA'** o la tecla de acceso directo <kbd>F12</kbd>.\n"
            . "4. Elige el medio de pago:\n"
            . "   * 💵 **Efectivo:** Usa los billetes rápidos o escribe lo recibido; calcula el vuelto exacto.\n"
            . "   * 💳 **Tarjeta / Transferencia:** Registra el pago por terminal Transbank o POS.\n"
            . "   * 🤝 **Fiado (Crédito Interno):** Requiere cliente seleccionado con cupo disponible.\n"
            . "   * ⭐ **Puntos:** Canjea puntos acumulados del cliente (1 pt = \$1).\n"
            . "   * 🔀 **Pago Mixto:** Divide el monto combinando varias formas de pago.\n"
            . "5. Presiona **'CONFIRMAR E IMPRIMIR'** para emitir el comprobante y abrir la gaveta.";
    }

    // 2.17 Puntos y Fidelización
    if (str_contains($q, 'punto') || str_contains($q, 'fideliza') || str_contains($q, 'acumular punto') || str_contains($q, 'canjear punto')) {
        return "⭐ **Sistema de Puntos y Fidelización:**\n\n"
            . "1. **Acumulación:** Cada venta asociada a un cliente acumula automáticamente **1 punto por cada \$1.000** de compra.\n"
            . "2. **Equivalencia:** Cada punto equivale a **\$1 CLP** de saldo a favor.\n"
            . "3. **Canje en Caja:** En el POS, al asignar al cliente e ir a cobrar (<kbd>F12</kbd>), aparecerá la opción de Puntos. El cliente puede canjear todos sus puntos o parte de ellos en Pago Mixto.";
    }

    // 2.18 Balanza y Pesables (PLU)
    if (str_contains($q, 'pesable') || str_contains($q, 'balanza') || str_contains($q, 'plu') || str_contains($q, 'kilos') || str_contains($q, 'pesar')) {
        return "⚖️ **Productos Pesables y Balanza:**\n\n"
            . "**En Catálogo (Creación):**\n"
            . "1. En **Maestros → Productos**, marca la casilla **'Es Pesable'**.\n"
            . "2. Presiona **'⚡ Generar PLU'** para asignarle un código de 4 dígitos (ej. `0012` o `1200`), o ingresa el de tu balanza.\n\n"
            . "**En el POS (Venta):**\n"
            . "1. **Balanza con Etiqueta:** Escanea la etiqueta impresa por la balanza (el código EAN-13 incluye el peso o precio).\n"
            . "2. **Balanza sin Etiqueta:** Al digitar el PLU o elegir el producto, se abrirá una ventana para ingresar los **kilos** (calcula el monto) o ingresar el **monto en pesos** (calcula los kilos equivalentes).";
    }

    // 2.19 Impresión de Códigos en Hoja A4
    if (str_contains($q, 'a4') || str_contains($q, 'hoja normal') || str_contains($q, 'pliego') || str_contains($q, 'carta de mostrador') || str_contains($q, 'imprimir codigos a4') || str_contains($q, 'imprimir códigos a4')) {
        return "🖨️ **Impresión de Códigos de Barra en Hoja A4 (`imprimir_codigos_a4.php`):**\n\n"
            . "Módulo especializado para imprimir en hojas estándar de oficina A4:\n\n"
            . "1. **¿Dónde ingresar?**\n"
            . "   * Menú superior **Mantenedores → Imprimir Códigos A4** o botón azul en **Maestros → Productos**.\n"
            . "   * O botón **'Pliego A4'** en el modal de etiqueta de cualquier producto individual.\n\n"
            . "2. **Modos Disponibles:**\n"
            . "   * 🏷️ **Pliego de Etiquetas:** Stickers o recortables (12, 24, 30, 48 o personalizada) de 1 artículo, o activa **'Pliego Mixto'** para imprimir 1 etiqueta de cada código interno del local.\n"
            . "   * 📋 **Carta de Mostrador:** Catálogo escaneable organizado por categorías para tener en el mesón de caja y pistolear artículos pesables o sin código comercial.\n\n"
            . "3. **Opciones:**\n"
            . "   * Columnas: 2, 3 o 4 columnas por hoja.\n"
            . "   * Filtro ⭐ **Solo Internos** para aislar artículos propios/pesables frente a comerciales.\n"
            . "   * Opciones para mostrar precio, nombre del local y líneas de corte punteadas.\n\n"
            . "4. Presiona **'IMPRIMIR HOJA A4'** o <kbd>Ctrl + P</kbd>.";
    }

    // 2.20 Impresión de Etiquetas Individuales (Térmica 50x30mm)
    if (str_contains($q, 'etiqueta individual') || str_contains($q, 'rollo') || str_contains($q, '50x30') || (str_contains($q, 'imprimir') && str_contains($q, 'etiqueta') && !str_contains($q, 'a4'))) {
        return "🏷️ **Impresión de Etiquetas Individuales (Impresora Térmica):**\n\n"
            . "1. Ve a **Maestros → Productos y Precios** (`productos.php`).\n"
            . "2. En la tabla, presiona el botón con ícono de **Código de Barras** en la fila del producto.\n"
            . "3. Se abrirá la ventana modal con la vista previa de la etiqueta individual (50x30mm o continuo de góndola).\n"
            . "4. Puedes elegir si mostrar el precio y presionar **Imprimir Etiqueta**.\n\n"
            . "💡 *Si lo que necesitas es imprimir varios códigos en una hoja de papel normal de oficina, usa el botón 'Pliego A4' o 'Imprimir Códigos A4'.*";
    }

    // 2.21 Códigos EAN-8 Automáticos
    if (str_contains($q, 'ean') || str_contains($q, 'generar ean') || str_contains($q, 'codigo de barra propio') || str_contains($q, 'codigo interno')) {
        return "⚡ **Generación de Códigos EAN-8 Internos:**\n\n"
            . "1. En **Maestros → Productos y Precios**, al crear o editar un producto sin código comercial de fábrica (pan, empanadas, colaciones, miel, etc.), presiona el botón **'⚡ Generar EAN-8'**.\n"
            . "2. El sistema creará un código estándar oficial de 8 dígitos (prefijo 2 + correlativo + dígito verificador Módulo 10).\n"
            . "3. Guarda el producto. Podrás imprimir sus stickers en la impresora térmica o en pliego A4.";
    }

    // 2.22 Múltiples Códigos de Barra por Producto
    if (str_contains($q, 'multiples codigos') || str_contains($q, 'múltiples códigos') || str_contains($q, 'varios codigos') || str_contains($q, 'dos codigos')) {
        return "🔢 **Múltiples Códigos de Barras por Producto:**\n\n"
            . "Permite que un mismo producto pueda ser pistoleado con diferentes códigos (ej. si el fabricante cambió el código de envase):\n\n"
            . "1. Ve a **Maestros → Productos y Precios** y edita el producto.\n"
            . "2. En la sección **Códigos Adicionales**, presiona **'Agregar Código'**.\n"
            . "3. Escanea o escribe el código secundario y guarda.\n"
            . "4. En el POS podrás pistolear cualquiera de los códigos registrados y cargará el mismo producto.";
    }

    // 2.23 Mermas y Pérdidas de Mercadería
    if (str_contains($q, 'merma') || str_contains($q, 'pérdida') || str_contains($q, 'vencido') || str_contains($q, 'dañado') || str_contains($q, 'rotura') || str_contains($q, 'consumo interno')) {
        return "🗑️ **¿Cómo registrar Mermas y Pérdidas de Mercadería?**\n\n"
            . "1. Ve a **Inventario → Ajustes de Stock** y presiona **'Nuevo Ajuste de Stock'**.\n"
            . "2. Selecciona el producto y coloca la cantidad a dar de baja.\n"
            . "3. En **Tipo de Movimiento**, elige **SALIDA**.\n"
            . "4. En **Motivo**, selecciona la causa clasificada:\n"
            . "   * 🛑 **Vencimiento**\n"
            . "   * ⚠️ **Daño / Rotura**\n"
            . "   * 📦 **Pérdida / Descuadre**\n"
            . "   * 🥪 **Consumo Interno**\n"
            . "5. Guarda el ajuste. El sistema congelará el costo de compra histórico.\n\n"
            . "📊 **Impacto Contable:** Audita las pérdidas en **Reportes → Mermas y Pérdidas**. En **Reportes → Utilidades** verás cómo se descuenta automáticamente de la ganancia bruta para reflejar la **Utilidad Real Neta**.";
    }

    // 2.24 Kardex de Inventario y Trazabilidad
    if (str_contains($q, 'kardex') || str_contains($q, 'stock inicial') || str_contains($q, 'historial producto') || str_contains($q, 'movimiento stock')) {
        return "📜 **¿Cómo usar el Kardex de Inventario?**\n\n"
            . "1. Ve a **Inventario → Kardex de Movimientos** (`kardex.php`).\n"
            . "2. **Buscador Predictivo:** Escribe el nombre, código de barra o PLU de cualquier artículo para aislar su historial.\n"
            . "3. **Tarjeta de KPIs:** Muestra **Stock Inicial** (apertura), **Total Entradas (+)**, **Total Salidas (-)** y **Saldo Actual (=)**.\n"
            . "4. **Filtros por Tipo:** Filtra por `STOCK INICIAL`, `VENTAS`, `COMPRAS` o `AJUSTES`.\n"
            . "5. Permite descargar la cartola completa en Excel CSV.";
    }

    // 2.25 Utilidades y Márgenes Comerciales
    if (str_contains($q, 'utilidad') || str_contains($q, 'margen') || str_contains($q, 'rentabilidad') || str_contains($q, 'ganancia')) {
        return "📈 **¿Cómo ver Utilidades y Márgenes de Ganancia?**\n\n"
            . "1. Ve a **Reportes → Utilidades y Márgenes** (`reportes.php?tab=utilidades`).\n"
            . "2. Selecciona el período (Hoy, Ayer, Esta Semana, Este Mes, etc.).\n"
            . "3. **Fórmula Comercial (Mark-up sobre costo):**\n"
            . "   * Margen % = `((Venta - Costo) / Costo) * 100`.\n"
            . "   * Ejemplo: Si compras a \$1.000 y vendes a \$1.300, refleja exactamente **+30%** de ganancia real.\n"
            . "4. **Categorías en Acordeón:** Haz clic sobre cualquier categoría para expandir o colapsar el detalle de sus productos vendidos.\n"
            . "5. **Utilidad Real Neta:** Deduce automáticamente las pérdidas por merma para mostrar la ganancia limpia.";
    }

    // 2.26 Inventario Valorizado
    if (str_contains($q, 'inventario valorizado') || str_contains($q, 'capital inmovilizado') || str_contains($q, 'valor inventario')) {
        return "📦 **Reporte de Inventario Valorizado:**\n\n"
            . "1. Ve a **Reportes → Inventario Valorizado** (`reportes.php?tab=inventario`).\n"
            . "2. Te muestra el capital total invertido al costo y la proyección total a precio de venta.\n"
            . "3. Incluye buscador en vivo, desglose por categoría y alerta para identificar productos sin costo (`S/C`).\n"
            . "4. Permite exportar la nómina completa a Excel CSV.";
    }

    // 2.27 Toma de Inventario Físico
    if (str_contains($q, 'toma de inventario') || str_contains($q, 'inventario físico') || str_contains($q, 'conteo físico') || str_contains($q, 'cuadre de stock')) {
        return "📋 **Toma de Inventario Físico:**\n\n"
            . "1. Ve a **Inventario → Toma de Inventario** (`inventario.php`).\n"
            . "2. Permite realizar el conteo físico de los artículos en bodega o sala.\n"
            . "3. Al ingresar el stock contado real, el sistema calcula las diferencias (sobrantes o faltantes) y genera los ajustes correspondientes de forma masiva.";
    }

    // 2.28 Alertas de Stock Mínimo
    if (str_contains($q, 'alerta stock') || str_contains($q, 'stock mínimo') || str_contains($q, 'stock critico') || str_contains($q, 'agotado')) {
        return "⚠️ **Alertas de Stock Mínimo y Agotados:**\n\n"
            . "1. Ve a **Inventario → Alertas de Stock** (`alertas_stock.php`).\n"
            . "2. El sistema clasifica automáticamente los productos en:\n"
            . "   * 🔴 **Agotados:** Stock en 0 o negativo.\n"
            . "   * 🟡 **Stock Crítico:** Existencia física igual o menor al Stock Mínimo definido en su ficha.\n"
            . "3. Sirve para confeccionar la lista de pedidos a proveedores antes de que se quiebre el stock.";
    }

    // 2.29 Actualizador Rápido de Precios
    if (str_contains($q, 'actualizar precio') || str_contains($q, 'cambiar precio masivo') || str_contains($q, 'subir precio')) {
        return "💲 **Actualizador Rápido de Precios:**\n\n"
            . "1. Ve a **Inventario → Actualizar Precios** (`actualizar_precios.php`).\n"
            . "2. Muestra una grilla editable rápida donde puedes cambiar el costo o precio de venta de varios productos sin tener que entrar a editarlos uno por uno.\n"
            . "3. El margen de ganancia se recalcula automáticamente en vivo mientras escribes.\n"
            . "4. Presiona **Guardar Cambios** para aplicar de inmediato a la caja.";
    }

    // 2.30 Cotizaciones y Notas de Pedido
    if (str_contains($q, 'cotizaci') || str_contains($q, 'presupuesto') || str_contains($q, 'nota de pedido') || str_contains($q, 'apartar')) {
        return "📄 **Cotizaciones y Notas de Pedido:**\n\n"
            . "1. Ve a **Operaciones → Cotizaciones** (`cotizaciones.php`).\n"
            . "2. Presiona **'Nueva cotización'**, elige al cliente y agrega los productos.\n"
            . "3. Presiona **'Guardar cotización'**.\n"
            . "4. **Ver Detalle:** Presiona el ícono del **Ojo** para ver en pantalla el desglose y montos.\n"
            . "5. **Pasar al POS:** Presiona el botón azul **'A la caja'** para transferir todos los productos al carrito de venta listo para cobrar con <kbd>F12</kbd>.\n\n"
            . "*(Nota honesta: El sistema no cuenta con botón para enviar por WhatsApp o PDF directo; la consulta se hace en pantalla o se cobra en caja).*";
    }

    // 2.31 Recepción de Compras de Proveedores
    if (str_contains($q, 'compra') || str_contains($q, 'recepción') || str_contains($q, 'ingresar mercadería') || str_contains($q, 'factura proveedor')) {
        return "📥 **Recepción de Compras de Mercadería:**\n\n"
            . "El módulo opera en **2 Fases:**\n\n"
            . "**Fase 1: Ingreso de Factura / Guía**\n"
            . "1. Ve a **Operaciones → Recepción de Compras** y presiona **'Ingresar Mercadería'**.\n"
            . "2. Selecciona el Proveedor e ingresa el N° de Documento.\n"
            . "3. Agrega cada producto recibido con su cantidad y costo unitario.\n"
            . "4. Presiona **Guardar**. *¡El stock y costo ingresan de inmediato al inventario!*\n\n"
            . "**Fase 2: Ajuste de Precios al Público (Opcional)**\n"
            . "1. El sistema sugiere nuevos precios de venta calculando el margen deseado sobre el nuevo costo.\n"
            . "2. Puedes confirmarlos o presionar *'Mantener Precios Actuales'*.";
    }

    // 2.32 Proveedores
    if (str_contains($q, 'proveedor') || str_contains($q, 'distribuidor')) {
        return "🏢 **Gestión de Proveedores:**\n\n"
            . "1. Ve a **Maestros → Proveedores** (`proveedores.php`).\n"
            . "2. Registra el RUT, Nombre/Razón Social, Teléfono, Contacto y Dirección de cada distribuidor.\n"
            . "3. Al ingresar compras de mercadería, podrás asociar la factura a su ficha correspondiente.";
    }

    // 2.33 Promociones, Packs y Ofertas
    if (str_contains($q, 'promoción') || str_contains($q, 'oferta') || str_contains($q, 'pack') || str_contains($q, 'descuento volumen')) {
        return "🎁 **Ofertas y Promociones Automáticas:**\n\n"
            . "1. Ve a **Maestros → Ofertas y Promociones** (`promociones.php`).\n"
            . "2. Presiona **'Nueva Oferta'** y selecciona el producto:\n"
            . "   * **Descuento Unitario:** Porcentaje rebajado por unidad.\n"
            . "   * **Pack por Volumen:** Precio especial por cantidad (ej. *'3 unidades por \$2.000'*).\n"
            . "3. Fija la fecha de vigencia y guarda.\n"
            . "4. En el **POS**, la oferta se aplicará sola en cuanto el cajero alcance la cantidad requerida en el carrito.";
    }

    // 2.34 Modo Offline del POS
    if (str_contains($q, 'offline') || str_contains($q, 'sin internet') || str_contains($q, 'sin conexión') || str_contains($q, 'se cayó el internet')) {
        return "📶 **Modo Offline del Punto de Venta:**\n\n"
            . "1. Si el local pierde conexión a internet, el POS **continúa operando normalmente** utilizando una base de datos local en el navegador (`IndexedDB`).\n"
            . "2. El indicador superior cambiará a color amarillo/naranja indicando *'Modo Offline'* y el número de ventas pendientes.\n"
            . "3. Al restablecerse la conexión, el sistema sincronizará todas las ventas pendientes de forma automática hacia el servidor sin perder ningún dato.";
    }

    // 2.35 Verificador y Consulta de Precios
    if (str_contains($q, 'consulta de precio') || str_contains($q, 'verificador de precio') || str_contains($q, 'consultar precio')) {
        return "🔍 **Verificador / Consulta Rápida de Precios:**\n\n"
            . "1. Ve a **Operaciones → Consulta de Precios** (`consulta_precio.php`).\n"
            . "2. Es una pantalla limpia y de alta visibilidad pensada para escanear artículos rápidamente con el lector y mostrar en grande el nombre, precio y stock actual.";
    }

    // 2.36 Boleta Electrónica SII (DTE)
    if (str_contains($q, 'sii') || str_contains($q, 'boleta electronica') || str_contains($q, 'boleta electrónica') || str_contains($q, 'dte') || str_contains($q, 'openfactura') || str_contains($q, 'haulmer')) {
        return "🏛️ **Boleta Electrónica SII (DTE):**\n\n"
            . "1. Ve a **Configuración → Boleta Electrónica (DTE)**.\n"
            . "2. Proveedores integrados disponibles:\n"
            . "   * **OpenFactura / Haulmer:** Emisión en tiempo real con firma y envío automático al SII.\n"
            . "   * **Modo Simulador (Mock):** Emite folios de prueba para verificar la operación sin costo.\n"
            . "   * **Comprobante Local (Sin SII):** Emite solo el ticket interno del negocio sin enviar datos al SII.";
    }

    // 2.37 Impresora Térmica Directa
    if (str_contains($q, 'impresora') || str_contains($q, 'ticketera') || str_contains($q, 'posbank') || str_contains($q, 'web serial') || str_contains($q, 'papel térmico')) {
        return "🖨️ **Configuración de Impresora Térmica:**\n\n"
            . "1. Ve a **Configuración → Impresora** (`configuracion.php`).\n"
            . "2. El sistema soporta conexión directa por **Web Serial / USB** (compatible con impresoras POSBANK A11 Prime 80mm y similares) sin necesidad de instalar drivers en Windows.\n"
            . "3. Puedes activar la apertura automática de gaveta y el corte automático de papel al confirmar cada venta.";
    }

    // 2.38 Personalización Visual (Tema Oscuro / Claro y Logo)
    if (str_contains($q, 'logo') || str_contains($q, 'tema oscuro') || str_contains($q, 'tema claro') || str_contains($q, 'color') || str_contains($q, 'apariencia')) {
        return "🎨 **Personalización Visual y Logo:**\n\n"
            . "1. Ve a **Configuración → Apariencia y Marca**.\n"
            . "2. **Modo de Color:** Alterna entre Modo Oscuro (Dark) y Modo Claro (Light).\n"
            . "3. **Color de Acento:** Elige entre Índigo, Verde Minimarket u otros tonos de marca.\n"
            . "4. **Logo de la Empresa:** Sube un archivo PNG/JPG o escribe la URL de tu imagen para que aparezca en el menú superior y comprobantes.";
    }

    // 2.39 Usuarios, Roles y Clave Supervisor
    if (str_contains($q, 'usuario') || str_contains($q, 'crear cajero') || str_contains($q, 'rol') || str_contains($q, 'cambiar clave')) {
        return "👤 **Usuarios y Roles del Sistema:**\n\n"
            . "1. Ve a **Administración → Usuarios y Permisos** (`usuarios.php`).\n"
            . "2. Roles disponibles:\n"
            . "   * **Administrador:** Acceso completo a finanzas, compras, configuración y usuarios.\n"
            . "   * **Supervisor:** Puede autorizar descuentos, cancelaciones de venta y cierres de turno mediante su **Clave PIN de Supervisor**.\n"
            . "   * **Cajero:** Operación del POS y ventas diarias.";
    }

    // 2.40 Categorías de Productos
    if (str_contains($q, 'categoría') || str_contains($q, 'categoria') || str_contains($q, 'familias')) {
        return "📁 **Gestión de Categorías (`categorias.php`):**\n\n"
            . "1. Ve a **Mantenedores → Categorías** en el menú superior.\n"
            . "2. Presiona **'Nueva Categoría'** e ingresa el nombre (ej. *Bebidas*, *Lácteos*, *Panadería*, *Abarrotes*).\n"
            . "3. Sirve para clasificar los artículos del catálogo, organizar la Carta de Mostrador A4 y filtrar reportes de utilidades.";
    }

    // 2.41 Notas de Pedido a Distribuidores
    if (str_contains($q, 'nota de pedido') || str_contains($q, 'notas de pedido') || str_contains($q, 'pedir mercadería') || str_contains($q, 'pedido a distribuidor')) {
        return "📝 **Notas de Pedido (`notaspedido.php`):**\n\n"
            . "1. Ve a **Stock y Compras → Notas de Pedido**.\n"
            . "2. Presiona **'Nueva Nota de Pedido'**, selecciona el proveedor y añade los productos requeridos.\n"
            . "3. Permite hacer seguimiento a pedidos pendientes antes de recibir la factura física en el local.";
    }

    // 2.42 Atajos de Teclado del POS
    if (str_contains($q, 'atajo') || str_contains($q, 'teclado') || str_contains($q, 'teclas') || str_contains($q, 'shortcut') || str_contains($q, 'hotkey')) {
        return "⌨️ **Atajos de Teclado en Caja POS:**\n\n"
            . "* <kbd>F12</kbd>: Abrir ventana modal de Cobro inmediato.\n"
            . "* <kbd>Ctrl + F10</kbd>: Abrir gaveta de dinero sin realizar venta.\n"
            . "* <kbd>F2</kbd> (o clic en buscador): Enfocar la barra de búsqueda de productos.\n"
            . "* <kbd>+</kbd> / <kbd>-</kbd>: Aumentar o disminuir la cantidad del artículo seleccionado.\n"
            . "* <kbd>Supr</kbd> / <kbd>Delete</kbd>: Quitar el producto seleccionado del carrito.\n"
            . "* <kbd>Esc</kbd>: Cerrar cualquier ventana emergente o modal.";
    }

    // 2.43 Diseños y Modos de Pantalla POS (Supermercado / Táctil / Clásico)
    if (str_contains($q, 'layout') || str_contains($q, 'diseño') || str_contains($q, 'pantalla') || str_contains($q, 'modo tactil') || str_contains($q, 'supermercado') || str_contains($q, 'touch')) {
        return "🖥️ **Modos de Pantalla y Diseños del POS:**\n\n"
            . "En la barra superior del POS dispones de 3 modos visuales intercambiables al instante:\n\n"
            . "1. ⚡ **Modo Supermercado:** Diseñado para escaneo continuo y ultra veloz con pistola de código de barras. Muestra lista compacta vertical y totalizador gigante.\n"
            . "2. 📱 **Modo Táctil (Touch):** Botones grandes y tarjetas con fotos táctiles pensadas para pantallas touch screen o venta sin lector de barras.\n"
            . "3. 💻 **Modo Clásico:** Pantalla dividida tradicional con cuadrícula de productos a la izquierda y ticket/carrito a la derecha.";
    }

    // 2.44 Multi-Caja (Gestión de Terminales)
    if (str_contains($q, 'multicaja') || str_contains($q, 'multi-caja') || str_contains($q, 'varias cajas') || str_contains($q, 'caja 1') || str_contains($q, 'caja 2') || str_contains($q, 'crear caja')) {
        return "🖥️ **Gestión Multi-Caja:**\n\n"
            . "1. Ve a **Mantenedores → Cajas Registradoras** (`cajas.php`).\n"
            . "2. Puedes crear nuevas cajas (ej. *'Caja 01 Principal'*, *'Caja 02 Rápida'*).\n"
            . "3. Al abrir turno, el cajero selecciona en qué caja física está operando.\n"
            . "4. El navegador recordará automáticamente la caja elegida en ese equipo (`localStorage`).";
    }

    // ----------------------------------------------------------------
    // SECCIÓN 3: FALLBACK AMIGABLE Y ORIENTADOR
    // ----------------------------------------------------------------
    return "💡 **¿En qué procedimiento de Minimarket POS te puedo orientar?**\n\n"
        . "No reconocí con exactitud tu pregunta. En este sistema puedo guiarte paso a paso con cualquiera de estos temas:\n\n"
        . "• 🛒 **Caja y Ventas:** Cobros con <kbd>F12</kbd>, tarjetas/Transbank, efectivo con vuelto, fiados con cupo, pago mixto, pausar ventas y abrir gaveta (<kbd>Ctrl + F10</kbd>).\n"
        . "• 🔒 **Turnos y Arqueo:** Fondo de apertura, retiros e ingresos de dinero, arqueo ciego y Cierre Z.\n"
        . "• 📦 **Productos e Inventario:** Crear productos, códigos EAN-8, balanza PLU, precios, mermas y Kardex.\n"
        . "• 🧾 **Comprobantes y Anulaciones:** Consultar ventas de hoy, reimprimir tickets y anular boletas.\n"
        . "• 🖨️ **Impresión:** Etiquetas térmicas 50x30mm y Pliegos en hoja normal A4.\n"
        . "• 👥 **Clientes y Compras:** Cuenta de fiados con abonos FIFO, recepción de facturas y proveedores.\n"
        . "• 📊 **Reportes:** Utilidades sobre costo, ranking de ventas e inventario valorizado.\n\n"
        . "👉 *Escribe tu duda directamente (por ejemplo: '¿cómo anular una boleta?', '¿cómo crear un producto?', '¿cómo cobrar con tarjeta?').*";
}

/**
 * Consulta a la API de Gemini (utilizando los modelos activos de Google AI)
 * Con guardrails estrictos de honestidad y verificación instantánea de clave.
 */
function consultarGemini($pregunta, $apiKey, $rol, $nombre, $contextoEnVivo = [], $historial = []) {
    // Si la clave no tiene el formato estándar de Google AI Studio (AQ. o AIza), no ejecutar peticiones lentas
    if (empty($apiKey) || (!str_starts_with($apiKey, 'AQ.') && !str_starts_with($apiKey, 'AIza')) || strlen($apiKey) < 30) {
        return null;
    }

    $modelos = ['gemini-3.5-flash', 'gemini-3.8-flash'];

    $montoCLP = '$' . number_format($contextoEnVivo['ventas_monto'] ?? 0, 0, ',', '.');
    $cantVentas = (int)($contextoEnVivo['ventas_cant'] ?? 0);
    $fechaHoy = $contextoEnVivo['fecha_hoy'] ?? date('d/m/Y');

    $turnoTxt = "Sin turno abierto en este momento";
    if (!empty($contextoEnVivo['turno'])) {
        $horaApertura = date('H:i', strtotime($contextoEnVivo['turno']['FechaApertura']));
        $fondo = '$' . number_format($contextoEnVivo['turno']['MontoApertura'], 0, ',', '.');
        $turnoTxt = "Turno #{$contextoEnVivo['turno']['TurnoID']} ABIERTO desde las {$horaApertura} con fondo inicial de {$fondo}";
    }

    $pagosList = [];
    if (!empty($contextoEnVivo['medios_pago'])) {
        foreach ($contextoEnVivo['medios_pago'] as $mp) {
            $pagosList[] = $mp['MetodoPago'] . ': $' . number_format($mp['total'], 0, ',', '.');
        }
    }
    $pagosTxt = !empty($pagosList) ? (" | Desglose por método de pago: " . implode(', ', $pagosList)) : "";

    $liveDataPrompt = "DATOS EN TIEMPO REAL DEL SISTEMA HOY ({$fechaHoy}):\n"
        . "- Total ventas completadas hoy: {$montoCLP} CLP en {$cantVentas} transacciones{$pagosTxt}.\n"
        . "- Estado de caja de {$nombre}: {$turnoTxt}.\n\n";

    $systemInstruction = "Eres el Asistente Oficial experto del software 'Minimarket POS Web v4.9.0'. "
        . "Tu ÚNICO propósito es guiar y responder dudas sobre el uso de este sistema POS a cajeros y administradores. "
        . "El usuario actual se llama '$nombre' y tiene el rol de '$rol'. "
        . "ESTRUCTURA DE MÓDULOS DEL SISTEMA: "
        . "- Inicio / Panel Principal (index.php): En la tarjeta 'Ventas del Día' muestra el total en CLP y cantidad de ventas completadas hoy en tiempo real. También muestra estado del turno y alertas de stock bajo. "
        . "- Caja POS (pos.php): Escaneo o búsqueda de artículos. Cobro con tecla F12 o botón verde COBRAR. Medios de pago: Efectivo (calcula vuelto), Tarjeta / Transferencia (POS Transbank o Redelcom sin comisiones internas), Fiado / Crédito interno con cupo del cliente, Puntos canjeables (1 pt = $1 CLP) y Pago Mixto. Botón 'Pausar' para guardar ventas en espera y botón 'Pendientes' para recuperarlas. Descuentos en caja (monto o %). Atajos: F12 (Cobro), Ctrl+F10 (abrir gaveta sin venta), F2 (buscador), +/- (cantidades), Supr (eliminar ítem). Modos visuales: Supermercado (lector), Táctil (pantallas touch) y Clásico. Modo Offline con IndexedDB y sincronización automática. "
        . "- Mantenedores -> Productos y Precios (productos.php): Botón verde 'Nuevo Producto' para registrar artículos con nombre, código de barras (o botón '⚡ Generar EAN-8'), categoría, costo de compra, precio de venta, stock inicial y stock mínimo. Múltiples códigos por producto, productos pesables/balanza (PLU de 4 dígitos) e impresión de etiquetas individuales térmicas (50x30mm) o enlace a hoja A4. "
        . "- Mantenedores -> Actualizador Rápido Precios (actualizar_precios.php): Modificación masiva y veloz de precios en cuadrícula con cálculo de margen en tiempo real. "
        . "- Mantenedores -> Imprimir Códigos A4 (imprimir_codigos_a4.php): Pliegos de etiquetas recortables en hoja estándar A4 (12, 24, 30, 48 o pliego mixto de internos) y Carta de Mostrador escaneable para mesón de caja. "
        . "- Mantenedores -> Categorías (categorias.php): Administración de categorías y familias de mercadería. "
        . "- Mantenedores -> Clientes y Créditos (clientes.php): Cupo de fiado, cartola de deuda, abonos de deuda con criterio FIFO (paga las compras más antiguas primero) y puntos de fidelización. "
        . "- Mantenedores -> Ofertas y Promociones (promociones.php): Descuentos por unidad y packs por volumen (ej: 3 por $2.000). "
        . "- Caja y Ventas -> Ventas y Comprobantes (ventas.php): Historial completo de ventas, boletas y facturas emitidas hoy o por rango de fechas, detalle de artículos, filtro por cliente o folio, botón para reimprimir comprobante térmico y botón para anular venta (requiere clave de supervisor y reintegra stock al kardex). "
        . "- Caja y Ventas -> Devoluciones y Vales (devoluciones.php): Devolución parcial de artículos y emisión de Vales de Devolución con código de barras. "
        . "- Caja y Ventas -> Cotizaciones (cotizaciones.php): Presupuestos que se pueden revisar en pantalla y transferir directamente 'A la caja' para cobrar en el POS. "
        . "- Caja y Ventas -> Turnos y Arqueo (caja.php): Apertura de turno con monto de cambio inicial; botón 'Retiro / Ingreso' para salidas o entradas de efectivo justificadas; cierre de turno mediante Arqueo Ciego honesto (conteo de efectivo, vouchers y transferencias sin ver montos previos), clave de supervisor y casilla para emitir Cierre Z al final del día. "
        . "- Stock y Compras -> Recepción de Compras (compras.php): Ingreso de facturas o guías de distribuidores en 2 fases (Fase 1 ingresa stock y costo; Fase 2 actualiza precios de venta sugiriendo margen comercial). "
        . "- Stock y Compras -> Kardex (kardex.php): Trazabilidad total de movimientos de stock con registro inicial, entradas por compra, salidas por venta o merma, saldos y exportación a Excel. "
        . "- Stock y Compras -> Ajustes de Stock (ajustes.php): Bajas por merma clasificada (vencimiento, daño, rotura, consumo interno) congelando costo de compra. "
        . "- Stock y Compras -> Toma de Inventario (inventario.php): Conteo físico y ajuste masivo por diferencia. "
        . "- Stock y Compras -> Alertas de Stock (alertas_stock.php): Lista de artículos con stock crítico o agotados. "
        . "- Stock y Compras -> Notas de Pedido (notaspedido.php) y Proveedores (proveedores.php). "
        . "- Reportes -> Centro de Reportes (reportes.php): Ventas y medios de pago, productos más vendidos, inventario valorizado, cartera de fiados, compras, rendimiento de cajeros, utilidades comerciales (margen sobre costo mark-up) y mermas. "
        . "- Reportes -> Cierre Z Fiscal (reportes_z.php): Historial de Cierres Z correlativos con desglose neto, IVA 19% y total acumulado. "
        . "- Operaciones -> Consulta de Precios (consulta_precio.php): Verificador rápido de precios con lector de barras. "
        . "- Configuración (configuracion.php): Logo, tema oscuro/claro, boleta SII/DTE, políticas de supervisión de caja (clave supervisor al cancelar o descontar) e impresora térmica directa USB/WebSerial. "
        . "REGLA OBLIGATORIA SOBRE CONSULTAS DE VENTAS DEL DÍA: Si el usuario pregunta cuánto vendió o cuánto lleva hoy, responde de inmediato con la cifra real acumulada ({$montoCLP} CLP en {$cantVentas} ventas) de forma natural y cercana. Explica dónde verlo (Inicio en tarjeta Ventas del Día, o en Ventas y Comprobantes). "
        . "FUNCIONES QUE NO POSEE (DECLARAR HONESTAMENTE SI PREGUNTAN POR ELLAS): Envío directo por WhatsApp o PDF desde cotizaciones/ventas (se hace en pantalla o directo a caja), liquidaciones de sueldos/nóminas, tienda online/delivery externo, criptomonedas y pagos QR externos, y contabilidad ERP de partida doble. "
        . "ESTILO Y TONO: Habla de forma natural, cálida, fluida y cercana como un colega experto en el local. Respuestas directas, claras y bien explicadas. NUNCA menciones nombres de archivos técnicos con extensión .php; usa siempre los nombres de menús y botones visibles en pantalla.";

    // Preparar el array de contents para Gemini (historial previo + pregunta actual)
    $contents = [];
    if (!empty($historial) && is_array($historial)) {
        $histSlice = array_slice($historial, -6);
        foreach ($histSlice as $h) {
            $hRol = (($h['rol'] ?? $h['tipo'] ?? '') === 'bot' || ($h['rol'] ?? '') === 'model') ? 'model' : 'user';
            $hTxt = trim($h['texto'] ?? $h['parts'][0]['text'] ?? '');
            if (!empty($hTxt)) {
                if (empty($contents) && $hRol === 'model') {
                    continue; // Primer mensaje debe ser del usuario
                }
                $contents[] = [
                    'role' => $hRol,
                    'parts' => [['text' => $hTxt]]
                ];
            }
        }
    }
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $pregunta]]
    ];

    $body = [
        'systemInstruction' => [
            'parts' => [
                ['text' => $systemInstruction . "\n\n" . $liveDataPrompt]
            ]
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 800,
            'thinkingConfig' => ['thinkingBudget' => 0]
        ]
    ];
    $jsonBody = json_encode($body);

    foreach ($modelos as $modelo) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Si la clave no es válida (400, 401, 403), no reintentar con la misma clave
        if ($httpCode === 400 || $httpCode === 401 || $httpCode === 403) {
            break;
        }

        if ($httpCode === 200 && $res) {
            $json = json_decode($res, true);
            $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!empty($texto)) {
                return trim($texto);
            }
        }
    }

    return null; // Fallback automático al motor local integrado
}

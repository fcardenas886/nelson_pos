<?php
/**
 * Endpoint de Asistente de IA para Soporte Operativo y Manual del Sistema
 * Restringido exclusivamente al uso, procedimientos y funciones de Minimarket POS.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Validar que el usuario esté autenticado
if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado. Debes iniciar sesión.']);
    exit;
}

$user = $_SESSION['usuario'];
$userRol = $user['rol'] ?? 'Cajero';
$userNombre = $user['nombre'] ?? 'Operador';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];
$mensaje = trim($data['mensaje'] ?? $_POST['mensaje'] ?? $GLOBALS['test_mensaje'] ?? '');

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
// 2. DETECCIÓN DE PREGUNTAS AJENAS AL SISTEMA (GUARDRAILS ESTRICTOS)
// --------------------------------------------------------------------
$mensajeLower = mb_strtolower($mensaje, 'UTF-8');

$esPreguntaExterna = false;
$temasProhibidos = [
    'receta', 'cocinar', 'ingredientes para torta', 'política', 'presidente', 'elecciones', 
    'clima hoy', 'temperatura', 'chiste', 'fútbol', 'mundial', 'película', 'serie',
    'quién eres en la vida real', 'cuéntame un cuento', 'poema', 'capital de', 'religión',
    'horóscopo', 'zodiaco', 'matemática cuántica', 'código python', 'hackear'
];

foreach ($temasProhibidos as $tp) {
    if (str_contains($mensajeLower, $tp)) {
        $esPreguntaExterna = true;
        break;
    }
}

// Palabras clave que confirman que sí es del sistema
$palabrasClaveSistema = [
    'pos', 'caja', 'turno', 'venta', 'vender', 'cobrar', 'efectivo', 'tarjeta', 'transferencia',
    'fiado', 'crédito', 'puntos', 'vuelto', 'producto', 'código', 'barra', 'ean', 'plu', 'balanza',
    'pesable', 'kardex', 'stock', 'inventario', 'merma', 'pérdida', 'vencimiento', 'ajuste',
    'compra', 'factura', 'guía', 'proveedor', 'cliente', 'devolución', 'anular', 'vale',
    'reporte', 'utilidad', 'margen', 'costo', 'ganancia', 'cierre', 'arqueo', 'z', 'gaveta',
    'impresora', 'ticket', 'precio', 'promoción', 'oferta', 'pack', 'descuento', 'pausar',
    'pendiente', 'usuario', 'clave', 'rol', 'sueldo', 'gasto', 'servicio', 'online', 'web', 'sii',
    'boleta', 'cómo', 'donde', 'ayuda', 'manual'
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
        . "Mi función es **exclusivamente guiarte en el uso y operación de este sistema** (ventas, cobros, turnos de caja, inventario, mermas, kardex, compras, clientes y reportes).\n\n"
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
// 3. INTENTO DE RESPUESTA MEDIANTE CLOUD LLM (GEMINI) SI ESTÁ CONFIGURADO
// --------------------------------------------------------------------
if (!empty($apiKeyGemini)) {
    $respuestaGemini = consultarGemini($mensaje, $apiKeyGemini, $userRol, $userNombre);
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
$respuestaLocal = resolverConocimientoLocal($mensajeLower, $userRol, $userNombre);

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
 * Motor semántico de resolución local con manual completo y tratamiento de funciones ausentes
 */
function resolverConocimientoLocal($q, $rol, $nombre) {
    
    // ----------------------------------------------------------------
    // CASO A: FUNCIONES NO DISPONIBLES EN EL SISTEMA (Con alternativa práctica)
    // ----------------------------------------------------------------

    // 1. Sueldos, nóminas o pago de servicios (luz, agua, arriendo)
    if (str_contains($q, 'sueldo') || str_contains($q, 'pago de luz') || str_contains($q, 'pago de agua') || str_contains($q, 'arriendo') || str_contains($q, 'pagar empleado') || str_contains($q, 'nómina') || str_contains($q, 'gastos varios')) {
        return "⚠️ **Esta función no cuenta con un módulo de nóminas o pago de servicios específico en el sistema.**\n\n"
            . "💡 **Alternativa práctica recomendada:**\n"
            . "Para que el dinero físico de la caja registradora no quede descuadrado al momento del arqueo:\n"
            . "1. En la barra superior del **POS** o en **Turnos / Arqueo**, presiona el botón **'Retiro / Ingreso'**.\n"
            . "2. Selecciona **Tipo: Retiro de Efectivo**.\n"
            . "3. Ingresa el **Monto retirado** y escribe el motivo exacto (ej. *'Pago de luz local'*, *'Adelanto sueldo cajero'*).\n"
            . "4. Presiona **Registrar**.\n\n"
            . "✅ De esta forma, el dinero saliente quedará justificado contablemente en el reporte de turno y el arqueo cuadrará sin faltantes.";
    }

    // 2. Venta online, e-commerce, sincronizar con página web o delivery
    if (str_contains($q, 'online') || str_contains($q, 'página web') || str_contains($q, 'ecommerce') || str_contains($q, 'mercadolibre') || str_contains($q, 'pedidos ya') || str_contains($q, 'uber eats') || str_contains($q, 'tienda virtual')) {
        return "⚠️ **El sistema está diseñado exclusivamente para la venta presencial en mostrador físico.** No incluye sincronización con tiendas online externas.\n\n"
            . "💡 **Alternativa práctica recomendada:**\n"
            . "Si recibes pedidos por WhatsApp, teléfono o redes sociales que necesitas apartar antes de cobrar:\n"
            . "1. Ve a **Operaciones → Cotizaciones** o **Notas de Pedido**.\n"
            . "2. Genera la cotización con los productos acordados con el cliente.\n"
            . "3. Cuando el cliente venga a retirar o se despache, puedes cargarla directamente al carrito del **POS** con 1 solo clic para facturarla y cobrarla.";
    }

    // 3. Envío masivo de correos o newsletters a clientes
    if (str_contains($q, 'correo masivo') || str_contains($q, 'email marketing') || str_contains($q, 'newsletter') || str_contains($q, 'enviar mail')) {
        return "⚠️ **El sistema no cuenta con herramientas de envío masivo de correos electrónicos.**\n\n"
            . "💡 **Alternativa disponible:**\n"
            . "Puedes ir a **Maestros → Clientes y Créditos**, donde tienes el listado de tus clientes registrados con sus datos de contacto (teléfono, RUT, historial y puntos acumulados) para contactarlos directamente.";
    }

    // 4. Préstamos o anticipos de dinero
    if (str_contains($q, 'préstamo') || str_contains($q, 'prestar plata') || str_contains($q, 'anticipo')) {
        return "⚠️ **No existe un módulo financiero de préstamos personales.**\n\n"
            . "💡 **Alternativa práctica:**\n"
            . "Si sacas dinero de la caja física, regístralo inmediatamente como **'Retiro de Caja'** indicando el nombre de la persona en el motivo para no generar un faltante en la entrega de turno.";
    }


    // ----------------------------------------------------------------
    // CASO B: FUNCIONES ACTIVAS DEL SISTEMA (Manual Paso a Paso)
    // ----------------------------------------------------------------

    // 1. Abrir Caja y Turnos
    if ((preg_match('/(abr|apertur|inici)/u', $q) && (str_contains($q, 'caja') || str_contains($q, 'turno') || str_contains($q, 'dia') || str_contains($q, 'día'))) || str_contains($q, 'fondo fijo') || str_contains($q, 'abrir caja')) {
        return "🟢 **¿Cómo abrir tu turno de caja registradora?**\n\n"
            . "1. Ve al menú superior y selecciona **Turnos / Arqueo**.\n"
            . "2. Si hay más de una caja física configurada, selecciona la **Caja Asignada** correspondiente a tu equipo.\n"
            . "3. Ingresa el **Monto de Apertura** (el efectivo inicial o fondo de cambio con el que arranca la gaveta).\n"
            . "4. Presiona el botón verde **'Abrir Caja Registradora'**.\n\n"
            . "🚀 **Listo:** El sistema te redirigirá automáticamente a la pantalla de **Caja POS**, listo para escanear y vender.";
    }

    // 2. Cerrar Caja y Arqueo Ciego
    if ((preg_match('/(cerr|cierr|arque|cuadr)/u', $q) && (str_contains($q, 'caja') || str_contains($q, 'turno'))) || str_contains($q, 'cierre z') || str_contains($q, 'sobrante') || str_contains($q, 'faltante') || str_contains($q, 'arqueo ciego')) {
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
            . "📊 El sistema generará el resumen de cuadratura indicando si hubo diferencia exacta, sobrante o faltante.";
    }

    // 3. Vender y Métodos de Pago en el POS
    if (preg_match('/(vend|cobr|f12)/u', $q) && !str_contains($q, 'mixto') && !str_contains($q, 'fiado')) {
        return "🛒 **¿Cómo realizar una venta en Caja POS?**\n\n"
            . "1. En la pantalla de **Caja POS**, escanea el código de barras o escribe el nombre del producto en el buscador superior y presiona <kbd>Enter</kbd>.\n"
            . "2. El artículo se agregará al **Carrito de Compra** a la derecha. Puedes ajustar cantidades con <kbd>+</kbd> o <kbd>-</kbd>.\n"
            . "3. Presiona el botón verde **'COBRAR VENTA'** o la tecla de acceso directo <kbd>F12</kbd>.\n"
            . "4. Se abrirá la ventana de cobro. Selecciona el método de pago:\n"
            . "   * 💵 **Efectivo:** Usa los botones de billetes rápidos o escribe lo recibido; el sistema calcula el vuelto exacto.\n"
            . "   * 💳 **Tarjeta / Transferencia:** Registra el pago electrónico.\n"
            . "   * 🤝 **Fiado (Crédito Interno):** Requiere cliente seleccionado y valida su cupo disponible.\n"
            . "   * ⭐ **Puntos:** Canjea saldo acumulado (1 punto = \$1).\n"
            . "   * 🔀 **Pago Mixto:** Divide el total combinando varios métodos.\n"
            . "5. Presiona **'CONFIRMAR E IMPRIMIR'** para emitir el ticket y abrir la gaveta.";
    }

    // 4. Pago Mixto
    if (str_contains($q, 'pago mixto') || str_contains($q, 'dividir pago') || str_contains($q, 'parte efectivo parte tarjeta') || str_contains($q, 'dos medios') || (str_contains($q, 'mixto') && str_contains($q, 'pago'))) {
        return "🔀 **¿Cómo usar Pago Mixto en el cobro?**\n\n"
            . "1. Al presionar **COBRAR (<kbd>F12</kbd>)**, haz clic en la tarjeta **'Pago Mixto'**.\n"
            . "2. Se desplegarán los campos para ingresar los montos distribuidos:\n"
            . "   * Monto en Efectivo\n"
            . "   * Monto en Tarjeta\n"
            . "   * Monto en Transferencia\n"
            . "   * Monto a Fiado / Crédito (si el cliente tiene cupo)\n"
            . "   * Puntos a canjear\n"
            . "3. El recuadro inferior te indicará en tiempo real si **'Falta \$X'** o si el efectivo genera vuelto.\n"
            . "4. Cuando la suma cubra el total exacto o entregue vuelto, presiona **Confirmar Venta**.";
    }

    // 5. Fiado y Crédito a Clientes
    if (str_contains($q, 'fiado') || str_contains($q, 'credito') || str_contains($q, 'abono') || str_contains($q, 'deuda cliente')) {
        return "🤝 **Gestión de Fiados y Crédito Interno:**\n\n"
            . "**Para vender a Fiado:**\n"
            . "1. En el POS, selecciona al cliente en el recuadro **'Cliente Asignado'**.\n"
            . "2. Al cobrar (<kbd>F12</kbd>), selecciona **Crédito Interno / Fiado**.\n"
            . "3. El sistema verificará que la compra no exceda el límite de crédito configurado.\n\n"
            . "**Para abonar o pagar una deuda:**\n"
            . "1. Ve a **Maestros → Clientes y Créditos**.\n"
            . "2. Busca al cliente y presiona **'Ver Cuenta / Abonar'**.\n"
            . "3. Ingresa el monto abonado en efectivo o transferencia. El sistema aplica el abono con criterio **FIFO** (pagando las compras más antiguas primero).";
    }

    // 6. Mermas y Pérdidas
    if (str_contains($q, 'merma') || str_contains($q, 'pérdida') || str_contains($q, 'vencido') || str_contains($q, 'dañado') || str_contains($q, 'rotura') || str_contains($q, 'consumo interno')) {
        return "🗑️ **¿Cómo registrar Mermas y Pérdidas de Mercadería?**\n\n"
            . "1. Ve al menú **Inventario → Ajustes de Stock** y presiona **'Nuevo Ajuste de Stock'**.\n"
            . "2. Selecciona el producto y escribe la cantidad a dar de baja.\n"
            . "3. En **Tipo de Movimiento**, elige **SALIDA**.\n"
            . "4. En el selector estructurado de **Motivo**, elige la causa clasificada:\n"
            . "   * 🛑 **Vencimiento**\n"
            . "   * ⚠️ **Daño / Rotura**\n"
            . "   * 📦 **Pérdida / Descuadre**\n"
            . "   * 🥪 **Consumo Interno**\n"
            . "5. Guarda el ajuste. El sistema congelará el **Costo de Compra** histórico del producto.\n\n"
            . "📊 **Impacto Contable:** Puedes auditar el total de pérdidas en **Reportes → Mermas y Pérdidas**, y en **Reportes → Utilidades** verás cómo se descuenta automáticamente de la ganancia bruta para darte la **Utilidad Real Neta**.";
    }

    // 7. Kardex de Movimientos
    if (str_contains($q, 'kardex') || str_contains($q, 'stock inicial') || str_contains($q, 'historial producto') || str_contains($q, 'movimiento stock')) {
        return "📜 **¿Cómo usar el Kardex de Inventario?**\n\n"
            . "1. Ve a **Inventario → Kardex de Movimientos**.\n"
            . "2. **Buscador Predictivo Superior:** Escribe el nombre, código de barras o PLU de cualquier artículo para aislar su historial.\n"
            . "3. **Tarjeta Resumen (KPIs):** Al elegir un producto verás su **Stock Inicial** (apertura), **Total Entradas (+)**, **Total Salidas (-)** y **Saldo Actual (=)**.\n"
            . "4. **Filtros por Tipo:** Puedes filtrar para ver solo aperturas (`STOCK INICIAL`), solo `VENTAS`, solo `COMPRAS` o solo `AJUSTES`.\n"
            . "5. **Buscador en la tabla:** Puedes filtrar en vivo cualquier fila escribiendo en el campo de la cabecera.\n"
            . "6. Botón **Exportar a Excel:** Permite descargar la cartola completa en CSV.";
    }

    // 8. Utilidades y Márgenes
    if (str_contains($q, 'utilidad') || str_contains($q, 'margen') || str_contains($q, 'rentabilidad') || str_contains($q, 'ganancia')) {
        return "📈 **¿Cómo ver Utilidades y Márgenes de Ganancia?**\n\n"
            . "1. Ve a **Reportes → Utilidades y Márgenes**.\n"
            . "2. Selecciona el período (Hoy, Ayer, Esta Semana, Este Mes, etc.).\n"
            . "3. **Fórmula Comercial (Mark-up sobre costo):**\n"
            . "   * Margen % = `((Precio Venta - Costo) / Costo) * 100`.\n"
            . "   * Ejemplo: Si compras a \$1.000 y vendes a \$1.300, refleja exactamente **+30%** de ganancia.\n"
            . "4. **Categorías Desplegables:** Haz clic sobre cualquier categoría para expandir o colapsar sus productos vendidos en acordeón.\n"
            . "5. **Utilidad Real Neta:** En las tarjetas superiores verás el total facturado, costo de mercadería, ganancia bruta, **(-) Pérdidas por Mermas** y la **Utilidad Real Neta** limpia.";
    }

    // 9. Balanza y Pesables (PLU)
    if (str_contains($q, 'pesable') || str_contains($q, 'balanza') || str_contains($q, 'plu') || str_contains($q, 'kilos')) {
        return "⚖️ **Productos Pesables y Balanza:**\n\n"
            . "**En el Catálogo (Creación):**\n"
            . "1. Al crear o editar el producto en **Maestros → Productos**, marca la casilla **'Es Pesable'**.\n"
            . "2. Presiona el botón **'⚡ Generar PLU'** para asignarle un código de 4 dígitos (ej. `0012` o `1200`), o ingresa el de tu balanza.\n\n"
            . "**En el POS (Venta):**\n"
            . "1. Puedes digitar el PLU o escanear la etiqueta emitida por la balanza.\n"
            . "2. Si tu balanza es sin etiqueta, al seleccionar el producto se abrirá una ventana donde puedes ingresar los **kilos** (y calcula el total) o ingresar el **monto en pesos** (y calcula los kilos equivalentes).";
    }

    // 10. Códigos EAN-8 y Etiquetas Góndola
    if (str_contains($q, 'ean') || str_contains($q, 'etiqueta') || str_contains($q, 'generar código') || str_contains($q, 'codigo de barra')) {
        return "🏷️ **Generación de Códigos de Barra EAN-8 y Etiquetas:**\n\n"
            . "1. Ve a **Maestros → Productos y Precios** y crea un nuevo producto.\n"
            . "2. Si el producto no tiene código de barra de fábrica (ej. pan, artesanías, empanadas), presiona el botón **'⚡ Generar EAN-8'**.\n"
            . "3. El sistema le asignará un código estándar oficial de 8 dígitos.\n"
            . "4. Al guardar, puedes presionar **'Imprimir Etiqueta'** para generar e imprimir pegatinas térmicas con código de barra en SVG, nombre y precio para las góndolas.";
    }

    // 11. Promociones y Ofertas
    if (str_contains($q, 'promoción') || str_contains($q, 'oferta') || str_contains($q, 'pack') || str_contains($q, 'descuento')) {
        return "🎁 **¿Cómo crear Promociones y Ofertas?**\n\n"
            . "1. Ve a **Maestros → Ofertas y Promociones** y presiona **'Nueva Oferta'**.\n"
            . "2. Selecciona el producto y el tipo de promoción:\n"
            . "   * **Descuento Unitario:** Aplica un % de descuento directo por unidad.\n"
            . "   * **Pack por Volumen:** Define una cantidad y precio oferta (ej. *'3 unidades por \$2.000'*).\n"
            . "3. Define la fecha de inicio y término.\n"
            . "4. Guarda. En el **POS**, la promoción se aplicará de forma automática en cuanto el cajero agregue la cantidad requerida al carrito.";
    }

    // 12. Recepción de Compras (Ingreso de Mercadería)
    if (str_contains($q, 'compra') || str_contains($q, 'recepción') || str_contains($q, 'ingresar mercadería') || str_contains($q, 'factura proveedor')) {
        return "📥 **¿Cómo ingresar Compras de Mercadería?**\n\n"
            . "El módulo opera en **2 Fases:**\n\n"
            . "**Fase 1: Recepción de Factura / Guía**\n"
            . "1. Ve a **Operaciones → Recepción de Compras** y presiona **'Ingresar Mercadería'**.\n"
            . "2. Selecciona el Proveedor y coloca el N° de Documento.\n"
            . "3. Agrega cada producto con la cantidad recibida y el costo unitario de compra.\n"
            . "4. Presiona **'Guardar Recepción'**. *¡Listo! En este paso el stock ya ingresó al inventario y el costo quedó actualizado.*\n\n"
            . "**Fase 2: Actualización de Precios de Venta (Opcional)**\n"
            . "1. El sistema te mostrará los productos comprados con su margen recalculado.\n"
            . "2. Puedes ajustar los precios de venta al público o presionar **'Mantener Precios'**.";
    }

    // 13. Pausar y Retomar Ventas
    if (str_contains($q, 'pausar') || str_contains($q, 'retomar') || str_contains($q, 'pendiente') || str_contains($q, 'espera')) {
        return "⏸️ **Pausar y Retomar Ventas en el POS:**\n\n"
            . "**Para pausar una venta:**\n"
            . "1. Si el cliente olvidó un producto o fue a buscar efectivo, presiona el botón **'Pausar'** en el carrito.\n"
            . "2. Escribe una referencia rápida (ej. *'Señor polera azul'* o *'Cliente pan'*).\n"
            . "3. El carrito se vaciará de inmediato para que puedas atender al siguiente cliente en la fila.\n\n"
            . "**Para retomarla:**\n"
            . "1. Presiona el botón **'Pendientes'** junto al buscador superior.\n"
            . "2. Selecciona la venta pausada y presiona **'Restaurar'**.\n"
            . "3. Todos los productos y cantidades volverán al carrito para cobrar.";
    }

    // 14. Abrir Gaveta sin vender
    if (str_contains($q, 'gaveta') || str_contains($q, 'cajón') || str_contains($q, 'abrir gaveta') || str_contains($q, 'dar cambio')) {
        return "💵 **Apertura de Gaveta de Dinero:**\n\n"
            . "La gaveta salta automáticamente al confirmar un cobro en efectivo.\n\n"
            . "**Para abrirla manualmente sin hacer una venta (ej. dar cambio):**\n"
            . "1. Presiona el atajo de teclado <kbd>Ctrl + F10</kbd> en el POS.\n"
            . "2. O si tienes permisos, usa el botón de apertura manual en la barra de herramientas.\n"
            . "*Nota: Requiere que la impresora térmica esté conectada por puerto serie o USB en Configuración → Impresora.*";
    }

    // 15. Devoluciones y Anulaciones
    if (str_contains($q, 'devolución') || str_contains($q, 'anular venta') || str_contains($q, 'reembolso')) {
        return "↩️ **Devoluciones y Anulaciones de Ventas:**\n\n"
            . "**Anulación Total de Venta:**\n"
            . "1. Ve a **Operaciones → Ventas / Anulaciones**.\n"
            . "2. Localiza la venta y presiona **'Anular'**.\n"
            . "3. Requiere clave de Administrador/Supervisor y motivo.\n"
            . "4. Al confirmar, el stock completo de todos los productos (incluyendo packs) se devuelve automáticamente al inventario y se registra en el Kardex.\n\n"
            . "**Devolución Parcial:**\n"
            . "1. Ve a **Operaciones → Devoluciones y Vales** y presiona **'Registrar Devolución'**.\n"
            . "2. Ingresa el N° de venta original, el artículo y las unidades específicas a devolver.\n"
            . "3. Puedes emitir un vale de devolución o entregar el dinero en efectivo.";
    }

    // 16. Cotizaciones y Notas de Pedido
    if (str_contains($q, 'cotizaci') || str_contains($q, 'presupuesto') || str_contains($q, 'nota de pedido') || str_contains($q, 'apartar')) {
        return "📄 **¿Cómo gestionar y enviar Cotizaciones o Notas de Pedido?**\n\n"
            . "1. Ve al menú superior **Operaciones → Cotizaciones**.\n"
            . "2. Presiona el botón verde **'Nueva Cotización'**.\n"
            . "3. Selecciona al **Cliente** (o créalo si es nuevo) y agrega los productos con sus cantidades pactadas.\n"
            . "4. Presiona **'Guardar Cotización'**.\n\n"
            . "📲 **Para enviarla o compartirla con el cliente:**\n"
            . "   * Presiona el botón **'Imprimir Ticket'** para emitir el comprobante térmico.\n"
            . "   * Usa el botón de **WhatsApp / Enlace** para compartir el detalle directamente al teléfono del cliente.\n\n"
            . "🛒 **Para facturar y cobrar en el POS:**\n"
            . "   * Cuando el cliente confirme la compra, presiona el botón **'Cargar al POS'** y todos los artículos se transferirán instantáneamente al carrito de venta para cobrar con <kbd>F12</kbd>.";
    }

    // ----------------------------------------------------------------
    // RESPUESTA GENERAL SI NO COINCIDE CON NINGÚN PATRÓN ESPECÍFICO
    // ----------------------------------------------------------------
    return "👋 Hola **$nombre**, soy el Asistente Operativo de **Minimarket POS**.\n\n"
        . "Puedo ayudarte paso a paso con cualquiera de las funciones del sistema:\n\n"
        . "🔹 **Ventas y Caja POS:** Cobros (<kbd>F12</kbd>), pago mixto, fiados con cupo, puntos, balanza (PLU), pausar ventas.\n"
        . "🔹 **Turnos y Arqueos:** Monto de apertura, arqueo ciego, cuadratura y Cierre Z.\n"
        . "🔹 **Cotizaciones:** Crear notas de pedido, enviar al cliente y cargar al POS con 1 clic.\n"
        . "🔹 **Inventario y Catálogo:** Crear productos, códigos EAN-8, etiquetas góndola, alertas de stock mínimo.\n"
        . "🔹 **Mermas y Kardex:** Trazabilidad de Stock Inicial, bajas por vencimiento o rotura y Utilidad Real Neta.\n"
        . "🔹 **Compras y Proveedores:** Recepción de facturas en 2 fases y cálculo de márgenes.\n\n"
        . "💡 *¿Sobre qué procedimiento específico te gustaría que te explique?*";
}

/**
 * Consulta a la API de Gemini (utilizando los modelos activos de Google AI)
 */
function consultarGemini($pregunta, $apiKey, $rol, $nombre) {
    // Modelos admitidos en orden de preferencia
    $modelos = ['gemini-flash-latest', 'gemini-3.8-flash', 'gemini-2.5-pro'];

    $systemInstruction = "Eres el Asistente Oficial experto del software 'Minimarket POS Web v4.8.0'. "
        . "Tu ÚNICO propósito es guiar y responder dudas sobre el uso de este sistema POS a cajeros y administradores. "
        . "ESTRUCTURA DE MÓDULOS DEL SISTEMA: "
        . "- Caja POS (pos.php): Venta rápida, búsqueda por código de barra o nombre, cobro con F12 (Efectivo, Tarjeta, Transferencia, Fiado/Crédito interno, Puntos, Pago Mixto con cálculo de vuelto o monto faltante). Pausar ventas y recuperarlas en botón 'Pendientes'. Abrir gaveta con Ctrl+F10. "
        . "- Turnos y Arqueo (caja.php): Apertura con monto inicial, asignación de caja física; cierre con Arqueo Ciego (conteo físico de efectivo, vouchers y transferencias), clave de supervisor y opción Cierre Z. Botón Retiro / Ingreso para justificar salidas de efectivo. "
        . "- Operaciones -> Cotizaciones (cotizaciones.php): Crear notas de pedido y cotizaciones con cliente y productos, imprimir ticket, compartir por WhatsApp o enlace, y cargar directamente al carrito del POS con un clic. "
        . "- Operaciones -> Recepción de Compras (recepcion_compras.php): En 2 fases: Fase 1 ingresa stock y costo de factura del proveedor; Fase 2 actualiza precios con recálculo de margen sobre costo. "
        . "- Operaciones -> Devoluciones y Anulaciones (ventas.php, devoluciones.php): Anulación total reponiendo stock (incluso packs) o vales de devolución. "
        . "- Inventario -> Productos (productos.php): Catálogo, generar código EAN-8, generar PLU para balanza pesable, imprimir etiquetas de góndola, stock mínimo. "
        . "- Inventario -> Kardex (kardex.php): Trazabilidad de movimientos con movimiento INICIAL de apertura de catálogo, buscador predictivo, tarjeta resumen con KPIs (Stock Inicial, Entradas +, Salidas -, Saldo Actual =), filtros y Excel. "
        . "- Inventario -> Ajustes de Stock (ajustes_stock.php) y Mermas (reportes.php?tab=mermas): Registro clasificado de bajas (Vencimiento, Daño/Rotura, Pérdida, Consumo Interno) congelando costo unitario. "
        . "- Maestros -> Clientes y Créditos (clientes.php): Cupo de fiado, pagos/abonos con algoritmo FIFO, y acumulación/canje de puntos (1 punto = $1). "
        . "- Maestros -> Ofertas (promociones.php): Descuentos por unidad y packs por volumen (ej. 3 por $2.000). "
        . "- Reportes -> Utilidades y Márgenes (reportes.php?tab=utilidades): Margen comercial sobre costo ((Venta - Costo)/Costo * 100), categorías desplegables en acordeón y Utilidad Real Neta descontando mermas. "
        . "- Configuración (configuracion.php): Logo, datos del local, terminales, boleta SII/DTE, impresora térmica y Asistente de IA. "
        . "REGLAS CRÍTICAS: "
        . "1. Responde ÚNICAMENTE sobre las funciones de Minimarket POS. "
        . "2. Si el usuario pregunta algo ajeno al sistema (política, cocina, religión, ciencia, chistes), declina amablemente y reorienta a las funciones del POS. "
        . "3. SI PREGUNTA POR UNA FUNCIÓN QUE NO TIENE EL SISTEMA (ej. nómina/sueldos o venta web): NUNCA inventes botones ni menús inexistentes. Declara con honestidad que no está integrada y ofrece la alternativa práctica dentro del sistema (ej. para pagos usar 'Retiro de Caja' con motivo; para pedidos online usar 'Cotizaciones / Notas de Pedido'). "
        . "4. Usa formato claro con negritas, pasos numerados (1., 2., 3.) y atajos como <kbd>F12</kbd> o <kbd>Alt + I</kbd>. "
        . "5. El usuario actual se llama '$nombre' y tiene el rol de '$rol'. Sé amable, profesional y conciso.";

    $body = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemInstruction . "\n\nPregunta del usuario: " . $pregunta]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 800
        ]
    ];
    $jsonBody = json_encode($body);

    foreach ($modelos as $modelo) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $res) {
            $json = json_decode($res, true);
            $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!empty($texto)) {
                return trim($texto);
            }
        }
    }

    return null; // Si todos los modelos fallan, el controlador hace fallback al motor local
}

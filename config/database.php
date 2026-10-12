<?php
// Version de la app: unica fuente de verdad, para que login y footer nunca queden desincronizados.
define('APP_VERSION', 'v4.9.0');

// Novedades reales por versión, para la pantalla de bienvenida y el módulo "Acerca de".
// Registra la cronología completa de la evolución del sistema desde su inicio.
define('APP_CHANGELOG', [
    'v4.9.0' => [
        'Agente Asistente de IA Integrado (Tipo Chat): Asistente inteligente flotante en todas las pantallas para resolver dudas operativas y guiar en el uso del sistema (atajo Alt + I).',
        'Diseño Compacto y Ergonómico: Ventana estilizada no invasiva (330x440px) y botón flotante discreto para operar en el POS sin obstaculizar la vista.',
        'Lenguaje Natural y Cercano: Respuestas fluidas y humanas sin jerga técnica de archivos ni menciones innecesarias a Cierres Z para consultas del día a día.',
        'Métricas del Día en Tiempo Real: Consulta instantánea con cifras reales de ventas acumuladas en CLP, cantidad de transacciones y estado del turno de caja.',
        'Motor Dual Híbrido: Soporte oficial para API Keys 2026 de Google Gemini AI (modelos 3.5-flash y 3.8-flash) con respaldo automático (failover) en motor local offline con más de 35 guías estructuradas.',
        'Guardrails Estrictos de Operación: Especializado exclusivamente en procedimientos del POS, declinando amablemente preguntas ajenas a la tienda.',
        'Tratamiento Honesto de Funciones Ausentes: Si una función no está disponible en el sistema, lo declara con franqueza y ofrece la alternativa práctica real.',
        'Diagnóstico en Configuración y Discreción: Panel para probar la API Key de Gemini en tiempo real e indicador visual sutil en el chat.',
        'Corrección de Historial en Caja: Visualización restaurada del historial de aperturas y cierres en el módulo de Turnos y Arqueo.',
        'Estimador Rápido de Costos por Margen: Herramienta en lote dentro del Actualizador de Precios para cargar productos sin costo ($0) y calcular costos automáticamente según el margen deseado (Mark-up o comercial).',
    ],
    'v4.8.0' => [
        'Módulo de Impresión de Códigos de Barra en Hoja A4: Sistema integral para generar pliegos de stickers recortables y catálogos de mostrador en papel estándar de oficina A4.',
        'Diseño Ergonómico en 2 Columnas: Panel lateral ampliado a 580px con controles organizados en grilla horizontal para que todas las opciones quepan 100% en pantalla sin scroll vertical.',
        'Modo Pliego de Etiquetas y Pliego Mixto: Impresión de stickers para un producto o pliego mixto con 1 etiqueta de cada código interno del local (EAN-8 / PLU / Balanza) para optimizar papel.',
        'Modo Carta / Catálogo de Mostrador: Hoja de consulta rápida organizada por categorías para el mesón de caja, facilitando el escaneo de pesables y productos sin código comercial.',
        'Previsualizador Calibrado con Zoom: Vista previa A4 con escala inicial compacta al 70% y controles rápidos (60%, 70%, 80%, 100%), garantizando escala 100% nítida al imprimir.',
        'Filtro de Códigos Internos (⭐): Aislamiento con un clic de productos propios y pesables frente a productos comerciales de fábrica.',
        'Opciones de Diseño Flexibles: Configuración en vivo para mostrar/ocultar precio, nombre del local, líneas de corte punteadas y selector de 2, 3 o 4 columnas por hoja.',
        'Integración en Catálogo: Acceso directo desde Mantenedores, cabecera de Catálogo de Productos y botón "Pliego A4" dentro de la etiqueta individual.',
    ],
    'v4.7.2' => [
        'Kardex con Stock Inicial de Apertura: Registro y visualización del movimiento inicial de apertura de catálogo (INICIAL) para todos los productos, garantizando trazabilidad completa desde el origen.',
        'Buscador Inteligente de Productos en Kardex: Selector ergonómico con autocompletado en vivo por nombre, código de barras o código PLU, mostrando stock actual y categoría.',
        'Tarjeta de Resumen y KPIs de Trazabilidad: Panel métrico al seleccionar un producto con Stock Inicial, Total Entradas (+), Total Salidas (-) y Stock Saldo Actual (=).',
        'Filtro Rápido en Vivo de la Tabla: Barra de búsqueda instantánea para filtrar movimientos en tiempo real por texto o código sin recargar la página.',
        'Filtros por Tipo de Movimiento y Fechas: Selector estructurado para auditar Stock Inicial, Ventas, Compras, Ajustes o Devoluciones con exportación a Excel.',
        'Automatización en Creación de Productos: Registro automático e instantáneo del Stock Inicial en Kardex al dar de alta nuevos productos.',
    ],
    'v4.7.1' => [
        'Utilidades por Categoría Desplegables: Vista jerárquica tipo acordeón que agrupa ventas y ganancias por categoría, permitiendo expandir y colapsar para ver el detalle de cada producto.',
        'Selector y Filtro de Categorías en Utilidades: Menú desplegable para aislar rápidamente cualquier categoría específica con auto-apertura instantánea.',
        'Búsqueda Inteligente en Tiempo Real: El buscador expande de inmediato las categorías que contienen productos coincidentes (por nombre, código de barra o PLU).',
        'Controles de Expansión Masiva: Botones de "Expandir" y "Colapsar" para abrir o cerrar todas las categorías de un solo clic.',
    ],
    'v4.7.0' => [
        'Módulo de Mermas y Pérdidas: Auditoría completa de bajas por vencimiento, roturas, consumo interno o descuadres valorizados al costo de compra y precio de venta con exportación a Excel.',
        'Utilidad Real Neta: Deducción contable de pérdidas por merma sobre la ganancia bruta de ventas (Utilidad Neta = Ganancia Ventas - Mermas).',
        'Tipificación de Motivos en Ajustes de Stock: Selector estructurado con motivos clasificados (Vencimiento, Daño/Rotura, Pérdida, Consumo Interno) y detalle personalizado.',
        'Congelamiento de Costo Unitario en Mermas: Nueva columna en detalle de ajustes para preservar el costo histórico de cada artículo dado de baja.',
    ],
    'v4.6.1' => [
        'Márgenes Calculados sobre Costo (Mark-up): Adopción del estándar intuitivo del comerciante ((Venta - Costo) / Costo * 100), donde comprar a $1.000 y vender a $1.300 refleja un 30% de ganancia real.',
        'Tratamiento de Productos Sin Costo (S/C): Se reemplaza el falso margen del 100% por insignias claras de "S/C" (Sin Costo) en inventario, utilidades y actualizador de precios.',
        'KPI de Margen Promedio sobre Costo: Incorporación de tarjeta de rendimiento consolidado en el módulo de Utilidades.',
    ],
    'v4.6.0' => [
        'Reporte de Inventario Valorizado Detallado: Desglose completo de productos con costos unitarios, precios de venta, stock actual, capital total inmovilizado y cálculo de márgenes con exportación a Excel.',
        'Detección de Productos sin Costo: Alerta visual y filtro rápido para identificar artículos con costo $0 en inventario.',
        'Soporte y Blindaje Multi-Caja: Asignación y memoria de caja física por equipo (localStorage), resolución de apertura y visualización destacada en POS.',
        'Buscador del POS Optimizado en 1 Línea: Barra compacta en una sola fila sin salto vertical, con máxima amplitud para el lector de código de barras.',
    ],
    'v4.5.3' => [
        'Devoluciones en Unidades Reales: Al devolver se cuentan unidades físicas sumando unidades sueltas y packs (un pack de 3 son 3 unidades); se repone el stock real y se reembolsa lo efectivamente pagado por unidad.',
        'Kardex Correcto con Líneas Repetidas: Si un producto aparece en varias líneas de una venta (ej. suelto + pack), el saldo del kardex se encadena correctamente.',
        'Control de Stock Acumulado: La validación de stock suma todas las líneas del mismo producto, para no vender más de lo disponible repartiéndolo en varias líneas.',
    ],
    'v4.5.2' => [
        'Precios Protegidos en el Servidor: El precio enviado por la caja solo se acepta en productos de Precio Variable; el resto se calcula siempre desde el catálogo (unidad, pack, promoción), también en ventas sincronizadas desde modo offline.',
        'Anulación de Ventas con Packs: Al anular, se repone el stock completo de cada pack (ej. un pack de 3 devuelve 3 unidades, no 1) y el kardex lo registra igual.',
    ],
    'v4.5.1' => [
        'Canje Parcial de Puntos: Al elegir un cliente se muestran sus puntos y cupo de fiado; puede usar parte o todos sus puntos (1 punto = $1) y el resto se paga con cualquier forma de pago.',
        'Fiado dentro del Pago Mixto: Se puede dividir una venta entre efectivo, tarjeta, transferencia y fiado, validando el cupo del cliente.',
        'Vuelto en Pago Mixto: Si el efectivo entregado sobra, se calcula el vuelto y en caja se registra solo el efectivo que realmente queda.',
        'Desglose en el Cobro: Subtotal, descuento, vale y puntos canjeados junto al total a pagar.',
        'Vuelto en Pago Mixto igual que en Efectivo: Recuadros "Recibido" y "Vuelto" (o "Falta", en rojo) con el mismo diseño en ambos modos.',
        'Cobro sin Scroll: Pantalla de pago más compacta que cabe completa en monitores de caja de 1366x768.',
    ],
    'v4.5.0' => [
        'Pantalla de Cobro Renovada: Nuevo diseño del modal de pago con el total destacado arriba, tarjetas grandes por forma de pago y paneles más claros.',
        'Forma de Pago Seleccionada Visible: La opción elegida se resalta con su color, borde, brillo y un check; el botón final indica el monto y el método (ej. "COBRAR $4.490 · Efectivo").',
        'Efectivo más Claro: El billete rápido elegido queda marcado y, si el monto recibido no alcanza, se muestra "Falta $X" en rojo en lugar del vuelto.',
        'Ayudas por Método: Tarjeta, Transferencia, Fiado y Puntos muestran una indicación breve; en Pago Mixto se ve en vivo cuánto falta para cubrir el total.',
    ],
    'v4.4.3' => [
        'Corrección del Comprobante Post-Venta: Se arregla el error que aparecía tras confirmar la venta (la venta se grababa, pero el comprobante no se mostraba).',
        'Pesables en el Comprobante: El detalle muestra kilos y precio por kilo (ej. 2,166 kg x $1.500/kg).',
    ],
    'v4.4.2' => [
        'Venta con Stock Negativo en el POS: La caja ahora respeta la opción "Vender con stock negativo"; con "Permitir" se pueden agregar productos sin stock y subir su cantidad libremente.',
        'Base de Datos Compatible con Stock Negativo: Se eliminan las restricciones que rechazaban la venta (error 3819) aunque la opción estuviera activa. Requiere ejecutar la migración 0011.',
    ],
    'v4.4.1' => [
        'Código PLU Automático: Botón "Generar" en el formulario de productos y asignación automática al guardar un pesable sin PLU.',
        'PLU de 4 Dígitos según Tipo de Balanza: Sin etiqueta se usa el ID del producto × 100 (ej. ID 12 → 1200), fácil de digitar en caja; con etiqueta EAN-13 se usa el ID con 4 dígitos (ej. 0012). Si no cabe o está ocupado, se toma el siguiente libre.',
        'Formulario de Productos Compacto: Ventana con scroll interno, campos agrupados y botones Guardar/Cancelar siempre visibles en pantallas bajas.',
    ],
    'v4.4.0' => [
        'Balanza sin Etiqueta (Ingreso Manual): Nuevo tipo de balanza en Configuración para locales cuya balanza solo muestra el peso y no imprime código de barras.',
        'Venta por Peso o por Monto en el POS: Al agregar un producto pesable se abre una ventana donde el cajero ingresa los kilos (el sistema calcula el precio) o el monto en pesos (el sistema calcula los kilos equivalentes), con vista previa en vivo.',
        'Modo por Defecto Configurable: Se elige si la ventana parte en "por peso" o "por monto", y el cajero puede alternar en la misma ventana para cada venta.',
        'Subtotales Redondeados por Línea: Los productos con kilos fraccionados muestran y cobran montos enteros en pesos, igual que el cálculo del servidor.',
    ],
    'v4.3.2' => [
        'Ajuste Compacto y Fijo en una Sola Línea del Menú: Se eliminó el salto de línea que hacía caer "Admin" a una segunda fila y estiraba verticalmente la barra de navegación.',
        'Dimensiones Optimizadas y Anti-Wrap: Ajuste de espaciados, paddings y flexbox para que todos los accesos quepan ordenadamente en una sola fila en cualquier resolución.',
    ],
    'v4.3.1' => [
        'Navegación Fluida y Soporte Táctil en Menús: Corrección del cierre involuntario al mover el mouse y soporte integral para alternar menús desplegables con un solo clic o toque en pantallas táctiles.',
        'Puente Hover Anti-Flicker: Eliminación de la brecha física que cerraba los desplegables al mover el cursor hacia las opciones.',
        'Alineación Inteligente de Submenús: Desplegables de Administración y Reportes anclados a la derecha para evitar recortes en pantallas compactas o medianas.',
    ],
    'v4.3.0' => [
        'Generación Automática de Códigos EAN-8: Asignación automática de códigos de barra estándar GS1 (prefijo 2 + secuencia interna + dígito verificador Módulo 10) para productos sin código de fábrica al guardar.',
        'Botón de Autogeneración Rápida: Botón interactivo "⚡ Generar EAN-8" en el formulario de creación/edición de productos para asignar y previsualizar el código antes de guardar.',
        'Módulo de Impresión de Etiquetas Térmicas / Góndola: Nueva ventana de visualización e impresión directa de etiquetas adhesivas con código de barras en SVG (JsBarcode 100% offline), nombre y precio.',
        'Compatibilidad Universal y Códigos Cortos: Código de 8 dígitos de lectura instantánea para escáneres láser y CCD, ideal para pegatinas pequeñas, productos agrícolas, plantas o artesanías.',
    ],
    'v4.2.0' => [
        'Productos con Precio Variable / Abierto: Soporte nativo para artículos de valor dinámico (plantas, flores, artesanías, remates o servicios) marcados en el catálogo.',
        'Ventana Emergente Rápida en el POS: Al escanear o seleccionar un producto variable, el sistema solicita de inmediato el precio de venta acordado con autofoco numérico y confirmación con Enter.',
        'Convivencia de Precios Múltiples en el Carrito: Posibilidad de vender varias unidades del mismo producto variable con precios distintos en líneas separadas.',
        'Identificación Visual y Compatibilidad Total: Insignias distintivas en catálogo y carrito, con soporte para modo online y modo contingencia offline (IndexedDB).',
    ],
    'v4.1.1' => [
        'Cierre Blindado y Ágil de Comprobante de Venta: Corrección de overflow en flexbox para que los botones de acción nunca se corten en pantallas estándar de POS.',
        'Múltiples Vías de Cierre Rápido: Botón superior (X), cierre con tecla Escape o Enter, y cierre al hacer clic fuera del ticket.',
        'Recuperación Automática del Foco: Tras cerrar el comprobante, el cursor vuelve inmediatamente a la barra de escaneo (Modo Supermercado o Clásico) listo para la siguiente venta.',
    ],
    'v4.1.0' => [
        'Impresión Térmica Directa ESC/POS (80mm): Conexión directa nativa por Web Serial y WebUSB desde Google Chrome/Edge sin necesidad de controladores de Windows ni permisos de administrador.',
        'Apertura Automática de Gaveta de Dinero: Disparo instantáneo de micropulso de 24V al puerto DK (RJ11) al cobrar y botón manual en el POS para abrir gaveta sin gastar papel.',
        'Corte Automático por Guillotina: Comando de corte limpio incorporado en el protocolo de ticket térmico.',
        'Panel de Diagnóstico y Pruebas en Vivo: Nueva pestaña en Configuración para vincular el puerto de la impresora (COM / USB) y probar la apertura de gaveta con 1 solo clic.',
    ],
    'v4.0.1' => [
        'Heartbeat Activo de Conectividad: Monitorización continua de red cada 5 segundos mediante ping ultraligero para conmutación inmediata a contingencia.',
        'Simulador de Modo Offline con 1 Clic: Píldora interactiva que permite alternar y probar ventas locales en IndexedDB sin desconectar cables ni routers.',
        'Blindaje Total Anti-Autofill: Protección semántica y por script para evitar que el gestor de contraseñas del navegador inyecte "admin" en la barra de escaneo.',
        'Sincronización Automática Dinámica: Detección inteligente de versión y reconexión en segundo plano con refresco de catálogo local.',
    ],
    'v4.0.0' => [
        'Arquitectura PWA (Progressive Web App): Aplicación instalable en el escritorio de Windows para operar en ventana nativa sin barras de navegador.',
        'Motor de Venta Offline Autónomo: Operación continua de la caja registradora durante cortes de energía o caídas de internet.',
        'Base de Datos Local IndexedDB: Búsqueda y escaneo instantáneo de productos, códigos alternativos, promociones y balanzas pesables en memoria local.',
        'Cola de Ventas y Comprobante Provisional: Registro persistente en disco e impresión de tickets de contingencia sin conexión.',
        'Sincronización Inteligente con el VPS: Detección automática de reconexión y carga masiva de transacciones pendientes en MySQL.',
        'Service Worker con Caché Resiliente: Capacidad de abrir y cargar la terminal de caja incluso arrancando el computador sin internet.',
    ],
    'v3.3.0' => [
        'Actualizador Rápido de Precios: Nueva pantalla especializada para cambiar precios de venta y costos mediante escaneo continuo de códigos de barra o búsqueda rápida.',
        'Cálculo de Margen Comercial en Tiempo Real: Visualización instantánea del margen de ganancia (%) con alertas de rentabilidad al modificar precios.',
        'Gestión de Packs y Códigos Secundarios: Ajuste directo de precios para Six Packs, Cajas y presentaciones alternativas desde la misma pantalla.',
        'Herramientas Masivas de Ajuste: Aplicación de porcentajes globales (+5%, +10%, etc.) y redondeo comercial chileno a decenas/centenas.',
        'Impresión de Flejes y Etiquetas de Góndola: Generación e impresión directa de etiquetas de estantería para los productos actualizados.',
        'Jerarquía Inteligente de Precios en POS: Armonización automática entre promociones Multibuy (ej. 3x$5.000) y códigos de pack sin precio fijo.',
    ],
    'v3.2.0' => [
        'Consulta de Ventas con Filtros Avanzados: Búsqueda flexible por rango de fechas (con accesos directos: Hoy, Ayer, Últimos 7 Días, Este Mes), N° de venta, Folio DTE, Nombre de Cliente o RUT.',
        'Detalle Completo de Operación: Modal interactivo con inspección profunda de cajero, turno, cliente, desglose de ítems, descuentos, impuestos (Neto / IVA 19%) y medios de pago múltiples/mixtos.',
        'Reimpresión de Ticket Térmico (80mm/58mm): Generación y reimpresión instantánea del comprobante físico con membrete del local, detalle de artículos y pagaré firmado para ventas a crédito (Fiado).',
        'Integración y Descarga de Boleta/Factura Electrónica (DTE): Botón de acceso directo e impresión limpia del PDF oficial tributario emitido ante el SII.',
        'KPIs Ejecutivos en Tiempo Real: Tarjetas con total recaudado en el período, cantidad de operaciones, ticket promedio y monto anulado.',
    ],
    'v3.1.0' => [
        'Centro Integral de Reportes y BI: Módulo analítico unificado para auditar ventas, cartera, compras, rotación de inventario y personal.',
        'Reporte de Ventas y Medios de Pago: Métricas ejecutivas (Bruto, Neto, IVA 19%, Descuentos, Ticket Promedio), medios de pago y mapa de horas peak.',
        'Cartera de Clientes y Fiados: Auditoría de cuentas por cobrar, ranking de clientes con deuda activa, porcentaje de cupo utilizado y flujo de abonos.',
        'Compras y Gastos por Proveedor: Egresos en mercadería, ranking de distribuidores por volumen facturado y detalle de recepciones.',
        'Ranking y Detector de Stock Estancado ("Huesos"): Identificación de artículos líderes y productos sin movimiento con capital inmovilizado.',
        'Valorización de Inventario: Capital total en bodega a costo de adquisición vs. retorno proyectado a precio de venta y margen potencial.',
        'Rendimiento de Cajeros y Cuadraturas: Monitoreo de recaudación por cajero y balance histórico de sobrantes/faltantes en arqueos de turno.',
        'Exportación a Excel (CSV) universal con codificación UTF-8 e impresión limpia sin cabeceras innecesarias.',
    ],
    'v3.0.0' => [
        'Múltiples Códigos de Barra por Producto: Asocia códigos alternativos (packs, latas, cambio de presentación o nuevo EAN) sin duplicar stock.',
        'Búsqueda unificada en POS y Compras: Al escanear cualquiera de los códigos alternativos o el principal se localiza de inmediato el producto unificado.',
        'Bloqueo Rápido de Pantalla de Caja (Lock Screen): Protege la terminal con un clic (🔒) o atajo rápido (Alt + L / F9) con reloj digital en tiempo real.',
        'Desbloqueo seguro por Cajero o Supervisor: Validación por contraseña del cajero titular o clave de supervisor con registro en auditoría de seguridad.',
        'Protección total de ventas en curso: La venta actual, descuentos y clientes seleccionados permanecen intactos durante el bloqueo y recargas.',
    ],
    'v2.9.4' => [
        'Rediseño operativo del POS en 3 modos integrales: Supermercado (Caja Rápida), Táctil y Clásico.',
        'Modo Supermercado: oculta tarjetas de catálogo y despliega en el centro una tabla amplia de venta con escaneo continuo.',
        'Modo Táctil: barra deslizable superior de categorías y tarjetas táctiles optimizadas para touchscreen.',
        'Modo Clásico personalizable: selector en vivo de catálogo para elegir ⭐ Más Vendidos, 🏷️ En Oferta, 📦 Todos (A-Z) o por categoría.',
        'Persistencia de modo favorito y sincronización bidireccional entre la tabla y el carrito en tiempo real.',
    ],
    'v2.9.3' => [
        'Personalización visual de la marca: selector de Modo Claro (Light) y Modo Oscuro (Dark).',
        'Paleta de 5 colores de acento corporativo: Índigo, Verde Minimarket, Naranja, Cyan y Rojo.',
        'Soporte para subir o definir el Logo de la Empresa visible en Navbar, Bienvenida y Login.',
        'Diseño dinámico de grilla POS: selector en vivo entre Modo Táctil, Modo Lista Compacta y Estándar.',
        'Nueva pantalla "Acerca de..." con la línea de tiempo interactiva de toda la historia del sistema.',
    ],
    'v2.9.2' => [
        'Autorización directa de clave de supervisor en el modal de cobro para descuentos especiales.',
        'Flujo ágil con validación inmediata y atajo directo en el teclado.',
    ],
    'v2.9.1' => [
        'Supervisión configurable en caja: autorización para anular venta en proceso y eliminar productos.',
        'Descuento máximo en caja por porcentaje (%) configurable desde Ajustes Generales.',
    ],
    'v2.9.0' => [
        'Nueva pantalla de Cotizaciones: crear presupuestos y cargarlos en la caja para cobrar.',
        'Toma de Inventario físico: contar por categoría, ver diferencias y ajustar el stock de una vez.',
        'Al pausar una venta en el POS queda como cotización, visible desde Cotizaciones.',
    ],
    'v2.8.0' => [
        'Comprobante de venta con formato para impresora térmica de 80 mm.',
        'Al vender con crédito interno se imprime un comprobante de fiado con firma del cliente.',
        'El comprobante muestra los datos del local, el desglose de pagos y el vuelto.',
    ],
    'v2.7.0' => [
        'Los avisos del POS ahora son notificaciones (toasts) que no frenan la caja.',
        'Vaciar carrito y pausar venta usan ventanas del sistema, no las del navegador.',
    ],
    'v2.6.0' => [
        'Carrito del POS rediseñado: líneas más claras y botones de cantidad más grandes.',
        'La pantalla de actualización ahora muestra las novedades reales de cada versión.',
    ],
    'v2.5.0' => [
        'Productos pesables: venta por peso leyendo el código de la balanza (PLU).',
        'Ajustes de stock con varios productos y proveedor en un mismo movimiento.',
        'Facturación electrónica (DTE) con selección de proveedor en Configuración.',
    ],
    'v2.4.0' => [
        'Nueva pestaña de Configuración para la balanza de pesaje.',
        'Impresión directa del PDF del DTE al cobrar.',
    ],
    'v2.3.0' => [
        'Canje de vales usando el número de boleta, no solo el código.',
        'Editar productos sin afectar el stock; activar/desactivar productos.',
        'Menú de navegación reorganizado.',
    ],
    'v2.2.0' => [
        'Ticket de Cambio de Mercadería (TC-) con validación de saldo para nuevas compras.',
        'Módulo de Notas de Pedido: acordar cantidad y costo con el proveedor antes de recibir.',
    ],
    'v2.1.0' => [
        'Devoluciones multi-producto en una sola transacción.',
        'Emisión y canje de Vales de Devolución como saldo a favor en tienda.',
    ],
    'v2.0.0' => [
        'Reimpresión de tickets históricos y comprobantes de ventas anteriores.',
        'Abonos de crédito con imputación automática inteligente FIFO venta por venta.',
    ],
    'v1.5.0' => [
        'Centro de Ayuda y Manual de Usuario integrado dentro del sistema.',
        'Guías operativas para aperturas de turno, arqueos y administración.',
    ],
    'v1.4.0' => [
        'Motor de Promociones: descuentos por unidad y ofertas multibuy por volumen (packs).',
        'Validación automática de promociones en tiempo real en la caja registradora.',
    ],
    'v1.3.0' => [
        'Recepción de Compras como asistente en dos fases: multi-ítem y ajuste de precios/costos.',
        'Protección CSRF en los endpoints de inventario y compras.',
    ],
    'v1.2.0' => [
        'Generación de Cierre Z fiscal al cerrar turno con clave de supervisor.',
        'Registro de movimientos de caja (retiro e ingreso de efectivo) desde el POS.',
        'Redirección automática a la caja al iniciar turno.',
    ],
    'v1.1.0' => [
        'Auditoría y fortalecimiento de seguridad: sanitización y escape contra XSS en todas las vistas.',
        'Iconografía local con Font Awesome sin dependencias de internet.',
        'Control estricto de roles (Cajeros con accesos restringidos a módulos administrativos).',
    ],
    'v1.0.0' => [
        'Lanzamiento inicial: base del Punto de Venta (POS) con carrito y catálogo de productos.',
        'Gestión de turnos de caja registradora, apertura, control de efectivo y arqueo.',
        'Control de stock, catálogo de productos y módulo de clientes para ventas a crédito.',
    ],
]);

// Cargar variables desde .env (no versionado) si existe
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

// Configuración de Conexión a MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'minimarketdb');

$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
if ($dbUser === false || $dbPass === false) {
    die("<div style='font-family:sans-serif; padding:20px; color:#c0392b; background:#fadbd8; border-radius:8px; margin:20px;'>
        <h2>⚠️ Faltan credenciales de base de datos</h2>
        <p>Copia <code>.env.example</code> a <code>.env</code> y completa <code>DB_USER</code> y <code>DB_PASS</code> con las credenciales reales.</p>
    </div>");
}
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif; padding:20px; color:#c0392b; background:#fadbd8; border-radius:8px; margin:20px;'>
                <h2>⚠️ Error de conexión a la Base de Datos</h2>
                <p><strong>Detalle:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p>Asegúrate de que el servicio MySQL en Laragon esté encendido y que la base de datos <code>" . DB_NAME . "</code> exista.</p>
            </div>");
        }
    }
    return $pdo;
}

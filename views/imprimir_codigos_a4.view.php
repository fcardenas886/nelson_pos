<!-- Vista de Impresión de Códigos de Barras en Hoja A4 -->
<div class="a4-page-container">
  
  <!-- BARRA DE HERRAMIENTAS Y CONTROLES (OCULTA AL IMPRIMIR) -->
  <aside class="a4-sidebar" id="a4Sidebar">
    
    <div class="a4-sidebar-header">
      <a href="productos.php" class="btn btn-secondary btn-sm" title="Volver al Catálogo">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </a>
      <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-print" style="color: #38bdf8;"></i> Imprimir en A4
      </h2>
    </div>

    <!-- Selector de Modo de Impresión -->
    <div class="a4-section">
      <label class="a4-section-title">MODO DE IMPRESIÓN</label>
      <div class="a4-mode-selector">
        <button type="button" class="a4-mode-btn active" id="btnModoPliego" onclick="cambiarModo('pliego')">
          <i class="fa-solid fa-tags"></i>
          <div>
            <strong>Pliego</strong>
            <small>Stickers / Planchas</small>
          </div>
        </button>
        <button type="button" class="a4-mode-btn" id="btnModoCatalogo" onclick="cambiarModo('catalogo')">
          <i class="fa-solid fa-clipboard-list"></i>
          <div>
            <strong>Carta Mostrador</strong>
            <small>Catálogo escaneable</small>
          </div>
        </button>
      </div>
    </div>

    <!-- Filtro de Origen: Todos vs Solo Códigos Internos -->
    <div class="a4-section">
      <label class="a4-section-title">ORIGEN DEL CÓDIGO</label>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem;">
        <button type="button" class="btn btn-secondary btn-sm active" id="btnFiltroTipoTodos" onclick="setFiltroTipoCodigo('todos')">
          <i class="fa-solid fa-boxes-stacked"></i> Todos
        </button>
        <button type="button" class="btn btn-secondary btn-sm" id="btnFiltroTipoInternos" onclick="setFiltroTipoCodigo('internos')" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.4);">
          <i class="fa-solid fa-star"></i> Solo Internos
        </button>
      </div>
      <small id="txtDescFiltroTipo" style="display: block; color: var(--text-muted); font-size: 0.72rem; margin-top: 0.35rem; line-height: 1.25;">
        Mostrando todos los productos del catálogo.
      </small>
    </div>

    <!-- CONTROLES ESPECÍFICOS: MODO PLIEGO -->
    <div id="panelModoPliego" class="a4-section">
      <label class="a4-section-title">SELECCIÓN DE PRODUCTO</label>
      
      <div style="margin-bottom: 0.65rem;">
        <select id="selectProductoPliego" class="form-control" onchange="seleccionarProductoPliego()" style="font-size: 0.85rem;">
          <option value="">-- Elige un producto --</option>
          <?php foreach ($todosProductos as $prod): ?>
            <option value="<?= $prod['ProductoID'] ?>" 
                    data-nombre="<?= htmlspecialchars($prod['Nombre']) ?>"
                    data-codigo="<?= htmlspecialchars($prod['CodigoBarras'] ?: $prod['CodigoPLU'] ?: '') ?>"
                    data-precio="<?= (int)$prod['PrecioVenta'] ?>"
                    data-cat="<?= htmlspecialchars($prod['Categoria']) ?>"
                    data-interno="<?= (int)$prod['EsCodigoInterno'] ?>"
                    <?= ($preselectedId === (int)$prod['ProductoID']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($prod['Nombre']) ?> (<?= $prod['CodigoBarras'] ?: 'PLU: ' . $prod['CodigoPLU'] ?>) - $<?= number_format($prod['PrecioVenta'], 0, ',', '.') ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div id="btnPliegoMixtoWrap" style="margin-top: 0.45rem; display: none;">
          <button type="button" class="btn btn-secondary btn-sm btn-block" id="btnPliegoMixto" onclick="togglePliegoMixtoInternos()" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.4); font-weight: 600; font-size: 0.76rem;">
            <i class="fa-solid fa-layer-group"></i> Llenar pliego con todos los internos (1 de c/u)
          </button>
        </div>
      </div>

      <label class="a4-section-title">CANTIDAD DE ETIQUETAS</label>
      <div class="a4-cant-chips">
        <button type="button" class="btn btn-secondary btn-sm" onclick="setCantidadPliego(12)">12 (½ H)</button>
        <button type="button" class="btn btn-secondary btn-sm active" id="btnCant24" onclick="setCantidadPliego(24)">24 (Hoja)</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="setCantidadPliego(30)">30 (3x10)</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="setCantidadPliego(48)">48 (2 H)</button>
      </div>
      
      <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 0.4rem;">
        <label style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">Personalizada:</label>
        <input type="number" id="inputCantidadCustom" class="form-control" value="24" min="1" max="120" style="width: 75px; padding: 0.25rem 0.5rem; text-align: center; font-size: 0.85rem;" onchange="actualizarCantidadCustom()">
        <span style="font-size: 0.78rem; color: var(--text-muted);">etiquetas</span>
      </div>
    </div>

    <!-- CONTROLES ESPECÍFICOS: MODO CATÁLOGO / CARTA -->
    <div id="panelModoCatalogo" class="a4-section" style="display: none;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
        <div>
          <label class="a4-section-title" style="margin-bottom: 0.35rem;">CATEGORÍA</label>
          <select id="selectCategoriaCatalogo" class="form-control" onchange="filtrarCategoriaCatalogo()" style="font-size: 0.84rem;">
            <option value="todas">Todas</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['CategoriaID'] ?>" <?= ($preselectedCat === (int)$c['CategoriaID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['Nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="a4-section-title" style="margin-bottom: 0.35rem;">BUSCAR</label>
          <input type="text" id="inputBusquedaCatalogo" class="form-control" placeholder="Filtrar..." oninput="filtrarTextoCatalogo()" style="font-size: 0.84rem;">
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
        <span id="txtTotalItemsCatalogo" style="font-size: 0.76rem; color: var(--text-muted);">0 productos visibles</span>
        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSeleccionarTodos()" style="padding: 0.15rem 0.45rem; font-size: 0.72rem;">
          Invertir selección
        </button>
      </div>

      <div id="listaCheckProductos" class="a4-checklist">
        <!-- Generado dinámicamente con JS -->
      </div>
    </div>

    <!-- CONFIGURACIÓN VISUAL COMÚN -->
    <div class="a4-section">
      <label class="a4-section-title">OPCIONES DE DISEÑO Y COLUMNAS</label>
      
      <div class="a4-opt-grid">
        <label class="a4-checkbox-row">
          <input type="checkbox" id="chkMostrarPrecio" checked onchange="regenerarVistaA4()">
          <span>Mostrar Precio ($)</span>
        </label>
        <label class="a4-checkbox-row">
          <input type="checkbox" id="chkMostrarEmpresa" checked onchange="regenerarVistaA4()">
          <span>Nombre del Local</span>
        </label>
        <label class="a4-checkbox-row" style="grid-column: span 2;">
          <input type="checkbox" id="chkLineasCorte" checked onchange="regenerarVistaA4()">
          <span>Líneas de corte punteadas</span>
        </label>
      </div>

      <div style="margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed rgba(255, 255, 255, 0.1);">
        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; display: block; margin-bottom: 0.35rem;">COLUMNAS POR HOJA</label>
        <div class="a4-cols-chips">
          <button type="button" class="btn btn-secondary btn-sm" id="btnCol2" onclick="setColumnasA4(2)">2 Columnas</button>
          <button type="button" class="btn btn-secondary btn-sm active" id="btnCol3" onclick="setColumnasA4(3)">3 Columnas (Recomendado)</button>
          <button type="button" class="btn btn-secondary btn-sm" id="btnCol4" onclick="setColumnasA4(4)">4 Columnas</button>
        </div>
      </div>
    </div>

    <!-- BOTÓN DE IMPRESIÓN DIRECTA -->
    <div style="margin-top: auto; padding-top: 0.5rem;">
      <button type="button" onclick="imprimirHojaA4()" class="btn btn-primary btn-block a4-btn-print">
        <i class="fa-solid fa-print"></i> IMPRIMIR HOJA A4
      </button>
      <small style="display: block; text-align: center; color: var(--text-muted); font-size: 0.72rem; margin-top: 0.35rem;">
        Atajo: <kbd>Ctrl + P</kbd> · Configura escala 100% y tamaño A4
      </small>
    </div>

  </aside>

  <!-- ÁREA DE PREVISUALIZACIÓN DE LA HOJA A4 (ESTO ES LO QUE SE IMPRIME) -->
  <main class="a4-preview-viewport">
    
    <div class="a4-preview-toolbar">
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <span id="badgePaginasA4" class="badge badge-info" style="font-size: 0.78rem; padding: 0.3rem 0.65rem;">
          <i class="fa-regular fa-file"></i> Previsualización A4 (1 página)
        </span>
      </div>

      <!-- Controles de Zoom para achicar o agrandar la hoja en pantalla -->
      <div class="a4-zoom-toolbar">
        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><i class="fa-solid fa-magnifying-glass"></i> Zoom:</span>
        <button type="button" class="btn btn-secondary btn-sm a4-zoom-btn" id="btnZoom70" onclick="setZoomA4(0.70)">70%</button>
        <button type="button" class="btn btn-secondary btn-sm a4-zoom-btn active" id="btnZoom80" onclick="setZoomA4(0.80)">80%</button>
        <button type="button" class="btn btn-secondary btn-sm a4-zoom-btn" id="btnZoom90" onclick="setZoomA4(0.90)">90%</button>
        <button type="button" class="btn btn-secondary btn-sm a4-zoom-btn" id="btnZoom100" onclick="setZoomA4(1.00)">100%</button>
      </div>
    </div>

    <!-- Contenedor Real de Impresión -->
    <div id="areaImpresionA4" class="a4-sheet" data-cols="3">
      
      <!-- Cabecera de Catálogo (Solo visible en modo catálogo) -->
      <header id="a4HeaderCatalogo" class="a4-catalogo-header" style="display: none;">
        <div class="a4-header-left">
          <h1 id="a4EmpresaNombre"><?= htmlspecialchars($empresaInfo['nombre']) ?></h1>
          <p id="a4CatalogoSubtitulo">Catálogo de Códigos de Barra para Mostrador</p>
        </div>
        <div class="a4-header-right">
          <span id="a4CatalogoFecha"><?= date('d/m/Y') ?></span>
          <small id="a4CatalogoCategoriaTxt">Todas las categorías</small>
        </div>
      </header>

      <!-- Grilla de Etiquetas -->
      <div id="a4GridEtiquetas" class="a4-grid cols-3">
        <!-- Renderizado dinámico vía JS -->
      </div>

    </div>

  </main>

</div>

<!-- INCLUSIÓN DE JSBARCODE OFFLINE -->
<script src="assets/vendor/JsBarcode.all.min.js"></script>

<!-- DATOS DESDE PHP PARA JS -->
<script>
  window.A4_EMPRESA_NOMBRE = <?= json_encode($empresaInfo['nombre']) ?>;
  window.A4_TODOS_PRODUCTOS = <?= json_encode($todosProductos) ?>;
  window.A4_PRESELECTED_ID = <?= (int)$preselectedId ?>;
  window.A4_PRESELECTED_MODO = <?= json_encode($preselectedModo) ?>;
  window.A4_PRESELECTED_SOLO_INTERNOS = <?= (int)$preselectedSoloInternos ?>;
</script>

<style>
/* ============================================================== */
/* ESTILOS DE LA INTERFAZ EN PANTALLA                             */
/* ============================================================== */
:root {
  --a4-zoom: 0.80;
}

.a4-page-container {
  display: flex;
  min-height: calc(100vh - 70px);
  background: #090d16;
  position: relative;
}

.a4-sidebar {
  width: 440px;
  background: #0f172a;
  border-right: 1px solid var(--border-dark);
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex-shrink: 0;
  max-height: calc(100vh - 70px);
  overflow-y: auto;
}

.a4-sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-dark);
  padding-bottom: 0.5rem;
}

.a4-section {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid var(--border-dark);
  border-radius: 9px;
  padding: 0.75rem 0.85rem;
}

.a4-section-title {
  display: block;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #94a3b8;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
}

.a4-mode-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.45rem;
}

.a4-mode-btn {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.55rem 0.65rem;
  background: rgba(15, 23, 42, 0.6);
  border: 2px solid var(--border-dark);
  border-radius: 8px;
  color: #cbd5e1;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
}

.a4-mode-btn i {
  font-size: 1.15rem;
  color: #64748b;
}

.a4-mode-btn strong {
  display: block;
  font-size: 0.84rem;
  color: #fff;
  line-height: 1.2;
}

.a4-mode-btn small {
  display: block;
  font-size: 0.7rem;
  color: #94a3b8;
  line-height: 1.2;
}

.a4-mode-btn.active {
  border-color: var(--primary);
  background: rgba(99, 102, 241, 0.12);
}

.a4-mode-btn.active i {
  color: #818cf8;
}

.a4-cant-chips {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.35rem;
}

.a4-cant-chips .btn.active {
  background: var(--primary) !important;
  color: #fff !important;
  border-color: var(--primary) !important;
}

.a4-cols-chips {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.35rem;
}

.a4-cols-chips .btn.active {
  background: var(--primary) !important;
  color: #fff !important;
  border-color: var(--primary) !important;
}

.a4-opt-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 0.6rem;
}

.a4-checkbox-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8rem;
  color: #e2e8f0;
  cursor: pointer;
  margin: 0;
  user-select: none;
}

.a4-checklist {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--border-dark);
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.7);
  padding: 0.35rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.a4-check-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.3rem 0.45rem;
  border-radius: 6px;
  font-size: 0.78rem;
  color: #e2e8f0;
  cursor: pointer;
  transition: background 0.15s;
}

.a4-check-item:hover {
  background: rgba(255, 255, 255, 0.06);
}

.a4-check-item input {
  margin: 0;
}

.a4-btn-print {
  padding: 0.75rem;
  font-size: 0.95rem;
  font-weight: 800;
  background: #10b981;
  border-color: #10b981;
  letter-spacing: 0.03em;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

.a4-btn-print:hover {
  background: #059669;
  border-color: #059669;
}

/* Área de Previsualización */
.a4-preview-viewport {
  flex: 1;
  padding: 1.25rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.a4-preview-toolbar {
  width: 100%;
  max-width: calc(210mm * var(--a4-zoom, 0.80));
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: max-width 0.15s ease;
}

.a4-zoom-toolbar {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--border-dark);
  padding: 0.18rem 0.4rem;
  border-radius: 6px;
}

.a4-zoom-btn {
  padding: 0.16rem 0.45rem !important;
  font-size: 0.72rem !important;
  line-height: 1.2 !important;
}

.a4-zoom-btn.active {
  background: var(--primary) !important;
  color: #ffffff !important;
  border-color: var(--primary) !important;
}

/* ============================================================== */
/* LA HOJA A4 (SIMULADOR DE PAPEL 210mm x 297mm)                  */
/* ============================================================== */
.a4-sheet {
  width: 210mm;
  min-height: 297mm;
  background: #ffffff;
  color: #000000;
  padding: 8mm 6mm;
  box-sizing: border-box;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
  border-radius: 3px;
  position: relative;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  zoom: var(--a4-zoom, 0.80);
  transform-origin: top center;
  transition: zoom 0.15s ease;
}

/* Cabecera de Catálogo */
.a4-catalogo-header {
  border-bottom: 2px solid #000;
  padding-bottom: 4mm;
  margin-bottom: 5mm;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.a4-catalogo-header h1 {
  font-size: 16pt;
  font-weight: 900;
  margin: 0;
  text-transform: uppercase;
  color: #000;
  line-height: 1.1;
}

.a4-catalogo-header p {
  font-size: 9pt;
  font-weight: 600;
  margin: 1.5mm 0 0 0;
  color: #475569;
}

.a4-header-right {
  text-align: right;
  font-size: 9pt;
  color: #000;
}

.a4-header-right span {
  font-weight: 800;
  display: block;
}

.a4-header-right small {
  font-size: 8pt;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
}

/* Grilla de Etiquetas */
.a4-grid {
  display: grid;
  gap: 2mm;
  width: 100%;
  box-sizing: border-box;
}

.a4-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
.a4-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.a4-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

/* Celda / Etiqueta individual */
.a4-etiqueta-card {
  border: 1px dashed #94a3b8;
  border-radius: 4px;
  padding: 3mm 2.5mm;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  text-align: center;
  background: #ffffff;
  color: #000000;
  page-break-inside: avoid;
  break-inside: avoid;
  min-height: 31mm;
}

.a4-etiqueta-card.sin-borde {
  border: 1px solid transparent;
}

.a4-card-empresa {
  font-size: 6.5pt;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #475569;
  line-height: 1;
  margin-bottom: 1mm;
}

.a4-card-nombre {
  font-size: 8pt;
  font-weight: 800;
  line-height: 1.15;
  text-transform: uppercase;
  color: #0f172a;
  word-break: break-word;
  max-height: 2.3em;
  overflow: hidden;
  margin-bottom: 1.5mm;
}

.a4-card-barcode-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 1mm 0;
}

.a4-card-barcode-wrap svg {
  max-width: 96%;
  height: auto;
  display: block;
}

.a4-card-precio {
  font-size: 11pt;
  font-weight: 900;
  color: #000000;
  line-height: 1;
  margin-top: 1mm;
}

/* ============================================================== */
/* ESTILOS EXACTOS PARA IMPRESIÓN (CTRL + P)                      */
/* ============================================================== */
@media print {
  @page {
    size: A4 portrait;
    margin: 6mm 5mm;
  }

  body {
    background: #ffffff !important;
    color: #000000 !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* Ocultar toda la interfaz del POS (header, footer, sidebar) */
  nav.navbar, footer, #a4Sidebar, .a4-preview-toolbar, #btnToggleAsistenteIA, #ventanaChatIA {
    display: none !important;
    visibility: hidden !important;
  }

  .a4-page-container {
    display: block !important;
    background: #ffffff !important;
    min-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .a4-preview-viewport {
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }

  .a4-sheet {
    width: 100% !important;
    min-height: 0 !important;
    zoom: 1 !important;
    transform: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .a4-etiqueta-card {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }
}
</style>

<!-- ============================================================== -->
<!-- CONTROLADOR JAVASCRIPT DE PREVISUALIZACIÓN Y GENERACIÓN EN VIVO -->
<!-- ============================================================== */ -->
<script>
let modoActual = window.A4_PRESELECTED_MODO || 'pliego';
let columnasActuales = 3;
let cantidadPliego = 24;
let productoSeleccionadoId = window.A4_PRESELECTED_ID || 0;
let productosCatalogoSeleccionados = new Set();
let filtroTipoActual = (window.A4_PRESELECTED_SOLO_INTERNOS === 1) ? 'internos' : 'todos';
let pliegoMixtoActivo = false;

document.addEventListener('DOMContentLoaded', function() {
  initModuloA4();
});

function initModuloA4() {
  setFiltroTipoCodigo(filtroTipoActual, true);
  cambiarModo(modoActual);
}

function setFiltroTipoCodigo(tipo, omitirRegenerar = false) {
  filtroTipoActual = tipo;

  const btnTodos = document.getElementById('btnFiltroTipoTodos');
  const btnInternos = document.getElementById('btnFiltroTipoInternos');
  if (btnTodos) btnTodos.classList.toggle('active', tipo === 'todos');
  if (btnInternos) btnInternos.classList.toggle('active', tipo === 'internos');

  const desc = document.getElementById('txtDescFiltroTipo');
  if (desc) {
    desc.textContent = (tipo === 'internos')
      ? 'Mostrando solo productos con código interno propio (EAN-8, PLU, balanza o creados por el local).'
      : 'Mostrando todos los productos del catálogo.';
  }

  const wrapMixto = document.getElementById('btnPliegoMixtoWrap');
  if (wrapMixto) {
    wrapMixto.style.display = (tipo === 'internos') ? 'block' : 'none';
  }
  if (tipo !== 'internos') {
    pliegoMixtoActivo = false;
    actualizarBotonPliegoMixtoUI();
  }

  const sub = document.getElementById('a4CatalogoSubtitulo');
  if (sub) {
    sub.textContent = (tipo === 'internos')
      ? 'Catálogo de Códigos Internos del Local (EAN-8 / PLU)'
      : 'Catálogo de Códigos de Barra para Mostrador';
  }

  poblarSelectProductoPliego();

  // Actualizar selección de productos en catálogo
  productosCatalogoSeleccionados.clear();
  window.A4_TODOS_PRODUCTOS.forEach(p => {
    if (p.CodigoBarras || p.CodigoPLU) {
      if (tipo === 'internos') {
        if (p.EsCodigoInterno == 1) productosCatalogoSeleccionados.add(p.ProductoID);
      } else {
        productosCatalogoSeleccionados.add(p.ProductoID);
      }
    }
  });

  renderizarChecklistCatalogo();

  if (!omitirRegenerar) {
    regenerarVistaA4();
  }
}

function poblarSelectProductoPliego() {
  const sel = document.getElementById('selectProductoPliego');
  if (!sel) return;

  const valActual = productoSeleccionadoId;
  sel.innerHTML = '<option value="">-- Elige un producto --</option>';

  let lista = window.A4_TODOS_PRODUCTOS;
  if (filtroTipoActual === 'internos') {
    lista = lista.filter(p => p.EsCodigoInterno == 1);
  }

  let existeActual = false;
  lista.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.ProductoID;
    opt.dataset.nombre = p.Nombre;
    opt.dataset.codigo = p.CodigoBarras || p.CodigoPLU || '';
    opt.dataset.precio = p.PrecioVenta;
    opt.dataset.cat = p.Categoria;
    opt.dataset.interno = p.EsCodigoInterno;
    const tipoBadge = (p.EsCodigoInterno == 1) ? '⭐ ' : '';
    opt.textContent = `${tipoBadge}${p.Nombre} (${p.CodigoBarras || 'PLU: ' + p.CodigoPLU}) - $${Number(p.PrecioVenta).toLocaleString('es-CL')}`;

    if (p.ProductoID === valActual) {
      opt.selected = true;
      existeActual = true;
    }
    sel.appendChild(opt);
  });

  if (!existeActual && lista.length > 0) {
    productoSeleccionadoId = lista[0].ProductoID;
    sel.value = productoSeleccionadoId;
  } else if (lista.length === 0) {
    productoSeleccionadoId = 0;
    sel.value = '';
  }
}

function togglePliegoMixtoInternos() {
  pliegoMixtoActivo = !pliegoMixtoActivo;
  actualizarBotonPliegoMixtoUI();
  regenerarVistaA4();
}

function actualizarBotonPliegoMixtoUI() {
  const btn = document.getElementById('btnPliegoMixto');
  const sel = document.getElementById('selectProductoPliego');
  if (!btn) return;
  if (pliegoMixtoActivo) {
    btn.classList.add('active');
    btn.style.background = '#0284c7';
    btn.style.color = '#ffffff';
    btn.style.borderColor = '#38bdf8';
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Pliego Mixto Activo (Todos los internos)';
    if (sel) sel.disabled = true;
  } else {
    btn.classList.remove('active');
    btn.style.background = '';
    btn.style.color = '#38bdf8';
    btn.style.borderColor = 'rgba(56, 189, 248, 0.4)';
    btn.innerHTML = '<i class="fa-solid fa-layer-group"></i> Llenar pliego con todos los internos (1 de c/u)';
    if (sel) sel.disabled = false;
  }
}

function cambiarModo(modo) {
  modoActual = modo;
  const btnPliego = document.getElementById('btnModoPliego');
  const btnCat = document.getElementById('btnModoCatalogo');
  const panelPliego = document.getElementById('panelModoPliego');
  const panelCat = document.getElementById('panelModoCatalogo');
  const headerCat = document.getElementById('a4HeaderCatalogo');

  if (modo === 'pliego') {
    btnPliego.classList.add('active');
    btnCat.classList.remove('active');
    panelPliego.style.display = 'block';
    panelCat.style.display = 'none';
    headerCat.style.display = 'none';
  } else {
    btnCat.classList.add('active');
    btnPliego.classList.remove('active');
    panelCat.style.display = 'block';
    panelPliego.style.display = 'none';
    headerCat.style.display = 'flex';
  }

  regenerarVistaA4();
}

function seleccionarProductoPliego() {
  const sel = document.getElementById('selectProductoPliego');
  productoSeleccionadoId = parseInt(sel.value) || 0;
  regenerarVistaA4();
}

function setCantidadPliego(cant) {
  cantidadPliego = cant;
  document.getElementById('inputCantidadCustom').value = cant;

  document.querySelectorAll('.a4-cant-chips .btn').forEach(b => b.classList.remove('active'));
  if (cant === 24) {
    const b24 = document.getElementById('btnCant24');
    if (b24) b24.classList.add('active');
  }

  regenerarVistaA4();
}

function actualizarCantidadCustom() {
  const input = document.getElementById('inputCantidadCustom');
  let val = parseInt(input.value) || 24;
  if (val < 1) val = 1;
  if (val > 120) val = 120;
  input.value = val;
  cantidadPliego = val;
  regenerarVistaA4();
}

function setColumnasA4(cols) {
  columnasActuales = cols;
  document.getElementById('btnCol2').classList.toggle('active', cols === 2);
  document.getElementById('btnCol3').classList.toggle('active', cols === 3);
  document.getElementById('btnCol4').classList.toggle('active', cols === 4);

  const grid = document.getElementById('a4GridEtiquetas');
  grid.className = `a4-grid cols-${cols}`;
  regenerarVistaA4();
}

/* ---- Gestión de Catálogo ---- */
function renderizarChecklistCatalogo(filtroTexto = '', filtroCat = 'todas') {
  const container = document.getElementById('listaCheckProductos');
  if (!container) return;

  container.innerHTML = '';
  let visibles = 0;

  window.A4_TODOS_PRODUCTOS.forEach(p => {
    const tieneCod = Boolean(p.CodigoBarras || p.CodigoPLU);
    if (!tieneCod) return;

    if (filtroTipoActual === 'internos' && p.EsCodigoInterno != 1) return;
    if (filtroCat !== 'todas' && String(p.CategoriaID) !== String(filtroCat)) return;

    if (filtroTexto) {
      const q = filtroTexto.toLowerCase();
      const match = p.Nombre.toLowerCase().includes(q) || 
                    (p.CodigoBarras && p.CodigoBarras.toLowerCase().includes(q)) ||
                    (p.CodigoPLU && p.CodigoPLU.includes(q));
      if (!match) return;
    }

    visibles++;
    const isChecked = productosCatalogoSeleccionados.has(p.ProductoID);

    const row = document.createElement('label');
    row.className = 'a4-check-item';
    const tagInterno = (p.EsCodigoInterno == 1) ? '<span style="color:#38bdf8; font-size:0.7rem; margin-right:4px;">⭐</span>' : '';
    row.innerHTML = `
      <input type="checkbox" value="${p.ProductoID}" ${isChecked ? 'checked' : ''} onchange="toggleItemCatalogo(${p.ProductoID}, this.checked)">
      <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
        ${tagInterno}${escapeHtml(p.Nombre)}
      </span>
      <strong style="color:var(--success); font-size:0.75rem;">$${Number(p.PrecioVenta).toLocaleString('es-CL')}</strong>
    `;
    container.appendChild(row);
  });

  const txtTot = document.getElementById('txtTotalItemsCatalogo');
  if (txtTot) {
    const sufijo = (filtroTipoActual === 'internos') ? 'códigos internos disponibles' : 'productos disponibles';
    txtTot.textContent = `${visibles} ${sufijo}`;
  }
}

function filtrarCategoriaCatalogo() {
  const cat = document.getElementById('selectCategoriaCatalogo').value;
  const texto = document.getElementById('inputBusquedaCatalogo').value;
  
  // Actualizar subtítulo en la hoja
  const sub = document.getElementById('a4CatalogoCategoriaTxt');
  if (sub) {
    if (cat === 'todas') {
      sub.textContent = 'Todas las categorías';
    } else {
      const sel = document.getElementById('selectCategoriaCatalogo');
      sub.textContent = sel.options[sel.selectedIndex].text;
    }
  }

  renderizarChecklistCatalogo(texto, cat);
  regenerarVistaA4();
}

function filtrarTextoCatalogo() {
  const cat = document.getElementById('selectCategoriaCatalogo').value;
  const texto = document.getElementById('inputBusquedaCatalogo').value;
  renderizarChecklistCatalogo(texto, cat);
}

function toggleItemCatalogo(id, checked) {
  if (checked) {
    productosCatalogoSeleccionados.add(id);
  } else {
    productosCatalogoSeleccionados.delete(id);
  }
  regenerarVistaA4();
}

function toggleSeleccionarTodos() {
  const cat = document.getElementById('selectCategoriaCatalogo').value;
  const texto = document.getElementById('inputBusquedaCatalogo').value.toLowerCase();

  const visibles = window.A4_TODOS_PRODUCTOS.filter(p => {
    if (!p.CodigoBarras && !p.CodigoPLU) return false;
    if (filtroTipoActual === 'internos' && p.EsCodigoInterno != 1) return false;
    if (cat !== 'todas' && String(p.CategoriaID) !== String(cat)) return false;
    if (texto && !p.Nombre.toLowerCase().includes(texto)) return false;
    return true;
  });

  const todosEstan = visibles.every(p => productosCatalogoSeleccionados.has(p.ProductoID));

  visibles.forEach(p => {
    if (todosEstan) {
      productosCatalogoSeleccionados.delete(p.ProductoID);
    } else {
      productosCatalogoSeleccionados.add(p.ProductoID);
    }
  });

  renderizarChecklistCatalogo(texto, cat);
  regenerarVistaA4();
}

/* ---- Generador Principal de la Hoja A4 ---- */
function regenerarVistaA4() {
  const grid = document.getElementById('a4GridEtiquetas');
  grid.innerHTML = '';

  const mostrarPrecio = document.getElementById('chkMostrarPrecio').checked;
  const mostrarEmpresa = document.getElementById('chkMostrarEmpresa').checked;
  const conLineasCorte = document.getElementById('chkLineasCorte').checked;

  let itemsParaRenderizar = [];

  if (modoActual === 'pliego') {
    if (pliegoMixtoActivo) {
      const prodsInternos = window.A4_TODOS_PRODUCTOS.filter(p => p.EsCodigoInterno == 1 && (p.CodigoBarras || p.CodigoPLU));
      if (prodsInternos.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #64748b; font-size: 1rem;">No hay productos con código interno registrados.</div>';
        actualizarBadgePaginas(0);
        return;
      }
      for (let i = 0; i < cantidadPliego; i++) {
        itemsParaRenderizar.push(prodsInternos[i % prodsInternos.length]);
      }
    } else {
      // Repetir el producto seleccionado X veces
      const prod = window.A4_TODOS_PRODUCTOS.find(p => p.ProductoID === productoSeleccionadoId);
      if (!prod) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #64748b; font-size: 1rem;">Selecciona un producto para generar el pliego de etiquetas.</div>';
        actualizarBadgePaginas(0);
        return;
      }

      for (let i = 0; i < cantidadPliego; i++) {
        itemsParaRenderizar.push(prod);
      }
    }
  } else {
    // Catálogo: renderizar productos seleccionados
    const catFiltro = document.getElementById('selectCategoriaCatalogo').value;
    itemsParaRenderizar = window.A4_TODOS_PRODUCTOS.filter(p => {
      if (!productosCatalogoSeleccionados.has(p.ProductoID)) return false;
      if (filtroTipoActual === 'internos' && p.EsCodigoInterno != 1) return false;
      if (catFiltro !== 'todas' && String(p.CategoriaID) !== String(catFiltro)) return false;
      return true;
    });

    if (itemsParaRenderizar.length === 0) {
      const msg = (filtroTipoActual === 'internos') 
        ? 'No hay productos con código interno seleccionados. Marca las casillas en el panel izquierdo.'
        : 'No hay productos seleccionados para el catálogo. Marca las casillas en el panel izquierdo.';
      grid.innerHTML = `<div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #64748b; font-size: 1rem;">${msg}</div>`;
      actualizarBadgePaginas(0);
      return;
    }
  }

  // Generar tarjetas HTML
  itemsParaRenderizar.forEach((item, index) => {
    const card = document.createElement('div');
    card.className = `a4-etiqueta-card ${conLineasCorte ? '' : 'sin-borde'}`;

    const svgId = `barcodeSvg_${index}`;
    const codigo = (item.CodigoBarras || item.CodigoPLU || '').trim();
    const precio = parseInt(item.PrecioVenta || 0);

    let html = '';
    if (mostrarEmpresa) {
      html += `<div class="a4-card-empresa">${escapeHtml(window.A4_EMPRESA_NOMBRE)}</div>`;
    }

    html += `<div class="a4-card-nombre" title="${escapeHtml(item.Nombre)}">${escapeHtml(item.Nombre)}</div>`;
    html += `<div class="a4-card-barcode-wrap"><svg id="${svgId}"></svg></div>`;

    if (mostrarPrecio) {
      if (parseInt(item.EsPrecioVariable) === 1) {
        html += `<div class="a4-card-precio" style="font-size:8pt; color:#2563eb;">PRECIO VARIABLE</div>`;
      } else {
        html += `<div class="a4-card-precio">$ ${Number(precio).toLocaleString('es-CL')}</div>`;
      }
    }

    card.innerHTML = html;
    grid.appendChild(card);

    // Dibujar código de barras en el SVG
    dibujarCodigoBarras(svgId, codigo);
  });

  actualizarBadgePaginas(itemsParaRenderizar.length);
}

function dibujarCodigoBarras(elementId, codigo) {
  const svg = document.getElementById(elementId);
  if (!svg || !codigo) return;

  let formato = "CODE128";
  const soloNumeros = /^\d+$/.test(codigo);
  if (soloNumeros && codigo.length === 8) {
    formato = "EAN8";
  } else if (soloNumeros && codigo.length === 13) {
    formato = "EAN13";
  }

  // Dimensiones según número de columnas
  let barWidth = 1.35;
  let barHeight = 32;
  if (columnasActuales === 2) {
    barWidth = 1.8;
    barHeight = 40;
  } else if (columnasActuales === 4) {
    barWidth = 1.1;
    barHeight = 26;
  }

  try {
    JsBarcode(svg, codigo, {
      format: formato,
      width: barWidth,
      height: barHeight,
      displayValue: true,
      fontSize: 10,
      font: "monospace",
      textMargin: 1,
      margin: 2,
      background: "#ffffff",
      lineColor: "#000000"
    });
  } catch (e) {
    // Fallback a CODE128 genérico
    try {
      JsBarcode(svg, codigo, {
        format: "CODE128",
        width: barWidth,
        height: barHeight,
        displayValue: true,
        fontSize: 10,
        font: "monospace",
        textMargin: 1,
        margin: 2
      });
    } catch (err2) {
      svg.parentElement.innerHTML = `<span style="font-size:8pt; font-family:monospace; font-weight:bold;">${escapeHtml(codigo)}</span>`;
    }
  }
}

function actualizarBadgePaginas(totalItems) {
  const badge = document.getElementById('badgePaginasA4');
  if (!badge) return;

  if (totalItems === 0) {
    badge.innerHTML = '<i class="fa-regular fa-file"></i> 0 etiquetas';
    return;
  }

  // Estimación aproximada de items por página según columnas
  const itemsPorPagina = columnasActuales * 8; // ~24 en 3 cols, ~16 en 2 cols, ~32 en 4 cols
  const pags = Math.ceil(totalItems / itemsPorPagina);
  badge.innerHTML = `<i class="fa-regular fa-file"></i> ${totalItems} etiquetas (aprox. ${pags} ${pags === 1 ? 'página A4' : 'páginas A4'})`;
}

function setZoomA4(val) {
  document.documentElement.style.setProperty('--a4-zoom', val);
  const toolbar = document.querySelector('.a4-preview-toolbar');
  if (toolbar) toolbar.style.maxWidth = `calc(210mm * ${val})`;
  document.querySelectorAll('.a4-zoom-btn').forEach(b => b.classList.remove('active'));
  const btn = document.getElementById('btnZoom' + Math.round(val * 100));
  if (btn) btn.classList.add('active');
}

function imprimirHojaA4() {
  window.print();
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
</script>

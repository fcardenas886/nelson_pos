<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
      <i class="fa-solid fa-boxes-packing" style="color: var(--primary);"></i> Kardex de Movimientos de Inventario
    </h1>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
      Trazabilidad integral de entradas, salidas, ventas, compras, ajustes de stock y saldo inicial de apertura.
    </p>
  </div>

  <div style="display: flex; gap: 0.5rem; align-items: center;">
    <?php 
      $exportQuery = $_GET;
      $exportQuery['export'] = 'csv';
      $exportUrl = 'kardex.php?' . http_build_query($exportQuery);
    ?>
    <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem;" title="Descargar reporte en formato Excel CSV">
      <i class="fa-solid fa-file-excel" style="color: var(--success);"></i> Exportar a Excel
    </a>
    <?php if ($productoID > 0 || !empty($tipoFiltro) || !empty($fechaDesde) || !empty($fechaHasta) || !empty($searchQ)): ?>
      <a href="kardex.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #f87171;" title="Quitar todos los filtros aplicados">
        <i class="fa-solid fa-filter-circle-xmark"></i> Quitar Filtros
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- ============================================================== -->
<!-- BARRA PRINCIPAL DE BÚSQUEDA Y FILTROS -->
<!-- ============================================================== -->
<div class="table-card" style="margin-bottom: 1.5rem; padding: 1.25rem; background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 12px;">
  <form method="GET" action="kardex.php" id="formFiltrosKardex">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: flex-end;">
      
      <!-- Buscador de Producto -->
      <div style="grid-column: span 2; min-width: 280px; position: relative;">
        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem;">
          <i class="fa-solid fa-magnifying-glass" style="color: var(--primary);"></i> Buscar y Filtrar por Producto
        </label>
        
        <div style="position: relative;">
          <input type="text" 
                 id="inputBuscarProducto" 
                 class="form-control" 
                 placeholder="Escribe nombre, código de barras o PLU..." 
                 value="<?= $productoSeleccionado ? htmlspecialchars($productoSeleccionado['Nombre']) : htmlspecialchars($searchQ) ?>"
                 autocomplete="off"
                 style="padding-left: 2.25rem; font-size: 0.9rem; font-weight: 500;"
                 onfocus="mostrarDropdownProductos()"
                 oninput="filtrarDropdownProductos(this.value)">
          <i class="fa-solid fa-barcode" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
          
          <?php if ($productoID > 0 || !empty($searchQ)): ?>
            <a href="kardex.php" style="position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.9rem;" title="Limpiar producto">
              <i class="fa-solid fa-xmark"></i>
            </a>
          <?php endif; ?>
        </div>

        <input type="hidden" name="producto_id" id="hiddenProductoId" value="<?= (int)$productoID ?>">
        <input type="hidden" name="q" id="hiddenSearchQ" value="<?= htmlspecialchars($searchQ) ?>">

        <!-- Dropdown interactivo con resultados de productos -->
        <div id="dropdownResultadosProductos" 
             style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #1e293b; border: 1px solid #334155; border-radius: 8px; max-height: 280px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
          
          <div onclick="seleccionarProductoKardex(0, '')" 
               style="padding: 0.65rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 0.5rem; color: #38bdf8; font-weight: 600;">
            <i class="fa-solid fa-list"></i> -- Mostrar todos los productos --
          </div>

          <?php foreach ($productosList as $p): ?>
            <?php 
              $codStr = $p['CodigoBarras'] ?: ($p['CodigoPLU'] ? 'PLU:'.$p['CodigoPLU'] : '');
              $stockNum = (float)$p['Stock'];
            ?>
            <div class="opcion-prod-item"
                 data-id="<?= $p['ProductoID'] ?>"
                 data-nombre="<?= htmlspecialchars(mb_strtolower($p['Nombre'])) ?>"
                 data-codigo="<?= htmlspecialchars(mb_strtolower($codStr)) ?>"
                 data-display="<?= htmlspecialchars($p['Nombre']) ?>"
                 onclick="seleccionarProductoKardex(<?= $p['ProductoID'] ?>, '<?= addslashes($p['Nombre']) ?>')"
                 style="padding: 0.65rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center; transition: background 0.15s ease;">
              <div>
                <strong style="color: #fff; font-size: 0.88rem; display: block;"><?= htmlspecialchars($p['Nombre']) ?></strong>
                <small style="color: var(--text-muted); font-size: 0.75rem;">
                  <?= htmlspecialchars($p['Categoria']) ?>
                  <?php if ($codStr): ?>
                    · <code><?= htmlspecialchars($codStr) ?></code>
                  <?php endif; ?>
                </small>
              </div>
              <span class="badge <?= $stockNum > 0 ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.78rem;">
                Stock: <?= $stockNum ?> u
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Filtro Tipo de Transacción -->
      <div>
        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem;">
          <i class="fa-solid fa-tag"></i> Tipo de Movimiento
        </label>
        <select name="tipo" class="form-control" onchange="document.getElementById('formFiltrosKardex').submit()" style="font-size: 0.88rem;">
          <option value="todos" <?= $tipoFiltro === '' || $tipoFiltro === 'todos' ? 'selected' : '' ?>>Todos los movimientos</option>
          <option value="INICIAL" <?= $tipoFiltro === 'INICIAL' ? 'selected' : '' ?>>📦 Stock Inicial (Apertura)</option>
          <option value="VENTA" <?= $tipoFiltro === 'VENTA' ? 'selected' : '' ?>>🛒 Ventas</option>
          <option value="COMPRA" <?= $tipoFiltro === 'COMPRA' ? 'selected' : '' ?>>📥 Compras (Recepción)</option>
          <option value="AJUSTE" <?= $tipoFiltro === 'AJUSTE' ? 'selected' : '' ?>>⚖️ Ajustes de Stock (Todos)</option>
          <option value="AJUSTE_ENTRADA" <?= $tipoFiltro === 'AJUSTE_ENTRADA' ? 'selected' : '' ?>>➕ Ajuste Entrada</option>
          <option value="AJUSTE_SALIDA" <?= $tipoFiltro === 'AJUSTE_SALIDA' ? 'selected' : '' ?>>➖ Ajuste Salida (Mermas)</option>
          <option value="ANULACION_VENTA" <?= $tipoFiltro === 'ANULACION_VENTA' ? 'selected' : '' ?>>↩️ Anulación de Venta</option>
          <option value="DEVOLUCION_VENTA" <?= $tipoFiltro === 'DEVOLUCION_VENTA' ? 'selected' : '' ?>>🔄 Devolución de Venta</option>
        </select>
      </div>

      <!-- Filtro Fecha Desde -->
      <div>
        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem;">
          <i class="fa-regular fa-calendar"></i> Fecha Desde
        </label>
        <input type="date" name="desde" value="<?= htmlspecialchars($fechaDesde) ?>" class="form-control" style="font-size: 0.85rem;">
      </div>

      <!-- Filtro Fecha Hasta -->
      <div>
        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem;">
          <i class="fa-regular fa-calendar"></i> Fecha Hasta
        </label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($fechaHasta) ?>" class="form-control" style="font-size: 0.85rem;">
      </div>

      <!-- Botón Filtrar -->
      <div>
        <button type="submit" class="btn btn-primary" style="width: 100%; height: 38px; display: flex; align-items: center; justify-content: center; gap: 0.4rem; font-size: 0.88rem; font-weight: 600;">
          <i class="fa-solid fa-filter"></i> Aplicar Filtros
        </button>
      </div>

    </div>
  </form>
</div>

<!-- ============================================================== -->
<!-- TARJETA RESUMEN DEL PRODUCTO (SI ESTÁ SELECCIONADO) -->
<!-- ============================================================== -->
<?php if ($productoSeleccionado): ?>
  <?php 
    $codProdSel = $productoSeleccionado['CodigoBarras'] ?: ($productoSeleccionado['CodigoPLU'] ? 'PLU:'.$productoSeleccionado['CodigoPLU'] : 'S/C');
  ?>
  <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95)); border: 1px solid var(--border-dark); border-left: 4px solid var(--primary); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
      <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
          <span class="badge badge-secondary" style="font-size: 0.75rem;"><?= htmlspecialchars($productoSeleccionado['Categoria']) ?></span>
          <code style="font-size: 0.82rem; color: #38bdf8; background: rgba(56, 189, 248, 0.1); padding: 0.2rem 0.5rem; border-radius: 4px;"><?= htmlspecialchars($codProdSel) ?></code>
          <?php if (!empty($productoSeleccionado['EsPesable'])): ?>
            <span class="badge badge-info" style="font-size: 0.72rem;">Pesable</span>
          <?php endif; ?>
        </div>
        <h2 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin: 0;">
          <?= htmlspecialchars($productoSeleccionado['Nombre']) ?>
        </h2>
      </div>

      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <div style="text-align: right;">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block;">Costo Compra</small>
          <strong style="color: #fbbf24; font-family: monospace; font-size: 0.95rem;"><?= formatCLP($kpiProducto['CostoUnitario']) ?></strong>
        </div>
        <div style="height: 25px; width: 1px; background: var(--border-dark);"></div>
        <div style="text-align: right;">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block;">Precio Venta</small>
          <strong style="color: var(--success); font-family: monospace; font-size: 0.95rem;"><?= formatCLP($kpiProducto['PrecioVenta']) ?></strong>
        </div>
      </div>
    </div>

    <!-- Mini KPIs del Producto -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
      <div style="background: rgba(15, 23, 42, 0.6); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <div style="color: #38bdf8; font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
          <i class="fa-solid fa-box-open"></i> Stock Inicial
        </div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #fff; margin-top: 0.25rem;">
          <?= (float)$kpiProducto['StockInicial'] ?> u
        </div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Apertura en catálogo</small>
      </div>

      <div style="background: rgba(15, 23, 42, 0.6); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <div style="color: var(--success); font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
          <i class="fa-solid fa-arrow-down-long"></i> Total Entradas
        </div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #34d399; margin-top: 0.25rem;">
          +<?= (float)$kpiProducto['TotalEntradas'] ?> u
        </div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Compras y ajustes (+)</small>
      </div>

      <div style="background: rgba(15, 23, 42, 0.6); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <div style="color: var(--danger); font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
          <i class="fa-solid fa-arrow-up-long"></i> Total Salidas
        </div>
        <div style="font-size: 1.35rem; font-weight: 800; color: #f87171; margin-top: 0.25rem;">
          -<?= (float)$kpiProducto['TotalSalidas'] ?> u
        </div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Ventas y mermas (-)</small>
      </div>

      <div style="background: rgba(16, 185, 129, 0.1); padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
        <div style="color: #10b981; font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; gap: 0.35rem;">
          <i class="fa-solid fa-calculator"></i> Stock Saldo Actual
        </div>
        <div style="font-size: 1.45rem; font-weight: 900; color: #10b981; margin-top: 0.25rem;">
          <?= (float)$kpiProducto['StockActual'] ?> u
        </div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Físico en tienda</small>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ============================================================== -->
<!-- TABLA PRINCIPAL DE MOVIMIENTOS DE KARDEX -->
<!-- ============================================================== -->
<div class="table-card">
  <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-timeline" style="color: var(--primary);"></i> Historial de Movimientos de Stock
      </h2>
      <span style="color: var(--text-muted); font-size: 0.8rem;" id="lblTotalRegistros">
        <?= count($kardexList) ?> movimientos registrados
      </span>
    </div>

    <!-- Buscador en tiempo real de la tabla visible -->
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <div style="position: relative;">
        <input type="text" 
               id="buscadorKardexLive" 
               oninput="filtrarKardexEnVivo()" 
               class="form-control" 
               placeholder="Filtrar en tabla (texto o código)..." 
               style="padding: 0.4rem 0.75rem 0.4rem 2rem; font-size: 0.82rem; width: 230px;">
        <i class="fa-solid fa-filter" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);"></i>
      </div>
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" id="tablaKardex">
      <thead>
        <tr>
          <th style="width: 75px;">N° Mov.</th>
          <th style="min-width: 130px;">Fecha / Hora</th>
          <th style="min-width: 220px;">Producto</th>
          <th style="min-width: 150px; text-align: center;">Tipo Transacción</th>
          <th style="text-align: center; width: 100px;">Entrada</th>
          <th style="text-align: center; width: 100px;">Salida</th>
          <th style="text-align: center; width: 110px; color: #38bdf8;">Saldo Stock</th>
          <th style="text-align: right; width: 110px;">Valor Unit.</th>
          <th style="min-width: 170px;">Referencia / Detalle</th>
        </tr>
      </thead>
      <tbody id="tbodyKardex">
        <?php if (empty($kardexList)): ?>
          <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem;">No se encontraron registros en el Kardex para los filtros aplicados.</td></tr>
        <?php else: ?>
          <?php foreach ($kardexList as $k): ?>
            <?php 
              $tipo = $k['TipoTransaccion'];
              $cod = $k['CodigoBarras'] ?: ($k['CodigoPLU'] ? 'PLU:'.$k['CodigoPLU'] : '');
              
              // Badge de Tipo de Transacción
              $badgeClass = 'badge-secondary';
              $badgeIcon = 'fa-arrow-right-arrow-left';
              $badgeLabel = $tipo;

              if ($tipo === 'INICIAL') {
                $badgeClass = 'badge-info';
                $badgeIcon = 'fa-box-open';
                $badgeLabel = 'STOCK INICIAL';
              } elseif ($tipo === 'VENTA') {
                $badgeClass = 'badge-danger';
                $badgeIcon = 'fa-cart-shopping';
                $badgeLabel = 'VENTA';
              } elseif ($tipo === 'COMPRA') {
                $badgeClass = 'badge-success';
                $badgeIcon = 'fa-truck-ramp-box';
                $badgeLabel = 'COMPRA';
              } elseif ($tipo === 'AJUSTE_ENTRADA') {
                $badgeClass = 'badge-success';
                $badgeIcon = 'fa-plus';
                $badgeLabel = 'AJUSTE ENTRADA';
              } elseif ($tipo === 'AJUSTE_SALIDA') {
                $badgeClass = 'badge-warning';
                $badgeIcon = 'fa-minus';
                $badgeLabel = 'AJUSTE SALIDA (MERMA)';
              } elseif ($tipo === 'ANULACION_VENTA') {
                $badgeClass = 'badge-info';
                $badgeIcon = 'fa-rotate-left';
                $badgeLabel = 'ANULACIÓN VENTA';
              } elseif ($tipo === 'DEVOLUCION_VENTA') {
                $badgeClass = 'badge-info';
                $badgeIcon = 'fa-arrow-rotate-left';
                $badgeLabel = 'DEVOLUCIÓN VENTA';
              }

              // Detalle o referencia
              $refHtml = '<span style="color: var(--text-muted); font-size: 0.8rem;">-</span>';
              if ($tipo === 'VENTA' && $k['VentaID']) {
                $refHtml = '<span style="font-size: 0.82rem; color: #f87171;"><i class="fa-solid fa-receipt"></i> Venta #' . (int)$k['VentaID'] . ($k['Folio'] ? ' (' . htmlspecialchars($k['TipoDocumento'] . ' ' . $k['Folio']) . ')' : '') . '</span>';
              } elseif ($tipo === 'COMPRA' && $k['CompraID']) {
                $refHtml = '<span style="font-size: 0.82rem; color: #34d399;"><i class="fa-solid fa-file-invoice"></i> Compra #' . (int)$k['CompraID'] . ($k['NumeroDocumento'] ? ' (' . htmlspecialchars($k['NumeroDocumento']) . ')' : '') . '</span>';
              } elseif (str_contains($tipo, 'AJUSTE') && $k['AjusteStockID']) {
                $motivoTxt = $k['MotivoAjuste'] ? ': ' . htmlspecialchars($k['MotivoAjuste']) : '';
                $refHtml = '<span style="font-size: 0.82rem; color: #fbbf24;"><i class="fa-solid fa-sliders"></i> Ajuste #' . (int)$k['AjusteStockID'] . $motivoTxt . '</span>';
              } elseif ($tipo === 'INICIAL') {
                $refHtml = '<span style="font-size: 0.82rem; color: #38bdf8;"><i class="fa-solid fa-flag-checkered"></i> Apertura de Catálogo</span>';
              }
            ?>
            <tr class="fila-kardex"
                data-producto="<?= htmlspecialchars(mb_strtolower($k['ProductoName'])) ?>"
                data-codigo="<?= htmlspecialchars(mb_strtolower($cod)) ?>"
                data-tipo="<?= htmlspecialchars(mb_strtolower($tipo)) ?>"
                data-ref="<?= htmlspecialchars(mb_strtolower(strip_tags($refHtml))) ?>"
                style="<?= $tipo === 'INICIAL' ? 'background: rgba(56, 189, 248, 0.03);' : '' ?>">
              
              <td style="color: var(--text-muted); font-size: 0.82rem; font-family: monospace;">
                #<?= $k['KardexID'] ?>
              </td>

              <td style="color: var(--text-muted); font-size: 0.83rem; white-space: nowrap;">
                <?= date('d/m/Y H:i', strtotime($k['FechaMovimiento'])) ?>
              </td>

              <td>
                <strong style="color: #fff; font-size: 0.88rem; display: block;">
                  <?= htmlspecialchars($k['ProductoName']) ?>
                </strong>
                <small style="color: var(--text-muted); font-size: 0.75rem;">
                  <?= htmlspecialchars($k['Categoria']) ?>
                  <?php if ($cod): ?>
                    · <code><?= htmlspecialchars($cod) ?></code>
                  <?php endif; ?>
                </small>
              </td>

              <td style="text-align: center;">
                <span class="badge <?= $badgeClass ?>" style="font-size: 0.75rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                  <i class="fa-solid <?= $badgeIcon ?>" style="font-size: 0.7rem;"></i> <?= $badgeLabel ?>
                </span>
              </td>

              <td style="text-align: center;">
                <?php if ((float)$k['CantidadEntrada'] > 0): ?>
                  <span style="color: var(--success); font-weight: 700; font-size: 0.9rem;">
                    +<?= (float)$k['CantidadEntrada'] ?>
                  </span>
                <?php else: ?>
                  <span style="color: var(--text-muted);">-</span>
                <?php endif; ?>
              </td>

              <td style="text-align: center;">
                <?php if ((float)$k['CantidadSalida'] > 0): ?>
                  <span style="color: var(--danger); font-weight: 700; font-size: 0.9rem;">
                    -<?= (float)$k['CantidadSalida'] ?>
                  </span>
                <?php else: ?>
                  <span style="color: var(--text-muted);">-</span>
                <?php endif; ?>
              </td>

              <td style="text-align: center;">
                <strong style="color: #38bdf8; font-size: 0.95rem; font-family: monospace;">
                  <?= (float)$k['StockSaldo'] ?>
                </strong>
              </td>

              <td style="text-align: right; font-family: monospace; font-size: 0.85rem; color: #fbbf24;">
                <?= formatCLP($k['ValorUnitario']) ?>
              </td>

              <td>
                <?= $refHtml ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================== -->
<!-- JAVASCRIPT: BÚSQUEDA INTERACTIVA Y FILTROS -->
<!-- ============================================================== -->
<script>
function mostrarDropdownProductos() {
  const dd = document.getElementById('dropdownResultadosProductos');
  if (dd) dd.style.display = 'block';
}

function filtrarDropdownProductos(texto) {
  const q = texto.toLowerCase().trim();
  const items = document.querySelectorAll('.opcion-prod-item');
  const dd = document.getElementById('dropdownResultadosProductos');
  if (dd) dd.style.display = 'block';

  items.forEach(el => {
    const nombre = el.getAttribute('data-nombre') || '';
    const codigo = el.getAttribute('data-codigo') || '';
    if (!q || nombre.includes(q) || codigo.includes(q)) {
      el.style.display = 'flex';
    } else {
      el.style.display = 'none';
    }
  });
}

function seleccionarProductoKardex(id, nombre) {
  const hiddenId = document.getElementById('hiddenProductoId');
  const hiddenQ = document.getElementById('hiddenSearchQ');
  const input = document.getElementById('inputBuscarProducto');
  
  if (hiddenId) hiddenId.value = id;
  if (hiddenQ) hiddenQ.value = '';
  if (input) input.value = nombre;
  
  document.getElementById('formFiltrosKardex').submit();
}

// Cerrar dropdown al hacer clic afuera
document.addEventListener('click', function(e) {
  const contenedor = document.getElementById('inputBuscarProducto')?.parentElement?.parentElement;
  const dropdown = document.getElementById('dropdownResultadosProductos');
  if (contenedor && dropdown && !contenedor.contains(e.target)) {
    dropdown.style.display = 'none';
  }
});

// Filtro instantáneo sobre la tabla visible
function filtrarKardexEnVivo() {
  const q = (document.getElementById('buscadorKardexLive')?.value || '').toLowerCase().trim();
  const filas = document.querySelectorAll('.fila-kardex');
  let countVisible = 0;

  filas.forEach(tr => {
    const prod = tr.getAttribute('data-producto') || '';
    const cod = tr.getAttribute('data-codigo') || '';
    const tipo = tr.getAttribute('data-tipo') || '';
    const ref = tr.getAttribute('data-ref') || '';

    if (!q || prod.includes(q) || cod.includes(q) || tipo.includes(q) || ref.includes(q)) {
      tr.style.display = '';
      countVisible++;
    } else {
      tr.style.display = 'none';
    }
  });

  const lbl = document.getElementById('lblTotalRegistros');
  if (lbl) {
    lbl.textContent = countVisible + ' movimientos visibles';
  }
}
</script>

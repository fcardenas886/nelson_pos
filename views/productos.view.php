<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Catálogo de Productos</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Gestión de precios, stock mínimo y códigos de barra</p>
  </div>
  <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
    <a href="imprimir_codigos_a4.php?solo_internos=1" class="btn btn-secondary" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.4); font-weight: 600;" title="Imprimir hoja A4 con solo los códigos internos del local (EAN-8 / PLU)">
      <i class="fa-solid fa-star"></i> Códigos Internos A4
    </a>
    <a href="imprimir_codigos_a4.php" class="btn btn-secondary" style="color: #94a3b8; border-color: rgba(148, 163, 184, 0.35); font-weight: 600;" title="Imprimir hoja A4 (Pliego de etiquetas o Carta de mostrador)">
      <i class="fa-solid fa-print"></i> Todos los Códigos A4
    </a>
    <a href="actualizar_precios.php" class="btn btn-secondary" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.35); font-weight: 600;">
      <i class="fa-solid fa-bolt"></i> Actualizador Rápido
    </a>
    <button onclick="abrirNuevoModal()" class="btn btn-primary">
      <i class="fa-solid fa-plus"></i> Nuevo Producto
    </button>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div class="table-card">
  <div class="table-header">
    <form method="GET" action="productos.php" style="display: flex; gap: 0.5rem; width: 100%; max-width: 400px;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Buscar por nombre o código..." style="padding: 0.5rem 0.85rem;">
      <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem;"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <div style="color: var(--text-muted); font-size: 0.85rem;">Total: <?= count($productos) ?> productos</div>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nombre Producto</th>
        <th>Categoría</th>
        <th>Stock</th>
        <th>Precio Venta</th>
        <th>Estado Stock</th>
        <th style="text-align: center; width: 140px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($productos)): ?>
        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay productos registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($productos as $p): ?>
          <tr style="<?= !$p['Activo'] ? 'opacity: 0.5; background: rgba(255,255,255,0.02);' : '' ?>">
            <td><code><?= htmlspecialchars($p['CodigoBarras'] ?: 'SIN CÓDIGO') ?></code></td>
            <td style="font-weight: 600; color: #fff;">
              <?= htmlspecialchars($p['Nombre']) ?>
              <?php if (!empty($p['EsPrecioVariable'])): ?>
                <span class="badge badge-info" style="font-size: 0.65rem; padding: 0.15rem 0.35rem; margin-left: 0.3rem; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);" title="El precio se solicita en el POS al momento de vender">
                  <i class="fa-solid fa-tag"></i> Variable
                </span>
              <?php endif; ?>
              <?php if (!empty($p['EsPesable'])): ?>
                <span class="badge badge-warning" style="font-size: 0.65rem; padding: 0.15rem 0.35rem; margin-left: 0.3rem;" title="Balanza Kg">
                  <i class="fa-solid fa-weight-scale"></i> Kg
                </span>
              <?php endif; ?>
              <?php if (!$p['Activo']): ?>
                <span class="badge badge-danger" style="font-size: 0.65rem; padding: 0.15rem 0.3rem; margin-left: 0.4rem;">INACTIVO</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['Categoria'] ?: 'General') ?></td>
            <td style="font-weight: 600;"><?= $p['Stock'] ?></td>
            <td style="font-weight: 700; color: var(--success);">
              <?php if (!empty($p['EsPrecioVariable'])): ?>
                <span style="color: #60a5fa; font-size: 0.85rem;" title="Precio fijado en mostrador al vender">
                  <?= $p['PrecioVenta'] > 0 ? formatCLP($p['PrecioVenta']) . ' <small>(sug.)</small>' : '<i class="fa-solid fa-hand-holding-dollar"></i> Variable' ?>
                </span>
              <?php else: ?>
                <?= formatCLP($p['PrecioVenta']) ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$p['Activo']): ?>
                <span class="badge badge-secondary">Inactivo</span>
              <?php elseif ($p['Stock'] <= 0): ?>
                <span class="badge badge-danger">Sin Stock</span>
              <?php elseif ($p['Stock'] <= $p['StockMinimo']): ?>
                <span class="badge badge-warning">Bajo Stock</span>
              <?php else: ?>
                <span class="badge badge-success">OK</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">
              <div style="display: flex; gap: 0.4rem; justify-content: center;">
                <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" 
                        onclick='abrirEditarModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Editar producto">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.8rem; position: relative;" 
                        onclick='abrirModalCodigos(<?= (int)$p['ProductoID'] ?>, <?= json_encode($p['Nombre'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($p['CodigoBarras'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                        title="Gestionar códigos de barra alternativos">
                  <i class="fa-solid fa-barcode"></i>
                  <?php if (!empty($p['TotalCodigosAlt'])): ?>
                    <span style="background: var(--primary); color: #fff; font-size: 0.65rem; padding: 0.1rem 0.35rem; border-radius: 10px; font-weight: bold; margin-left: 0.15rem;">+<?= (int)$p['TotalCodigosAlt'] ?></span>
                  <?php endif; ?>
                </button>
                <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.8rem; color: #10b981;" 
                        onclick='abrirModalImprimirEtiqueta(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                        title="Ver / Imprimir etiqueta de código de barras">
                  <i class="fa-solid fa-print"></i>
                </button>
                <form method="POST" action="productos.php" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle_activo">
                  <input type="hidden" name="producto_id" value="<?= $p['ProductoID'] ?>">
                  <?php if ($p['Activo']): ?>
                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" title="Desactivar">
                      <i class="fa-solid fa-ban"></i>
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" title="Activar">
                      <i class="fa-solid fa-check"></i>
                    </button>
                  <?php endif; ?>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Nuevo/Editar Producto -->
<div id="productModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 520px; max-width: 95vw; max-height: 92vh; overflow-y: auto; padding: 1.4rem 1.6rem; box-shadow: var(--shadow-lg);">
    <h2 id="modalTitle" style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.9rem;">Agregar Nuevo Producto</h2>
    
    <form method="POST" action="productos.php" style="display: flex; flex-direction: column; gap: 0.75rem;">
      <?= csrfField() ?>
      <input type="hidden" name="producto_id" id="prodIdInput" value="">
      
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin: 0;">CÓDIGO DE BARRAS</label>
          <button type="button" id="btnGenEAN8" onclick="autogenerarCodigoEAN8()" class="btn btn-secondary" style="padding: 0.2rem 0.6rem; font-size: 0.72rem; color: #818cf8; border-color: rgba(129, 140, 248, 0.3);" title="Generar código de barras interno EAN-8 oficial (8 dígitos)">
            <i class="fa-solid fa-bolt"></i> Generar EAN-8
          </button>
        </div>
        <input type="text" name="codigo_barras" id="prodCodigoInput" class="form-control" placeholder="Ej: 780123456789 o dejar vacío para autogenerar">
        <small style="color: var(--text-muted); font-size: 0.72rem; margin-top: 0.2rem; display: block;">
          Vacío = se asigna un EAN-8 interno al guardar.
        </small>
      </div>
      <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 0.75rem;">
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NOMBRE PRODUCTO *</label>
        <input type="text" name="nombre" id="prodNombreInput" class="form-control" required placeholder="Ej: Coca Cola 1.5L">
      </div>
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CATEGORÍA *</label>
        <select name="categoria_id" id="prodCategoriaSelect" class="form-control" required>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['CategoriaID'] ?>"><?= htmlspecialchars($cat['Nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PRECIO VENTA ($) *</label>
          <input type="number" name="precio_venta" id="prodPrecioVentaInput" class="form-control" required placeholder="1500">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COSTO COMPRA ($)</label>
          <input type="number" name="costo_compra" id="prodCostoCompraInput" class="form-control" placeholder="1000">
        </div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;"><span id="prodStockLabel">STOCK INICIAL</span></label>
          <input type="number" step="0.001" name="stock" id="prodStockInput" class="form-control" value="0">
          <small id="prodStockHint" style="display: none; color: var(--text-muted); font-size: 0.72rem;">El stock solo cambia por Compras, ventas o Ajustes de Stock.</small>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">STOCK MÍNIMO</label>
          <input type="number" step="0.001" name="stock_minimo" id="prodStockMinimoInput" class="form-control" value="0">
        </div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.25rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <input type="checkbox" name="es_pesable" id="prodEsPesableInput" value="1" onchange="togglePluField(true)" style="width: 18px; height: 18px; cursor: pointer;">
          <label for="prodEsPesableInput" style="font-size: 0.85rem; font-weight: 600; cursor: pointer; user-select: none;">Es Pesable (Balanza)</label>
        </div>
        <div id="divProdPLU" style="display: none;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CÓDIGO PLU (4 DÍG.)</label>
          <div style="display: flex; gap: 0.4rem;">
            <input type="text" inputmode="numeric" name="codigo_plu" id="prodCodigoPLUInput" class="form-control" placeholder="Vacío = auto" maxlength="4" pattern="[0-9]{4}" title="4 dígitos numéricos" style="flex: 1; min-width: 0;">
            <button type="button" onclick="autogenerarCodigoPLU()" class="btn btn-secondary" style="padding: 0 0.7rem; color: #818cf8; border-color: rgba(129, 140, 248, 0.4); white-space: nowrap;" title="<?= $modoBalanzaVista === 'manual' ? 'ID del producto × 100' : 'ID del producto con 4 dígitos' ?>">
              <i class="fa-solid fa-bolt"></i> Generar
            </button>
          </div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.15rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <input type="checkbox" name="es_precio_variable" id="prodEsPrecioVariableInput" value="1" onchange="togglePrecioVariableField()" style="width: 18px; height: 18px; cursor: pointer;">
          <label for="prodEsPrecioVariableInput" style="font-size: 0.85rem; font-weight: 600; cursor: pointer; user-select: none; color: #60a5fa;">
            <i class="fa-solid fa-tag"></i> Precio Variable / Abierto (Pedir precio en caja)
          </label>
        </div>
        <small id="prodPrecioVarHint" style="display: none; color: #93c5fd; font-size: 0.75rem; padding-left: 1.6rem;">
          <i class="fa-solid fa-circle-info"></i> Al vender este producto (ej. plantas, flores), el POS solicitará el precio con una ventana rápida. El precio de arriba puede ser $0 o sugerido.
        </small>
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <input type="checkbox" name="activo" id="prodActivoInput" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
        <label for="prodActivoInput" style="font-size: 0.85rem; font-weight: 600; cursor: pointer; user-select: none;">Producto Activo (disponible para venta)</label>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 0.25rem; position: sticky; bottom: -1.4rem; background: var(--card-bg); padding: 0.6rem 0 0.2rem;">
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
        <button type="button" onclick="document.getElementById('productModal').style.display='none'" class="btn btn-secondary btn-block">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function togglePluField(desdeCheckbox = false) {
  const isPesable = document.getElementById('prodEsPesableInput').checked;
  const divPLU = document.getElementById('divProdPLU');
  const pluInput = document.getElementById('prodCodigoPLUInput');
  
  if (isPesable) {
    divPLU.style.display = 'block';
    // Al marcarlo recién, proponer el PLU de inmediato (se puede cambiar o regenerar)
    if (desdeCheckbox && pluInput.value === '') {
      autogenerarCodigoPLU();
    }
  } else {
    divPLU.style.display = 'none';
    pluInput.value = '';
  }
}

async function autogenerarCodigoPLU() {
  const id = document.getElementById('prodIdInput').value;
  try {
    const res = await fetch('api/generar_codigo_plu.php' + (id ? '?producto_id=' + encodeURIComponent(id) : ''));
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Error desconocido');
    document.getElementById('prodCodigoPLUInput').value = data.codigo;
  } catch (e) {
    alert('No se pudo generar el PLU: ' + e.message);
  }
}

function togglePrecioVariableField() {
  const isVar = document.getElementById('prodEsPrecioVariableInput').checked;
  const hint = document.getElementById('prodPrecioVarHint');
  const precioInput = document.getElementById('prodPrecioVentaInput');
  if (isVar) {
    hint.style.display = 'block';
    precioInput.placeholder = '0 (o sugerido)';
    if (precioInput.value === '') precioInput.value = '0';
  } else {
    hint.style.display = 'none';
    precioInput.placeholder = '1500';
  }
}

function abrirNuevoModal() {
  document.getElementById('modalTitle').textContent = 'Agregar Nuevo Producto';
  document.getElementById('prodIdInput').value = '';
  document.getElementById('prodCodigoInput').value = '';
  document.getElementById('prodNombreInput').value = '';
  document.getElementById('prodCategoriaSelect').selectedIndex = 0;
  document.getElementById('prodPrecioVentaInput').value = '';
  document.getElementById('prodCostoCompraInput').value = '';
  document.getElementById('prodStockInput').value = '0';
  document.getElementById('prodStockMinimoInput').value = '0';
  document.getElementById('prodActivoInput').checked = true;
  document.getElementById('prodEsPesableInput').checked = false;
  document.getElementById('prodEsPrecioVariableInput').checked = false;
  document.getElementById('prodCodigoPLUInput').value = '';
  togglePluField();
  togglePrecioVariableField();

  // Stock editable solo al crear (stock inicial)
  var stockInput = document.getElementById('prodStockInput');
  stockInput.readOnly = false;
  stockInput.style.opacity = '';
  document.getElementById('prodStockLabel').textContent = 'STOCK INICIAL';
  document.getElementById('prodStockHint').style.display = 'none';
  document.getElementById('productModal').style.display = 'flex';
}

function abrirEditarModal(p) {
  document.getElementById('modalTitle').textContent = 'Editar Producto';
  document.getElementById('prodIdInput').value = p.ProductoID;
  document.getElementById('prodCodigoInput').value = p.CodigoBarras || '';
  document.getElementById('prodNombreInput').value = p.Nombre;
  document.getElementById('prodCategoriaSelect').value = p.CategoriaID;
  document.getElementById('prodPrecioVentaInput').value = p.PrecioVenta;
  document.getElementById('prodCostoCompraInput').value = p.CostoCompra;
  document.getElementById('prodStockInput').value = p.Stock;
  document.getElementById('prodStockMinimoInput').value = p.StockMinimo;
  document.getElementById('prodActivoInput').checked = parseInt(p.Activo) === 1;
  document.getElementById('prodEsPesableInput').checked = parseInt(p.EsPesable) === 1;
  document.getElementById('prodEsPrecioVariableInput').checked = parseInt(p.EsPrecioVariable) === 1;
  document.getElementById('prodCodigoPLUInput').value = p.CodigoPLU || '';
  togglePluField();
  togglePrecioVariableField();

  // Al editar, el stock actual es de solo lectura: se ajusta por Compras/ventas/Ajustes
  var stockInput = document.getElementById('prodStockInput');
  stockInput.readOnly = true;
  stockInput.style.opacity = '0.55';
  document.getElementById('prodStockLabel').textContent = 'STOCK ACTUAL (solo lectura)';
  document.getElementById('prodStockHint').style.display = 'block';
  document.getElementById('productModal').style.display = 'flex';
}
</script>

<!-- Modal Códigos de Barra Adicionales / Alternativos -->
<div id="modalCodigosProducto" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 560px; max-width: 95vw; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--shadow-lg);">
    
    <!-- Header -->
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-barcode" style="color: var(--primary);"></i> Códigos de Barra Alternativos
        </h2>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
          Asocia otros códigos (packs, latas, nuevo EAN) que compartirán el mismo stock.
        </p>
      </div>
      <button type="button" onclick="cerrarModalCodigos()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;">&times;</button>
    </div>

    <!-- Product Info Banner -->
    <div style="background: var(--bg-dark); padding: 0.85rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
      <div>
        <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block;">Producto</span>
        <strong id="mCodProdNombre" style="font-size: 0.95rem; color: var(--text-main);">...</strong>
      </div>
      <div>
        <span id="mCodProdPrincipal" class="badge badge-secondary" style="font-family: monospace; font-size: 0.8rem; padding: 0.35rem 0.6rem;">
          Principal: -
        </span>
      </div>
    </div>

    <!-- Body / Content -->
    <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1;">
      <!-- Alert feedback -->
      <div id="mCodAlerta" style="display: none; padding: 0.6rem 0.9rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1rem;"></div>

      <!-- Add New Code Form -->
      <form id="formAgregarCodigoAlt" onsubmit="agregarCodigoAlternativo(event)" style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border-dark); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
        <div id="mCodFormTitle" style="font-size: 0.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i> Asociar nuevo código
        </div>
        <div style="display: grid; grid-template-columns: 1.2fr 1.5fr; gap: 0.6rem; margin-bottom: 0.6rem;">
          <div>
            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.2rem;">CÓDIGO DE BARRAS *</label>
            <input type="text" id="mCodInputBarras" class="form-control" placeholder="Escanear o escribir..." required style="font-family: monospace;">
          </div>
          <div>
            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.2rem;">DESCRIPCIÓN / PRESENTACIÓN</label>
            <input type="text" id="mCodInputDesc" class="form-control" placeholder="Ej: Six Pack, Caja x24, Envase 2026">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 0.6rem; margin-bottom: 0.6rem;">
          <div>
            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.2rem;">
              <i class="fa-solid fa-layer-group" style="color: #38bdf8;"></i> UNIDADES QUE CONTIENE *
            </label>
            <input type="number" id="mCodInputCant" class="form-control" value="1" min="0.001" step="any" required placeholder="1 = unidad, 6 = sixpack...">
          </div>
          <div>
            <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.2rem;">
              <i class="fa-solid fa-tag" style="color: #34d399;"></i> PRECIO VENTA PACK ($)
            </label>
            <input type="number" id="mCodInputPrecio" class="form-control" min="0" step="1" placeholder="Opcional (Ej: 6500)">
          </div>
        </div>
        <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.75rem; line-height: 1.35;">
          <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> <strong>Tip:</strong> Usa <strong>1</strong> para códigos de envase individual, o <strong>3, 6, 24</strong> para packs. Si dejas el precio vacío, <strong>heredará automáticamente ofertas activas</strong> (como promociones por cantidad 3x o descuentos) o multiplicará el precio unitario si no hay promo.
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <button type="submit" id="btnGuardarCodigoAlt" class="btn btn-primary" style="flex: 1; justify-content: center; padding: 0.55rem; font-size: 0.85rem;">
            <i class="fa-solid fa-plus"></i> Vincular Código al Producto
          </button>
          <button type="button" id="btnCancelarEdicionCod" onclick="cancelarEdicionCodigo()" class="btn btn-secondary" style="display: none; padding: 0.55rem 1rem; font-size: 0.85rem;">
            Cancelar
          </button>
        </div>
      </form>

      <!-- Codes List Header -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
          Códigos Alternativos Registrados (<span id="mCodCount">0</span>)
        </span>
      </div>

      <!-- Codes Table Container -->
      <div id="mCodListContainer" style="border: 1px solid var(--border-dark); border-radius: 10px; overflow: hidden; background: var(--card-bg);">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
          <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-dark); text-align: left;">
              <th style="padding: 0.6rem 0.85rem; color: var(--text-muted); font-weight: 600;">Código</th>
              <th style="padding: 0.6rem 0.85rem; color: var(--text-muted); font-weight: 600;">Presentación</th>
              <th style="padding: 0.6rem 0.85rem; color: var(--text-muted); font-weight: 600; text-align: center;">Unidades</th>
              <th style="padding: 0.6rem 0.85rem; color: var(--text-muted); font-weight: 600; text-align: right;">Precio Pack</th>
              <th style="padding: 0.6rem 0.85rem; color: var(--text-muted); font-weight: 600;">Fecha</th>
              <th style="padding: 0.6rem 0.85rem; text-align: right; color: var(--text-muted); font-weight: 600;">Acción</th>
            </tr>
          </thead>
          <tbody id="mCodTableBody">
            <tr>
              <td colspan="6" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Cargando códigos...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Footer -->
    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: flex-end; background: var(--bg-dark);">
      <button type="button" onclick="cerrarModalCodigos()" class="btn btn-secondary" style="padding: 0.5rem 1.25rem;">
        Cerrar
      </button>
    </div>
  </div>
</div>

<script>
window.CSRF_TOKEN = '<?= csrfToken() ?>';
let currentCodProductoId = null;
let editingCodigoId = null;

function mostrarAlertaCod(tipo, mensaje) {
  const alerta = document.getElementById('mCodAlerta');
  alerta.style.display = 'block';
  if (tipo === 'error') {
    alerta.style.background = 'rgba(239, 68, 68, 0.15)';
    alerta.style.border = '1px solid rgba(239, 68, 68, 0.3)';
    alerta.style.color = '#ef4444';
  } else {
    alerta.style.background = 'rgba(34, 197, 94, 0.15)';
    alerta.style.border = '1px solid rgba(34, 197, 94, 0.3)';
    alerta.style.color = '#22c55e';
  }
  alerta.innerHTML = mensaje;
}

function abrirModalCodigos(id, nombre, principal) {
  currentCodProductoId = id;
  cancelarEdicionCodigo();
  document.getElementById('mCodProdNombre').textContent = nombre;
  document.getElementById('mCodProdPrincipal').textContent = principal ? ('Principal: ' + principal) : 'Sin código principal';
  document.getElementById('mCodAlerta').style.display = 'none';
  document.getElementById('modalCodigosProducto').style.display = 'flex';
  cargarCodigosProducto(id);
  setTimeout(() => document.getElementById('mCodInputBarras').focus(), 150);
}

function cerrarModalCodigos() {
  document.getElementById('modalCodigosProducto').style.display = 'none';
  currentCodProductoId = null;
  cancelarEdicionCodigo();
}

function editarCodigoAlternativo(c) {
  editingCodigoId = c.CodigoID;
  document.getElementById('mCodInputBarras').value = c.CodigoBarras || '';
  document.getElementById('mCodInputDesc').value = c.Descripcion || '';
  document.getElementById('mCodInputCant').value = c.Cantidad || '1';
  document.getElementById('mCodInputPrecio').value = (c.PrecioVenta !== null && c.PrecioVenta !== undefined) ? c.PrecioVenta : '';
  
  document.getElementById('mCodFormTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #38bdf8;"></i> Modificar código / precio de pack';
  document.getElementById('btnGuardarCodigoAlt').innerHTML = '<i class="fa-solid fa-check"></i> Actualizar Cambios';
  document.getElementById('btnCancelarEdicionCod').style.display = 'inline-flex';
  document.getElementById('mCodInputPrecio').focus();
}

function cancelarEdicionCodigo() {
  editingCodigoId = null;
  document.getElementById('mCodInputBarras').value = '';
  document.getElementById('mCodInputDesc').value = '';
  document.getElementById('mCodInputCant').value = '1';
  document.getElementById('mCodInputPrecio').value = '';
  document.getElementById('mCodFormTitle').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i> Asociar nuevo código';
  document.getElementById('btnGuardarCodigoAlt').innerHTML = '<i class="fa-solid fa-plus"></i> Vincular Código al Producto';
  document.getElementById('btnCancelarEdicionCod').style.display = 'none';
}

function cargarCodigosProducto(productoId) {
  const tbody = document.getElementById('mCodTableBody');
  tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 1.2rem; color: var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>';

  fetch('api/codigos_producto.php?producto_id=' + productoId)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 1rem; color: #ef4444;">${data.error || 'Error al cargar códigos'}</td></tr>`;
        return;
      }
      
      const codigos = data.codigos || [];
      document.getElementById('mCodCount').textContent = codigos.length;

      if (codigos.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.82rem;">
              <i class="fa-solid fa-barcode" style="font-size: 1.5rem; opacity: 0.4; display: block; margin-bottom: 0.35rem;"></i>
              No hay códigos adicionales registrados.<br>
              <span style="font-size: 0.75rem; opacity: 0.8;">Agrega arriba un código secundario para asociarlo a este producto.</span>
            </td>
          </tr>
        `;
        return;
      }

      let html = '';
      codigos.forEach(c => {
        const cantNum = parseFloat(c.Cantidad || 1);
        const cantBadge = cantNum > 1 
          ? `<span class="badge badge-primary" style="font-weight: 700; font-size: 0.78rem;"><i class="fa-solid fa-layer-group"></i> x${cantNum}</span>` 
          : `<span style="color: var(--text-muted); font-size: 0.78rem;">1 unid</span>`;

        const precioNum = (c.PrecioVenta !== null && c.PrecioVenta !== undefined) ? parseInt(c.PrecioVenta) : null;
        const precioText = precioNum !== null
          ? `<strong style="color: var(--success); font-size: 0.85rem;">$${Number(precioNum).toLocaleString('es-CL')}</strong>`
          : `<span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">Auto (x${cantNum})</span>`;

        html += `
          <tr style="border-bottom: 1px solid var(--border-dark);">
            <td style="padding: 0.6rem 0.85rem; font-family: monospace; font-weight: 700; color: var(--text-main);">
              ${escapeHtml(c.CodigoBarras)}
            </td>
            <td style="padding: 0.6rem 0.85rem; color: var(--text-muted);">
              ${escapeHtml(c.Descripcion || '—')}
            </td>
            <td style="padding: 0.6rem 0.85rem; text-align: center;">
              ${cantBadge}
            </td>
            <td style="padding: 0.6rem 0.85rem; text-align: right;">
              ${precioText}
            </td>
            <td style="padding: 0.6rem 0.85rem; font-size: 0.75rem; color: var(--text-muted);">
              ${escapeHtml(c.CreadoEnFmt || '')}
            </td>
            <td style="padding: 0.6rem 0.85rem; text-align: right; white-space: nowrap;">
              <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem; color: #38bdf8; border-color: rgba(56,189,248,0.3); margin-right: 0.25rem;"
                      onclick='editarCodigoAlternativo(${JSON.stringify(c)})' title="Editar código, presentación o precio">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.75rem; color: var(--danger); border-color: rgba(239,68,68,0.2);"
                      onclick="eliminarCodigoAlternativo(${c.CodigoID}, '${escapeHtml(c.CodigoBarras)}')" title="Eliminar código">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 1rem; color: #ef4444;">Error de red al consultar códigos.</td></tr>`;
    });
}

function agregarCodigoAlternativo(e) {
  e.preventDefault();
  if (!currentCodProductoId) return;

  const btn = document.getElementById('btnGuardarCodigoAlt');
  const codigoBarras = document.getElementById('mCodInputBarras').value.trim();
  const descripcion = document.getElementById('mCodInputDesc').value.trim();
  const cantidad = parseFloat(document.getElementById('mCodInputCant').value) || 1;
  const precioInput = document.getElementById('mCodInputPrecio').value.trim();
  const precioVenta = precioInput ? parseInt(precioInput) : null;

  if (!codigoBarras) {
    mostrarAlertaCod('error', 'Por favor ingresa un código de barras.');
    return;
  }

  if (cantidad <= 0) {
    mostrarAlertaCod('error', 'La cantidad de unidades debe ser mayor a 0.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

  const action = editingCodigoId ? 'update' : 'add';
  const payload = {
    action: action,
    producto_id: currentCodProductoId,
    codigo_id: editingCodigoId,
    codigo_barras: codigoBarras,
    descripcion: descripcion,
    cantidad: cantidad,
    precio_venta: precioVenta
  };

  fetch('api/codigos_producto.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.CSRF_TOKEN
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = editingCodigoId ? '<i class="fa-solid fa-check"></i> Actualizar Cambios' : '<i class="fa-solid fa-plus"></i> Vincular Código al Producto';
    if (!data.success) {
      mostrarAlertaCod('error', data.error || 'No se pudo guardar el código.');
    } else {
      mostrarAlertaCod('success', data.mensaje || 'Guardado exitosamente.');
      cancelarEdicionCodigo();
      cargarCodigosProducto(currentCodProductoId);
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = editingCodigoId ? '<i class="fa-solid fa-check"></i> Actualizar Cambios' : '<i class="fa-solid fa-plus"></i> Vincular Código al Producto';
    mostrarAlertaCod('error', 'Error de conexión con el servidor.');
  });
}

function eliminarCodigoAlternativo(codigoId, codigoBarras) {
  if (!confirm(`¿Estás seguro de eliminar el código alternativo "${codigoBarras}"?`)) return;

  fetch('api/codigos_producto.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.CSRF_TOKEN
    },
    body: JSON.stringify({
      action: 'delete',
      codigo_id: codigoId
    })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      mostrarAlertaCod('error', data.error || 'Error al eliminar el código.');
    } else {
      mostrarAlertaCod('success', data.mensaje || 'Código eliminado.');
      cargarCodigosProducto(currentCodProductoId);
    }
  })
  .catch(err => {
    mostrarAlertaCod('error', 'Error al comunicarse con el servidor.');
  });
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

<!-- Modal Imprimir Etiqueta de Código de Barras -->
<div id="modalEtiquetaProducto" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 440px; max-width: 95vw; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--shadow-lg);">
    
    <!-- Header -->
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-barcode" style="color: #10b981;"></i> Etiqueta de Código de Barras
        </h2>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
          Imprime etiquetas adhesivas o flejes para tu mercadería.
        </p>
      </div>
      <button type="button" onclick="cerrarModalEtiqueta()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; align-items: center; gap: 1rem;">
      <!-- Options toolbar -->
      <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; background: var(--bg-dark); padding: 0.6rem 0.9rem; border-radius: 8px; border: 1px solid var(--border-dark);">
        <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; margin: 0; user-select: none;">
          <input type="checkbox" id="chkEtiquetaMostrarPrecio" checked onchange="actualizarVistaEtiqueta()">
          <span>Mostrar Precio</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; margin: 0; user-select: none;">
          <input type="checkbox" id="chkEtiquetaMostrarEmpresa" checked onchange="actualizarVistaEtiqueta()">
          <span>Nombre Local</span>
        </label>
      </div>

      <!-- Preview container (this is what gets printed) -->
      <div id="printableEtiquetaArea" style="width: 100%; display: flex; justify-content: center; padding: 0.5rem 0;">
        <div id="printableEtiquetaBox" style="background: #ffffff; color: #000000; border: 2px dashed #475569; border-radius: 8px; padding: 12px 14px; width: 280px; text-align: center; box-shadow: 0 4px 14px rgba(0,0,0,0.12); display: flex; flex-direction: column; align-items: center; justify-content: space-between; box-sizing: border-box;">
          <div id="etiquetaEmpresa" style="font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #475569; margin-bottom: 2px;">
            <?= htmlspecialchars($nombreEmpresa) ?>
          </div>
          <div id="etiquetaNombre" style="font-size: 0.88rem; font-weight: 800; line-height: 1.2; text-transform: uppercase; margin: 2px 0 6px 0; color: #0f172a; word-break: break-word;">
            NOMBRE DEL PRODUCTO
          </div>
          <div style="width: 100%; display: flex; justify-content: center; margin: 2px 0;">
            <svg id="svgEtiquetaBarcode" style="max-width: 100%; height: auto; display: block;"></svg>
          </div>
          <div id="etiquetaPrecio" style="font-size: 1.35rem; font-weight: 900; color: #000000; margin-top: 4px; line-height: 1;">
            $ 0
          </div>
        </div>
      </div>

      <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center; line-height: 1.35;">
        <i class="fa-solid fa-circle-info" style="color: #38bdf8;"></i> Compatible con impresoras térmicas de stickers (50x30mm, 58mm, 80mm) y papel de oficina.
      </div>
    </div>

    <!-- Footer -->
    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: flex-end; gap: 0.5rem; background: var(--bg-dark); flex-wrap: wrap;">
      <button type="button" onclick="cerrarModalEtiqueta()" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
        Cerrar
      </button>
      <button type="button" onclick="irAPliegoA4()" class="btn btn-secondary" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.4); padding: 0.5rem 1rem;" title="Generar pliego con múltiples etiquetas de este producto en hoja A4">
        <i class="fa-solid fa-file-lines"></i> Pliego A4
      </button>
      <button type="button" onclick="imprimirEtiquetaDirecta()" class="btn btn-primary" style="padding: 0.5rem 1.25rem; background: #10b981; border-color: #10b981;">
        <i class="fa-solid fa-print"></i> Imprimir Etiqueta
      </button>
    </div>
  </div>
</div>

<style>
/* Estilos para impresión directa de etiqueta */
@media print {
  body * {
    visibility: hidden !important;
  }
  #printableEtiquetaArea, #printableEtiquetaArea * {
    visibility: visible !important;
  }
  #printableEtiquetaArea {
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    justify-content: center !important;
    align-items: flex-start !important;
    background: transparent !important;
  }
  #printableEtiquetaBox {
    border: none !important;
    box-shadow: none !important;
    margin-top: 4mm !important;
  }
  @page {
    size: auto;
    margin: 0mm;
  }
}
</style>

<!-- Librería JsBarcode (Offline) -->
<script src="assets/vendor/JsBarcode.all.min.js"></script>

<script>
let currentEtiquetaProducto = null;

async function autogenerarCodigoEAN8() {
  const btn = document.getElementById('btnGenEAN8');
  const input = document.getElementById('prodCodigoInput');
  const originalHtml = btn.innerHTML;

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';

  try {
    const res = await fetch('api/generar_codigo_ean8.php');
    const data = await res.json();
    if (data.success && data.codigo) {
      input.value = data.codigo;
      input.focus();
      input.style.borderColor = '#10b981';
      input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.2)';
      setTimeout(() => {
        input.style.borderColor = '';
        input.style.boxShadow = '';
      }, 1500);
    } else {
      alert(data.error || 'No se pudo generar el código EAN-8.');
    }
  } catch (err) {
    alert('Error al conectar con el servidor para generar código.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

function abrirModalImprimirEtiqueta(p) {
  if (!p) return;
  currentEtiquetaProducto = p;

  let codigo = (p.CodigoBarras || '').trim();
  if (!codigo) {
    if (confirm(`El producto "${p.Nombre}" no tiene código de barras asignado.\n\n¿Deseas editarlo ahora para asignarle un código EAN-8 automático?`)) {
      abrirEditarModal(p);
      autogenerarCodigoEAN8();
    }
    return;
  }

  document.getElementById('etiquetaNombre').textContent = p.Nombre || 'SIN NOMBRE';

  if (parseInt(p.EsPrecioVariable) === 1) {
    document.getElementById('etiquetaPrecio').innerHTML = '<span style="font-size: 0.95rem; font-weight: 700; color: #2563eb;">PRECIO VARIABLE</span>';
  } else {
    const precio = parseInt(p.PrecioVenta || 0);
    document.getElementById('etiquetaPrecio').textContent = '$ ' + Number(precio).toLocaleString('es-CL');
  }

  actualizarVistaEtiqueta();
  renderizarBarcodeEtiqueta(codigo);

  document.getElementById('modalEtiquetaProducto').style.display = 'flex';
}

function cerrarModalEtiqueta() {
  document.getElementById('modalEtiquetaProducto').style.display = 'none';
  currentEtiquetaProducto = null;
}

function irAPliegoA4() {
  if (currentEtiquetaProducto && currentEtiquetaProducto.ProductoID) {
    window.location.href = 'imprimir_codigos_a4.php?producto_id=' + currentEtiquetaProducto.ProductoID;
  }
}

function actualizarVistaEtiqueta() {
  const chkPrecio = document.getElementById('chkEtiquetaMostrarPrecio');
  const chkEmpresa = document.getElementById('chkEtiquetaMostrarEmpresa');
  const precioEl = document.getElementById('etiquetaPrecio');
  const empresaEl = document.getElementById('etiquetaEmpresa');

  if (precioEl) precioEl.style.display = (chkPrecio && chkPrecio.checked) ? 'block' : 'none';
  if (empresaEl) empresaEl.style.display = (chkEmpresa && chkEmpresa.checked) ? 'block' : 'none';
}

function renderizarBarcodeEtiqueta(codigo) {
  const svgEl = document.getElementById('svgEtiquetaBarcode');
  svgEl.innerHTML = '';

  let formato = "CODE128";
  const soloNumeros = /^\d+$/.test(codigo);
  if (soloNumeros && codigo.length === 8) {
    formato = "EAN8";
  } else if (soloNumeros && codigo.length === 13) {
    formato = "EAN13";
  }

  try {
    JsBarcode(svgEl, codigo, {
      format: formato,
      width: 1.8,
      height: 44,
      displayValue: true,
      fontSize: 12,
      font: "monospace",
      textMargin: 2,
      margin: 4,
      background: "#ffffff",
      lineColor: "#000000"
    });
  } catch (err) {
    console.warn('[Barcode] Reintentando con CODE128:', err);
    try {
      JsBarcode(svgEl, codigo, {
        format: "CODE128",
        width: 1.8,
        height: 44,
        displayValue: true,
        fontSize: 12,
        font: "monospace",
        textMargin: 2,
        margin: 4,
        background: "#ffffff",
        lineColor: "#000000"
      });
    } catch (e2) {
      svgEl.innerHTML = `<text x="50%" y="50%" text-anchor="middle" fill="#ef4444" font-size="12">Error código: ${escapeHtml(codigo)}</text>`;
    }
  }
}

function imprimirEtiquetaDirecta() {
  const boxHtml = document.getElementById('printableEtiquetaBox').outerHTML;
  const printWindow = window.open('', '_blank', 'width=450,height=400');
  if (printWindow) {
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Imprimir Etiqueta - ${escapeHtml(currentEtiquetaProducto?.Nombre || 'Producto')}</title>
        <style>
          @page { margin: 2mm; size: auto; }
          body {
            margin: 0;
            padding: 4mm;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
          }
          #printableEtiquetaBox {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 60mm;
            padding: 2mm 3mm !important;
          }
        </style>
      </head>
      <body>
        ${boxHtml}
        <script>
          window.onload = function() {
            window.focus();
            window.print();
            setTimeout(function() { window.close(); }, 500);
          };
        <\/script>
      </body>
      </html>
    `);
    printWindow.document.close();
  } else {
    window.print();
  }
}

window.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (document.getElementById('modalEtiquetaProducto')?.style.display === 'flex') {
      cerrarModalEtiqueta();
    }
  }
});
</script>

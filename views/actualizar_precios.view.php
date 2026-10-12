<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
  <div>
    <h1 class="page-title" style="display: flex; align-items: center; gap: 0.6rem; margin: 0; font-size: 1.6rem;">
      <i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Actualizador Rápido de Precios
      <span class="badge badge-primary" style="font-size: 0.75rem; vertical-align: middle;">v3.3.0</span>
    </h1>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
      Escanea códigos de barra o busca productos para ajustar precios y costos en lote con cálculo de margen en vivo.
    </p>
  </div>
  <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
    <a href="productos.php" class="btn btn-secondary">
      <i class="fa-solid fa-arrow-left"></i> Volver a Catálogo
    </a>
    <button type="button" onclick="abrirModalFlejes()" id="btnImprimirFlejes" class="btn btn-secondary" style="color: #38bdf8; border-color: rgba(56,189,248,0.3);" disabled>
      <i class="fa-solid fa-print"></i> Imprimir Flejes de Góndola
    </button>
    <button type="button" onclick="guardarTodosLosPrecios()" id="btnGuardarTop" class="btn btn-success" disabled>
      <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios (F10)
    </button>
  </div>
</div>

<!-- Feedback Alert -->
<div id="alertaPrecios" style="display: none; padding: 0.85rem 1.25rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.25rem; font-weight: 600;"></div>

<!-- Scanner and Bulk Controls Card -->
<div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
  <div style="display: grid; grid-template-columns: 2fr 1.3fr auto; gap: 0.85rem; align-items: center;">
    
    <!-- Big Scanner Input -->
    <div style="position: relative;">
      <i class="fa-solid fa-barcode" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); font-size: 1.35rem; color: #f59e0b;"></i>
      <input type="text" id="scanPreciosInput" class="form-control" 
             placeholder="Escanear código de barra o escribir nombre de producto (Presiona Enter)..." 
             style="height: 52px; padding-left: 3.2rem; font-size: 1.05rem; font-weight: 600; border-radius: 12px; border: 2px solid rgba(245, 158, 11, 0.4);" 
             autofocus autocomplete="off">
      <div id="searchDropdown" style="display: none; position: absolute; top: 56px; left: 0; right: 0; background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 10px; max-height: 280px; overflow-y: auto; z-index: 50; box-shadow: var(--shadow-lg);"></div>
    </div>

    <!-- Category Batch Loader -->
    <div style="display: flex; gap: 0.5rem; align-items: center;">
      <select id="selectCatBatch" class="form-control" style="height: 52px; border-radius: 12px;">
        <option value="">-- Cargar categoría completa --</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['CategoriaID'] ?>"><?= htmlspecialchars($cat['Nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" onclick="cargarPorCategoria()" class="btn btn-primary" style="height: 52px; padding: 0 1.15rem; border-radius: 12px; white-space: nowrap;">
        <i class="fa-solid fa-folder-open"></i> Cargar
      </button>
    </div>

    <!-- Cargar Productos Sin Costo -->
    <div>
      <button type="button" onclick="cargarProductosSinCosto()" id="btnCargarSinCosto" class="btn btn-secondary" 
              style="height: 52px; padding: 0 1.2rem; border-radius: 12px; white-space: nowrap; border-color: rgba(245, 158, 11, 0.45); color: #fbbf24; background: rgba(245, 158, 11, 0.08); font-weight: 700; display: flex; align-items: center; gap: 0.5rem;"
              title="Cargar todos los productos que no tienen costo asignado ($0 o S/C)">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Sin Costo ($0)</span>
        <span class="badge" id="badgeSinCostoCount" style="background: #f59e0b; color: #000; font-weight: 800; font-size: 0.78rem; padding: 0.2rem 0.55rem; border-radius: 6px;"><?= $totalSinCosto ?></span>
      </button>
    </div>

  </div>

  <!-- Bulk Tools Bar -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid var(--border-dark);">
    
    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
      <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">
        <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--primary);"></i> Acciones en Lote:
      </span>
      <button type="button" onclick="abrirModalEstimarCostos()" class="btn btn-primary" 
              style="padding: 0.35rem 0.85rem; font-size: 0.82rem; background: linear-gradient(135deg, #0284c7, #0369a1); border: none; font-weight: 700; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35); display: flex; align-items: center; gap: 0.4rem;"
              title="Calcular costos estimados en lote según precio de venta y margen deseado">
        <i class="fa-solid fa-calculator"></i> 🎯 Estimar Costos por Margen %
      </button>
      <button type="button" onclick="aplicarPorcentaje(5)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
        +5% Venta
      </button>
      <button type="button" onclick="aplicarPorcentaje(10)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
        +10% Venta
      </button>
      <button type="button" onclick="aplicarPorcentaje(15)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
        +15% Venta
      </button>
      <button type="button" onclick="pedirPorcentajePersonalizado()" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;">
        +X% Personalizado
      </button>
      <button type="button" onclick="redondearPrecios(100)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; color: #34d399;">
        <i class="fa-solid fa-coins"></i> Redondear a $100
      </button>
      <button type="button" onclick="redondearPrecios(10)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; color: #34d399;">
        <i class="fa-solid fa-coins"></i> Redondear a $10
      </button>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center;">
      <button type="button" onclick="limpiarLista()" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239,68,68,0.25);">
        <i class="fa-solid fa-trash-can"></i> Limpiar Lista
      </button>
    </div>

  </div>
</div>

<!-- Products Table Container -->
<div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 5rem;">
  
  <div style="padding: 0.85rem 1.25rem; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center;">
    <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
      <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
      Productos en esta sesión: <span id="lblTotalItems" style="color: #38bdf8;">0</span>
      <span id="lblModificadosBadge" class="badge badge-warning" style="display: none; font-size: 0.75rem; margin-left: 0.5rem;">0 modificados</span>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-muted);">
      <kbd style="background: var(--bg-dark); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid var(--border-dark);">Tab</kbd> para avanzar de campo &bull; 
      <kbd style="background: var(--bg-dark); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid var(--border-dark);">F10</kbd> para Guardar
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left;">
      <thead>
        <tr style="background: rgba(15,23,42,0.6); border-bottom: 1px solid var(--border-dark); font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">
          <th style="padding: 0.85rem 1rem; width: 45px; text-align: center;">#</th>
          <th style="padding: 0.85rem 1rem;">Producto / SKU</th>
          <th style="padding: 0.85rem 1rem; width: 140px;">Costo Actual</th>
          <th style="padding: 0.85rem 1rem; width: 150px;">Nuevo Costo ($)</th>
          <th style="padding: 0.85rem 1rem; width: 140px;">Venta Actual</th>
          <th style="padding: 0.85rem 1rem; width: 170px;">Nuevo Precio Venta ($)</th>
          <th style="padding: 0.85rem 1rem; width: 130px; text-align: center;">Margen % s/Costo</th>
          <th style="padding: 0.85rem 1rem; width: 150px; text-align: center;">Packs Vinculados</th>
          <th style="padding: 0.85rem 1rem; width: 60px; text-align: center;">Quitar</th>
        </tr>
      </thead>
      <tbody id="preciosTableBody">
        <tr id="rowEmptyState">
          <td colspan="9" style="text-align: center; padding: 3.5rem 1rem; color: var(--text-muted);">
            <i class="fa-solid fa-barcode" style="font-size: 3rem; opacity: 0.25; margin-bottom: 0.75rem; display: block;"></i>
            <p style="font-size: 1.05rem; font-weight: 600; margin: 0 0 0.35rem 0; color: var(--text-main);">No hay productos en la lista</p>
            <span style="font-size: 0.85rem; opacity: 0.75;">Escanea un código de barras arriba con tu lector o carga una categoría para comenzar.</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Sticky Bottom Action Bar -->
<div style="position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); border-top: 1px solid var(--border-dark); padding: 0.85rem 2rem; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -4px 20px rgba(0,0,0,0.4);">
  <div style="display: flex; align-items: center; gap: 1.5rem;">
    <div>
      <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Total Ítems</span>
      <strong id="barTotalItems" style="font-size: 1.25rem; color: #fff;">0</strong>
    </div>
    <div style="border-left: 1px solid var(--border-dark); padding-left: 1.5rem;">
      <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Modificados</span>
      <strong id="barModificados" style="font-size: 1.25rem; color: #f59e0b;">0</strong>
    </div>
  </div>
  <div style="display: flex; gap: 0.75rem; align-items: center;">
    <button type="button" onclick="limpiarLista()" class="btn btn-secondary" style="padding: 0.65rem 1.25rem;">
      Descartar
    </button>
    <button type="button" onclick="guardarTodosLosPrecios()" id="btnGuardarBottom" class="btn btn-success" style="padding: 0.65rem 1.75rem; font-size: 0.95rem; font-weight: 700;" disabled>
      <i class="fa-solid fa-floppy-disk"></i> Guardar Todos los Cambios (F10)
    </button>
  </div>
</div>

<!-- Modal Estimar Precio de Costo según Margen de Venta -->
<div id="modalEstimarCostos" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 100%; max-width: 580px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-lg);">
    
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-calculator" style="color: #38bdf8;"></i> Estimar Precio Costo según Margen
        </h3>
        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; display: block;">
          Calcula y autocompleta el costo de compra en base al precio de venta actual.
        </span>
      </div>
      <button type="button" onclick="cerrarModalEstimarCostos()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 1.25rem;">
      
      <!-- 1. Margen de Ganancia Deseado -->
      <div>
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.4rem;">
          1. Margen de Ganancia Deseado (%)
        </label>
        <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
          <div style="position: relative; width: 140px;">
            <input type="number" id="estimarMargenPorc" value="30" min="1" max="500" step="1"
                   class="form-control" style="font-size: 1.1rem; font-weight: 800; text-align: center; height: 42px; border-radius: 10px;"
                   oninput="actualizarPreviewEstimacion()">
            <span style="position: absolute; right: 0.8rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-muted);">%</span>
          </div>
          <!-- Chips Rápidos -->
          <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
            <button type="button" onclick="setMargenChip(20)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem;">20%</button>
            <button type="button" onclick="setMargenChip(25)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem;">25%</button>
            <button type="button" onclick="setMargenChip(30)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem; font-weight: 700; color: #38bdf8; border-color: rgba(56,189,248,0.4);">30%</button>
            <button type="button" onclick="setMargenChip(35)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem;">35%</button>
            <button type="button" onclick="setMargenChip(40)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem;">40%</button>
            <button type="button" onclick="setMargenChip(50)" class="btn btn-secondary" style="padding: 0.35rem 0.6rem; font-size: 0.78rem;">50%</button>
          </div>
        </div>
      </div>

      <!-- 2. Método de Cálculo -->
      <div>
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.4rem;">
          2. Método de Cálculo de Margen
        </label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
          <label style="border: 1px solid var(--border-dark); border-radius: 10px; padding: 0.75rem 0.85rem; cursor: pointer; display: flex; gap: 0.6rem; align-items: flex-start; background: rgba(255,255,255,0.02);">
            <input type="radio" name="metodoCalculoCosto" value="markup" checked onchange="actualizarPreviewEstimacion()" style="margin-top: 0.2rem;">
            <div>
              <strong style="font-size: 0.85rem; color: #fff; display: block;">Sobre Costo (Mark-up POS)</strong>
              <small style="color: var(--text-muted); font-size: 0.74rem; line-height: 1.25; display: block; margin-top: 0.15rem;">
                Costo = Venta ÷ (1 + Margen%). Estándar de utilidades del sistema.
              </small>
            </div>
          </label>
          <label style="border: 1px solid var(--border-dark); border-radius: 10px; padding: 0.75rem 0.85rem; cursor: pointer; display: flex; gap: 0.6rem; align-items: flex-start; background: rgba(255,255,255,0.02);">
            <input type="radio" name="metodoCalculoCosto" value="sobre_venta" onchange="actualizarPreviewEstimacion()" style="margin-top: 0.2rem;">
            <div>
              <strong style="font-size: 0.85rem; color: #fff; display: block;">Sobre Venta (Comercial)</strong>
              <small style="color: var(--text-muted); font-size: 0.74rem; line-height: 1.25; display: block; margin-top: 0.15rem;">
                Costo = Venta × (1 - Margen%). Ganancia directa sobre precio final.
              </small>
            </div>
          </label>
        </div>
      </div>

      <!-- 3. Ámbito de Aplicación -->
      <div>
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.4rem;">
          3. ¿A qué productos aplicar?
        </label>
        <div style="display: flex; flex-direction: column; gap: 0.45rem;">
          <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
            <input type="radio" name="ambitoEstimacion" value="solo_sin_costo" checked style="width: 16px; height: 16px;">
            <span><strong>Solo productos sin costo</strong> (Costo actual $0) <em style="color: #34d399; font-style: normal;">— Recomendado, protege costos ya cargados</em></span>
          </label>
          <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
            <input type="radio" name="ambitoEstimacion" value="todos" style="width: 16px; height: 16px;">
            <span><strong>Todos los productos en la lista actual</strong> (Sobrescribe costos existentes)</span>
          </label>
        </div>
      </div>

      <!-- 4. Redondeo del Costo -->
      <div>
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 0.4rem;">
          4. Redondeo del Costo Calculado
        </label>
        <select id="selectRedondeoCosto" class="form-control" style="font-size: 0.85rem;" onchange="actualizarPreviewEstimacion()">
          <option value="10" selected>Redondear a $10 más cercano (Ej: $769 → $770)</option>
          <option value="50">Redondear a $50 más cercano (Ej: $769 → $750)</option>
          <option value="100">Redondear a $100 más cercano (Ej: $769 → $800)</option>
          <option value="1">Sin redondeo (Al peso exacto, Ej: $769)</option>
        </select>
      </div>

      <!-- Caja de Previsualización en Vivo -->
      <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 12px; padding: 0.85rem 1rem;">
        <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: #38bdf8; margin-bottom: 0.25rem;">
          <i class="fa-solid fa-eye"></i> Simulación en Tiempo Real
        </div>
        <div id="previewEstimacionTxt" style="font-size: 0.88rem; color: #fff; font-weight: 600;">
          <!-- Se actualiza por JS -->
        </div>
      </div>

    </div>

    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center; background: var(--bg-dark);">
      <button type="button" onclick="cerrarModalEstimarCostos()" class="btn btn-secondary">
        Cancelar
      </button>
      <button type="button" onclick="aplicarEstimacionCostos()" class="btn btn-success" style="padding: 0.55rem 1.35rem; font-weight: 700;">
        <i class="fa-solid fa-check"></i> Aplicar Costos a la Lista
      </button>
    </div>

  </div>
</div>

<!-- Modal de Packs Vinculados -->
<div id="modalPacksItem" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-lg);">
    
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-main);">
          <i class="fa-solid fa-boxes-stacked" style="color: #38bdf8;"></i> Precios de Packs y Presentaciones
        </h3>
        <span id="modalPackProdNombre" style="font-size: 0.8rem; color: var(--text-muted);"></span>
      </div>
      <button type="button" onclick="cerrarModalPacks()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1;">
      <div id="modalPackListContainer">
        <!-- Generado dinámicamente -->
      </div>
    </div>

    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: flex-end; gap: 0.5rem; background: var(--bg-dark);">
      <button type="button" onclick="cerrarModalPacks()" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">
        Listo
      </button>
    </div>

  </div>
</div>

<!-- Modal Imprimir Flejes de Góndola -->
<div id="modalFlejes" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 200; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 100%; max-width: 780px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-lg);">
    
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-main);">
        <i class="fa-solid fa-print" style="color: #38bdf8;"></i> Flejes / Etiquetas de Góndola para Estantes
      </h3>
      <button type="button" onclick="cerrarModalFlejes()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; background: #fff; color: #000;" id="printableFlejesArea">
      <div id="flejesContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
        <!-- Se llena con JS -->
      </div>
    </div>

    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center; background: var(--bg-dark);">
      <span style="font-size: 0.82rem; color: var(--text-muted);">
        Listo para imprimir en hoja A4 / Carta o recortar para perfiles de góndola.
      </span>
      <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="window.print()" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">
          <i class="fa-solid fa-print"></i> Imprimir Ahora
        </button>
        <button type="button" onclick="cerrarModalFlejes()" class="btn btn-secondary">
          Cerrar
        </button>
      </div>
    </div>

  </div>
</div>

<style>
/* Estilos para impresión de flejes */
@media print {
  body * {
    visibility: hidden;
  }
  #printableFlejesArea, #printableFlejesArea * {
    visibility: visible;
  }
  #printableFlejesArea {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10px;
    background: #fff !important;
    color: #000 !important;
  }
  .modal-overlay {
    background: none !important;
    position: static !important;
  }
}

.fleje-card {
  border: 2px solid #000;
  border-radius: 6px;
  padding: 8px 10px;
  background: #fff;
  color: #000;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  page-break-inside: avoid;
}
.fleje-header {
  font-size: 0.85rem;
  font-weight: 800;
  text-transform: uppercase;
  line-height: 1.2;
  margin-bottom: 4px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.fleje-sku {
  font-size: 0.7rem;
  font-family: monospace;
  color: #444;
  margin-bottom: 6px;
}
.fleje-price {
  font-size: 1.85rem;
  font-weight: 900;
  color: #000;
  text-align: right;
  line-height: 1;
}
.fleje-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.65rem;
  color: #666;
  border-top: 1px dotted #888;
  padding-top: 3px;
  margin-top: 6px;
}
</style>

<script>
window.CSRF_TOKEN = '<?= csrfToken() ?>';

// Lista en memoria de productos en la sesión de edición
let batchItems = [];
let currentEditingPackItem = null;

// Audio Beep al escanear
let audioCtx = null;
function playBeep() {
  try {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(950, audioCtx.currentTime);
    gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.08);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.08);
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
  const scanInput = document.getElementById('scanPreciosInput');

  // Atajo F10 para guardar
  window.addEventListener('keydown', (e) => {
    if (e.key === 'F10') {
      e.preventDefault();
      guardarTodosLosPrecios();
    }
  });

  // Escaneo y búsqueda
  scanInput.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = scanInput.value.trim();
      if (!code) return;
      await buscarYAgregarProducto(code);
      scanInput.value = '';
    } else if (e.key === 'Escape') {
      scanInput.value = '';
      document.getElementById('searchDropdown').style.display = 'none';
    }
  });

  // Autocomplete suave si escribe más de 3 letras
  let debounceTimer = null;
  scanInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = scanInput.value.trim();
    if (q.length < 3) {
      document.getElementById('searchDropdown').style.display = 'none';
      return;
    }
    debounceTimer = setTimeout(() => buscarDropdown(q), 220);
  });

  // Si se ingresó con parámetro ?filtro=sin_costo (ej. desde el Centro de Reportes), cargar de inmediato
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('filtro') === 'sin_costo') {
    cargarProductosSinCosto();
  }
});

async function buscarDropdown(q) {
  try {
    const res = await fetch(`api/buscar_producto.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    const dropdown = document.getElementById('searchDropdown');
    if (!data.success || !data.productos || data.productos.length === 0) {
      dropdown.style.display = 'none';
      return;
    }

    dropdown.innerHTML = data.productos.slice(0, 8).map(p => `
      <div onclick="seleccionarDelDropdown(${p.ProductoID})" 
           style="padding: 0.65rem 1rem; border-bottom: 1px solid var(--border-dark); cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
           onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
        <div>
          <strong style="color: #fff; font-size: 0.88rem;">${escapeHtml(p.Nombre)}</strong>
          <div style="font-size: 0.75rem; color: var(--text-muted);">${p.CodigoBarras || 'Sin código'} &bull; ${p.CategoriaNombre || 'General'}</div>
        </div>
        <div style="text-align: right;">
          <span style="font-size: 0.95rem; font-weight: 700; color: var(--success);">$${formatNum(p.PrecioVenta)}</span>
        </div>
      </div>
    `).join('');
    dropdown.style.display = 'block';
  } catch (e) {}
}

async function seleccionarDelDropdown(productoId) {
  document.getElementById('searchDropdown').style.display = 'none';
  document.getElementById('scanPreciosInput').value = '';
  await buscarYAgregarProducto(productoId.toString());
}

async function buscarYAgregarProducto(code) {
  try {
    const res = await fetch(`api/buscar_producto.php?q=${encodeURIComponent(code)}`);
    const data = await res.json();

    if (!data.success || !data.productos || data.productos.length === 0) {
      mostrarAlerta('error', `No se encontró ningún producto con el código o nombre: "${code}"`);
      return;
    }

    const p = data.productos[0];
    agregarItemABatch(p);
  } catch (err) {
    mostrarAlerta('error', 'Error al consultar producto: ' + err.message);
  }
}

async function cargarPorCategoria() {
  const catId = document.getElementById('selectCatBatch').value;
  if (!catId) {
    mostrarAlerta('error', 'Selecciona una categoría para cargar sus productos.');
    return;
  }

  mostrarAlerta('info', '<i class="fa-solid fa-spinner fa-spin"></i> Cargando productos de la categoría...');

  try {
    const res = await fetch(`api/buscar_producto.php?filtro=categoria&cat=${catId}`);
    const data = await res.json();

    if (!data.success || !data.productos || data.productos.length === 0) {
      mostrarAlerta('error', 'Esta categoría no tiene productos registrados.');
      return;
    }

    let agregados = 0;
    for (const p of data.productos) {
      if (!batchItems.some(i => i.ProductoID === p.ProductoID)) {
        await agregarItemABatch(p, false);
        agregados++;
      }
    }

    mostrarAlerta('success', `Se cargaron ${agregados} productos de la categoría a la lista.`);
    renderBatchTable();
  } catch (e) {
    mostrarAlerta('error', 'Error al cargar categoría: ' + e.message);
  }
}

async function cargarProductosSinCosto() {
  mostrarAlerta('info', '<i class="fa-solid fa-spinner fa-spin"></i> Cargando productos sin costo asignado ($0 o S/C)...');

  try {
    const res = await fetch('api/buscar_producto.php?filtro=sin_costo');
    const data = await res.json();

    if (!data.success || !data.productos || data.productos.length === 0) {
      mostrarAlerta('success', '¡Excelente! No hay productos con costo $0 en el catálogo.');
      return;
    }

    let agregados = 0;
    for (const p of data.productos) {
      if (!batchItems.some(i => i.ProductoID === p.ProductoID)) {
        await agregarItemABatch(p, false);
        agregados++;
      }
    }

    mostrarAlerta('success', `<i class="fa-solid fa-check"></i> Se cargaron <strong>${agregados} productos sin costo</strong> a la lista de edición. Puedes usar <em>🎯 Estimar Costos por Margen %</em> para completarlos.`);
    renderBatchTable();
  } catch (e) {
    mostrarAlerta('error', 'Error al cargar productos sin costo: ' + e.message);
  }
}

async function agregarItemABatch(producto, renderInmediato = true) {
  // Verificar si ya existe en la lista
  const existIdx = batchItems.findIndex(i => i.ProductoID === producto.ProductoID);
  if (existIdx > -1) {
    // Si ya existe, resaltar la fila y enfocar el input
    playBeep();
    renderBatchTable();
    setTimeout(() => {
      const input = document.getElementById(`inputVenta_${producto.ProductoID}`);
      if (input) {
        input.focus();
        input.select();
      }
    }, 100);
    return;
  }

  // Consultar packs vinculados
  let packs = [];
  try {
    const resCod = await fetch(`api/codigos_producto.php?producto_id=${producto.ProductoID}`);
    const dataCod = await resCod.json();
    if (dataCod.success && dataCod.codigos) {
      packs = dataCod.codigos.map(c => ({
        CodigoID: c.CodigoID,
        CodigoBarras: c.CodigoBarras,
        Descripcion: c.Descripcion || `Pack x${c.Cantidad}`,
        Cantidad: parseFloat(c.Cantidad || 1),
        PrecioOriginal: (c.PrecioVenta !== null && c.PrecioVenta !== undefined) ? parseInt(c.PrecioVenta) : null,
        NuevoPrecio: (c.PrecioVenta !== null && c.PrecioVenta !== undefined) ? parseInt(c.PrecioVenta) : null
      }));
    }
  } catch (e) {}

  const costoOriginal = parseInt(producto.CostoCompra) || 0;
  const precioOriginal = parseInt(producto.PrecioVenta) || 0;

  const item = {
    ProductoID: producto.ProductoID,
    CodigoBarras: producto.CodigoBarras || '',
    Nombre: producto.Nombre,
    Categoria: producto.CategoriaNombre || 'General',
    Stock: parseFloat(producto.Stock || 0),
    CostoOriginal: costoOriginal,
    NuevoCosto: costoOriginal,
    PrecioOriginal: precioOriginal,
    NuevoPrecio: precioOriginal,
    Packs: packs
  };

  // Insertar al inicio para que el último escaneado quede arriba
  batchItems.unshift(item);
  playBeep();

  if (renderInmediato) {
    renderBatchTable();
    setTimeout(() => {
      const input = document.getElementById(`inputVenta_${producto.ProductoID}`);
      if (input) {
        input.focus();
        input.select();
      }
    }, 100);
  }
}

function calcularMargen(precioVenta, costo) {
  if (!costo || costo <= 0) return null;
  const margen = ((precioVenta - costo) / costo) * 100;
  return Math.round(margen * 10) / 10;
}

function renderBatchTable() {
  const tbody = document.getElementById('preciosTableBody');
  const btnGuardarTop = document.getElementById('btnGuardarTop');
  const btnGuardarBottom = document.getElementById('btnGuardarBottom');
  const btnImprimirFlejes = document.getElementById('btnImprimirFlejes');
  const lblTotal = document.getElementById('lblTotalItems');
  const barTotal = document.getElementById('barTotalItems');
  const barModif = document.getElementById('barModificados');
  const lblModifBadge = document.getElementById('lblModificadosBadge');

  lblTotal.textContent = batchItems.length;
  barTotal.textContent = batchItems.length;

  // Contar cuántos productos sufrieron modificaciones de precio o costo
  const modificadosCount = batchItems.filter(i => {
    const prodModif = (i.NuevoPrecio !== i.PrecioOriginal) || (i.NuevoCosto !== i.CostoOriginal);
    const packModif = i.Packs.some(p => p.NuevoPrecio !== p.PrecioOriginal);
    return prodModif || packModif;
  }).length;

  barModif.textContent = modificadosCount;
  if (modificadosCount > 0) {
    lblModifBadge.style.display = 'inline-block';
    lblModifBadge.textContent = `${modificadosCount} modificados`;
  } else {
    lblModifBadge.style.display = 'none';
  }

  const hayItems = batchItems.length > 0;
  btnGuardarTop.disabled = !hayItems || modificadosCount === 0;
  btnGuardarBottom.disabled = !hayItems || modificadosCount === 0;
  btnImprimirFlejes.disabled = !hayItems;

  if (!hayItems) {
    tbody.innerHTML = `
      <tr id="rowEmptyState">
        <td colspan="9" style="text-align: center; padding: 3.5rem 1rem; color: var(--text-muted);">
          <i class="fa-solid fa-barcode" style="font-size: 3rem; opacity: 0.25; margin-bottom: 0.75rem; display: block;"></i>
          <p style="font-size: 1.05rem; font-weight: 600; margin: 0 0 0.35rem 0; color: var(--text-main);">No hay productos en la lista</p>
          <span style="font-size: 0.85rem; opacity: 0.75;">Escanea un código de barras arriba con tu lector o carga una categoría para comenzar.</span>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = batchItems.map((item, idx) => {
    const isModified = (item.NuevoPrecio !== item.PrecioOriginal) || (item.NuevoCosto !== item.CostoOriginal) || item.Packs.some(p => p.NuevoPrecio !== p.PrecioOriginal);
    const margen = calcularMargen(item.NuevoPrecio, item.NuevoCosto);

    let margenBadgeClass = 'badge-success';
    let margenText = '';
    if (margen === null) {
      margenBadgeClass = 'badge-warning';
      margenText = 'S/C';
    } else if (margen < 0) {
      margenBadgeClass = 'badge-danger';
      margenText = `${margen}%`;
    } else if (margen < 15) {
      margenBadgeClass = 'badge-warning';
      margenText = `+${margen}%`;
    } else {
      margenBadgeClass = 'badge-success';
      margenText = `+${margen}%`;
    }

    const diff = item.NuevoPrecio - item.PrecioOriginal;
    let diffHtml = '';
    if (diff > 0) {
      diffHtml = `<span style="font-size: 0.72rem; color: #34d399; display: block; font-weight: 700;">+${formatNum(diff)} (+${Math.round((diff/item.PrecioOriginal)*100)}%)</span>`;
    } else if (diff < 0) {
      diffHtml = `<span style="font-size: 0.72rem; color: #ef4444; display: block; font-weight: 700;">${formatNum(diff)} (${Math.round((diff/item.PrecioOriginal)*100)}%)</span>`;
    }

    const rowBg = isModified ? 'rgba(245, 158, 11, 0.05)' : 'transparent';
    const borderLeft = isModified ? '3px solid #f59e0b' : '3px solid transparent';

    const packsCount = item.Packs.length;
    let packsBtnHtml = '<span style="color: var(--text-muted); font-size: 0.75rem;">Sin packs</span>';
    if (packsCount > 0) {
      const anyPackModif = item.Packs.some(p => p.NuevoPrecio !== p.PrecioOriginal);
      const badgeStyle = anyPackModif ? 'background: #f59e0b; color: #000;' : 'background: rgba(56,189,248,0.15); color: #38bdf8;';
      packsBtnHtml = `
        <button type="button" onclick="abrirModalPacks(${item.ProductoID})" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 6px;">
          <span class="badge" style="${badgeStyle} font-weight: 700; margin-right: 0.25rem;">${packsCount}</span> Ver Packs
        </button>
      `;
    }

    return `
      <tr style="background: ${rowBg}; border-bottom: 1px solid var(--border-dark); border-left: ${borderLeft};">
        <td style="padding: 0.75rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.78rem;">
          ${idx + 1}
        </td>
        <td style="padding: 0.75rem 1rem;">
          <div style="font-weight: 700; color: var(--text-main); font-size: 0.92rem;">${escapeHtml(item.Nombre)}</div>
          <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
            ${item.CodigoBarras ? '#' + item.CodigoBarras : 'Sin código'} &bull; <span style="font-family: inherit;">${escapeHtml(item.Categoria)}</span>
          </div>
        </td>
        <td style="padding: 0.75rem 1rem; color: var(--text-muted); font-family: monospace; font-size: 0.88rem;">
          $${formatNum(item.CostoOriginal)}
        </td>
        <td style="padding: 0.75rem 1rem;">
          <div style="position: relative;">
            <span style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;">$</span>
            <input type="number" id="inputCosto_${item.ProductoID}" value="${item.NuevoCosto}" min="0" step="1"
                   onchange="onCostoChange(${item.ProductoID}, this.value)"
                   class="form-control" style="padding-left: 1.4rem; height: 38px; font-weight: 700; font-family: monospace; font-size: 0.9rem; border-radius: 8px;">
          </div>
        </td>
        <td style="padding: 0.75rem 1rem; color: var(--text-muted); font-family: monospace; font-size: 0.88rem;">
          $${formatNum(item.PrecioOriginal)}
        </td>
        <td style="padding: 0.75rem 1rem;">
          <div style="position: relative;">
            <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--success); font-weight: 700; font-size: 0.85rem;">$</span>
            <input type="number" id="inputVenta_${item.ProductoID}" value="${item.NuevoPrecio}" min="0" step="1"
                   onchange="onPrecioVentaChange(${item.ProductoID}, this.value)"
                   onkeydown="onPrecioInputKeydown(event, ${idx})"
                   class="form-control" style="padding-left: 1.5rem; height: 38px; font-weight: 800; font-family: monospace; font-size: 1rem; color: var(--success); border-radius: 8px; border: 1.5px solid rgba(34, 197, 94, 0.4);">
          </div>
          ${diffHtml}
        </td>
        <td style="padding: 0.75rem 1rem; text-align: center;">
          <span class="badge ${margenBadgeClass}" style="font-size: 0.82rem; font-weight: 800; padding: 0.3rem 0.6rem;" title="${margen === null ? 'Sin costo de compra cargado' : 'Margen sobre costo'}">
            ${margenText}
          </span>
        </td>
        <td style="padding: 0.75rem 1rem; text-align: center;">
          ${packsBtnHtml}
        </td>
        <td style="padding: 0.75rem 1rem; text-align: center;">
          <button type="button" onclick="quitarItem(${item.ProductoID})" class="btn btn-secondary" 
                  style="padding: 0.25rem 0.45rem; font-size: 0.78rem; color: var(--danger); border-color: rgba(239,68,68,0.2);" title="Quitar de la lista">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function onPrecioVentaChange(productoId, valor) {
  const item = batchItems.find(i => i.ProductoID === productoId);
  if (!item) return;
  const num = parseInt(valor) || 0;
  item.NuevoPrecio = Math.max(0, num);
  renderBatchTable();
}

function onCostoChange(productoId, valor) {
  const item = batchItems.find(i => i.ProductoID === productoId);
  if (!item) return;
  const num = parseInt(valor) || 0;
  item.NuevoCosto = Math.max(0, num);
  renderBatchTable();
}

function onPrecioInputKeydown(e, idx) {
  if (e.key === 'Enter') {
    e.preventDefault();
    // Pasar al input de la fila siguiente o al buscador si es el último
    if (idx + 1 < batchItems.length) {
      const nextId = batchItems[idx + 1].ProductoID;
      const nextInput = document.getElementById(`inputVenta_${nextId}`);
      if (nextInput) {
        nextInput.focus();
        nextInput.select();
      }
    } else {
      document.getElementById('scanPreciosInput').focus();
    }
  }
}

function quitarItem(productoId) {
  batchItems = batchItems.filter(i => i.ProductoID !== productoId);
  renderBatchTable();
}

function limpiarLista() {
  if (batchItems.length === 0) return;
  if (!confirm('¿Deseas vaciar la lista actual de precios?')) return;
  batchItems = [];
  renderBatchTable();
}

// Herramientas en lote
function aplicarPorcentaje(porcentaje) {
  if (batchItems.length === 0) {
    mostrarAlerta('error', 'La lista está vacía. Agrega productos primero.');
    return;
  }
  const factor = 1 + (porcentaje / 100);
  batchItems.forEach(item => {
    item.NuevoPrecio = Math.round(item.PrecioOriginal * factor);
    // Ajustar proporcionalmente los packs si tienen precio fijo
    item.Packs.forEach(pk => {
      if (pk.PrecioOriginal !== null) {
        pk.NuevoPrecio = Math.round(pk.PrecioOriginal * factor);
      }
    });
  });
  renderBatchTable();
  mostrarAlerta('success', `Se aplicó un incremento de +${porcentaje}% a los ${batchItems.length} productos.`);
}

function pedirPorcentajePersonalizado() {
  if (batchItems.length === 0) {
    mostrarAlerta('error', 'La lista está vacía. Agrega productos primero.');
    return;
  }
  const str = prompt('Ingresa el porcentaje de incremento (ej: 8 para +8%, -5 para bajar 5%):', '10');
  if (str === null) return;
  const val = parseFloat(str);
  if (isNaN(val)) {
    alert('Porcentaje inválido');
    return;
  }
  aplicarPorcentaje(val);
}

function redondearPrecios(base) {
  if (batchItems.length === 0) return;
  batchItems.forEach(item => {
    item.NuevoPrecio = Math.round(item.NuevoPrecio / base) * base;
    item.Packs.forEach(pk => {
      if (pk.NuevoPrecio !== null) {
        pk.NuevoPrecio = Math.round(pk.NuevoPrecio / base) * base;
      }
    });
  });
  renderBatchTable();
  mostrarAlerta('success', `Precios redondeados a múltiplos de $${base}.`);
}

// ==============================================================
// ASISTENTE DE ESTIMACIÓN DE COSTOS POR MARGEN
// ==============================================================
function abrirModalEstimarCostos() {
  if (batchItems.length === 0) {
    mostrarAlerta('error', 'La lista de edición está vacía. Escanea productos o presiona "Sin Costo ($0)" arriba primero.');
    return;
  }
  document.getElementById('modalEstimarCostos').style.display = 'flex';
  actualizarPreviewEstimacion();
}

function cerrarModalEstimarCostos() {
  document.getElementById('modalEstimarCostos').style.display = 'none';
}

function setMargenChip(pct) {
  document.getElementById('estimarMargenPorc').value = pct;
  actualizarPreviewEstimacion();
}

function calcularCostoEstimado(precioVenta, margenPct, metodo, redondeoBase) {
  if (!precioVenta || precioVenta <= 0) return 0;
  let costo = 0;
  if (metodo === 'sobre_venta') {
    // Margen sobre venta: Ganancia = Venta * (Margen/100) -> Costo = Venta * (1 - Margen/100)
    costo = precioVenta * (1 - (margenPct / 100));
  } else {
    // Mark-up sobre costo: Venta = Costo * (1 + Margen/100) -> Costo = Venta / (1 + Margen/100)
    costo = precioVenta / (1 + (margenPct / 100));
  }
  costo = Math.max(0, costo);
  if (redondeoBase > 1) {
    costo = Math.round(costo / redondeoBase) * redondeoBase;
  } else {
    costo = Math.round(costo);
  }
  return costo;
}

function actualizarPreviewEstimacion() {
  const pct = parseFloat(document.getElementById('estimarMargenPorc').value) || 30;
  const metodo = document.querySelector('input[name="metodoCalculoCosto"]:checked')?.value || 'markup';
  const redondeo = parseInt(document.getElementById('selectRedondeoCosto').value) || 10;
  
  const precioSim = 1000;
  const costoSim = calcularCostoEstimado(precioSim, pct, metodo, redondeo);
  const margenEfectivo = calcularMargen(precioSim, costoSim);
  
  const metodoLabel = metodo === 'sobre_venta' ? 'sobre venta (comercial)' : 'sobre costo (mark-up)';
  const box = document.getElementById('previewEstimacionTxt');
  if (box) {
    box.innerHTML = `Para un producto vendido a <strong>$${formatNum(precioSim)}</strong> con <strong>${pct}%</strong> de margen ${metodoLabel}:<br>
    Costo estimado = <span style="color: #34d399; font-weight: 800; font-size: 1.05rem;">$${formatNum(costoSim)}</span> 
    (Margen efectivo resultante: <span style="color: #38bdf8; font-weight: 700;">${margenEfectivo !== null ? (margenEfectivo >= 0 ? '+' : '') + margenEfectivo + '%' : 'S/C'}</span>)`;
  }
}

function aplicarEstimacionCostos() {
  if (batchItems.length === 0) return;
  const pct = parseFloat(document.getElementById('estimarMargenPorc').value) || 30;
  const metodo = document.querySelector('input[name="metodoCalculoCosto"]:checked')?.value || 'markup';
  const ambito = document.querySelector('input[name="ambitoEstimacion"]:checked')?.value || 'solo_sin_costo';
  const redondeo = parseInt(document.getElementById('selectRedondeoCosto').value) || 10;

  let aplicados = 0;
  batchItems.forEach(item => {
    // Si se eligió "solo_sin_costo", solo modificar productos que tengan CostoOriginal = 0
    if (ambito === 'solo_sin_costo' && item.CostoOriginal > 0) {
      return;
    }
    const baseVenta = item.NuevoPrecio || item.PrecioOriginal;
    const nuevoCostoCalc = calcularCostoEstimado(baseVenta, pct, metodo, redondeo);
    item.NuevoCosto = nuevoCostoCalc;
    aplicados++;
  });

  cerrarModalEstimarCostos();
  renderBatchTable();
  mostrarAlerta('success', `<i class="fa-solid fa-circle-check"></i> ¡Se calculó y aplicó costo estimado a <strong>${aplicados} productos</strong> (margen ${pct}%)! Recuerda presionar <strong>Guardar Cambios (F10)</strong> para confirmar.`);
}

// Modal de Packs
function abrirModalPacks(productoId) {
  const item = batchItems.find(i => i.ProductoID === productoId);
  if (!item) return;
  currentEditingPackItem = item;

  document.getElementById('modalPackProdNombre').textContent = `${item.Nombre} (Venta Base: $${formatNum(item.NuevoPrecio)})`;
  const container = document.getElementById('modalPackListContainer');

  if (item.Packs.length === 0) {
    container.innerHTML = `<p style="text-align: center; color: var(--text-muted); padding: 1rem;">No hay packs configurados para este producto.</p>`;
  } else {
    container.innerHTML = item.Packs.map((pk, pIdx) => {
      const precioVal = pk.NuevoPrecio !== null ? pk.NuevoPrecio : '';
      return `
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
          <div>
            <strong style="color: #fff; font-size: 0.95rem;">${escapeHtml(pk.Descripcion)}</strong>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
              Código: ${escapeHtml(pk.CodigoBarras)} &bull; Contiene: ${pk.Cantidad} unid.
            </div>
          </div>
          <div style="width: 170px;">
            <label style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-bottom: 0.2rem; font-weight: 600;">PRECIO FIJO PACK ($)</label>
            <input type="number" value="${precioVal}" placeholder="Vacío = Auto" min="0" step="1"
                   onchange="onPackPrecioChange(${pIdx}, this.value)"
                   class="form-control" style="font-weight: 700; font-size: 0.9rem; font-family: monospace; border-radius: 8px;">
          </div>
        </div>
      `;
    }).join('');
  }

  document.getElementById('modalPacksItem').style.display = 'flex';
}

function onPackPrecioChange(pIdx, valor) {
  if (!currentEditingPackItem) return;
  const valTrim = valor.trim();
  currentEditingPackItem.Packs[pIdx].NuevoPrecio = valTrim !== '' ? parseInt(valTrim) : null;
  renderBatchTable();
}

function cerrarModalPacks() {
  document.getElementById('modalPacksItem').style.display = 'none';
  currentEditingPackItem = null;
}

// Guardar todos los precios masivamente
async function guardarTodosLosPrecios() {
  const modificados = batchItems.filter(i => {
    const prodModif = (i.NuevoPrecio !== i.PrecioOriginal) || (i.NuevoCosto !== i.CostoOriginal);
    const packModif = i.Packs.some(p => p.NuevoPrecio !== p.PrecioOriginal);
    return prodModif || packModif;
  });

  if (modificados.length === 0) {
    mostrarAlerta('info', 'No hay cambios pendientes por guardar.');
    return;
  }

  const payload = {
    cambios: modificados.map(i => ({
      producto_id: i.ProductoID,
      precio_venta: i.NuevoPrecio,
      costo_compra: i.NuevoCosto,
      packs: i.Packs.map(p => ({
        codigo_id: p.CodigoID,
        precio_venta: p.NuevoPrecio
      }))
    }))
  };

  const btnTop = document.getElementById('btnGuardarTop');
  const btnBottom = document.getElementById('btnGuardarBottom');
  btnTop.disabled = true;
  btnBottom.disabled = true;
  btnTop.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
  btnBottom.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

  try {
    const res = await fetch('api/guardar_cambio_precios.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (!data.success) {
      throw new Error(data.error || 'Error al guardar los cambios.');
    }

    // Actualizar precios originales con los nuevos valores guardados
    batchItems.forEach(i => {
      i.PrecioOriginal = i.NuevoPrecio;
      i.CostoOriginal = i.NuevoCosto;
      i.Packs.forEach(p => {
        p.PrecioOriginal = p.NuevoPrecio;
      });
    });

    renderBatchTable();
    mostrarAlerta('success', `<i class="fa-solid fa-circle-check"></i> ${data.mensaje}`);
  } catch (err) {
    mostrarAlerta('error', err.message);
  } finally {
    btnTop.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios (F10)';
    btnBottom.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Todos los Cambios (F10)';
    btnTop.disabled = false;
    btnBottom.disabled = false;
  }
}

// Modal Flejes de Góndola
function abrirModalFlejes() {
  if (batchItems.length === 0) return;
  const container = document.getElementById('flejesContainer');
  const hoyFmt = new Date().toLocaleDateString('es-CL');

  container.innerHTML = batchItems.map(item => `
    <div class="fleje-card">
      <div>
        <div class="fleje-header">${escapeHtml(item.Nombre)}</div>
        <div class="fleje-sku">${item.CodigoBarras ? 'EAN: ' + item.CodigoBarras : 'SKU #' + item.ProductoID}</div>
      </div>
      <div>
        <div class="fleje-price">$${formatNum(item.NuevoPrecio)}</div>
        <div class="fleje-footer">
          <span>${escapeHtml(item.Categoria)}</span>
          <span>Actualizado: ${hoyFmt}</span>
        </div>
      </div>
    </div>
  `).join('');

  document.getElementById('modalFlejes').style.display = 'flex';
}

function cerrarModalFlejes() {
  document.getElementById('modalFlejes').style.display = 'none';
}

function mostrarAlerta(tipo, html) {
  const al = document.getElementById('alertaPrecios');
  al.style.display = 'block';
  if (tipo === 'error') {
    al.style.background = 'rgba(239,68,68,0.15)';
    al.style.border = '1px solid rgba(239,68,68,0.3)';
    al.style.color = '#ef4444';
  } else if (tipo === 'info') {
    al.style.background = 'rgba(56,189,248,0.15)';
    al.style.border = '1px solid rgba(56,189,248,0.3)';
    al.style.color = '#38bdf8';
  } else {
    al.style.background = 'rgba(34,197,94,0.15)';
    al.style.border = '1px solid rgba(34,197,94,0.3)';
    al.style.color = '#22c55e';
  }
  al.innerHTML = html;
  if (tipo === 'success') {
    setTimeout(() => { al.style.display = 'none'; }, 6000);
  }
}

function formatNum(n) {
  return new Intl.NumberFormat('es-CL').format(n);
}

function escapeHtml(s) {
  if (!s) return '';
  return s.toString().replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}
</script>

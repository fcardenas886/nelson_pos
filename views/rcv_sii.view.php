<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem;">
      <i class="fa-solid fa-scale-balanced" style="color: #10b981;"></i>
      Centro de Cuadratura Fiscal: Caja POS vs. SII
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.2rem;">
      Auditoría día a día entre las ventas de caja y lo informado por Mercado Pago / SII
    </p>
  </div>
  <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
    <button onclick="abrirModalCredenciales()" class="btn btn-secondary" style="font-size: 0.85rem;">
      <i class="fa-solid fa-key" style="color: #f59e0b;"></i> Credenciales SII
    </button>
    <button onclick="consultarRCV('demo')" class="btn btn-secondary" style="font-size: 0.85rem; border-color: rgba(129, 140, 248, 0.4);">
      <i class="fa-solid fa-flask" style="color: #818cf8;"></i> Datos Demo
    </button>
    <button onclick="forzarSincronizacionSII()" class="btn btn-primary" id="btnSyncSii" style="font-size: 0.85rem;">
      <i class="fa-solid fa-arrows-rotate"></i> Sincronizar SII en Vivo
    </button>
  </div>
</div>

<!-- Banner de Estado de Persistencia en Base de Datos -->
<div id="bannerPersistencia" style="display: none; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.85rem; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
  <div style="display: flex; align-items: center; gap: 0.5rem;">
    <i class="fa-solid fa-bolt" style="color: #10b981; font-size: 1.1rem;"></i>
    <span>
      <strong style="color: #10b981;">Carga instantánea desde Base de Datos:</strong>
      <span id="txtInfoPersistencia" style="color: var(--text-main);">Información guardada localmente.</span>
    </span>
  </div>
  <button onclick="forzarSincronizacionSII()" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
    <i class="fa-solid fa-arrows-rotate"></i> Actualizar desde SII
  </button>
</div>

<!-- Barra de Control y Navegación de Pestañas -->
<div class="table-card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    
    <!-- Pestañas Principales -->
    <div style="display: flex; background: rgba(255,255,255,0.06); padding: 4px; border-radius: 8px; border: 1px solid var(--border-dark); gap: 4px;">
      <button type="button" id="tabNavCuadratura" onclick="cambiarVistaPrincipal('CUADRATURA')" 
              style="padding: 0.45rem 1rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s;">
        <i class="fa-solid fa-scale-balanced"></i> Cuadratura Diaria (Caja vs. SII)
      </button>
      <button type="button" id="tabNavCompras" onclick="cambiarVistaPrincipal('COMPRA')" 
              style="padding: 0.45rem 1rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s;">
        <i class="fa-solid fa-truck-ramp-box"></i> Compras (Proveedores)
      </button>
      <button type="button" id="tabNavVentas" onclick="cambiarVistaPrincipal('VENTA')" 
              style="padding: 0.45rem 1rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s;">
        <i class="fa-solid fa-receipt"></i> Ventas (SII)
      </button>
      <button type="button" id="tabNavImpuestos" onclick="cambiarVistaPrincipal('IMPUESTOS')" 
              style="padding: 0.45rem 1rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s;">
        <i class="fa-solid fa-coins"></i> 💰 Estimador Impuestos (F29)
      </button>
    </div>

    <!-- Selectores de Período (Mes y Año) -->
    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
      <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PERÍODO:</label>
      <select id="selectMes" class="form-control" style="width: 140px; padding: 0.45rem 0.6rem;" onchange="cambiarPeriodo()">
        <?php
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        foreach ($meses as $num => $nombre): ?>
          <option value="<?= sprintf('%02d', $num) ?>" <?= $num === $mesActual ? 'selected' : '' ?>>
            <?= $nombre ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="selectAnio" class="form-control" style="width: 100px; padding: 0.45rem 0.6rem;" onchange="cambiarPeriodo()">
        <?php for ($y = $anioActual + 1; $y >= $anioActual - 3; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $anioActual ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>

      <span id="badgeEstadoConsulta" class="badge" style="background: rgba(148, 163, 184, 0.2); color: #94a3b8; padding: 0.45rem 0.75rem;">
        <i class="fa-solid fa-circle" style="font-size: 0.55rem; margin-right: 4px;"></i> Sin consultar
      </span>
    </div>

  </div>
</div>

<!-- ========================================================================= -->
<!-- VISTA 1: CUADRATURA DIARIA (CAJA POS VS SII) -->
<!-- ========================================================================= -->
<div id="seccionCuadratura">
  <!-- KPIs de Resumen de Cuadratura del Mes -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Ventas Caja POS</h3>
        <div class="stat-value" id="kpiCuadCaja" style="color: #818cf8;">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
          Registrado en tickets locales
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(129, 140, 248, 0.15); color: #818cf8;">
        <i class="fa-solid fa-cash-register"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Informado en SII</h3>
        <div class="stat-value" id="kpiCuadSii" style="color: #38bdf8;">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
          Vía Mercado Pago / Boletas DTE
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
        <i class="fa-solid fa-building-columns"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Diferencia Neta Acumulada</h3>
        <div class="stat-value" id="kpiCuadDif" style="color: var(--success);">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;" id="kpiCuadDifDesc">
          Caja POS vs. SII
        </p>
      </div>
      <div class="stat-icon" id="kpiCuadDifIcon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
        <i class="fa-solid fa-scale-balanced"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Estado de Cuadre del Mes</h3>
        <div class="stat-value" id="kpiCuadExactitud" style="font-size: 1.35rem; color: #f59e0b;">0%</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;" id="kpiCuadExactitudSub">
          0 días cuadrados
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
        <i class="fa-solid fa-calendar-check"></i>
      </div>
    </div>
  </div>

  <!-- Tabla Día a Día -->
  <div class="table-card">
    <div class="table-header">
      <div style="display: flex; align-items: center; gap: 0.6rem;">
        <h2 style="font-size: 1.1rem; font-weight: 600;">
          <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i> Cuadratura Día por Día
        </h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Compara tickets locales de caja vs boletas en SII</span>
      </div>
      <div style="font-size: 0.85rem; color: var(--text-muted);">
        <i class="fa-solid fa-circle-info" style="color: #38bdf8;"></i> Toda venta pasada queda guardada en BD
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" id="tablaCuadratura">
        <thead>
          <tr>
            <th style="width: 140px;">Fecha</th>
            <th style="text-align: right; width: 140px;">Ventas Caja POS</th>
            <th style="text-align: right; width: 140px;">Ventas en SII</th>
            <th style="text-align: right; width: 140px;">Diferencia ($)</th>
            <th style="text-align: center; width: 170px;">Estado Fiscal</th>
            <th style="text-align: center; width: 120px;">Acción</th>
          </tr>
        </thead>
        <tbody id="tbodyCuadratura">
          <tr>
            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
              <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary); display: block; margin-bottom: 0.5rem;"></i>
              Calculando cuadratura diaria...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- VISTA 2 y 3: DETALLE DE DOCUMENTOS (COMPRAS Y VENTAS) -->
<!-- ========================================================================= -->
<div id="seccionDocumentos" style="display: none;">
  <!-- Tarjetas KPI de Resumen Fiscal -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Documentos</h3>
        <div class="stat-value" id="kpiTotalDocs">0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;" id="kpiSubDocs">
          Registros procesados
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
        <i class="fa-solid fa-file-invoice"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Monto Neto</h3>
        <div class="stat-value" id="kpiMontoNeto">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
          Base Imponible Afecta
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
        <i class="fa-solid fa-coins"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3 id="lblKpiIva">IVA</h3>
        <div class="stat-value" id="kpiMontoIva" style="color: #38bdf8;">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
          19% Impuesto al Valor Agregado
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
        <i class="fa-solid fa-percent"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Monto Total</h3>
        <div class="stat-value" id="kpiMontoTotal" style="color: var(--success);">$0</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
          Total Fiscal del Período
        </p>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
        <i class="fa-solid fa-sack-dollar"></i>
      </div>
    </div>
  </div>

  <!-- Tabla Detallada -->
  <div class="table-card">
    <div class="table-header" style="flex-wrap: wrap; gap: 0.75rem;">
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <h2 style="font-size: 1.1rem; font-weight: 600;" id="lblTituloTabla">Detalle de Compras</h2>
        <span id="badgeCantidadFiltrada" style="background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; color: var(--text-muted);">0 items</span>
      </div>

      <!-- Filtros de búsqueda rápida -->
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <!-- Filtro por Día puntual -->
        <input type="date" id="filtroFechaDoc" class="form-control" style="width: 150px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="filtrarTabla()" title="Filtrar por día específico">

        <input type="text" id="filtroTexto" placeholder="Buscar folio, RUT o razón social..." 
               class="form-control" style="width: 230px; padding: 0.4rem 0.75rem; font-size: 0.85rem;" oninput="filtrarTabla()">
        
        <select id="filtroTipoDte" class="form-control" style="width: 160px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="filtrarTabla()">
          <option value="">Todos los Tipos</option>
          <option value="33">Factura Electrónica (33)</option>
          <option value="34">Factura Exenta (34)</option>
          <option value="61">Nota de Crédito (61)</option>
          <option value="39">Boleta Electrónica (39)</option>
        </select>

        <select id="filtroConciliacion" class="form-control" style="width: 150px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="filtrarTabla()">
          <option value="">Todas</option>
          <option value="si">✓ Ingresadas</option>
          <option value="no">⚠️ Pendientes</option>
        </select>

        <button onclick="exportarCSV()" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;" title="Exportar tabla a archivo CSV">
          <i class="fa-solid fa-file-csv"></i> Exportar
        </button>
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" id="tablaRcv">
        <thead>
          <tr>
            <th style="width: 140px;">Tipo DTE</th>
            <th style="width: 90px;">Folio</th>
            <th style="width: 110px;">Fecha</th>
            <th style="width: 120px;" id="thRutContraparte">RUT Contraparte</th>
            <th id="thRazonContraparte">Razón Social</th>
            <th style="text-align: right; width: 100px;">Neto</th>
            <th style="text-align: right; width: 90px;">IVA</th>
            <th style="text-align: right; width: 110px;">Total</th>
            <th style="text-align: center; width: 140px;">Minimarket</th>
          </tr>
        </thead>
        <tbody id="tbodyRcv">
          <tr>
            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
              Cargando documentos...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- VISTA 4: ESTIMADOR DE IMPUESTOS (F29: IVA DÉBITO - CRÉDITO + PPM) -->
<!-- ========================================================================= -->
<div id="seccionImpuestos" style="display: none;">

  <!-- Banner Informativo del Período -->
  <div class="table-card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #818cf8; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-calculator" style="color: #818cf8;"></i>
        Proyección de Impuestos Mensuales (Formulario 29)
      </h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem; margin-bottom: 0;">
        Estimación automática calculada a partir de compras recibidas y ventas informadas en el SII para <strong id="lblPeriodoImpuestosTexto" style="color: var(--text-main);">este mes</strong>.
      </p>
    </div>
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <button onclick="forzarSincronizacionSII()" class="btn btn-secondary" style="font-size: 0.85rem;">
        <i class="fa-solid fa-arrows-rotate"></i> Sincronizar Mes Completo
      </button>
      <button onclick="imprimirResumenImpuestos()" class="btn btn-secondary" style="font-size: 0.85rem;">
        <i class="fa-solid fa-print"></i> Imprimir Reporte
      </button>
    </div>
  </div>

  <!-- Alerta si no hay datos sincronizados -->
  <div id="alertaSinDatosImpuestos" style="display: none; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b; font-size: 1.5rem;"></i>
      <div>
        <strong style="color: #f59e0b;">Sin registros fiscales para este período en la base de datos local.</strong>
        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 2px;">
          Haz clic en sincronizar para consultar automáticamente las compras y ventas del mes en el SII.
        </div>
      </div>
    </div>
    <button onclick="forzarSincronizacionSII()" class="btn btn-primary" style="font-size: 0.85rem;">
      <i class="fa-solid fa-arrows-rotate"></i> Sincronizar Ahora
    </button>
  </div>

  <!-- Grid Comparativo: Ventas vs Compras vs PPM -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
    
    <!-- TARJETA 1: VENTAS (IVA DÉBITO FISCAL) -->
    <div class="table-card" style="padding: 1.25rem; border-top: 4px solid #38bdf8;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: #38bdf8;">
          <i class="fa-solid fa-receipt"></i> Ventas (Débito Fiscal)
        </h3>
        <span class="badge" id="badgeCantVentasImp" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-weight: 600;">
          0 Docs
        </span>
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        Ventas totales informadas al SII (Boletas Mercado Pago + Facturas emitidas)
      </p>

      <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-dark);">
          <span style="color: var(--text-muted);">Venta Neta (Base Imponible):</span>
          <strong id="impVentaNeto" style="font-family: monospace; font-size: 1rem;">$0</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-dark);">
          <span style="color: #38bdf8; font-weight: 600;">(+) IVA Débito Fiscal (19%):</span>
          <strong id="impIvaDebito" style="font-family: monospace; font-size: 1.1rem; color: #38bdf8;">$0</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding-top: 0.25rem;">
          <span style="color: var(--text-muted);">Total Bruto en Ventas:</span>
          <strong id="impVentaTotal" style="font-family: monospace; font-size: 1rem; color: var(--text-main);">$0</strong>
        </div>
      </div>

      <div style="margin-top: 1.25rem; background: rgba(56, 189, 248, 0.08); border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.8rem; color: var(--text-muted);">
        <i class="fa-solid fa-circle-info" style="color: #38bdf8;"></i> Este es el impuesto recaudado que le cobraste a tus clientes y que debes rendir al Fisco.
      </div>
    </div>

    <!-- TARJETA 2: COMPRAS (IVA CRÉDITO FISCAL) -->
    <div class="table-card" style="padding: 1.25rem; border-top: 4px solid #10b981;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: #10b981;">
          <i class="fa-solid fa-truck-ramp-box"></i> Compras (Crédito Fiscal)
        </h3>
        <span class="badge" id="badgeCantComprasImp" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 600;">
          0 Facturas
        </span>
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        Facturas de mercadería e insumos recibidas de tus proveedores en el mes
      </p>

      <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-dark);">
          <span style="color: var(--text-muted);">Compra Neta (Afecta):</span>
          <strong id="impCompraNeto" style="font-family: monospace; font-size: 1rem;">$0</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-dark);">
          <span style="color: #10b981; font-weight: 600;">(-) IVA Crédito Fiscal (19%):</span>
          <strong id="impIvaCredito" style="font-family: monospace; font-size: 1.1rem; color: #10b981;">$0</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding-top: 0.25rem;">
          <span style="color: var(--text-muted);">Total Bruto en Compras:</span>
          <strong id="impCompraTotal" style="font-family: monospace; font-size: 1rem; color: var(--text-main);">$0</strong>
        </div>
      </div>

      <div style="margin-top: 1.25rem; background: rgba(16, 185, 129, 0.08); border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.8rem; color: var(--text-muted);">
        <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Este IVA ya se lo pagaste a tus proveedores y se resta 100% de tus impuestos a pagar.
      </div>
    </div>

    <!-- TARJETA 3: PPM (PAGO PROVISIONAL MENSUAL) -->
    <div class="table-card" style="padding: 1.25rem; border-top: 4px solid #f59e0b;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: #f59e0b;">
          <i class="fa-solid fa-percent"></i> PPM (Anticipo de Renta)
        </h3>
        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-weight: 600;">
          Renta F22
        </span>
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
        Anticipo mensual de impuesto a la renta calculado sobre las ventas netas
      </p>

      <div style="margin-bottom: 1rem;">
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">
          Tasa PPM Aplicable:
        </label>
        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
          <button type="button" class="btn btn-secondary btn-tasa-ppm" onclick="seleccionarTasaPpm(0.125, this)" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">0.125%</button>
          <button type="button" class="btn btn-primary btn-tasa-ppm" onclick="seleccionarTasaPpm(0.25, this)" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">0.25% (Pyme)</button>
          <button type="button" class="btn btn-secondary btn-tasa-ppm" onclick="seleccionarTasaPpm(0.50, this)" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">0.50%</button>
          <button type="button" class="btn btn-secondary btn-tasa-ppm" onclick="seleccionarTasaPpm(1.00, this)" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">1.00%</button>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <span style="font-size: 0.8rem; color: var(--text-muted);">Personalizada:</span>
          <input type="number" step="0.01" min="0" max="15" id="inputTasaPpm" value="0.25" class="form-control" 
                 style="width: 80px; padding: 0.25rem 0.5rem; font-size: 0.85rem;" onchange="ajustarTasaManual(this.value)">
          <span style="font-size: 0.85rem; font-weight: 600;">%</span>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; font-size: 0.95rem; padding-top: 0.5rem; border-top: 1px solid var(--border-dark);">
        <span style="color: #f59e0b; font-weight: 600;">(+) PPM Estimado a Pagar:</span>
        <strong id="impPpmEstimado" style="font-family: monospace; font-size: 1.1rem; color: #f59e0b;">$0</strong>
      </div>

      <div style="margin-top: 1.25rem; background: rgba(245, 158, 11, 0.08); border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.8rem; color: var(--text-muted);">
        <i class="fa-solid fa-piggy-bank" style="color: #f59e0b;"></i> Este monto no se pierde: se acumula como ahorro para tu declaración anual de renta de abril.
      </div>
    </div>

  </div>

  <!-- LIQUIDACIÓN FINAL F29 (RESULTADO DESTACADO) -->
  <div class="table-card" style="padding: 1.5rem; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.9) 100%); border: 2px solid var(--primary); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
      <div>
        <span class="badge" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; font-size: 0.8rem; margin-bottom: 0.4rem; padding: 0.35rem 0.65rem;">
          RESUMEN TRIBUTARIO MENSUAL
        </span>
        <h2 style="font-size: 1.35rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
          <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i>
          Total Estimado a Pagar F29 (SII)
        </h2>
      </div>

      <!-- Estado visual de IVA -->
      <div id="badgeResultadoIva" style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
        <!-- Se llena dinámicamente con JS -->
      </div>
    </div>

    <!-- Desglose Tipo Factura / Liquidación -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; align-items: center;">
      
      <!-- Detalle numérico -->
      <div style="display: flex; flex-direction: column; gap: 0.65rem; background: rgba(0,0,0,0.25); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-dark);">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
          <span style="color: var(--text-muted);">(+) IVA Débito (Ventas):</span>
          <span id="liqIvaDebito" style="font-family: monospace; color: #38bdf8;">$0</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
          <span style="color: var(--text-muted);">(-) IVA Crédito (Compras):</span>
          <span id="liqIvaCredito" style="font-family: monospace; color: #10b981;">-$0</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; padding-top: 0.4rem; border-top: 1px dashed var(--border-dark); font-weight: 600;">
          <span id="lblLiqIvaSubtotal">(=) Subtotal IVA a Pagar:</span>
          <span id="liqIvaSubtotal" style="font-family: monospace; color: var(--text-main);">$0</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: 600;">
          <span style="color: var(--text-muted);">(+) PPM Anticipo Renta:</span>
          <span id="liqPpmSubtotal" style="font-family: monospace; color: #f59e0b;">$0</span>
        </div>
      </div>

      <!-- Gran Total a Pagar -->
      <div style="text-align: center; padding: 1rem;">
        <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; display: block; margin-bottom: 0.25rem;">
          MONTO ESTIMADO DECLARACIÓN F29
        </span>
        <div id="liqTotalF29" style="font-size: 2.75rem; font-weight: 800; font-family: monospace; color: #fbbf24; text-shadow: 0 4px 12px rgba(251, 191, 36, 0.25); line-height: 1.1;">
          $0
        </div>
        <p id="txtSubtextoLiquidacion" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">
          A pagar en sii.cl antes del día 20 del mes siguiente
        </p>
      </div>

    </div>

    <!-- Banner Remanente a Favor (si aplica) -->
    <div id="bannerRemanenteFavor" style="display: none; margin-top: 1.25rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); padding: 0.85rem 1.1rem; border-radius: 8px; align-items: center; gap: 0.75rem;">
      <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 1.4rem;"></i>
      <div style="font-size: 0.85rem; color: var(--text-main);">
        <strong style="color: #10b981;">¡Tienes Remanente de Crédito Fiscal a Favor!</strong>
        <span style="margin-left: 4px;">Tus compras con factura superaron tus ventas afectas por <strong id="lblMontoRemanente" style="color: #10b981;">$0</strong>. Este monto no se pierde y quedará como saldo a tu favor para descontar del IVA del mes siguiente.</span>
      </div>
    </div>

    <!-- Footer Informativo y Botones de Acción -->
    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; font-size: 0.85rem;">
      <div style="color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-regular fa-calendar-check" style="color: #818cf8;"></i>
        <span>Vencimiento pago F29 con Boleta Electrónica: <strong>Día 20 del mes siguiente</strong>.</span>
      </div>
      <div>
        <a href="https://homer.sii.cl" target="_blank" class="btn btn-primary" style="font-size: 0.85rem; text-decoration: none;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Ingresar a sii.cl para Declarar
        </a>
      </div>
    </div>

  </div>

  <!-- Guía Rápida para el Comerciante -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; font-size: 0.85rem; margin-bottom: 2rem;">
    <div class="table-card" style="padding: 1rem 1.25rem;">
      <h4 style="font-weight: 700; color: #818cf8; margin-top: 0; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
        <i class="fa-solid fa-credit-card"></i> Mercado Pago Point
      </h4>
      <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">
        Todas las transacciones procesadas por la máquina Mercado Pago emiten comprobante electrónico con validez de Boleta (Tipo 39). Su IVA Débito (19%) ya está incluido en este reporte automáticamente.
      </p>
    </div>
    <div class="table-card" style="padding: 1rem 1.25rem;">
      <h4 style="font-weight: 700; color: #10b981; margin-top: 0; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
        <i class="fa-solid fa-file-invoice"></i> ¿Cómo pagar menos IVA legalmente?
      </h4>
      <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">
        Pide siempre <strong>Factura con RUT de tu empresa</strong> a todos tus proveedores (bebidas, cecinas, panadería, abarrotes, luz, arriendo comercial). Cada $100.000 netos te descuentan $19.000 directos de IVA.
      </p>
    </div>
    <div class="table-card" style="padding: 1rem 1.25rem;">
      <h4 style="font-weight: 700; color: #f59e0b; margin-top: 0; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
        <i class="fa-solid fa-circle-question"></i> ¿Qué es el PPM?
      </h4>
      <p style="color: var(--text-muted); margin: 0; line-height: 1.45;">
        El Pago Provisional Mensual es un ahorro obligatorio anticipado para tu Impuesto de Primera Categoría (Operación Renta F22). En las pymes (Régimen ProPyme) suele ser de solo el 0.25% de la venta neta.
      </p>
    </div>
  </div>

</div>

<!-- Modal de Credenciales SII -->
<div id="modalCredenciales" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1050; justify-content: center; align-items: center; padding: 1rem;">
  <div style="background: var(--bg-card); width: 100%; max-width: 480px; border-radius: 12px; border: 1px solid var(--border-dark); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); overflow: hidden;">
    <div style="padding: 1.25rem; border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-shield-halved" style="color: #f59e0b;"></i> Credenciales del SII
      </h3>
      <button type="button" onclick="cerrarModalCredenciales()" style="background: transparent; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
      <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
        Ingresa tus credenciales del SII. Quedarán guardadas de forma segura para sincronizaciones automáticas.
      </p>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">
          RUT DEL USUARIO / CONSULTOR
        </label>
        <input type="text" id="modalRutUsuario" class="form-control" placeholder="Ej: 76711901-1" 
               value="<?= htmlspecialchars($rutUsuarioGuardado) ?>" required>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">
          CLAVE TRIBUTARIA DEL SII
        </label>
        <div style="position: relative;">
          <input type="password" id="modalClaveUsuario" class="form-control" placeholder="Contraseña secreta portal sii.cl" 
                 value="<?= htmlspecialchars($claveGuardada) ?>" required style="padding-right: 2.5rem;">
          <button type="button" onclick="togglePasswordVisibility('modalClaveUsuario', this)" 
                  style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">
          RUT DE LA EMPRESA / CONTRIBUYENTE
        </label>
        <input type="text" id="modalRutEmpresa" class="form-control" placeholder="Ej: 76711901-1" 
               value="<?= htmlspecialchars($rutEmpresaGuardado) ?>" required>
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
        <input type="checkbox" id="modalRecordarCreds" checked style="accent-color: var(--primary);">
        <label for="modalRecordarCreds" style="font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
          Guardar estas credenciales en el sistema
        </label>
      </div>

      <div id="modalCredsFeedback" style="display: none; padding: 0.65rem 0.85rem; border-radius: 6px; font-size: 0.85rem;"></div>
    </div>

    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-dark); background: rgba(0,0,0,0.15); display: flex; justify-content: flex-end; gap: 0.6rem;">
      <button type="button" onclick="cerrarModalCredenciales()" class="btn btn-secondary">Cancelar</button>
      <button type="button" onclick="guardarYConsultarCredenciales()" class="btn btn-primary">
        <i class="fa-solid fa-check"></i> Guardar y Usar
      </button>
    </div>
  </div>
</div>

<script>
window.CSRF_TOKEN = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '<?= csrfToken() ?>';

// Estado de la pantalla
let vistaPrincipal = 'CUADRATURA'; // 'CUADRATURA', 'COMPRA', 'VENTA'
let todosLosDocumentos = [];
let totalesActuales = {};
let cuadraturaDias = [];

function formatoCLP(monto) {
  return '$' + Number(monto || 0).toLocaleString('es-CL');
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

function abrirModalCredenciales() {
  document.getElementById('modalCredenciales').style.display = 'flex';
}
function cerrarModalCredenciales() {
  document.getElementById('modalCredenciales').style.display = 'none';
}

// Cambiar pestaña principal (Cuadratura Diaria vs Compras vs Ventas vs Impuestos)
function cambiarVistaPrincipal(vista) {
  vistaPrincipal = vista;
  const btnCuad = document.getElementById('tabNavCuadratura');
  const btnComp = document.getElementById('tabNavCompras');
  const btnVent = document.getElementById('tabNavVentas');
  const btnImp = document.getElementById('tabNavImpuestos');

  const secCuad = document.getElementById('seccionCuadratura');
  const secDocs = document.getElementById('seccionDocumentos');
  const secImp = document.getElementById('seccionImpuestos');

  [btnCuad, btnComp, btnVent, btnImp].forEach(b => {
    if (b) {
      b.style.background = 'transparent';
      b.style.color = 'var(--text-muted)';
    }
  });

  if (vista === 'CUADRATURA') {
    btnCuad.style.background = 'var(--primary)';
    btnCuad.style.color = '#fff';
    secCuad.style.display = 'block';
    secDocs.style.display = 'none';
    secImp.style.display = 'none';
    cargarCuadraturaDiaria();
  } else if (vista === 'COMPRA') {
    btnComp.style.background = 'var(--primary)';
    btnComp.style.color = '#fff';
    secCuad.style.display = 'none';
    secDocs.style.display = 'block';
    secImp.style.display = 'none';
    document.getElementById('lblTituloTabla').textContent = 'Facturas de Compras Recibidas (Proveedores)';
    document.getElementById('lblKpiIva').textContent = 'IVA Crédito Fiscal';
    document.getElementById('thRutContraparte').textContent = 'RUT Proveedor';
    document.getElementById('thRazonContraparte').textContent = 'Razón Social Proveedor';
    consultarRCV('sii', false, 'COMPRA');
  } else if (vista === 'VENTA') {
    btnVent.style.background = 'var(--primary)';
    btnVent.style.color = '#fff';
    secCuad.style.display = 'none';
    secDocs.style.display = 'block';
    secImp.style.display = 'none';
    document.getElementById('lblTituloTabla').textContent = 'Boletas y Facturas Emitidas (Ventas)';
    document.getElementById('lblKpiIva').textContent = 'IVA Débito Fiscal';
    document.getElementById('thRutContraparte').textContent = 'RUT Cliente / Receptor';
    document.getElementById('thRazonContraparte').textContent = 'Razón Social';
    consultarRCV('sii', false, 'VENTA');
  } else if (vista === 'IMPUESTOS') {
    btnImp.style.background = 'var(--primary)';
    btnImp.style.color = '#fff';
    secCuad.style.display = 'none';
    secDocs.style.display = 'none';
    secImp.style.display = 'block';
    cargarEstimacionImpuestos();
  }
}

function cambiarPeriodo() {
  if (vistaPrincipal === 'CUADRATURA') {
    cargarCuadraturaDiaria();
  } else if (vistaPrincipal === 'IMPUESTOS') {
    cargarEstimacionImpuestos();
  } else {
    consultarRCV('sii', false, vistaPrincipal);
  }
}

async function forzarSincronizacionSII() {
  const btnSync = document.getElementById('btnSyncSii');

  if (vistaPrincipal === 'CUADRATURA') {
    // Sincronizar tanto ventas como compras y recalcular
    consultarRCV('sii', true, 'VENTA', () => {
      cargarCuadraturaDiaria();
    });
  } else if (vistaPrincipal === 'IMPUESTOS') {
    // Sincronizar Compras y Ventas consecutivamente
    btnSync.disabled = true;
    btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando Compras...';
    try {
      await sincronizarOperacionDirecta('COMPRA');
      btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando Ventas...';
      await sincronizarOperacionDirecta('VENTA');
      await cargarEstimacionImpuestos();
    } catch(err) {
      alert('Error en sincronización con SII: ' + err.message);
    } finally {
      btnSync.disabled = false;
      btnSync.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Sincronizar SII en Vivo';
    }
  } else {
    consultarRCV('sii', true, vistaPrincipal);
  }
}

// -------------------------------------------------------------
// 1. CARGAR CUADRATURA DIARIA (Caja POS vs SII)
// -------------------------------------------------------------
async function cargarCuadraturaDiaria() {
  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;
  const periodo = `${anio}${mes}`;

  const tbody = document.getElementById('tbodyCuadratura');
  tbody.innerHTML = `
    <tr>
      <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary); display: block; margin-bottom: 0.5rem;"></i>
        Calculando auditoría de ventas día por día...
      </td>
    </tr>
  `;

  try {
    const res = await fetch('api/rcv_sii.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({ action: 'cuadratura_diaria', periodo: periodo })
    });
    const data = await res.json();

    if (!data.success) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #ef4444; padding: 2rem;">${data.error}</td></tr>`;
      return;
    }

    cuadraturaDias = data.dias || [];
    const r = data.resumen || {};

    // Actualizar KPIs de Cuadratura
    document.getElementById('kpiCuadCaja').textContent = formatoCLP(r.total_caja);
    document.getElementById('kpiCuadSii').textContent = formatoCLP(r.total_sii);

    const kpiDif = document.getElementById('kpiCuadDif');
    const kpiDifIcon = document.getElementById('kpiCuadDifIcon');
    kpiDif.textContent = formatoCLP(r.diferencia);

    if (r.diferencia === 0) {
      kpiDif.style.color = '#10b981';
      kpiDifIcon.style.color = '#10b981';
      kpiDifIcon.style.background = 'rgba(16, 185, 129, 0.15)';
      document.getElementById('kpiCuadDifDesc').textContent = '✓ Totalmente cuadrado';
    } else {
      kpiDif.style.color = '#ef4444';
      kpiDifIcon.style.color = '#ef4444';
      kpiDifIcon.style.background = 'rgba(239, 68, 68, 0.15)';
      document.getElementById('kpiCuadDifDesc').textContent = r.diferencia > 0 ? 'Faltan en SII' : 'Sobran en SII';
    }

    const totalEvaluados = r.dias_cuadrados + r.dias_con_diferencia;
    const pct = totalEvaluados > 0 ? Math.round((r.dias_cuadrados / totalEvaluados) * 100) : 100;
    document.getElementById('kpiCuadExactitud').textContent = pct + '%';
    document.getElementById('kpiCuadExactitudSub').textContent = `${r.dias_cuadrados} de ${totalEvaluados} días cuadrados`;

    // Renderizar Filas de Cuadratura
    if (cuadraturaDias.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay registros para este período.</td></tr>`;
      return;
    }

    let html = '';
    cuadraturaDias.forEach(dia => {
      let badgeEstado = '';
      let colorDif = 'var(--text-main)';

      if (dia.estado === 'CUADRADO') {
        badgeEstado = `<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.8rem; padding: 0.3rem 0.6rem;"><i class="fa-solid fa-circle-check"></i> Cuadrado</span>`;
        colorDif = '#10b981';
      } else if (dia.estado === 'SIN_VENTAS') {
        badgeEstado = `<span class="badge" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; font-size: 0.8rem; padding: 0.3rem 0.6rem;">Sin Ventas</span>`;
        colorDif = 'var(--text-muted)';
      } else if (dia.estado === 'FALTAN_EN_SII') {
        badgeEstado = `<span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 0.8rem; padding: 0.3rem 0.6rem;"><i class="fa-solid fa-triangle-exclamation"></i> Faltan en SII</span>`;
        colorDif = '#ef4444';
      } else {
        badgeEstado = `<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.8rem; padding: 0.3rem 0.6rem;"><i class="fa-solid fa-circle-exclamation"></i> Sobran en SII</span>`;
        colorDif = '#f59e0b';
      }

      // Nombre amigable de día
      const dObj = new Date(dia.fecha + 'T00:00:00');
      const formatoFecha = dObj.toLocaleDateString('es-CL', { weekday: 'short', day: '2-digit', month: '2-digit' });

      html += `
        <tr>
          <td style="font-weight: 600; color: #fff;">${formatoFecha}</td>
          <td style="text-align: right; font-weight: 600; color: #818cf8;">
            ${formatoCLP(dia.total_caja)}
            <small style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">${dia.cant_caja} tickets</small>
          </td>
          <td style="text-align: right; font-weight: 600; color: #38bdf8;">
            ${formatoCLP(dia.total_sii)}
            <small style="display: block; font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">${dia.cant_sii} DTEs</small>
          </td>
          <td style="text-align: right; font-weight: 700; color: ${colorDif};">
            ${dia.diferencia > 0 ? '+' : ''}${formatoCLP(dia.diferencia)}
          </td>
          <td style="text-align: center;">${badgeEstado}</td>
          <td style="text-align: center;">
            <button onclick="verDetalleDia('${dia.fecha}')" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
              <i class="fa-solid fa-eye"></i> Ver Detalle
            </button>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #ef4444; padding: 2rem;">Error: ${err.message}</td></tr>`;
  }
}

// Filtrar documentos al hacer clic en "Ver Detalle" de un día
function verDetalleDia(fecha) {
  cambiarVistaPrincipal('VENTA');
  document.getElementById('filtroFechaDoc').value = fecha;
  filtrarTabla();
}

// -------------------------------------------------------------
// 2. CONSULTAR DOCUMENTOS (Compras o Ventas)
// -------------------------------------------------------------
async function consultarRCV(modo, forzarSync = false, operacion = 'COMPRA', callback = null) {
  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;
  const periodo = `${anio}${mes}`;

  const rutUsuario = document.getElementById('modalRutUsuario').value.trim();
  const claveUsuario = document.getElementById('modalClaveUsuario').value.trim();
  const rutEmpresa = document.getElementById('modalRutEmpresa').value.trim();

  const btnSync = document.getElementById('btnSyncSii');
  const badgeEstado = document.getElementById('badgeEstadoConsulta');
  const banner = document.getElementById('bannerPersistencia');
  const tbody = document.getElementById('tbodyRcv');

  btnSync.disabled = true;
  btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Conectando...';

  tbody.innerHTML = `
    <tr>
      <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2.5rem; color: var(--primary); display: block; margin-bottom: 0.75rem;"></i>
        ${forzarSync ? 'Conectando con el SII en vivo...' : 'Cargando registros...'}
      </td>
    </tr>
  `;

  try {
    const res = await fetch('api/rcv_sii.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({
        action: 'consultar',
        modo: modo,
        forzar_sync: forzarSync,
        periodo: periodo,
        operacion: operacion,
        rut_usuario: rutUsuario,
        clave_usuario: claveUsuario,
        rut_empresa: rutEmpresa
      })
    });

    const data = await res.json();

    if (!data.success) {
      badgeEstado.style.background = 'rgba(239, 68, 68, 0.2)';
      badgeEstado.style.color = '#ef4444';
      badgeEstado.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error';
      banner.style.display = 'none';

      tbody.innerHTML = `
        <tr>
          <td colspan="9" style="text-align: center; color: #ef4444; padding: 2.5rem 1rem;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
            <strong>Error al obtener información:</strong><br>${data.error}
          </td>
        </tr>
      `;
      return;
    }

    todosLosDocumentos = data.documentos || [];
    totalesActuales = data.totales || {};

    // Aviso de si vino de BD o del SII
    if (data.origen === 'db') {
      banner.style.display = 'flex';
      document.getElementById('txtInfoPersistencia').textContent = `Datos del período ${mes}/${anio} recuperados al instante. Última sincronización con SII: ${data.fecha_sincronizacion || 'Reciente'}.`;
      badgeEstado.style.background = 'rgba(16, 185, 129, 0.2)';
      badgeEstado.style.color = '#10b981';
      badgeEstado.innerHTML = '<i class="fa-solid fa-bolt"></i> Desde BD Local';
    } else if (data.origen === 'sii') {
      banner.style.display = 'flex';
      document.getElementById('txtInfoPersistencia').textContent = `¡Sincronizado con el SII en vivo con éxito y guardado en Base de Datos!`;
      badgeEstado.style.background = 'rgba(16, 185, 129, 0.2)';
      badgeEstado.style.color = '#10b981';
      badgeEstado.innerHTML = '<i class="fa-solid fa-circle-check"></i> Sincronizado SII';
    } else {
      banner.style.display = 'none';
      badgeEstado.style.background = 'rgba(129, 140, 248, 0.2)';
      badgeEstado.style.color = '#818cf8';
      badgeEstado.innerHTML = '<i class="fa-solid fa-flask"></i> Modo Demo';
    }

    actualizarKPIs(totalesActuales, todosLosDocumentos.length);
    filtrarTabla();

    if (callback) callback();

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: #ef4444; padding: 2rem;">Error de conexión: ${err.message}</td></tr>`;
  } finally {
    btnSync.disabled = false;
    btnSync.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Sincronizar SII en Vivo';
  }
}

function actualizarKPIs(totales, cantidad) {
  document.getElementById('kpiTotalDocs').textContent = cantidad;
  document.getElementById('kpiSubDocs').textContent = `${cantidad} documentos`;
  document.getElementById('kpiMontoNeto').textContent = formatoCLP(totales.total_neto);
  document.getElementById('kpiMontoIva').textContent = formatoCLP(totales.total_iva);
  document.getElementById('kpiMontoTotal').textContent = formatoCLP(totales.total_general);
}

function filtrarTabla() {
  const query = document.getElementById('filtroTexto').value.toLowerCase().trim();
  const filtroTipo = document.getElementById('filtroTipoDte').value;
  const filtroConc = document.getElementById('filtroConciliacion').value;
  const filtroFch = document.getElementById('filtroFechaDoc').value; // YYYY-MM-DD

  const filtrados = todosLosDocumentos.filter(doc => {
    // Filtro por fecha específica
    if (filtroFch) {
      const fchDocLimpia = (doc.fecha_emision || '').replace(/\//g, '-');
      if (!fchDocLimpia.startsWith(filtroFch)) {
        return false;
      }
    }

    if (query) {
      const matchFolio = String(doc.folio).includes(query);
      const matchRut = (doc.rut_contraparte || '').toLowerCase().includes(query);
      const matchRazon = (doc.razon_social || '').toLowerCase().includes(query);
      if (!matchFolio && !matchRut && !matchRazon) return false;
    }

    if (filtroTipo && String(doc.tipo_dte) !== filtroTipo) {
      return false;
    }

    if (filtroConc === 'si' && !doc.conciliado) return false;
    if (filtroConc === 'no' && doc.conciliado) return false;

    return true;
  });

  renderizarTabla(filtrados);
}

function renderizarTabla(docs) {
  const tbody = document.getElementById('tbodyRcv');
  const badgeCantidad = document.getElementById('badgeCantidadFiltrada');
  badgeCantidad.textContent = `${docs.length} de ${todosLosDocumentos.length} items`;

  if (docs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No se encontraron documentos para los filtros seleccionados.</td></tr>`;
    return;
  }

  let html = '';
  docs.forEach(doc => {
    let badgeTipoColor = '#3b82f6';
    let badgeTipoBg = 'rgba(59, 130, 246, 0.15)';
    if (doc.tipo_dte === 34) {
      badgeTipoColor = '#a855f7';
      badgeTipoBg = 'rgba(168, 85, 247, 0.15)';
    } else if (doc.tipo_dte === 61) {
      badgeTipoColor = '#ef4444';
      badgeTipoBg = 'rgba(239, 68, 68, 0.15)';
    } else if (doc.tipo_dte === 39) {
      badgeTipoColor = '#10b981';
      badgeTipoBg = 'rgba(16, 185, 129, 0.15)';
    }

    let badgeConciliado = '';
    if (doc.conciliado) {
      badgeConciliado = `<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.75rem; padding: 0.25rem 0.5rem;"><i class="fa-solid fa-circle-check"></i> Ingresado</span>`;
    } else {
      badgeConciliado = `<span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.75rem; padding: 0.25rem 0.5rem;"><i class="fa-solid fa-clock"></i> Pendiente</span>`;
    }

    const signoNC = doc.es_nota_credito ? '-' : '';
    const colorTotal = doc.es_nota_credito ? '#ef4444' : 'var(--text-main)';

    html += `
      <tr>
        <td>
          <span style="background: ${badgeTipoBg}; color: ${badgeTipoColor}; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; white-space: nowrap;">
            ${doc.nombre_tipo}
          </span>
        </td>
        <td style="font-weight: 700; color: #fff;">#${doc.folio}</td>
        <td style="font-size: 0.85rem; color: var(--text-muted);">${doc.fecha_emision}</td>
        <td style="font-family: monospace; font-size: 0.85rem; color: #a5b4fc;">${doc.rut_contraparte}</td>
        <td style="font-weight: 500; font-size: 0.85rem; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${doc.razon_social}">
          ${doc.razon_social}
        </td>
        <td style="text-align: right; font-size: 0.85rem; color: var(--text-muted);">${signoNC}${formatoCLP(doc.monto_neto)}</td>
        <td style="text-align: right; font-size: 0.85rem; color: #38bdf8;">${signoNC}${formatoCLP(doc.monto_iva)}</td>
        <td style="text-align: right; font-weight: 700; color: ${colorTotal};">${signoNC}${formatoCLP(doc.monto_total)}</td>
        <td style="text-align: center;">${badgeConciliado}</td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function guardarYConsultarCredenciales() {
  const rutUsuario = document.getElementById('modalRutUsuario').value.trim();
  const claveUsuario = document.getElementById('modalClaveUsuario').value.trim();
  const rutEmpresa = document.getElementById('modalRutEmpresa').value.trim();
  const fb = document.getElementById('modalCredsFeedback');

  if (!rutUsuario || !claveUsuario || !rutEmpresa) {
    fb.style.display = 'block';
    fb.style.background = 'rgba(239, 68, 68, 0.15)';
    fb.style.color = '#ef4444';
    fb.textContent = 'Por favor completa todos los campos.';
    return;
  }

  // Guardar en backend
  await fetch('api/rcv_sii.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
    body: JSON.stringify({
      action: 'guardar_credenciales',
      rut_usuario: rutUsuario,
      clave_usuario: claveUsuario,
      rut_empresa: rutEmpresa
    })
  });

  cerrarModalCredenciales();
  forzarSincronizacionSII();
}

function exportarCSV() {
  if (todosLosDocumentos.length === 0) {
    alert('No hay datos en la tabla para exportar.');
    return;
  }

  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;

  let csv = "Tipo DTE;Folio;Fecha Emision;RUT Contraparte;Razon Social;Monto Neto;Monto Exento;Monto IVA;Monto Total;Estado Conciliacion\n";

  todosLosDocumentos.forEach(doc => {
    const estado = doc.conciliado ? 'Ingresado en Minimarket' : 'Pendiente';
    const fila = [
      `"${doc.nombre_tipo}"`,
      doc.folio,
      doc.fecha_emision,
      `"${doc.rut_contraparte}"`,
      `"${(doc.razon_social || '').replace(/"/g, '""')}"`,
      doc.monto_neto,
      doc.monto_exento,
      doc.monto_iva,
      doc.monto_total,
      `"${estado}"`
    ];
    csv += fila.join(';') + "\n";
  });

  const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `RCV_${vistaPrincipal}_${anio}${mes}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

// -------------------------------------------------------------
// 3. ESTIMADOR DE IMPUESTOS (F29: IVA + PPM)
// -------------------------------------------------------------
let tasaPpmSeleccionada = 0.25;

function seleccionarTasaPpm(tasa, btn) {
  tasaPpmSeleccionada = tasa;
  document.getElementById('inputTasaPpm').value = tasa;
  document.querySelectorAll('.btn-tasa-ppm').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  if (btn) {
    btn.classList.remove('btn-secondary');
    btn.classList.add('btn-primary');
  }
  cargarEstimacionImpuestos();
}

function ajustarTasaManual(val) {
  const num = parseFloat(val);
  if (!isNaN(num) && num >= 0) {
    tasaPpmSeleccionada = num;
    document.querySelectorAll('.btn-tasa-ppm').forEach(b => {
      b.classList.remove('btn-primary');
      b.classList.add('btn-secondary');
    });
    cargarEstimacionImpuestos();
  }
}

async function cargarEstimacionImpuestos() {
  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;
  const periodo = `${anio}${mes}`;
  const selMes = document.getElementById('selectMes');
  const selMesNombre = selMes.options[selMes.selectedIndex].text;
  
  document.getElementById('lblPeriodoImpuestosTexto').textContent = `${selMesNombre} ${anio}`;

  try {
    const res = await fetch('api/rcv_sii.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({
        action: 'estimar_impuestos',
        periodo: periodo,
        tasa_ppm: tasaPpmSeleccionada
      })
    });
    const data = await res.json();
    if (!data.success) {
      alert('Error al calcular estimación de impuestos: ' + data.error);
      return;
    }

    const v = data.ventas || {};
    const c = data.compras || {};
    const calc = data.calculo || {};

    const alertaSinDatos = document.getElementById('alertaSinDatosImpuestos');
    if ((v.cant_documentos || 0) === 0 && (c.cant_documentos || 0) === 0) {
      alertaSinDatos.style.display = 'flex';
    } else {
      alertaSinDatos.style.display = 'none';
    }

    // Ventas
    document.getElementById('badgeCantVentasImp').textContent = `${v.cant_documentos || 0} Docs`;
    document.getElementById('impVentaNeto').textContent = formatoCLP(v.neto);
    document.getElementById('impIvaDebito').textContent = formatoCLP(v.iva_debito);
    document.getElementById('impVentaTotal').textContent = formatoCLP(v.total);

    // Compras
    document.getElementById('badgeCantComprasImp').textContent = `${c.cant_documentos || 0} Facturas`;
    document.getElementById('impCompraNeto').textContent = formatoCLP(c.neto);
    document.getElementById('impIvaCredito').textContent = formatoCLP(c.iva_credito);
    document.getElementById('impCompraTotal').textContent = formatoCLP(c.total);

    // PPM
    document.getElementById('impPpmEstimado').textContent = formatoCLP(calc.ppm_estimado);

    // Liquidación
    document.getElementById('liqIvaDebito').textContent = formatoCLP(calc.iva_debito);
    document.getElementById('liqIvaCredito').textContent = '-' + formatoCLP(calc.iva_credito);
    document.getElementById('liqPpmSubtotal').textContent = '+' + formatoCLP(calc.ppm_estimado);

    const badgeRes = document.getElementById('badgeResultadoIva');
    const bannerRem = document.getElementById('bannerRemanenteFavor');
    const lblSubtotal = document.getElementById('lblLiqIvaSubtotal');
    const liqIvaSub = document.getElementById('liqIvaSubtotal');

    if (calc.iva_a_pagar > 0) {
      badgeRes.style.background = 'rgba(239, 68, 68, 0.2)';
      badgeRes.style.color = '#ef4444';
      badgeRes.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> IVA a Pagar: ' + formatoCLP(calc.iva_a_pagar);
      bannerRem.style.display = 'none';
      lblSubtotal.textContent = '(=) Subtotal IVA a Pagar:';
      lblSubtotal.style.color = '#ef4444';
      liqIvaSub.textContent = formatoCLP(calc.iva_a_pagar);
      liqIvaSub.style.color = '#ef4444';
    } else if (calc.remanente_a_favor > 0) {
      badgeRes.style.background = 'rgba(16, 185, 129, 0.2)';
      badgeRes.style.color = '#10b981';
      badgeRes.innerHTML = '<i class="fa-solid fa-circle-check"></i> Remanente a Favor: ' + formatoCLP(calc.remanente_a_favor);
      bannerRem.style.display = 'flex';
      document.getElementById('lblMontoRemanente').textContent = formatoCLP(calc.remanente_a_favor);
      lblSubtotal.textContent = '(=) Subtotal IVA a Pagar:';
      lblSubtotal.style.color = '#10b981';
      liqIvaSub.textContent = '$0 (Saldo a Favor)';
      liqIvaSub.style.color = '#10b981';
    } else {
      badgeRes.style.background = 'rgba(148, 163, 184, 0.15)';
      badgeRes.style.color = '#94a3b8';
      badgeRes.innerHTML = '<i class="fa-solid fa-minus"></i> IVA Neutro: $0';
      bannerRem.style.display = 'none';
      lblSubtotal.textContent = '(=) Subtotal IVA a Pagar:';
      lblSubtotal.style.color = 'var(--text-muted)';
      liqIvaSub.textContent = '$0';
      liqIvaSub.style.color = 'var(--text-muted)';
    }

    document.getElementById('liqTotalF29').textContent = formatoCLP(calc.total_f29_estimado);

  } catch (err) {
    console.error(err);
    alert('Error al conectar con la estimación de impuestos: ' + err.message);
  }
}

async function sincronizarOperacionDirecta(operacion) {
  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;
  const periodo = `${anio}${mes}`;
  const rutUsuario = document.getElementById('modalRutUsuario').value.trim();
  const claveUsuario = document.getElementById('modalClaveUsuario').value.trim();
  const rutEmpresa = document.getElementById('modalRutEmpresa').value.trim();

  const res = await fetch('api/rcv_sii.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
    body: JSON.stringify({
      action: 'consultar',
      modo: 'sii',
      forzar_sync: true,
      periodo: periodo,
      operacion: operacion,
      rut_usuario: rutUsuario,
      clave_usuario: claveUsuario,
      rut_empresa: rutEmpresa
    })
  });
  const data = await res.json();
  if (!data.success) {
    throw new Error(data.error || 'Error al sincronizar ' + operacion);
  }
  return data;
}

function imprimirResumenImpuestos() {
  window.print();
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', () => {
  cargarCuadraturaDiaria();
});
</script>

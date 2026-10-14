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

// Cambiar pestaña principal (Cuadratura Diaria vs Compras vs Ventas)
function cambiarVistaPrincipal(vista) {
  vistaPrincipal = vista;
  const btnCuad = document.getElementById('tabNavCuadratura');
  const btnComp = document.getElementById('tabNavCompras');
  const btnVent = document.getElementById('tabNavVentas');

  const secCuad = document.getElementById('seccionCuadratura');
  const secDocs = document.getElementById('seccionDocumentos');

  [btnCuad, btnComp, btnVent].forEach(b => {
    b.style.background = 'transparent';
    b.style.color = 'var(--text-muted)';
  });

  if (vista === 'CUADRATURA') {
    btnCuad.style.background = 'var(--primary)';
    btnCuad.style.color = '#fff';
    secCuad.style.display = 'block';
    secDocs.style.display = 'none';
    cargarCuadraturaDiaria();
  } else if (vista === 'COMPRA') {
    btnComp.style.background = 'var(--primary)';
    btnComp.style.color = '#fff';
    secCuad.style.display = 'none';
    secDocs.style.display = 'block';
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
    document.getElementById('lblTituloTabla').textContent = 'Boletas y Facturas Emitidas (Ventas)';
    document.getElementById('lblKpiIva').textContent = 'IVA Débito Fiscal';
    document.getElementById('thRutContraparte').textContent = 'RUT Cliente / Receptor';
    document.getElementById('thRazonContraparte').textContent = 'Razón Social';
    consultarRCV('sii', false, 'VENTA');
  }
}

function cambiarPeriodo() {
  if (vistaPrincipal === 'CUADRATURA') {
    cargarCuadraturaDiaria();
  } else {
    consultarRCV('sii', false, vistaPrincipal);
  }
}

function forzarSincronizacionSII() {
  if (vistaPrincipal === 'CUADRATURA') {
    // Sincronizar tanto ventas como compras y recalcular
    consultarRCV('sii', true, 'VENTA', () => {
      cargarCuadraturaDiaria();
    });
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

// Inicialización automática
document.addEventListener('DOMContentLoaded', () => {
  cargarCuadraturaDiaria();
});
</script>

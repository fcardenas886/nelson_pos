<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem;">
      <i class="fa-solid fa-file-invoice-dollar" style="color: #10b981;"></i>
      Registro de Compras y Ventas (RCV) - SII
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.2rem;">
      Consulta y conciliación directa con el Servicio de Impuestos Internos de Chile
    </p>
  </div>
  <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
    <button onclick="abrirModalCredenciales()" class="btn btn-secondary" style="font-size: 0.85rem;">
      <i class="fa-solid fa-key" style="color: #f59e0b;"></i> Credenciales SII
    </button>
    <button onclick="consultarRCV('demo')" class="btn btn-secondary" style="font-size: 0.85rem; border-color: rgba(129, 140, 248, 0.4);">
      <i class="fa-solid fa-flask" style="color: #818cf8;"></i> Cargar Datos Demo
    </button>
    <button onclick="consultarRCV('sii')" class="btn btn-primary" id="btnSyncSii" style="font-size: 0.85rem;">
      <i class="fa-solid fa-arrows-rotate"></i> Sincronizar SII en Vivo
    </button>
  </div>
</div>

<!-- Barra de Control y Filtros Principales -->
<div class="table-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    
    <!-- Selector Operación (Compras vs Ventas) -->
    <div style="display: flex; background: rgba(255,255,255,0.06); padding: 4px; border-radius: 8px; border: 1px solid var(--border-dark);">
      <button type="button" id="tabCompra" onclick="cambiarOperacion('COMPRA')" 
              style="padding: 0.45rem 1.2rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s;">
        <i class="fa-solid fa-truck-ramp-box"></i> Compras (Recibidas)
      </button>
      <button type="button" id="tabVenta" onclick="cambiarOperacion('VENTA')" 
              style="padding: 0.45rem 1.2rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s;">
        <i class="fa-solid fa-receipt"></i> Ventas (Emitidas)
      </button>
    </div>

    <!-- Selectores de Período (Mes y Año) -->
    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
      <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PERÍODO:</label>
      <select id="selectMes" class="form-control" style="width: 140px; padding: 0.45rem 0.6rem;" onchange="autoConsultar()">
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

      <select id="selectAnio" class="form-control" style="width: 100px; padding: 0.45rem 0.6rem;" onchange="autoConsultar()">
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

<!-- Tarjetas KPI de Resumen Fiscal -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
  <div class="stat-card">
    <div class="stat-info">
      <h3 id="lblKpiDte">Total Documentos</h3>
      <div class="stat-value" id="kpiTotalDocs">0</div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;" id="kpiSubDocs">
        Esperando sincronización
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
      <h3 id="lblKpiIva">IVA Crédito Fiscal</h3>
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

<!-- Tabla Detallada de Documentos -->
<div class="table-card">
  <div class="table-header" style="flex-wrap: wrap; gap: 0.75rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <h2 style="font-size: 1.1rem; font-weight: 600;" id="lblTituloTabla">Detalle de Compras Registradas en el SII</h2>
      <span id="badgeCantidadFiltrada" style="background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; color: var(--text-muted);">0 items</span>
    </div>

    <!-- Filtros de búsqueda rápida -->
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
      <input type="text" id="filtroTexto" placeholder="Buscar folio, RUT o razón social..." 
             class="form-control" style="width: 260px; padding: 0.4rem 0.75rem; font-size: 0.85rem;" oninput="filtrarTabla()">
      
      <select id="filtroTipoDte" class="form-control" style="width: 170px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="filtrarTabla()">
        <option value="">Todos los Tipos</option>
        <option value="33">Facturas Electrónicas (33)</option>
        <option value="34">Facturas Exentas (34)</option>
        <option value="61">Notas de Crédito (61)</option>
        <option value="39">Boletas Electrónicas (39)</option>
      </select>

      <select id="filtroConciliacion" class="form-control" style="width: 160px; padding: 0.4rem 0.6rem; font-size: 0.85rem;" onchange="filtrarTabla()">
        <option value="">Todas las compras</option>
        <option value="si">✓ En Minimarket</option>
        <option value="no">⚠️ No Ingresadas</option>
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
          <th style="width: 120px;" id="thRutContraparte">RUT Proveedor</th>
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
            <i class="fa-solid fa-cloud-arrow-down" style="font-size: 2.5rem; color: #475569; display: block; margin-bottom: 0.75rem;"></i>
            Haz clic en <strong>"Sincronizar SII en Vivo"</strong> para consultar tus datos reales con RUT y Clave,<br>
            o en <strong>"Cargar Datos Demo"</strong> para probar el funcionamiento ahora mismo.
          </td>
        </tr>
      </tbody>
    </table>
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
        Ingresa los datos para conectar con el Servicio de Impuestos Internos. Esta consulta es de <strong>solo lectura</strong> (no emite ni modifica documentos tributarios).
      </p>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">
          RUT DEL USUARIO / CONSULTOR
        </label>
        <input type="text" id="modalRutUsuario" class="form-control" placeholder="Ej: 15234567-8" 
               value="<?= htmlspecialchars($rutUsuarioGuardado) ?>" required>
        <small style="font-size: 0.75rem; color: #64748b;">RUT de la persona autorizada con Clave Tributaria ante el SII.</small>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">
          CLAVE TRIBUTARIA DEL SII
        </label>
        <div style="position: relative;">
          <input type="password" id="modalClaveUsuario" class="form-control" placeholder="Contraseña secreta del portal SII" 
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
        <input type="text" id="modalRutEmpresa" class="form-control" placeholder="Ej: 76123456-7" 
               value="<?= htmlspecialchars($rutEmpresaGuardado) ?>" required>
        <small style="font-size: 0.75rem; color: #64748b;">RUT de la empresa cuyas compras/ventas deseas consultar.</small>
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
        <input type="checkbox" id="modalRecordarCreds" <?= (!empty($rutUsuarioGuardado) && !empty($claveGuardada)) ? 'checked' : '' ?> style="accent-color: var(--primary);">
        <label for="modalRecordarCreds" style="font-size: 0.85rem; color: var(--text-main); cursor: pointer;">
          Guardar estas credenciales en el sistema para futuras consultas
        </label>
      </div>

      <div id="modalCredsFeedback" style="display: none; padding: 0.65rem 0.85rem; border-radius: 6px; font-size: 0.85rem;"></div>
    </div>

    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-dark); background: rgba(0,0,0,0.15); display: flex; justify-content: flex-end; gap: 0.6rem;">
      <button type="button" onclick="cerrarModalCredenciales()" class="btn btn-secondary">Cancelar</button>
      <button type="button" onclick="guardarYConsultarCredenciales()" class="btn btn-primary" id="btnGuardarModal">
        <i class="fa-solid fa-check"></i> Guardar y Usar
      </button>
    </div>
  </div>
</div>

<script>
// Estado de la pantalla
let operacionActual = 'COMPRA';
let todosLosDocumentos = [];
let totalesActuales = {};

// Formato de moneda CLP
function formatoCLP(monto) {
  return '$' + Number(monto || 0).toLocaleString('es-CL');
}

// Alternar visibilidad de contraseña
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Modal
function abrirModalCredenciales() {
  const modal = document.getElementById('modalCredenciales');
  modal.style.display = 'flex';
}
function cerrarModalCredenciales() {
  document.getElementById('modalCredenciales').style.display = 'none';
}

// Cambiar entre Compras y Ventas
function cambiarOperacion(op) {
  operacionActual = op;
  const btnC = document.getElementById('tabCompra');
  const btnV = document.getElementById('tabVenta');
  const lblTitulo = document.getElementById('lblTituloTabla');
  const lblKpiIva = document.getElementById('lblKpiIva');
  const thRut = document.getElementById('thRutContraparte');
  const thRazon = document.getElementById('thRazonContraparte');
  const filtroConc = document.getElementById('filtroConciliacion');

  if (op === 'COMPRA') {
    btnC.style.background = 'var(--primary)';
    btnC.style.color = '#fff';
    btnV.style.background = 'transparent';
    btnV.style.color = 'var(--text-muted)';
    lblTitulo.textContent = 'Detalle de Compras Registradas en el SII';
    lblKpiIva.textContent = 'IVA Crédito Fiscal';
    thRut.textContent = 'RUT Proveedor';
    thRazon.textContent = 'Razón Social Proveedor';
    filtroConc.options[0].text = 'Todas las compras';
    filtroConc.options[1].text = '✓ En Minimarket';
    filtroConc.options[2].text = '⚠️ No Ingresadas';
  } else {
    btnV.style.background = 'var(--primary)';
    btnV.style.color = '#fff';
    btnC.style.background = 'transparent';
    btnC.style.color = 'var(--text-muted)';
    lblTitulo.textContent = 'Detalle de Ventas Registradas en el SII';
    lblKpiIva.textContent = 'IVA Débito Fiscal';
    thRut.textContent = 'RUT Cliente / Receptor';
    thRazon.textContent = 'Razón Social Cliente';
    filtroConc.options[0].text = 'Todas las ventas';
    filtroConc.options[1].text = '✓ DTE Emitido Local';
    filtroConc.options[2].text = '⚠️ No Registrado Local';
  }

  // Si ya tenemos datos previos, re-consultar con la nueva operación
  if (todosLosDocumentos.length > 0) {
    const modoUltimo = document.getElementById('badgeEstadoConsulta').dataset.modo || 'demo';
    consultarRCV(modoUltimo);
  }
}

// Consulta automática si cambia mes o año
function autoConsultar() {
  const modoUltimo = document.getElementById('badgeEstadoConsulta').dataset.modo;
  if (modoUltimo) {
    consultarRCV(modoUltimo);
  }
}

// Ejecutar consulta (modo 'sii' o modo 'demo')
async function consultarRCV(modo) {
  const mes = document.getElementById('selectMes').value;
  const anio = document.getElementById('selectAnio').value;
  const periodo = `${anio}${mes}`;

  const rutUsuario = document.getElementById('modalRutUsuario').value.trim();
  const claveUsuario = document.getElementById('modalClaveUsuario').value.trim();
  const rutEmpresa = document.getElementById('modalRutEmpresa').value.trim();
  const recordar = document.getElementById('modalRecordarCreds').checked;

  if (modo === 'sii' && (!rutUsuario || !claveUsuario || !rutEmpresa)) {
    abrirModalCredenciales();
    const fb = document.getElementById('modalCredsFeedback');
    fb.style.display = 'block';
    fb.style.background = 'rgba(239, 68, 68, 0.15)';
    fb.style.color = '#ef4444';
    fb.textContent = 'Por favor ingresa tu RUT, Clave del SII y RUT de la empresa para consultar en vivo.';
    return;
  }

  // Estado de carga en la UI
  const btnSync = document.getElementById('btnSyncSii');
  const badgeEstado = document.getElementById('badgeEstadoConsulta');
  const tbody = document.getElementById('tbodyRcv');

  btnSync.disabled = true;
  btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Conectando...';
  badgeEstado.className = 'badge';
  badgeEstado.style.background = 'rgba(245, 158, 11, 0.2)';
  badgeEstado.style.color = '#f59e0b';
  badgeEstado.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando SII...';

  tbody.innerHTML = `
    <tr>
      <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2.5rem; color: var(--primary); display: block; margin-bottom: 0.75rem;"></i>
        ${modo === 'sii' ? 'Iniciando sesión y recuperando registros oficiales del SII...' : 'Generando datos de demostración para el período ' + mes + '/' + anio + '...'}
      </td>
    </tr>
  `;

  try {
    const res = await fetch('api/rcv_sii.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: JSON.stringify({
        action: 'consultar',
        modo: modo,
        periodo: periodo,
        operacion: operacionActual,
        rut_usuario: rutUsuario,
        clave_usuario: claveUsuario,
        rut_empresa: rutEmpresa,
        guardar_credenciales: recordar
      })
    });

    const data = await res.json();

    if (!data.success) {
      badgeEstado.style.background = 'rgba(239, 68, 68, 0.2)';
      badgeEstado.style.color = '#ef4444';
      badgeEstado.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error';

      tbody.innerHTML = `
        <tr>
          <td colspan="9" style="text-align: center; color: #ef4444; padding: 2.5rem 1rem;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
            <strong>No se pudo obtener la información:</strong><br>
            ${data.error || 'Ocurrió un error inesperado al conectar con el SII.'}
            <div style="margin-top: 1rem;">
              <button onclick="abrirModalCredenciales()" class="btn btn-secondary" style="font-size: 0.8rem;">
                <i class="fa-solid fa-key"></i> Revisar Credenciales
              </button>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    // Almacenar datos y actualizar estado
    todosLosDocumentos = data.documentos || [];
    totalesActuales = data.totales || {};
    badgeEstado.dataset.modo = modo;

    if (data.es_demo) {
      badgeEstado.style.background = 'rgba(129, 140, 248, 0.2)';
      badgeEstado.style.color = '#818cf8';
      badgeEstado.innerHTML = '<i class="fa-solid fa-flask"></i> Modo Demo Activo';
    } else {
      badgeEstado.style.background = 'rgba(16, 185, 129, 0.2)';
      badgeEstado.style.color = '#10b981';
      badgeEstado.innerHTML = '<i class="fa-solid fa-circle-check"></i> Sincronizado SII';
    }

    actualizarKPIs(totalesActuales, todosLosDocumentos.length);
    filtrarTabla();

  } catch (err) {
    badgeEstado.style.background = 'rgba(239, 68, 68, 0.2)';
    badgeEstado.style.color = '#ef4444';
    badgeEstado.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error de red';
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; color: #ef4444; padding: 2rem;">
          Error al comunicarse con el servidor local: ${err.message}
        </td>
      </tr>
    `;
  } finally {
    btnSync.disabled = false;
    btnSync.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Sincronizar SII en Vivo';
  }
}

// Actualizar tarjetas de resumen
function actualizarKPIs(totales, cantidad) {
  document.getElementById('kpiTotalDocs').textContent = cantidad;
  document.getElementById('kpiSubDocs').textContent = cantidad === 1 ? '1 documento procesado' : `${cantidad} documentos procesados`;

  document.getElementById('kpiMontoNeto').textContent = formatoCLP(totales.total_neto);
  document.getElementById('kpiMontoIva').textContent = formatoCLP(totales.total_iva);
  document.getElementById('kpiMontoTotal').textContent = formatoCLP(totales.total_general);
}

// Filtrar tabla en tiempo real
function filtrarTabla() {
  const query = document.getElementById('filtroTexto').value.toLowerCase().trim();
  const filtroTipo = document.getElementById('filtroTipoDte').value;
  const filtroConc = document.getElementById('filtroConciliacion').value;

  const filtrados = todosLosDocumentos.filter(doc => {
    // Filtro por texto (Folio, Rut, Razón Social)
    if (query) {
      const matchFolio = String(doc.folio).includes(query);
      const matchRut = (doc.rut_contraparte || '').toLowerCase().includes(query);
      const matchRazon = (doc.razon_social || '').toLowerCase().includes(query);
      if (!matchFolio && !matchRut && !matchRazon) return false;
    }

    // Filtro por tipo de DTE
    if (filtroTipo && String(doc.tipo_dte) !== filtroTipo) {
      return false;
    }

    // Filtro por conciliación
    if (filtroConc === 'si' && !doc.conciliado) return false;
    if (filtroConc === 'no' && doc.conciliado) return false;

    return true;
  });

  renderizarTabla(filtrados);
}

// Renderizar filas de la tabla
function renderizarTabla(docs) {
  const tbody = document.getElementById('tbodyRcv');
  const badgeCantidad = document.getElementById('badgeCantidadFiltrada');
  badgeCantidad.textContent = `${docs.length} de ${todosLosDocumentos.length} items`;

  if (docs.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
          No se encontraron documentos para los filtros seleccionados.
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  docs.forEach(doc => {
    // Color según tipo de documento
    let badgeTipoColor = '#3b82f6'; // Factura (azul)
    let badgeTipoBg = 'rgba(59, 130, 246, 0.15)';
    if (doc.tipo_dte === 34) { // Exenta (morado)
      badgeTipoColor = '#a855f7';
      badgeTipoBg = 'rgba(168, 85, 247, 0.15)';
    } else if (doc.tipo_dte === 61) { // NC (rojo/naranja)
      badgeTipoColor = '#ef4444';
      badgeTipoBg = 'rgba(239, 68, 68, 0.15)';
    } else if (doc.tipo_dte === 39) { // Boleta (verde)
      badgeTipoColor = '#10b981';
      badgeTipoBg = 'rgba(16, 185, 129, 0.15)';
    }

    // Badge conciliación
    let badgeConciliado = '';
    if (doc.conciliado) {
      badgeConciliado = `
        <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 0.75rem; padding: 0.25rem 0.5rem;" title="${operacionActual === 'COMPRA' ? 'Registrada en Compras locales' : 'Registrada en Ventas locales'}">
          <i class="fa-solid fa-circle-check"></i> Ingresado
        </span>
      `;
    } else {
      badgeConciliado = `
        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 0.75rem; padding: 0.25rem 0.5rem;" title="Aún no ha sido registrada en el sistema">
          <i class="fa-solid fa-clock"></i> Pendiente
        </span>
      `;
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

// Guardar credenciales desde el modal y ejecutar consulta
async function guardarYConsultarCredenciales() {
  const rutUsuario = document.getElementById('modalRutUsuario').value.trim();
  const claveUsuario = document.getElementById('modalClaveUsuario').value.trim();
  const rutEmpresa = document.getElementById('modalRutEmpresa').value.trim();
  const recordar = document.getElementById('modalRecordarCreds').checked;
  const fb = document.getElementById('modalCredsFeedback');

  if (!rutUsuario || !claveUsuario || !rutEmpresa) {
    fb.style.display = 'block';
    fb.style.background = 'rgba(239, 68, 68, 0.15)';
    fb.style.color = '#ef4444';
    fb.textContent = 'Por favor completa todos los campos del formulario.';
    return;
  }

  cerrarModalCredenciales();
  consultarRCV('sii');
}

// Exportar la tabla actual a CSV
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
  a.download = `RCV_${operacionActual}_${anio}${mes}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
</script>

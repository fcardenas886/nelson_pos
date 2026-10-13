<?php
// Vista principal de Sorteos y Cupones
$sorteoActivoId = $sorteoActivoDefault['SorteoID'] ?? 0;
?>

<div class="main-content" style="padding-bottom: 3rem;">
  <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <h1 style="font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; gap: 0.6rem; margin: 0;">
        <i class="fa-solid fa-gift" style="color: #f59e0b;"></i> Sorteos y Cupones
      </h1>
      <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0.3rem 0 0 0;">
        Emisión automática de cupones en ventas, registro de participantes y tómbola virtual en vivo.
      </p>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="sorteo-tabs" style="display: flex; gap: 0.5rem; background: var(--surface-dark); padding: 0.35rem; border-radius: 14px; border: 1px solid var(--border-dark);">
      <button type="button" class="btn-tab active" onclick="cambiarTabSorteo('tombola')" id="tabBtn-tombola">
        <i class="fa-solid fa-dice" style="color: #fbbf24;"></i> Tómbola en Vivo
      </button>
      <button type="button" class="btn-tab" onclick="cambiarTabSorteo('campanas')" id="tabBtn-campanas">
        <i class="fa-solid fa-bullhorn" style="color: #38bdf8;"></i> Campañas
      </button>
      <button type="button" class="btn-tab" onclick="cambiarTabSorteo('cupones')" id="tabBtn-cupones">
        <i class="fa-solid fa-ticket" style="color: #10b981;"></i> Cupones Emitidos
      </button>
      <button type="button" class="btn-tab" onclick="cambiarTabSorteo('ganadores')" id="tabBtn-ganadores">
        <i class="fa-solid fa-trophy" style="color: #eab308;"></i> Ganadores
      </button>
    </div>
  </div>

  <style>
    /* Estilos de las pestañas */
    .btn-tab {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 0.55rem 1rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
    }
    .btn-tab:hover {
      color: var(--text-main);
      background: rgba(255, 255, 255, 0.05);
    }
    .btn-tab.active {
      background: var(--primary, #3b82f6);
      color: #fff;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    .tab-content-panel {
      display: none;
    }
    .tab-content-panel.active {
      display: block;
    }

    /* Estilos específicos de la Tómbola Virtual */
    .tombola-stage {
      background: radial-gradient(circle at center, #1e1b4b 0%, #0f172a 70%, #020617 100%);
      border: 1px solid rgba(139, 92, 246, 0.3);
      border-radius: 24px;
      padding: 2.5rem 1.5rem;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), inset 0 0 80px rgba(99, 102, 241, 0.15);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 580px;
    }
    .tombola-stage.fullscreen {
      position: fixed;
      inset: 0;
      z-index: 99999;
      border-radius: 0;
      border: none;
      justify-content: center;
      padding: 2rem;
    }
    .tombola-title-glow {
      text-align: center;
      margin-bottom: 1.5rem;
      z-index: 2;
    }
    .tombola-title-glow h2 {
      font-size: 2.2rem;
      font-weight: 900;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #facc15 0%, #f59e0b 50%, #fb923c 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin: 0;
      text-shadow: 0 0 30px rgba(245, 158, 11, 0.4);
    }
    .tombola-title-glow p {
      color: #94a3b8;
      font-size: 1rem;
      margin: 0.3rem 0 0 0;
      font-weight: 500;
    }

    /* Esfera / Tambor de Números */
    .tombola-drum-container {
      position: relative;
      width: 320px;
      height: 320px;
      margin: 1rem 0 1.5rem 0;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
    }
    .tombola-drum-ring {
      position: absolute;
      inset: 0;
      border-radius: 50%;
      border: 4px dashed rgba(245, 158, 11, 0.5);
      animation: drumRotate 20s linear infinite;
      box-shadow: 0 0 50px rgba(245, 158, 11, 0.25);
    }
    .tombola-drum-ring.spinning {
      animation: drumRotateFast 0.5s linear infinite;
      border-color: #facc15;
      box-shadow: 0 0 80px rgba(250, 204, 21, 0.6);
    }
    @keyframes drumRotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    @keyframes drumRotateFast {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .tombola-drum-inner {
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: linear-gradient(145deg, #1e1b4b, #0f172a);
      border: 3px solid rgba(255, 255, 255, 0.15);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.8);
      position: relative;
      overflow: hidden;
    }
    .tombola-number-display {
      font-size: 3.2rem;
      font-weight: 900;
      font-family: 'Courier New', Courier, monospace;
      color: #facc15;
      text-shadow: 0 0 20px rgba(250, 204, 21, 0.8);
      letter-spacing: 2px;
      transition: all 0.1s ease;
    }
    .tombola-participant-preview {
      font-size: 0.95rem;
      font-weight: 700;
      color: #e2e8f0;
      max-width: 85%;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-top: 0.3rem;
    }

    /* Botón Girar */
    .btn-girar-tombola {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #fff;
      font-size: 1.35rem;
      font-weight: 900;
      padding: 1.1rem 3rem;
      border-radius: 999px;
      border: none;
      cursor: pointer;
      box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 0.8rem;
      transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      z-index: 2;
    }
    .btn-girar-tombola:hover:not(:disabled) {
      transform: translateY(-3px) scale(1.03);
      box-shadow: 0 15px 40px rgba(16, 185, 129, 0.6);
    }
    .btn-girar-tombola:disabled {
      background: #475569;
      color: #94a3b8;
      box-shadow: none;
      cursor: not-allowed;
      transform: none;
    }

    /* Tarjeta del Ganador */
    .winner-card-celebration {
      background: linear-gradient(135deg, rgba(30, 27, 75, 0.95), rgba(15, 23, 42, 0.95));
      border: 2px solid #facc15;
      border-radius: 20px;
      padding: 1.8rem 2.2rem;
      box-shadow: 0 20px 50px rgba(250, 204, 21, 0.35);
      text-align: center;
      margin-top: 1.5rem;
      display: none;
      animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      z-index: 10;
      max-width: 500px;
      width: 90%;
    }
    @keyframes popIn {
      from { opacity: 0; transform: scale(0.7); }
      to { opacity: 1; transform: scale(1); }
    }
    .winner-tag {
      background: #facc15;
      color: #0f172a;
      font-size: 0.85rem;
      font-weight: 900;
      text-transform: uppercase;
      padding: 0.25rem 0.8rem;
      border-radius: 999px;
      display: inline-block;
      margin-bottom: 0.6rem;
      letter-spacing: 0.05em;
    }
  </style>

  <!-- PESTAÑA 1: TÓMBOLA EN VIVO -->
  <div id="panel-tombola" class="tab-content-panel active">
    <!-- Selector de Sorteo y Premio -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem; background: var(--surface-dark); padding: 1rem 1.2rem; border-radius: 16px; border: 1px solid var(--border-dark);">
      <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <div>
          <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 0.3rem;">
            Campaña a Sortear:
          </label>
          <select id="selectSorteoTombola" class="form-control" style="min-width: 260px; font-weight: 700;" onchange="onSorteoSeleccionadoCambio()">
            <?php foreach ($sorteosList as $s): ?>
              <option value="<?= $s['SorteoID'] ?>" <?= $s['SorteoID'] == $sorteoActivoId ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['Titulo']) ?> (<?= $s['Estado'] ?>)
              </option>
            <?php endforeach; ?>
            <?php if (empty($sorteosList)): ?>
              <option value="">No hay sorteos creados</option>
            <?php endif; ?>
          </select>
        </div>

        <div>
          <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 0.3rem;">
            Premio a Sortear:
          </label>
          <select id="selectPremioTombola" class="form-control" style="min-width: 220px; font-weight: 700;">
            <!-- Rellenado dinámicamente -->
          </select>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 0.6rem;">
        <button type="button" class="btn btn-secondary" onclick="toggleAudioMute()" id="btnAudioMute" title="Alternar Sonido">
          <i class="fa-solid fa-volume-high" id="iconAudioMute"></i> Sonido
        </button>
        <button type="button" class="btn btn-secondary" onclick="toggleTombolaFullscreen()" id="btnFullscreen" title="Modo Pantalla Completa para Proyector / TV">
          <i class="fa-solid fa-expand"></i> Pantalla Completa
        </button>
      </div>
    </div>

    <!-- Escenario de la Tómbola -->
    <div id="tombolaStage" class="tombola-stage">
      <canvas id="confettiCanvas" style="position: absolute; inset: 0; pointer-events: none; z-index: 5; width: 100%; height: 100%;"></canvas>

      <button type="button" class="btn btn-secondary" id="btnCloseFullscreen" onclick="toggleTombolaFullscreen()" style="display: none; position: absolute; top: 1.5rem; right: 1.5rem; z-index: 20;">
        <i class="fa-solid fa-compress"></i> Salir de Pantalla Completa
      </button>

      <div class="tombola-title-glow">
        <h2 id="tombolaTituloSorteo"><?= htmlspecialchars($sorteoActivoDefault['Titulo'] ?? 'Sorteo de Compras') ?></h2>
        <p id="tombolaSubtituloSorteo">
          <i class="fa-solid fa-ticket"></i> <span id="lblTotalParticipantes">0</span> cupones participantes activos
        </p>
      </div>

      <!-- Tambor central -->
      <div class="tombola-drum-container">
        <div id="drumRing" class="tombola-drum-ring"></div>
        <div class="tombola-drum-inner">
          <div style="font-size: 0.72rem; color: #a5b4fc; text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 0.2rem;">
            CUPÓN N°
          </div>
          <div id="displayNumeroCupon" class="tombola-number-display">------</div>
          <div id="displayParticipante" class="tombola-participant-preview">Esperando inicio...</div>
        </div>
      </div>

      <!-- Botón de Acción -->
      <button type="button" id="btnGirarTombola" class="btn-girar-tombola" onclick="iniciarSorteoEnVivo()">
        <i class="fa-solid fa-play"></i> ¡GIRAR TÓMBOLA Y SORTEAR!
      </button>

      <!-- Tarjeta Revelación del Ganador -->
      <div id="winnerCard" class="winner-card-celebration">
        <div class="winner-tag">🎉 ¡TENEMOS GANADOR! 🎉</div>
        <h3 id="winPremio" style="color: #facc15; font-size: 1.4rem; font-weight: 900; margin: 0 0 0.5rem 0;">1er Lugar</h3>
        <div style="font-size: 2.2rem; font-weight: 900; color: #fff; margin-bottom: 0.2rem;" id="winCodigoCupon">SOR-00000</div>
        <div style="font-size: 1.25rem; font-weight: 800; color: #38bdf8;" id="winNombreParticipante">Nombre Cliente</div>
        <div style="font-size: 0.95rem; color: #94a3b8; margin-top: 0.2rem;" id="winTelefonoParticipante">+56 9 ...</div>
        <div style="font-size: 0.82rem; color: #64748b; margin-top: 0.3rem;" id="winBoletaInfo">Boleta #0 · Monto $0</div>

        <div style="display: flex; gap: 0.6rem; justify-content: center; margin-top: 1.2rem; flex-wrap: wrap;">
          <button type="button" class="btn btn-success" onclick="confirmarGanadorOficial()" style="padding: 0.55rem 1.1rem; font-weight: 700;">
            <i class="fa-solid fa-check-double"></i> Registrar Ganador Oficial
          </button>
          <button type="button" class="btn btn-secondary" onclick="sortearSuplente()" style="padding: 0.55rem 1rem; font-weight: 700;">
            <i class="fa-solid fa-rotate"></i> Sortear Suplente
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- PESTAÑA 2: GESTIÓN DE CAMPAÑAS -->
  <div id="panel-campanas" class="tab-content-panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
      <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Campañas de Sorteos Registradas</h3>
      <button type="button" class="btn btn-primary" onclick="abrirModalCrearSorteo()">
        <i class="fa-solid fa-plus"></i> Nueva Campaña
      </button>
    </div>

    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-dark); border-radius: 16px;">
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Título y Descripción</th>
              <th>Regla de Emisión</th>
              <th>Premios</th>
              <th>Vigencia</th>
              <th>Cupones</th>
              <th>Estado</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyCampanas">
            <!-- Rellenado por JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- PESTAÑA 3: CUPONES EMITIDOS -->
  <div id="panel-cupones" class="tab-content-panel">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem; background: var(--surface-dark); padding: 0.9rem 1.2rem; border-radius: 14px; border: 1px solid var(--border-dark);">
      <div style="display: flex; gap: 0.75rem; flex: 1; max-width: 700px; flex-wrap: wrap;">
        <input type="text" id="filtroBuscarCupon" class="form-control" placeholder="Buscar por N° cupón, código, nombre, fono o boleta..." style="flex: 2; min-width: 240px;" onkeydown="if(event.key==='Enter') cargarCuponesTabla(1)">
        <select id="filtroSorteoCupones" class="form-control" style="flex: 1; min-width: 180px;" onchange="cargarCuponesTabla(1)">
          <option value="0">Todos los sorteos</option>
          <?php foreach ($sorteosList as $s): ?>
            <option value="<?= $s['SorteoID'] ?>"><?= htmlspecialchars($s['Titulo']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-primary" onclick="cargarCuponesTabla(1)">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: var(--text-muted);">
          <input type="checkbox" id="chkSoloGanadores" onchange="cargarCuponesTabla(1)"> Solo ganadores
        </label>
      </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-dark); border-radius: 16px;">
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Código / N°</th>
              <th>Sorteo</th>
              <th>Participante</th>
              <th>Teléfono</th>
              <th>Boleta / Venta</th>
              <th>Monto Venta</th>
              <th>Fecha Emisión</th>
              <th>Estado</th>
              <th style="text-align: right;">Imprimir</th>
            </tr>
          </thead>
          <tbody id="tbodyCupones">
            <!-- Rellenado por JS -->
          </tbody>
        </table>
      </div>
    </div>

    <div id="paginacionCupones" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.88rem;">
      <span id="lblInfoCuponesPaginacion">Mostrando 0 de 0</span>
      <div style="display: flex; gap: 0.4rem;" id="botonesPaginacionCupones"></div>
    </div>
  </div>

  <!-- PESTAÑA 4: HISTORIAL DE GANADORES -->
  <div id="panel-ganadores" class="tab-content-panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
      <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Cuadro de Honor y Ganadores Oficiales</h3>
    </div>

    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-dark); border-radius: 16px;">
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Lugar / Premio</th>
              <th>Campaña Sorteo</th>
              <th>Cupón Ganador</th>
              <th>Ganador</th>
              <th>Teléfono</th>
              <th>Fecha del Sorteo</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyGanadores">
            <!-- Rellenado por JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Crear / Editar Sorteo -->
<div id="modalSorteo" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 10000; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--surface-dark); border: 1px solid var(--border-dark); border-radius: 20px; width: 620px; max-width: 95vw; max-height: 90vh; overflow-y: auto; padding: 1.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.6);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.8rem;">
      <h3 id="modalSorteoTitulo" style="margin: 0; font-size: 1.3rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-gift" style="color: #f59e0b;"></i> Configurar Campaña de Sorteo
      </h3>
      <button type="button" class="btn btn-secondary" onclick="cerrarModalSorteo()" style="padding: 0.3rem 0.6rem;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form id="formSorteo" onsubmit="guardarCampanaSorteo(event)">
      <input type="hidden" id="formSorteoId" value="0">

      <div style="margin-bottom: 1rem;">
        <label class="form-label" for="formSorteoTitulo">Título del Sorteo *</label>
        <input type="text" id="formTitulo" class="form-control" required placeholder="Ej. Gran Sorteo Aniversario Minimarket">
      </div>

      <div style="margin-bottom: 1rem;">
        <label class="form-label" for="formSorteoDesc">Descripción o Bases Breves</label>
        <textarea id="formDescripcion" class="form-control" rows="2" placeholder="Ej. Participan todas las compras sobre $5.000 entre las fechas indicadas."></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label class="form-label">Regla de Asignación *</label>
          <select id="formTipoRegla" class="form-control" onchange="toggleReglaMonto()">
            <option value="monto_cada_x">1 cupón por cada $X monto de compra</option>
            <option value="por_venta">1 cupón por cada venta (monto mínimo)</option>
          </select>
        </div>
        <div>
          <label class="form-label" id="lblFormMontoMin">Monto por cada Cupón ($ CLP) *</label>
          <input type="number" id="formMontoMinimo" class="form-control" min="100" step="100" value="5000" required>
        </div>
      </div>

      <div style="margin-bottom: 1rem;">
        <label class="form-label">Premios del Sorteo (1 por línea o desglosados)</label>
        <div id="listaPremiosContainer" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem;"></div>
        <button type="button" class="btn btn-secondary" onclick="agregarFilaPremio()" style="font-size: 0.8rem; padding: 0.3rem 0.7rem;">
          <i class="fa-solid fa-plus"></i> Agregar Premio
        </button>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
        <div>
          <label class="form-label">Fecha Inicio *</label>
          <input type="date" id="formFechaInicio" class="form-control" required>
        </div>
        <div>
          <label class="form-label">Fecha Fin / Sorteo *</label>
          <input type="date" id="formFechaFin" class="form-control" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem;">
        <div>
          <label class="form-label">Estado Inicial</label>
          <select id="formEstado" class="form-control">
            <option value="Activo">Activo (Emite cupones)</option>
            <option value="Pausado">Pausado (No emite)</option>
            <option value="Finalizado">Finalizado</option>
          </select>
        </div>
        <div style="display: flex; flex-direction: column; justify-content: center; gap: 0.4rem; padding-top: 0.5rem;">
          <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
            <input type="checkbox" id="formImprimirTicket" checked> Imprimir talón en papel térmico
          </label>
          <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
            <input type="checkbox" id="formRequiereCliente"> Solo clientes registrados
          </label>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--border-dark); padding-top: 1rem;">
        <button type="button" class="btn btn-secondary" onclick="cerrarModalSorteo()">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="font-weight: 700;">
          <i class="fa-solid fa-floppy-disk"></i> Guardar Campaña
        </button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/sorteos.js?v=<?= APP_VERSION ?>"></script>

/**
 * sorteos.js - Controlador de Sorteos, Cupones y Tómbola Virtual en Vivo
 * Minimarket POS Web
 */

let sorteoActualId = null;
let listaParticipantes = [];
let ganadorTentativo = null;
let animacionTombolaInterval = null;
let audioHabilitado = localStorage.getItem('sorteo_audio_mute') !== 'true';

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
  const selSorteo = document.getElementById('selectSorteoTombola');
  if (selSorteo && selSorteo.value) {
    sorteoActualId = parseInt(selSorteo.value, 10);
    cargarDetallesSorteoTombola(sorteoActualId);
  }
  actualizarIconoAudio();
});

/* ============================================================
   Navegación de Pestañas
   ============================================================ */
function cambiarTabSorteo(tab) {
  document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.btn-tab').forEach(b => b.classList.remove('active'));

  const panel = document.getElementById(`panel-${tab}`);
  const btn = document.getElementById(`tabBtn-${tab}`);
  if (panel) panel.classList.add('active');
  if (btn) btn.classList.add('active');

  if (tab === 'campanas') cargarCampanasTabla();
  if (tab === 'cupones') cargarCuponesTabla(1);
  if (tab === 'ganadores') cargarGanadoresTabla();
}

/* ============================================================
   Tómbola Virtual en Vivo
   ============================================================ */
async function onSorteoSeleccionadoCambio() {
  const sel = document.getElementById('selectSorteoTombola');
  if (!sel || !sel.value) return;
  sorteoActualId = parseInt(sel.value, 10);
  await cargarDetallesSorteoTombola(sorteoActualId);
}

async function cargarDetallesSorteoTombola(sorteoId) {
  const btnGirar = document.getElementById('btnGirarTombola');
  const lblTotal = document.getElementById('lblTotalParticipantes');
  const dispNum = document.getElementById('displayNumeroCupon');
  const dispPart = document.getElementById('displayParticipante');
  const selPremio = document.getElementById('selectPremioTombola');
  const winnerCard = document.getElementById('winnerCard');

  if (winnerCard) winnerCard.style.display = 'none';
  if (btnGirar) btnGirar.disabled = true;

  try {
    // 1. Obtener datos del sorteo para llenar selector de premios
    const resSorteos = await fetch('api/sorteos.php?accion=listar_sorteos');
    const dataSorteos = await resSorteos.json();
    if (dataSorteos.success) {
      const sorteo = dataSorteos.sorteos.find(s => s.SorteoID === sorteoId);
      if (sorteo) {
        document.getElementById('tombolaTituloSorteo').textContent = sorteo.Titulo;
        selPremio.innerHTML = '';
        const premios = Array.isArray(sorteo.Premios) ? sorteo.Premios : [];
        if (premios.length > 0) {
          premios.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.premio;
            opt.dataset.lugar = p.lugar;
            opt.textContent = `${p.lugar}° Lugar: ${p.premio}`;
            selPremio.appendChild(opt);
          });
        } else {
          selPremio.innerHTML = '<option value="Premio Principal" data-lugar="1">1° Lugar: Premio Principal</option>';
        }
      }
    }

    // 2. Cargar participantes elegibles (no ganadores)
    const resPart = await fetch(`api/sorteos.php?accion=participantes&sorteo_id=${sorteoId}`);
    const dataPart = await resPart.json();

    if (dataPart.success) {
      listaParticipantes = dataPart.participantes || [];
      if (lblTotal) lblTotal.textContent = formatNumber(listaParticipantes.length);

      if (listaParticipantes.length > 0) {
        if (btnGirar) btnGirar.disabled = false;
        if (dispNum) dispNum.textContent = '------';
        if (dispPart) dispPart.textContent = `${listaParticipantes.length} cupones listos para el sorteo`;
      } else {
        if (dispNum) dispNum.textContent = 'VACÍO';
        if (dispPart) dispPart.textContent = 'No hay cupones participantes sin premiar';
      }
    }
  } catch (err) {
    console.error('Error cargando participantes:', err);
    toast('No se pudieron cargar los participantes del sorteo.', 'error');
  }
}

function iniciarSorteoEnVivo() {
  if (!listaParticipantes || listaParticipantes.length === 0) {
    toast('No hay cupones participantes disponibles para este sorteo.', 'warn');
    return;
  }

  const btnGirar = document.getElementById('btnGirarTombola');
  const drumRing = document.getElementById('drumRing');
  const winnerCard = document.getElementById('winnerCard');
  const dispNum = document.getElementById('displayNumeroCupon');
  const dispPart = document.getElementById('displayParticipante');

  btnGirar.disabled = true;
  if (winnerCard) winnerCard.style.display = 'none';
  if (drumRing) drumRing.classList.add('spinning');

  // Elegir índice del ganador aleatoriamente por anticipado
  const indiceGanador = Math.floor(Math.random() * listaParticipantes.length);
  ganadorTentativo = listaParticipantes[indiceGanador];

  let velocidad = 40;
  let tiempoTranscurrido = 0;
  const tiempoTotalGiro = 4200; // 4.2 segundos de emoción
  let pasoActual = 0;

  playTensionRumble();

  function cicloGiro() {
    pasoActual = (pasoActual + 1) % listaParticipantes.length;
    const itemAleatorio = listaParticipantes[pasoActual];

    dispNum.textContent = itemAleatorio.CodigoCupon;
    dispPart.textContent = itemAleatorio.NombreParticipante || 'Participante';

    playTickSound();
    tiempoTranscurrido += velocidad;

    // A partir de los 2.4 segundos, desacelerar gradualmente
    if (tiempoTranscurrido > 2400) {
      velocidad = Math.floor(velocidad * 1.18);
    }

    if (tiempoTranscurrido >= tiempoTotalGiro) {
      // Detener en el ganador final
      finalizarGiroTombola();
    } else {
      animacionTombolaInterval = setTimeout(cicloGiro, velocidad);
    }
  }

  cicloGiro();
}

function finalizarGiroTombola() {
  if (animacionTombolaInterval) clearTimeout(animacionTombolaInterval);

  const drumRing = document.getElementById('drumRing');
  const dispNum = document.getElementById('displayNumeroCupon');
  const dispPart = document.getElementById('displayParticipante');
  const winnerCard = document.getElementById('winnerCard');
  const selPremio = document.getElementById('selectPremioTombola');

  if (drumRing) drumRing.classList.remove('spinning');

  // Mostrar el ganador exacto
  dispNum.textContent = ganadorTentativo.CodigoCupon;
  dispPart.textContent = ganadorTentativo.NombreParticipante || 'Ganador';

  // Sonido de victoria y confeti
  playVictoryFanfare();
  dispararConfeti();

  // Completar datos en la tarjeta dorada
  const optPremio = selPremio.selectedOptions[0];
  const lugar = optPremio ? optPremio.dataset.lugar : '1';
  const textoPremio = optPremio ? optPremio.value : 'Premio';

  document.getElementById('winPremio').textContent = `${lugar}° Lugar: ${textoPremio}`;
  document.getElementById('winCodigoCupon').textContent = ganadorTentativo.CodigoCupon;
  document.getElementById('winNombreParticipante').textContent = ganadorTentativo.NombreParticipante || 'Público General';
  document.getElementById('winTelefonoParticipante').textContent = ganadorTentativo.TelefonoParticipante ? `📞 ${ganadorTentativo.TelefonoParticipante}` : 'Sin teléfono registrado';
  document.getElementById('winBoletaInfo').textContent = ganadorTentativo.VentaID ? `Boleta / Venta N° #${ganadorTentativo.VentaID} · Compra de $${formatNumber(ganadorTentativo.MontoVenta)}` : 'Cupón Emitido';

  if (winnerCard) {
    winnerCard.style.display = 'block';
    winnerCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

async function confirmarGanadorOficial() {
  if (!ganadorTentativo) return;

  const selPremio = document.getElementById('selectPremioTombola');
  const optPremio = selPremio ? selPremio.selectedOptions[0] : null;
  const lugar = optPremio ? parseInt(optPremio.dataset.lugar, 10) : 1;
  const premio = optPremio ? optPremio.value : 'Premio';

  try {
    const res = await fetch('api/sorteos.php?accion=sortear_ganador', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sorteo_id: sorteoActualId,
        cupon_id: ganadorTentativo.CuponID,
        lugar_premio: lugar,
        premio: premio,
        notas: `Registrado en sorteo virtual en vivo por ${window.CURRENT_USER_NOMBRE || 'Cajero'}`
      })
    });
    const data = await res.json();
    if (data.success) {
      toast(`🎉 ¡${ganadorTentativo.NombreParticipante} registrado oficialmente como ganador del ${lugar}° lugar!`, 'success');
      // Recargar participantes de la tómbola
      await cargarDetallesSorteoTombola(sorteoActualId);
    } else {
      toast(data.error || 'Error al registrar ganador.', 'error');
    }
  } catch (err) {
    toast('Error de conexión al guardar ganador.', 'error');
  }
}

function sortearSuplente() {
  toast('Sorteando suplente para el mismo premio...', 'info');
  iniciarSorteoEnVivo();
}

function toggleTombolaFullscreen() {
  const stage = document.getElementById('tombolaStage');
  const btnClose = document.getElementById('btnCloseFullscreen');
  if (!stage) return;

  stage.classList.toggle('fullscreen');
  const isFull = stage.classList.contains('fullscreen');
  if (btnClose) btnClose.style.display = isFull ? 'inline-flex' : 'none';

  // Si entra a fullscreen, ajustar canvas
  if (isFull) {
    if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen().catch(() => {});
    }
  } else {
    if (document.exitFullscreen && document.fullscreenElement) {
      document.exitFullscreen().catch(() => {});
    }
  }
  ajustarCanvasConfeti();
}

/* ============================================================
   Efectos de Audio Sintetizados (Web Audio API - 100% Offline)
   ============================================================ */
let audioCtx = null;

function getAudioContext() {
  if (!audioCtx) {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (AudioContext) audioCtx = new AudioContext();
  }
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume();
  }
  return audioCtx;
}

function toggleAudioMute() {
  audioHabilitado = !audioHabilitado;
  localStorage.setItem('sorteo_audio_mute', (!audioHabilitado).toString());
  actualizarIconoAudio();
  toast(audioHabilitado ? '🔊 Sonido activado' : '🔇 Sonido silenciado', 'info');
}

function actualizarIconoAudio() {
  const icon = document.getElementById('iconAudioMute');
  if (icon) {
    icon.className = audioHabilitado ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
  }
}

// Clic mecánico tipo ruleta/tómbola
function playTickSound() {
  if (!audioHabilitado) return;
  const ctx = getAudioContext();
  if (!ctx) return;

  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.type = 'triangle';
  osc.frequency.setValueAtTime(600 + Math.random() * 200, ctx.currentTime);
  gain.gain.setValueAtTime(0.08, ctx.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.04);
  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.start();
  osc.stop(ctx.currentTime + 0.04);
}

// Redoble de suspenso
function playTensionRumble() {
  if (!audioHabilitado) return;
  const ctx = getAudioContext();
  if (!ctx) return;

  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.type = 'sawtooth';
  osc.frequency.setValueAtTime(80, ctx.currentTime);
  osc.frequency.linearRampToValueAtTime(140, ctx.currentTime + 3.5);
  gain.gain.setValueAtTime(0.04, ctx.currentTime);
  gain.gain.linearRampToValueAtTime(0.12, ctx.currentTime + 3.0);
  gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 3.8);
  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.start();
  osc.stop(ctx.currentTime + 3.8);
}

// Fanfarria victoriosa (acorde mayor arpegiado brillante)
function playVictoryFanfare() {
  if (!audioHabilitado) return;
  const ctx = getAudioContext();
  if (!ctx) return;

  const notas = [523.25, 659.25, 783.99, 1046.50]; // Do, Mi, Sol, Do agudo
  notas.forEach((freq, i) => {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(freq, ctx.currentTime + i * 0.12);
    gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.12);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.12 + 0.8);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(ctx.currentTime + i * 0.12);
    osc.stop(ctx.currentTime + i * 0.12 + 0.8);
  });
}

/* ============================================================
   Confeti en Canvas (Ligero y 100% Autónomo)
   ============================================================ */
let confettiParticles = [];
let confettiAnimationId = null;

function ajustarCanvasConfeti() {
  const canvas = document.getElementById('confettiCanvas');
  if (!canvas) return;
  canvas.width = canvas.parentElement.clientWidth;
  canvas.height = canvas.parentElement.clientHeight;
}

function dispararConfeti() {
  ajustarCanvasConfeti();
  const canvas = document.getElementById('confettiCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const colores = ['#f59e0b', '#facc15', '#10b981', '#38bdf8', '#8b5cf6', '#ec4899', '#ffffff'];
  confettiParticles = [];

  for (let i = 0; i < 180; i++) {
    confettiParticles.push({
      x: canvas.width / 2 + (Math.random() - 0.5) * 120,
      y: canvas.height * 0.45,
      vx: (Math.random() - 0.5) * 18,
      vy: (Math.random() - 0.9) * 16,
      size: Math.random() * 8 + 5,
      color: colores[Math.floor(Math.random() * colores.length)],
      rotation: Math.random() * 360,
      vRot: (Math.random() - 0.5) * 12,
      opacity: 1
    });
  }

  if (confettiAnimationId) cancelAnimationFrame(confettiAnimationId);

  let frameCount = 0;
  function animarConfeti() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    frameCount++;

    confettiParticles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.35; // Gravedad
      p.rotation += p.vRot;
      p.opacity -= 0.004;

      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate((p.rotation * Math.PI) / 180);
      ctx.fillStyle = p.color;
      ctx.globalAlpha = Math.max(0, p.opacity);
      ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
      ctx.restore();
    });

    confettiParticles = confettiParticles.filter(p => p.opacity > 0 && p.y < canvas.height);

    if (confettiParticles.length > 0 && frameCount < 260) {
      confettiAnimationId = requestAnimationFrame(animarConfeti);
    } else {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
  }

  animarConfeti();
}

/* ============================================================
   Pestaña 2: Gestión de Campañas de Sorteos
   ============================================================ */
async function cargarCampanasTabla() {
  const tbody = document.getElementById('tbodyCampanas');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando campañas...</td></tr>';

  try {
    const res = await fetch('api/sorteos.php?accion=listar_sorteos');
    const data = await res.json();
    if (!data.success || !data.sorteos) throw new Error(data.error);

    if (data.sorteos.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">No hay campañas registradas. Haz clic en "Nueva Campaña" para crear la primera.</td></tr>';
      return;
    }

    tbody.innerHTML = data.sorteos.map(s => {
      const premiosText = (s.Premios || []).map(p => `${p.lugar}° ${p.premio}`).join(', ') || 'Sin premios especificados';
      const reglaText = s.TipoRegla === 'monto_cada_x' ? `1 cupón cada $${formatNumber(s.MontoMinimoCupon)}` : `1 cupón por venta (mín $${formatNumber(s.MontoMinimoCupon)})`;
      const badgeEstado = s.Estado === 'Activo' 
        ? '<span class="badge" style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">🟢 Activo</span>'
        : (s.Estado === 'Pausado' 
          ? '<span class="badge" style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3);">🟡 Pausado</span>'
          : '<span class="badge" style="background: rgba(148,163,184,0.15); color: #94a3b8; border: 1px solid var(--border-dark);">⚪ Finalizado</span>');

      return `
        <tr>
          <td>
            <strong>${escapeHtml(s.Titulo)}</strong>
            <small style="display: block; color: var(--text-muted);">${escapeHtml(s.Descripcion || '')}</small>
          </td>
          <td><span style="font-size: 0.85rem; font-weight: 700; color: #38bdf8;">${reglaText}</span></td>
          <td style="max-width: 220px; font-size: 0.85rem; color: #facc15;">${escapeHtml(premiosText)}</td>
          <td style="font-size: 0.82rem; color: var(--text-muted);">${s.FechaInicio} al ${s.FechaFin}</td>
          <td>
            <span style="font-weight: 800; color: #10b981;">${formatNumber(s.TotalCupones)}</span> cupones
            <small style="display: block; color: var(--text-muted);">${s.TotalGanadores || 0} ganadores</small>
          </td>
          <td>${badgeEstado}</td>
          <td style="text-align: right; white-space: nowrap;">
            <button type="button" class="btn btn-secondary" onclick="editarCampana(${s.SorteoID})" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" title="Editar">
              <i class="fa-solid fa-pen"></i>
            </button>
            ${s.Estado === 'Activo'
              ? `<button type="button" class="btn btn-secondary" onclick="cambiarEstadoCampana(${s.SorteoID}, 'Pausado')" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; color: #fbbf24;" title="Pausar"><i class="fa-solid fa-pause"></i></button>`
              : `<button type="button" class="btn btn-secondary" onclick="cambiarEstadoCampana(${s.SorteoID}, 'Activo')" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; color: #34d399;" title="Activar"><i class="fa-solid fa-play"></i></button>`
            }
            <button type="button" class="btn btn-secondary" onclick="eliminarCampana(${s.SorteoID})" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; color: var(--danger);" title="Eliminar">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--danger);">Error cargando campañas: ${err.message}</td></tr>`;
  }
}

function abrirModalCrearSorteo() {
  document.getElementById('formSorteoId').value = '0';
  document.getElementById('formTitulo').value = '';
  document.getElementById('formDescripcion').value = '';
  document.getElementById('formTipoRegla').value = 'monto_cada_x';
  document.getElementById('formMontoMinimo').value = '5000';
  document.getElementById('formFechaInicio').value = new Date().toISOString().split('T')[0];
  const dFin = new Date();
  dFin.setDate(dFin.getDate() + 30);
  document.getElementById('formFechaFin').value = dFin.toISOString().split('T')[0];
  document.getElementById('formEstado').value = 'Activo';
  document.getElementById('formImprimirTicket').checked = true;
  document.getElementById('formRequiereCliente').checked = false;

  const contPremios = document.getElementById('listaPremiosContainer');
  contPremios.innerHTML = '';
  agregarFilaPremio(1, 'Smart TV 43"');
  agregarFilaPremio(2, 'Canasta Familiar');

  document.getElementById('modalSorteoTitulo').innerHTML = '<i class="fa-solid fa-gift" style="color: #f59e0b;"></i> Nueva Campaña de Sorteo';
  document.getElementById('modalSorteo').style.display = 'flex';
}

function cerrarModalSorteo() {
  document.getElementById('modalSorteo').style.display = 'none';
}

function agregarFilaPremio(lugar = null, premio = '') {
  const cont = document.getElementById('listaPremiosContainer');
  const n = lugar || (cont.children.length + 1);
  const row = document.createElement('div');
  row.style.display = 'flex';
  row.style.gap = '0.5rem';
  row.style.alignItems = 'center';
  row.innerHTML = `
    <span style="font-weight: 800; font-size: 0.85rem; color: #facc15; min-width: 60px;">${n}° Lugar:</span>
    <input type="text" class="form-control input-premio-item" data-lugar="${n}" value="${escapeHtml(premio)}" placeholder="Descripción del premio (ej. Bicicleta, Canasta $50.000...)" style="flex: 1;" required>
    <button type="button" class="btn btn-secondary" onclick="this.parentElement.remove()" style="padding: 0.25rem 0.5rem; color: var(--danger);"><i class="fa-solid fa-xmark"></i></button>
  `;
  cont.appendChild(row);
}

function toggleReglaMonto() {
  const sel = document.getElementById('formTipoRegla').value;
  const lbl = document.getElementById('lblFormMontoMin');
  if (lbl) {
    lbl.textContent = sel === 'monto_cada_x' ? 'Monto por cada Cupón ($ CLP) *' : 'Monto mínimo de compra para participar ($ CLP) *';
  }
}

async function guardarCampanaSorteo(e) {
  e.preventDefault();
  const id = parseInt(document.getElementById('formSorteoId').value, 10);
  const titulo = document.getElementById('formTitulo').value.trim();
  const descripcion = document.getElementById('formDescripcion').value.trim();
  const tipoRegla = document.getElementById('formTipoRegla').value;
  const montoMinimo = parseFloat(document.getElementById('formMontoMinimo').value) || 5000;
  const fechaInicio = document.getElementById('formFechaInicio').value;
  const fechaFin = document.getElementById('formFechaFin').value;
  const estado = document.getElementById('formEstado').value;
  const imprimirTicket = document.getElementById('formImprimirTicket').checked;
  const requiereCliente = document.getElementById('formRequiereCliente').checked;

  const premios = [];
  document.querySelectorAll('.input-premio-item').forEach((inp, idx) => {
    const val = inp.value.trim();
    if (val) premios.push({ lugar: idx + 1, premio: val });
  });

  if (premios.length === 0) {
    premios.push({ lugar: 1, premio: 'Premio Principal' });
  }

  try {
    const res = await fetch('api/sorteos.php?accion=guardar_sorteo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        sorteo_id: id,
        titulo: titulo,
        descripcion: descripcion,
        tipo_regla: tipoRegla,
        monto_minimo: montoMinimo,
        premios: premios,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        estado: estado,
        imprimir_ticket: imprimirTicket ? 1 : 0,
        requiere_cliente: requiereCliente ? 1 : 0
      })
    });
    const data = await res.json();
    if (data.success) {
      toast('Campaña guardada con éxito.', 'success');
      cerrarModalSorteo();
      cargarCampanasTabla();
    } else {
      toast(data.error || 'Error al guardar.', 'error');
    }
  } catch (err) {
    toast('Error de conexión al guardar.', 'error');
  }
}

async function cambiarEstadoCampana(sorteoId, nuevoEstado) {
  try {
    const res = await fetch('api/sorteos.php?accion=cambiar_estado', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sorteo_id: sorteoId, estado: nuevoEstado })
    });
    const data = await res.json();
    if (data.success) {
      toast(`Campaña actualizada a ${nuevoEstado}`, 'info');
      cargarCampanasTabla();
    }
  } catch (err) {
    toast('Error al actualizar estado.', 'error');
  }
}

async function eliminarCampana(sorteoId) {
  if (!confirm('¿Seguro que deseas eliminar esta campaña? Se borrarán también los cupones asociados.')) return;
  try {
    const res = await fetch('api/sorteos.php?accion=eliminar_sorteo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ sorteo_id: sorteoId })
    });
    const data = await res.json();
    if (data.success) {
      toast('Campaña eliminada.', 'info');
      cargarCampanasTabla();
    }
  } catch (err) {
    toast('Error al eliminar.', 'error');
  }
}

async function editarCampana(sorteoId) {
  try {
    const res = await fetch('api/sorteos.php?accion=listar_sorteos');
    const data = await res.json();
    if (!data.success) return;
    const s = data.sorteos.find(item => item.SorteoID === sorteoId);
    if (!s) return;

    document.getElementById('formSorteoId').value = s.SorteoID;
    document.getElementById('formTitulo').value = s.Titulo;
    document.getElementById('formDescripcion').value = s.Descripcion || '';
    document.getElementById('formTipoRegla').value = s.TipoRegla;
    document.getElementById('formMontoMinimo').value = s.MontoMinimoCupon;
    document.getElementById('formFechaInicio').value = s.FechaInicio;
    document.getElementById('formFechaFin').value = s.FechaFin;
    document.getElementById('formEstado').value = s.Estado;
    document.getElementById('formImprimirTicket').checked = s.ImprimirCuponEnTicket == 1;
    document.getElementById('formRequiereCliente').checked = s.RequiereCliente == 1;

    const contPremios = document.getElementById('listaPremiosContainer');
    contPremios.innerHTML = '';
    const premios = Array.isArray(s.Premios) ? s.Premios : [];
    if (premios.length > 0) {
      premios.forEach(p => agregarFilaPremio(p.lugar, p.premio));
    } else {
      agregarFilaPremio(1, 'Premio Principal');
    }

    document.getElementById('modalSorteoTitulo').innerHTML = '<i class="fa-solid fa-pen" style="color: #38bdf8;"></i> Editar Campaña de Sorteo';
    document.getElementById('modalSorteo').style.display = 'flex';
  } catch (err) {
    toast('Error al cargar datos del sorteo.', 'error');
  }
}

/* ============================================================
   Pestaña 3: Cupones Emitidos
   ============================================================ */
let paginaActualCupones = 1;

async function cargarCuponesTabla(page = 1) {
  paginaActualCupones = page;
  const tbody = document.getElementById('tbodyCupones');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando cupones...</td></tr>';

  const buscar = document.getElementById('filtroBuscarCupon').value.trim();
  const sorteoId = document.getElementById('filtroSorteoCupones').value;
  const soloGanadores = document.getElementById('chkSoloGanadores').checked ? 1 : 0;

  try {
    const url = `api/sorteos.php?accion=listar_cupones&page=${page}&sorteo_id=${sorteoId}&buscar=${encodeURIComponent(buscar)}&solo_ganadores=${soloGanadores}`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    if (data.cupones.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">No se encontraron cupones emitidos con los filtros actuales.</td></tr>';
      document.getElementById('lblInfoCuponesPaginacion').textContent = '0 cupones';
      document.getElementById('botonesPaginacionCupones').innerHTML = '';
      return;
    }

    document.getElementById('lblInfoCuponesPaginacion').textContent = `Total: ${formatNumber(data.total)} cupones (Página ${data.page} de ${data.total_paginas})`;

    tbody.innerHTML = data.cupones.map(c => {
      const badgeEstado = c.EsGanador == 1
        ? `<span class="badge" style="background: rgba(234,179,8,0.18); color: #facc15; border: 1px solid rgba(234,179,8,0.4); font-weight: 800;">🏆 GANADOR (${c.LugarPremio}° Lugar: ${escapeHtml(c.PremioGanado || '')})</span>`
        : `<span class="badge" style="background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.3);">🎟️ En Juego</span>`;

      return `
        <tr>
          <td><strong style="color: #facc15; font-family: monospace; font-size: 1.05rem;">${escapeHtml(c.CodigoCupon)}</strong></td>
          <td><small style="color: var(--text-muted);">${escapeHtml(c.SorteoTitulo)}</small></td>
          <td><strong>${escapeHtml(c.NombreParticipante || c.ClienteNombreRegistrado || 'Público General')}</strong></td>
          <td><small style="color: #38bdf8;">${escapeHtml(c.TelefonoParticipante || c.ClienteTelefonoRegistrado || '-')}</small></td>
          <td>${c.VentaID ? `<a href="ventas.php?buscar=${c.VentaID}" style="color: var(--primary); text-decoration: none; font-weight: 700;">#${c.VentaID}</a>` : '-'}</td>
          <td>$${formatNumber(c.MontoVenta)}</td>
          <td><small style="color: var(--text-muted);">${c.EmitidoEn}</small></td>
          <td>${badgeEstado}</td>
          <td style="text-align: right;">
            <button type="button" class="btn btn-secondary" onclick="imprimirCuponIndividual(${c.CuponID})" style="padding: 0.25rem 0.55rem; font-size: 0.78rem;" title="Imprimir Cupón Térmico">
              <i class="fa-solid fa-print"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    // Generar botones de paginación
    const btnBox = document.getElementById('botonesPaginacionCupones');
    btnBox.innerHTML = '';
    if (data.total_paginas > 1) {
      if (page > 1) {
        btnBox.innerHTML += `<button type="button" class="btn btn-secondary" onclick="cargarCuponesTabla(${page - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;
      }
      btnBox.innerHTML += `<span style="padding: 0.4rem 0.6rem; font-weight: 700;">${page} / ${data.total_paginas}</span>`;
      if (page < data.total_paginas) {
        btnBox.innerHTML += `<button type="button" class="btn btn-secondary" onclick="cargarCuponesTabla(${page + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;
      }
    }
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color: var(--danger);">Error: ${err.message}</td></tr>`;
  }
}

/* ============================================================
   Pestaña 4: Ganadores Oficiales
   ============================================================ */
async function cargarGanadoresTabla() {
  const tbody = document.getElementById('tbodyGanadores');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando ganadores...</td></tr>';

  try {
    const res = await fetch('api/sorteos.php?accion=listar_ganadores');
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    if (data.ganadores.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">Aún no se han registrado ganadores oficiales. ¡Usa la Tómbola Virtual para realizar el primer sorteo!</td></tr>';
      return;
    }

    tbody.innerHTML = data.ganadores.map(g => {
      return `
        <tr>
          <td><strong style="color: #facc15; font-size: 1rem;"><i class="fa-solid fa-trophy"></i> ${g.LugarPremio}° Lugar: ${escapeHtml(g.PremioGanado || '')}</strong></td>
          <td>${escapeHtml(g.SorteoTitulo)}</td>
          <td><strong style="color: #38bdf8; font-family: monospace;">${escapeHtml(g.CodigoCupon)}</strong></td>
          <td><strong>${escapeHtml(g.NombreParticipante || g.ClienteNombreRegistrado || 'Ganador')}</strong></td>
          <td>${escapeHtml(g.TelefonoParticipante || g.ClienteTelefonoRegistrado || '-')}</td>
          <td><small style="color: var(--text-muted);">${g.FechaGanador}</small></td>
          <td style="text-align: right;">
            <button type="button" class="btn btn-secondary" onclick="anularGanador(${g.CuponID})" style="padding: 0.25rem 0.6rem; font-size: 0.78rem; color: var(--danger);" title="Anular Ganador">
              <i class="fa-solid fa-rotate-left"></i> Anular
            </button>
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--danger);">Error: ${err.message}</td></tr>`;
  }
}

async function anularGanador(cuponId) {
  if (!confirm('¿Deseas anular la asignación de este premio para este cupón?')) return;
  try {
    const res = await fetch('api/sorteos.php?accion=deshacer_ganador', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cupon_id: cuponId })
    });
    const data = await res.json();
    if (data.success) {
      toast('Premio anulado correctamente.', 'info');
      cargarGanadoresTabla();
    }
  } catch (err) {
    toast('Error al anular premio.', 'error');
  }
}

/* ============================================================
   Impresión de Talón de Cupón Térmico
   ============================================================ */
async function imprimirCuponIndividual(cuponId) {
  try {
    const res = await fetch(`api/sorteos.php?accion=obtener_cupon_impresion&cupon_id=${cuponId}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    const c = data.cupon;
    const printWindow = window.open('', '_blank', 'width=380,height=550');
    if (!printWindow) {
      alert('Permite las ventanas emergentes para imprimir el cupón.');
      return;
    }

    const html = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>Cupón de Sorteo ${c.CodigoCupon}</title>
        <style>
          body { font-family: 'Courier New', monospace; font-size: 13px; margin: 0; padding: 10px; line-height: 1.3; color: #000; }
          .center { text-align: center; }
          .bold { font-weight: bold; }
          .lg { font-size: 17px; }
          .xl { font-size: 26px; }
          .sep { border-top: 1px dashed #000; margin: 8px 0; }
          .row { display: flex; justify-content: space-between; margin: 3px 0; }
          .box-cupon { border: 2px dashed #000; padding: 8px; margin: 10px 0; text-align: center; }
          @media print { body { margin: 0; padding: 5px; } }
        </style>
      </head>
      <body>
        <div class="center bold lg">TALÓN DE SORTEO</div>
        <div class="center bold">${escapeHtml(c.SorteoTitulo)}</div>
        <div class="center">${escapeHtml(c.SorteoDesc || '')}</div>
        <div class="sep"></div>

        <div class="box-cupon">
          <div style="font-size: 12px;">CUPÓN N°</div>
          <div class="bold xl">${escapeHtml(c.CodigoCupon)}</div>
          <div style="font-size: 11px;">Conserva este talón para reclamar tu premio</div>
        </div>

        <div class="row"><span>Participante:</span><span class="bold">${escapeHtml(c.NombreParticipante || 'Público General')}</span></div>
        <div class="row"><span>Teléfono:</span><span>${escapeHtml(c.TelefonoParticipante || 'No registrado')}</span></div>
        <div class="row"><span>Boleta / Venta:</span><span>#${c.VentaID || '-'}</span></div>
        <div class="row"><span>Fecha emisión:</span><span>${c.EmitidoEn}</span></div>
        <div class="row"><span>Fecha sorteo:</span><span class="bold">${c.SorteoFechaFin}</span></div>
        <div class="sep"></div>
        <div class="center bold" style="font-size: 11px;">¡MUCHA SUERTE! 🍀</div>
        <script>
          window.onload = function() { window.print(); setTimeout(() => window.close(), 600); }
        <\/script>
      </body>
      </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
  } catch (err) {
    toast('Error al generar impresión de cupón: ' + err.message, 'error');
  }
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text).replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

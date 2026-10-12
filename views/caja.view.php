<?php if (!empty($redirigirPos)): ?>
  <script>window.location.replace('pos.php');</script>
<?php endif; ?>

<div style="margin-bottom: 1.5rem;">
  <h1 style="font-size: 1.5rem; font-weight: 700;">Gestión y Arqueo de Caja</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Apertura, cierre, conteo de dinero por billetes/monedas y registro de gastos/retiros</p>
</div>

<!-- Mensajes del Sistema -->
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

<!-- Reporte Inmediato tras Cierre de Turno (Revelación de Arqueo Ciego) -->
<?php if ($reporteCierre): ?>
  <div class="table-card" style="padding: 1.75rem; margin-bottom: 2rem; border: 2px solid var(--primary); background: rgba(15,23,42,0.9);">
    <h2 style="font-size: 1.25rem; font-weight: 700; color: #818cf8; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
      <i class="fa-solid fa-file-contract"></i> Resumen de Cuadraje - Turno Cerrado #<?= $reporteCierre['TurnoID'] ?>
    </h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
      <div>
        <h3 style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Detalle de Caja (Efectivo)</h3>
        <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.9rem;">
          <div style="display: flex; justify-content: space-between;"><span>Fondo Inicial:</span> <strong><?= formatCLP($reporteCierre['MontoApertura']) ?></strong></div>
          <div style="display: flex; justify-content: space-between;"><span>(+) Ventas Efectivo:</span> <strong><?= formatCLP($reporteCierre['ventas_efectivo']) ?></strong></div>
          <div style="display: flex; justify-content: space-between;"><span>(+) Ingresos Manuales:</span> <strong><?= formatCLP($reporteCierre['ingresos_manuales']) ?></strong></div>
          <div style="display: flex; justify-content: space-between;"><span>(-) Retiros/Gastos:</span> <strong><?= formatCLP($reporteCierre['retiros_manuales']) ?></strong></div>
          <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-dark); padding-top: 0.4rem; margin-top: 0.4rem;">
            <span>Efectivo Esperado:</span> <strong><?= formatCLP($reporteCierre['efectivo_esperado']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 1rem; color: #fff; font-weight: bold; margin-top: 0.4rem;">
            <span>Efectivo Real Contado:</span> <span><?= formatCLP($reporteCierre['MontoCierreEfectivo']) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; border-top: 1px dashed var(--border-dark); padding-top: 0.3rem;">
            <span>Diferencia Efectivo:</span>
            <?php if ($reporteCierre['diferencia_efectivo'] == 0): ?>
              <strong style="color: var(--success);">$0 (Cuadrado)</strong>
            <?php elseif ($reporteCierre['diferencia_efectivo'] > 0): ?>
              <strong style="color: var(--success);">+<?= formatCLP($reporteCierre['diferencia_efectivo']) ?> (Sobrante)</strong>
            <?php else: ?>
              <strong style="color: var(--danger);"><?= formatCLP($reporteCierre['diferencia_efectivo']) ?> (Faltante)</strong>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div>
        <h3 style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Otros Medios y Balance Global</h3>
        <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.9rem;">
          <div style="display: flex; justify-content: space-between;">
            <span>Tarjeta (Esp vs Real):</span>
            <strong><?= formatCLP($reporteCierre['ventas_tarjeta']) ?> / <?= formatCLP($reporteCierre['tarjeta_real']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
            <span>Dif. Tarjeta:</span>
            <strong><?= $reporteCierre['diferencia_tarjeta'] >= 0 ? '+' : '' ?><?= formatCLP($reporteCierre['diferencia_tarjeta']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; margin-top: 0.3rem;">
            <span>Transferencia (Esp vs Real):</span>
            <strong><?= formatCLP($reporteCierre['ventas_transferencia']) ?> / <?= formatCLP($reporteCierre['transferencia_real']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
            <span>Dif. Transferencia:</span>
            <strong><?= $reporteCierre['diferencia_transferencia'] >= 0 ? '+' : '' ?><?= formatCLP($reporteCierre['diferencia_transferencia']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-dark); padding-top: 0.4rem; margin-top: 0.4rem;">
            <span>Vales Canjeados (Nota Crédito):</span>
            <strong><?= formatCLP($reporteCierre['ventas_vales'] ?? 0) ?></strong>
          </div>
          
          <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-dark); padding-top: 0.5rem; margin-top: 0.5rem; font-size: 1.15rem; font-weight: bold; color: #fff;">
            <span>Diferencia Neta Global:</span>
            <?php if ($reporteCierre['diferencia_neta'] == 0): ?>
              <span style="color: var(--success);">Cuadrado ($0)</span>
            <?php elseif ($reporteCierre['diferencia_neta'] > 0): ?>
              <span style="color: var(--success);">Sobrante (+<?= formatCLP($reporteCierre['diferencia_neta']) ?>)</span>
            <?php else: ?>
              <span style="color: var(--danger);">Faltante (<?= formatCLP($reporteCierre['diferencia_neta']) ?>)</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php if (!empty($reporteCierre['Observaciones'])): ?>
      <div style="background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
        <strong>Observaciones de Cierre:</strong> <?= htmlspecialchars($reporteCierre['Observaciones']) ?>
      </div>
    <?php endif; ?>
    <button onclick="location.href='caja.php'" class="btn btn-secondary">Aceptar y Continuar</button>
  </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 420px 1fr; gap: 1.5rem; margin-bottom: 2rem;">

  <!-- Formulario de Estado de Turno -->
  <div class="table-card" style="padding: 1.5rem;">
    <?php if (!$turnoActivo): ?>
      <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--warning); margin-bottom: 1rem;">
        <i class="fa-solid fa-lock"></i> Apertura de Turno
      </h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
        Ingresa el monto inicial para abrir tu caja registradora.
      </p>

      <form method="POST" action="caja.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="apertura">
        <?php if (!empty($cajasDisponibles) && count($cajasDisponibles) > 1): ?>
          <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CAJA REGISTRADORA</label>
            <select name="caja_id" id="selectCajaApertura" class="form-control" style="font-size: 0.95rem; font-weight: 600;" onchange="try{localStorage.setItem('pos_caja_preferida', this.value)}catch(e){}">
              <?php foreach ($cajasDisponibles as $cj): ?>
                <option value="<?= $cj['CajaID'] ?>"><?= htmlspecialchars($cj['Nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <script>
            (function() {
              try {
                const pref = localStorage.getItem('pos_caja_preferida');
                const sel = document.getElementById('selectCajaApertura');
                if (pref && sel) {
                  sel.value = pref;
                }
              } catch(e) {}
            })();
          </script>
        <?php elseif (!empty($cajasDisponibles)): ?>
          <input type="hidden" name="caja_id" value="<?= (int)$cajasDisponibles[0]['CajaID'] ?>">
        <?php endif; ?>
        <div style="margin-bottom: 1.25rem;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MONTO DE APERTURA ($)</label>
          <input type="number" name="monto_apertura" class="form-control" value="20000" required style="font-size: 1.1rem; font-weight: bold;">
        </div>
        <button type="submit" class="btn btn-success btn-block" style="padding: 0.85rem;">
          <i class="fa-solid fa-key"></i> Abrir Caja Registradora
        </button>
      </form>

    <?php else: ?>
      <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--success); margin-bottom: 1rem;">
        <i class="fa-solid fa-vault"></i> Arqueo de Caja (Arqueo Ciego)
      </h2>
      
      <!-- Información Básica -->
      <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.85rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
          <span style="color: var(--text-muted);">Caja Física:</span>
          <strong style="color: #818cf8;"><i class="fa-solid fa-cash-register"></i> <?= htmlspecialchars($turnoActivo['NombreCaja'] ?? 'Caja Principal') ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
          <span style="color: var(--text-muted);">Cajero Activo:</span>
          <strong><?= htmlspecialchars($user['nombre']) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
          <span style="color: var(--text-muted);">Apertura de Caja:</span>
          <strong><?= date('d/m/Y H:i', strtotime($turnoActivo['FechaApertura'])) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: var(--text-muted);">Estado del Turno:</span>
          <span style="color: var(--success); font-weight: bold;"><i class="fa-solid fa-circle-check"></i> ABIERTO</span>
        </div>
      </div>

      <!-- Calculadora Interactiva de Denominación de Dinero -->
      <div style="margin-bottom: 1rem; background: rgba(15,23,42,0.6); padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-dark);">
        <label style="font-size: 0.75rem; color: #818cf8; font-weight: 700; display: block; margin-bottom: 0.5rem;">CONTEO DE BILLETES Y MONEDAS</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; font-size: 0.8rem;">
          <div>$20.000 x <input type="number" min="0" class="denom-input" data-val="20000" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$10.000 x <input type="number" min="0" class="denom-input" data-val="10000" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$5.000 x <input type="number" min="0" class="denom-input" data-val="5000" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$2.000 x <input type="number" min="0" class="denom-input" data-val="2000" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$1.000 x <input type="number" min="0" class="denom-input" data-val="1000" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$500 x <input type="number" min="0" class="denom-input" data-val="500" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$100 x <input type="number" min="0" class="denom-input" data-val="100" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$50 x <input type="number" min="0" class="denom-input" data-val="50" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
          <div>$10 x <input type="number" min="0" class="denom-input" data-val="10" style="width: 50px; background:#000; color:#fff; border:1px solid var(--border-dark); border-radius:4px;" value="0"></div>
        </div>
      </div>

      <form method="POST" action="caja.php" id="cierreCajaForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="cierre">
        <input type="hidden" name="turno_id" id="cierreTurnoId" value="<?= $turnoActivo['TurnoID'] ?>">
        
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">EFECTIVO REAL CONTADO ($)</label>
          <input type="number" id="efectivoRealInput" name="efectivo_real" class="form-control" placeholder="Ingrese monto contado" required style="font-size: 1.1rem; font-weight: bold;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TARJETA REAL ($)</label>
            <input type="number" id="tarjetaRealInput" name="tarjeta_real" class="form-control" value="0" required style="font-weight: bold;">
          </div>
          <div>
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TRANSFERENCIA REAL ($)</label>
            <input type="number" id="transferenciaRealInput" name="transferencia_real" class="form-control" value="0" required style="font-weight: bold;">
          </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">OBSERVACIONES</label>
          <input type="text" name="observaciones" class="form-control" placeholder="Opcional: aclaraciones de arqueo">
        </div>

        <?php if ($user['rol'] === 'Cajero'): ?>
        <div style="margin-bottom: 1.25rem;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CLAVE DE ADMINISTRADOR / SUPERVISOR *</label>
          <input type="password" name="supervisor_pass" class="form-control" placeholder="Requerida para cerrar el turno" required>
        </div>
        <?php endif; ?>

        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #fff; font-weight: 600; margin-bottom: 1.25rem; cursor: pointer; background: rgba(79, 70, 229, 0.15); border: 1px solid var(--primary); border-radius: 8px; padding: 0.6rem 0.75rem;">
          <input type="checkbox" name="generar_z" value="1" checked style="width: 16px; height: 16px;">
          Generar Cierre Z de esta caja también
        </label>

        <button type="submit" class="btn btn-danger btn-block" style="padding: 0.85rem;" id="btnCerrarCaja">
          <i class="fa-solid fa-lock"></i> Cerrar Turno y Caja
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Resumen de movimientos de Caja -->
  <div style="display: flex; flex-direction: column; gap: 1.5rem;">
    
    <!-- Aviso de Arqueo Ciego -->
    <?php if ($turnoActivo): ?>
      <div style="background: rgba(79, 70, 229, 0.15); border: 1px solid var(--primary); border-radius: 12px; padding: 1.25rem; font-size: 0.9rem; color: #a5b4fc;">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff;"><i class="fa-solid fa-eye-slash"></i> Modalidad Arqueo Ciego Activa</h3>
        <p>Los totales de ventas, efectivo acumulado y diferencias de caja permanecen ocultos mientras el turno está abierto para garantizar un arqueo transparente. Se calculará el descuadre automáticamente una vez que cierres el turno.</p>
      </div>
    <?php endif; ?>

    <!-- Movimientos de Caja (Ingreso / Retiro) -->
    <div class="table-card" style="padding: 1.5rem;">
      <h2 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Registrar Movimiento de Caja (Ingreso / Retiro de Dinero)</h2>
      <div style="display: grid; grid-template-columns: 1fr 1fr 2fr 1fr; gap: 0.5rem; align-items: end;">
        <div>
          <label style="font-size: 0.75rem; color: var(--text-muted);">TIPO</label>
          <select id="movTipo" class="form-control" style="padding: 0.4rem;">
            <option value="RETIRO">Retiro (-)</option>
            <option value="INGRESO">Ingreso (+)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.75rem; color: var(--text-muted);">MONTO ($)</label>
          <input type="number" id="movMonto" class="form-control" placeholder="10000" style="padding: 0.4rem;">
        </div>
        <div>
          <label style="font-size: 0.75rem; color: var(--text-muted);">CONCEPTO / MOTIVO</label>
          <input type="text" id="movConcepto" class="form-control" placeholder="Ej: Pago de panadería / Retiro parcial" style="padding: 0.4rem;">
        </div>
        <button onclick="registrarMovimientoCaja()" class="btn btn-secondary" style="padding: 0.55rem;">
          <i class="fa-solid fa-floppy-disk"></i> Registrar
        </button>
      </div>
    </div>

  </div>

</div>

<!-- Historial de Turnos -->
<div class="table-card">
  <div class="table-header">
    <h2 style="font-size: 1.1rem; font-weight: 600;">Historial de Aperturas y Cierres</h2>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>ID Turno</th>
        <th>Cajero</th>
        <th>Apertura</th>
        <th>Cierre</th>
        <th>Monto Inicial</th>
        <th>Efectivo Cierre</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($historialTurnos)): ?>
        <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 1.75rem;">No hay registros previos de turnos de caja.</td></tr>
      <?php else: ?>
        <?php foreach ($historialTurnos as $h): ?>
          <tr>
            <td>#<?= $h['TurnoID'] ?></td>
            <td><?= htmlspecialchars($h['Usuario']) ?><?= !empty($h['NombreCaja']) ? ' <small style="color:var(--text-muted);">(' . htmlspecialchars($h['NombreCaja']) . ')</small>' : '' ?></td>
            <td><?= date('d/m/Y H:i', strtotime($h['FechaApertura'])) ?></td>
            <td><?= $h['FechaCierre'] ? date('d/m/Y H:i', strtotime($h['FechaCierre'])) : '<span style="color:var(--success); font-weight:600;">En curso</span>' ?></td>
            <td><?= formatCLP($h['MontoApertura']) ?></td>
            <td><?= $h['MontoCierreEfectivo'] !== null ? formatCLP($h['MontoCierreEfectivo']) : '-' ?></td>
            <td>
              <span class="badge <?= $h['Estado'] == 'Abierto' ? 'badge-success' : 'badge-warning' ?>">
                <?= $h['Estado'] ?>
              </span>
            </td>
            <td>
              <button onclick="verDetalleTurno(<?= $h['TurnoID'] ?>)" class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                <i class="fa-solid fa-eye"></i> Detalle
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Detalle de Turno Histórico -->
<div id="detalleTurnoModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: flex-start; overflow-y: auto; padding: 1.5rem 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 620px; padding: 1.5rem; box-shadow: var(--shadow-lg); margin: 1.5rem auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
      <h2 style="font-size: 1.2rem; font-weight: 700; color: #818cf8; margin: 0;" id="modalTitle">Detalle del Turno</h2>
      <button onclick="cerrarDetalleModal()" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <div id="modalBody" style="display: flex; flex-direction: column; gap: 1rem;">
      <!-- Se llena dinámicamente -->
    </div>
  </div>
</div>

<script>
const USUARIO_ROL = '<?= $user['rol'] ?>';

document.querySelectorAll('.denom-input').forEach(inp => {
  inp.addEventListener('input', calcularArqueo);
});

function calcularArqueo() {
  let suma = 0;
  document.querySelectorAll('.denom-input').forEach(inp => {
    const cant = parseInt(inp.value) || 0;
    const val = parseInt(inp.dataset.val) || 0;
    suma += cant * val;
  });
  const inputReal = document.getElementById('efectivoRealInput');
  if (inputReal) inputReal.value = suma;
  
  verificadoDescuadre = false;
}

// Lógica de alerta de descuadre para arqueo ciego antes de enviar formulario
let verificadoDescuadre = false;
let ultimoEfectivoContado = -1;

document.getElementById('cierreCajaForm')?.addEventListener('submit', async function(e) {
  const finalVal = parseInt(document.getElementById('efectivoRealInput').value) || 0;
  const tarjVal = parseInt(document.getElementById('tarjetaRealInput').value) || 0;
  const transVal = parseInt(document.getElementById('transferenciaRealInput').value) || 0;
  
  const currentTotal = finalVal + tarjVal + transVal;
  
  if (verificadoDescuadre && currentTotal === ultimoEfectivoContado) {
    return true; // Continuar con la sumisión normal del formulario
  }
  
  e.preventDefault(); // Detener envío provisionalmente
  
  const turnoId = document.getElementById('cierreTurnoId').value;
  
  try {
    const res = await fetch(`api/detalle_turno.php?id=${turnoId}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    
    const espEfectivo = data.cuadraje.EfectivoEsperado;
    const espTarjeta = data.cuadraje.TarjetaEsperada;
    const espTransferencia = data.cuadraje.TransferenciaEsperada;
    
    const difEfectivo = finalVal - espEfectivo;
    const difTarjeta = tarjVal - espTarjeta;
    const difTransferencia = transVal - espTransferencia;
    
    const difNeta = difEfectivo + difTarjeta + difTransferencia;
    
    if (difNeta !== 0) {
      alert("⚠️ ALERTA DE DESCUADRE NETO GLOBAL:\n\nEl total acumulado contado de caja y bancos no cuadra con el registro del sistema (Diferencia Neta: " + (difNeta > 0 ? "+" : "") + fmtGlobal(difNeta) + ").\n\nPor favor, verifica tus registros de tarjetas/transferencias e ingresa los montos nuevamente.\n\nSi la diferencia es real y deseas continuar, vuelve a presionar 'Cerrar Turno y Caja' para confirmar.");
      verificadoDescuadre = true;
      ultimoEfectivoContado = currentTotal;
    } else {
      verificadoDescuadre = true;
      ultimoEfectivoContado = currentTotal;
      this.submit(); // Proceder de inmediato si la diferencia neta global es 0
    }
  } catch (err) {
    if (confirm("No se pudo conectar con el servidor para verificar cuadratura. ¿Deseas cerrar de todas formas?")) {
      verificadoDescuadre = true;
      ultimoEfectivoContado = currentTotal;
      this.submit();
    }
  }

  function fmtGlobal(val) {
    return '$' + new Intl.NumberFormat('es-CL').format(val);
  }
});

async function registrarMovimientoCaja() {
  const tipo = document.getElementById('movTipo').value;
  const monto = parseInt(document.getElementById('movMonto').value) || 0;
  const concepto = document.getElementById('movConcepto').value.trim();

  if (monto <= 0) {
    alert('Ingresa un monto válido mayor a 0.');
    return;
  }

  try {
    const res = await fetch('api/movimiento_caja.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({ tipo, monto, concepto })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    alert(data.mensaje);
    location.reload();
  } catch (err) {
    alert('Error al registrar movimiento: ' + err.message);
  }
}

async function verDetalleTurno(id) {
  try {
    const res = await fetch(`api/detalle_turno.php?id=${id}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    const t = data.turno;
    const v = data.ventas;
    const m = data.movimientos;
    const c = data.cuadraje;

    document.getElementById('modalTitle').textContent = `Comprobante de Caja - Turno #${t.TurnoID}`;
    
    function fmt(val) {
      return '$' + new Intl.NumberFormat('es-CL').format(val);
    }

    let diffEfectivoHtml = '';
    if (t.Estado === 'Abierto') {
      diffEfectivoHtml = `<span style="color: var(--warning);">En proceso</span>`;
    } else {
      if (c.DifEfectivo === 0) {
        diffEfectivoHtml = `<strong style="color: var(--success);">$0</strong>`;
      } else if (c.DifEfectivo > 0) {
        diffEfectivoHtml = `<strong style="color: var(--success);">+${fmt(c.DifEfectivo)}</strong>`;
      } else {
        diffEfectivoHtml = `<strong style="color: var(--danger);">${fmt(c.DifEfectivo)}</strong>`;
      }
    }

    let diffTarjetaHtml = '';
    if (t.Estado === 'Abierto') {
      diffTarjetaHtml = `<span style="color: var(--warning);">En proceso</span>`;
    } else {
      if (c.DifTarjeta === 0) {
        diffTarjetaHtml = `<strong style="color: var(--success);">$0</strong>`;
      } else if (c.DifTarjeta > 0) {
        diffTarjetaHtml = `<strong style="color: var(--success);">+${fmt(c.DifTarjeta)}</strong>`;
      } else {
        diffTarjetaHtml = `<strong style="color: var(--danger);">${fmt(c.DifTarjeta)}</strong>`;
      }
    }

    let diffTransferenciaHtml = '';
    if (t.Estado === 'Abierto') {
      diffTransferenciaHtml = `<span style="color: var(--warning);">En proceso</span>`;
    } else {
      if (c.DifTransferencia === 0) {
        diffTransferenciaHtml = `<strong style="color: var(--success);">$0</strong>`;
      } else if (c.DifTransferencia > 0) {
        diffTransferenciaHtml = `<strong style="color: var(--success);">+${fmt(c.DifTransferencia)}</strong>`;
      } else {
        diffTransferenciaHtml = `<strong style="color: var(--danger);">${fmt(c.DifTransferencia)}</strong>`;
      }
    }

    let diffNetaHtml = '';
    if (t.Estado === 'Abierto') {
      diffNetaHtml = `<strong style="color: var(--warning);">TURNO ABIERTO (Sin Cierre)</strong>`;
    } else {
      if (c.DifNeta === 0) {
        diffNetaHtml = `<strong style="color: var(--success);">$0 (Cuadrado Neto)</strong>`;
      } else if (c.DifNeta > 0) {
        diffNetaHtml = `<strong style="color: var(--success);">+${fmt(c.DifNeta)} (Sobrante Neto)</strong>`;
      } else {
        diffNetaHtml = `<strong style="color: var(--danger);">${fmt(c.DifNeta)} (Faltante Neto)</strong>`;
      }
    }

    let bitacoraHtml = '';
    if (data.bitacora && data.bitacora.length > 0) {
      bitacoraHtml = `
        <h4 style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.75rem; text-transform: uppercase; font-weight: 600;">Bitácora de Movimientos</h4>
        <div style="max-height: 110px; overflow-y: auto; background: rgba(0,0,0,0.25); padding: 0.6rem; border-radius: 8px; font-size: 0.8rem;">
          ${data.bitacora.map(b => `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.15rem;">
              <span style="color: ${b.TipoMovimiento === 'INGRESO' ? '#34d399' : '#f87171'};">[${b.TipoMovimiento}] ${b.Hora} - ${b.Descripcion}</span>
              <strong>${b.TipoMovimiento === 'INGRESO' ? '+' : '-'}${fmt(b.Monto)}</strong>
            </div>
          `).join('')}
        </div>
      `;
    }

    const esAdmin = (USUARIO_ROL === 'Administrador' || USUARIO_ROL === 'Supervisor');

    const realContadoHtml = esAdmin
      ? `<input type="number" id="auditEfectivoReal" class="form-control" style="width: 105px; font-weight: bold; padding: 0.2rem 0.5rem; text-align: right; height: 28px;" value="${t.MontoCierreEfectivo !== null ? t.MontoCierreEfectivo : 0}">`
      : `<strong>${t.MontoCierreEfectivo !== null ? fmt(t.MontoCierreEfectivo) : '-'}</strong>`;

    const realTarjetaHtml = esAdmin
      ? `<input type="number" id="auditTarjetaReal" class="form-control" style="width: 105px; font-weight: bold; padding: 0.2rem 0.5rem; text-align: right; height: 28px;" value="${t.MontoCierreTarjeta !== null ? t.MontoCierreTarjeta : 0}">`
      : `<strong>${t.MontoCierreTarjeta !== null ? fmt(t.MontoCierreTarjeta) : '-'}</strong>`;

    const realTransferenciaHtml = esAdmin
      ? `<input type="number" id="auditTransferenciaReal" class="form-control" style="width: 105px; font-weight: bold; padding: 0.2rem 0.5rem; text-align: right; height: 28px;" value="${t.MontoCierreTransferencia !== null ? t.MontoCierreTransferencia : 0}">`
      : `<strong>${t.MontoCierreTransferencia !== null ? fmt(t.MontoCierreTransferencia) : '-'}</strong>`;

    const obsHtml = esAdmin
      ? `<input type="text" id="auditObservaciones" class="form-control" style="width: 100%; font-size: 0.8rem; height: 32px;" value="${t.Observaciones || ''}" placeholder="Agregar notas de auditoría">`
      : `<span style="font-size: 0.85rem; color: var(--text-muted);">${t.Observaciones || 'Sin observaciones'}</span>`;

    const footerHtml = esAdmin
      ? `
        <div style="border-top: 1px solid var(--border-dark); padding-top: 1rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
          <h4 style="font-size: 0.85rem; color: #818cf8; font-weight: 600; text-transform: uppercase; margin: 0;">Panel de Auditoría Administrador</h4>
          
          <div style="background: rgba(0,0,0,0.2); padding: 0.6rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); display: grid; grid-template-columns: 1.2fr 1fr 2fr; gap: 0.4rem; align-items: end;">
            <div>
              <label style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-bottom: 0.2rem;">TIPO MOV.</label>
              <select id="auditMovTipo" class="form-control" style="padding: 0.2rem; font-size: 0.75rem; height: 28px;">
                <option value="RETIRO">Retiro (-)</option>
                <option value="INGRESO">Ingreso (+)</option>
              </select>
            </div>
            <div>
              <label style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-bottom: 0.2rem;">MONTO ($)</label>
              <input type="number" id="auditMovMonto" class="form-control" placeholder="Monto" style="padding: 0.2rem; font-size: 0.75rem; height: 28px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-bottom: 0.2rem;">GLOSA / CONCEPTO</label>
              <input type="text" id="auditMovConcepto" class="form-control" placeholder="Glosa de corrección" style="padding: 0.2rem; font-size: 0.75rem; height: 28px;">
            </div>
          </div>

          <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem;">
            <button onclick="guardarAuditoriaTurno(${t.TurnoID})" class="btn btn-success btn-block" style="padding: 0.6rem;"><i class="fa-solid fa-floppy-disk"></i> Guardar Auditoría</button>
            <button onclick="cerrarDetalleModal()" class="btn btn-secondary">Cancelar</button>
          </div>
        </div>
      `
      : `
        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
          <button onclick="cerrarDetalleModal()" class="btn btn-secondary btn-block">Cerrar Detalle</button>
        </div>
      `;

    document.getElementById('modalBody').innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.85rem; background: rgba(0,0,0,0.15); padding: 0.75rem; border-radius: 8px;">
        <div>
          <p style="margin: 0.2rem 0;"><span style="color: var(--text-muted);">Cajero:</span> <strong>${t.Cajero}</strong></p>
          <p style="margin: 0.2rem 0;"><span style="color: var(--text-muted);">Caja:</span> <strong>${t.CajaName}</strong></p>
        </div>
        <div>
          <p style="margin: 0.2rem 0;"><span style="color: var(--text-muted);">Apertura:</span> <strong>${new Date(t.FechaApertura).toLocaleString()}</strong></p>
          <p style="margin: 0.2rem 0;"><span style="color: var(--text-muted);">Cierre:</span> <strong>${t.FechaCierre ? new Date(t.FechaCierre).toLocaleString() : 'En proceso'}</strong></p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 1.25rem; font-size: 0.85rem; margin-top: 0.5rem;">
        <div>
          <h4 style="font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border-dark); padding-bottom: 0.25rem; text-transform: uppercase; font-weight: 600;">Cuadraje de Efectivo</h4>
          <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">
            <div style="display: flex; justify-content: space-between;"><span>Fondo Inicial:</span> <strong>${fmt(t.MontoApertura)}</strong></div>
            <div style="display: flex; justify-content: space-between;"><span>Ventas Efectivo:</span> <strong>+${fmt(v.Efectivo)}</strong></div>
            <div style="display: flex; justify-content: space-between;"><span>Ingresos Manuales:</span> <strong>+${fmt(m.INGRESO)}</strong></div>
            <div style="display: flex; justify-content: space-between;"><span>Retiros/Gastos:</span> <strong>-${fmt(m.RETIRO)}</strong></div>
            <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-dark); padding-top: 0.4rem; font-weight: bold; margin-top: 0.25rem;">
              <span>Esperado:</span> <strong>${fmt(c.EfectivoEsperado)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: #fff; font-weight: bold; align-items: center;">
              <span>Real Contado:</span> ${realContadoHtml}
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.95rem; font-weight: bold; border-top: 1px dashed var(--border-dark); padding-top: 0.4rem; margin-top: 0.25rem;">
              <span>Diferencia:</span> ${diffEfectivoHtml}
            </div>
          </div>
        </div>
        <div>
          <h4 style="font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border-dark); padding-bottom: 0.25rem; text-transform: uppercase; font-weight: 600;">Otros Medios y Balance</h4>
          <div style="display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span>Tarjeta (Esp):</span> <strong>${fmt(c.TarjetaEsperada)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span>Tarjeta (Real):</span> ${realTarjetaHtml}
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>Dif. Tarjeta:</span> ${diffTarjetaHtml}
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-dark); padding-top: 0.25rem; margin-top: 0.25rem;">
              <span>Transf (Esp):</span> <strong>${fmt(c.TransferenciaEsperada)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span>Transf (Real):</span> ${realTransferenciaHtml}
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>Dif. Transf:</span> ${diffTransferenciaHtml}
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-dark); padding-top: 0.25rem; margin-top: 0.25rem;">
              <span>Vales Canjeados (Nota Crédito):</span> <strong>${fmt(v.Vale || 0)}</strong>
            </div>
          </div>
        </div>
      </div>

      <div style="border-top: 1px dashed var(--border-dark); padding-top: 0.5rem; display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: bold; color: #fff;">
        <span>Diferencia Neta Global:</span>
        <span>${diffNetaHtml}</span>
      </div>

      ${bitacoraHtml}

      <div style="margin-top: 0.25rem;">
        <h4 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.3rem; text-transform: uppercase; font-weight: 600;">Observaciones de Arqueo</h4>
        ${obsHtml}
      </div>

      ${footerHtml}
    `;

    const modalContainer = document.getElementById('detalleTurnoModal');
    modalContainer.style.display = 'flex';
    modalContainer.scrollTop = 0; // Reiniciar scroll al principio
  } catch (err) {
    alert('Error al obtener detalle del turno: ' + err.message);
  }
}

function cerrarDetalleModal() {
  document.getElementById('detalleTurnoModal').style.display = 'none';
}

async function guardarAuditoriaTurno(turnoId) {
  const efecReal = parseInt(document.getElementById('auditEfectivoReal').value) || 0;
  const tarjReal = parseInt(document.getElementById('auditTarjetaReal').value) || 0;
  const transReal = parseInt(document.getElementById('auditTransferenciaReal').value) || 0;
  const obs = document.getElementById('auditObservaciones').value.trim();
  
  const movMonto = parseInt(document.getElementById('auditMovMonto').value) || 0;
  const movTipo = document.getElementById('auditMovTipo').value;
  const movConcepto = document.getElementById('auditMovConcepto').value.trim();
  
  let nuevoMov = null;
  if (movMonto > 0 && movConcepto !== '') {
    nuevoMov = {
      tipo: movTipo,
      monto: movMonto,
      concepto: movConcepto
    };
  }

  try {
    const res = await fetch('api/auditar_turno.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({
        turno_id: turnoId,
        monto_cierre_efectivo: efecReal,
        monto_cierre_tarjeta: tarjReal,
        monto_cierre_transferencia: transReal,
        observaciones: obs,
        nuevo_movimiento: nuevoMov
      })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);
    
    alert(data.mensaje);
    cerrarDetalleModal();
    location.reload();
  } catch (err) {
    alert('Error al guardar auditoría: ' + err.message);
  }
}
</script>

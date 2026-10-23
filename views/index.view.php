<!-- Tarjetas de Estadísticas Rápidas -->
<div class="grid-stats" style="margin-bottom: 1.5rem;">
  <div class="stat-card">
    <div class="stat-info">
      <h3>Ventas del Día</h3>
      <div class="stat-value"><?= formatCLP($statsVentas['total_monto']) ?></div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <?= $statsVentas['total_ventas'] ?> transacciones completadas
      </p>
    </div>
    <div class="stat-icon">
      <i class="fa-solid fa-sack-dollar"></i>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Estado de Caja</h3>
      <div class="stat-value" style="font-size: 1.35rem;">
        <?php if ($turnoActivo): ?>
          <span style="color: var(--success);"><i class="fa-solid fa-circle-check"></i> Turno Abierto</span>
        <?php else: ?>
          <span style="color: var(--warning);"><i class="fa-solid fa-lock"></i> Turno Cerrado</span>
        <?php endif; ?>
      </div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <?= $turnoActivo ? 'Abierto a las ' . date('H:i', strtotime($turnoActivo['FechaApertura'])) : 'Abre caja para vender' ?>
      </p>
    </div>
    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
      <i class="fa-solid fa-cash-register"></i>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Alertas de Stock</h3>
      <div class="stat-value" style="color: <?= $lowStockCount > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
        <?= $lowStockCount ?>
      </div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <a href="alertas_stock.php" style="color: var(--danger); text-decoration: none;">Ver sugerencias &rarr;</a>
      </p>
    </div>
    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- SECCIÓN DE INTELIGENCIA COMERCIAL: CURVA SEMANAL & RENDIMIENTO MENSUAL    -->
<!-- ========================================================================= -->
<div style="display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">

  <!-- Gráfico Semanal -->
  <div class="table-card" style="padding: 1.35rem; display: flex; flex-direction: column;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
      <div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;">
            <i class="fa-solid fa-chart-area" style="color: #10b981;"></i> Ventas Semanales
          </h2>
          <span style="font-size: 0.75rem; background: rgba(51, 65, 85, 0.6); color: #cbd5e1; padding: 0.15rem 0.5rem; border-radius: 6px; font-weight: 500;">
            Lun <?= date('d/m', strtotime($lunesActual)) ?> - Dom <?= date('d/m', strtotime($domingoActual)) ?>
          </span>
        </div>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0.25rem 0 0 0;">
          Comparación diaria de la semana en curso frente a la semana pasada
        </p>
      </div>

      <!-- Métricas Rápidas de la Semana -->
      <div style="text-align: right; display: flex; align-items: center; gap: 0.75rem;">
        <div>
          <div style="font-size: 1.3rem; font-weight: 800; color: #10b981; line-height: 1.2;">
            <?= formatCLP($totalSemanaActual) ?>
          </div>
          <div style="font-size: 0.75rem; color: var(--text-muted);">Total esta semana</div>
        </div>
        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.78rem; font-weight: 700; padding: 0.3rem 0.55rem; border-radius: 8px; <?= $porcSemanal >= 0 ? 'background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);' : 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);' ?>">
          <i class="fa-solid <?= $porcSemanal >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>"></i>
          <?= ($porcSemanal >= 0 ? '+' : '') . $porcSemanal ?>%
        </span>
      </div>
    </div>

    <!-- Contenedor del Gráfico -->
    <div style="position: relative; height: 260px; width: 100%; flex: 1;">
      <canvas id="chartVentasSemanales"></canvas>
    </div>

    <!-- Barra Inferior de Información Semanal -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-dark); font-size: 0.8rem; color: var(--text-muted);">
      <div>
        <i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i> Semana Anterior: 
        <strong style="color: #cbd5e1;"><?= formatCLP($totalSemanaAnterior) ?></strong>
      </div>
      <div>
        <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i> Mejor Día: 
        <strong style="color: #f59e0b;"><?= $mejorDiaSemana ?></strong>
      </div>
    </div>
  </div>

  <!-- Panel de Comparativa Mensual & Proyección -->
  <div class="table-card" style="padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-scale-balanced" style="color: #6366f1;"></i> Comparativa Mes a Mes
        </h2>
        <span style="font-size: 0.75rem; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.25); color: #818cf8; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 600;">
          <?= $nombreMesActual ?> vs. <?= $nombreMesAnterior ?>
        </span>
      </div>

      <!-- Dos Columnas Lado a Lado: Mes Anterior vs Mes Actual -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1rem;">
        
        <!-- Columna Mes Anterior (Septiembre) -->
        <div style="background: rgba(15, 23, 42, 0.55); border: 1px solid var(--border-dark); border-radius: 10px; padding: 0.85rem;">
          <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; display: flex; align-items: center; gap: 0.35rem;">
            <i class="fa-solid fa-calendar-xmark" style="color: #64748b;"></i> <?= $nombreMesAnterior ?>
          </div>
          <div style="font-size: 1.25rem; font-weight: 800; color: #cbd5e1; margin-top: 0.3rem;">
            <?= $totalMesAntCompleto > 0 ? formatCLP($totalMesAntCompleto) : '$0' ?>
          </div>
          <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.25rem;">
            <?php if ($totalMesAntCompleto > 0): ?>
              Promedio: <?= formatCLP($promedioDiarioMesAnt) ?>/día
            <?php else: ?>
              <span style="font-style: italic;">Sin registros en BD</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Columna Mes Actual (Octubre) -->
        <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 10px; padding: 0.85rem;">
          <div style="font-size: 0.75rem; color: #818cf8; font-weight: 600; text-transform: uppercase; display: flex; align-items: center; gap: 0.35rem;">
            <i class="fa-solid fa-calendar-day"></i> <?= $nombreMesActual ?> (Hoy)
          </div>
          <div style="font-size: 1.25rem; font-weight: 800; color: #f8fafc; margin-top: 0.3rem;">
            <?= formatCLP($totalMesActual) ?>
          </div>
          <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 0.25rem;">
            <?= $cantVentasMesActual ?> ventas (Día <?= $diaActualDelMes ?>/<?= $totalDiasMesActual ?>)
          </div>
        </div>

      </div>

      <!-- Tarjeta de Proyección Ponderada por Patrón Comercial -->
      <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(51, 65, 85, 0.5); border-radius: 10px; padding: 0.9rem; margin-bottom: 0.85rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
          <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-wand-magic-sparkles" style="color: #a855f7;"></i> Proyección Cierre de Mes
          </span>
          <span style="font-size: 1.18rem; font-weight: 800; color: #a855f7;">
            <?= formatCLP($proyeccionCierrePonderada) ?>
          </span>
        </div>
        <p style="font-size: 0.74rem; color: var(--text-muted); margin: 0 0 0.65rem 0; line-height: 1.4;">
          Estimación basada en el comportamiento histórico reciente
        </p>

        <!-- Indicador de Esperado por Recaudar -->
        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(51, 65, 85, 0.4); border-radius: 8px; padding: 0.55rem 0.75rem; margin-bottom: 0.75rem;">
          <span style="font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 0.35rem;">
            <i class="fa-solid fa-hourglass-half" style="color: #38bdf8;"></i> Estimado por ingresar:
          </span>
          <strong style="font-size: 0.92rem; color: #38bdf8;">
            +<?= formatCLP($proyeccionRestante) ?>
          </strong>
        </div>

        <!-- Barra de Avance en el Mes -->
        <div style="background: rgba(51, 65, 85, 0.5); height: 7px; border-radius: 999px; overflow: hidden; margin-bottom: 0.35rem;">
          <div style="width: <?= $porcentajeMesTranscurrido ?>%; height: 100%; background: linear-gradient(90deg, #4f46e5, #a855f7); border-radius: 999px;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #64748b;">
          <span><?= $porcentajeMesTranscurrido ?>% del mes transcurrido</span>
          <span>Restan <?= $diasRestantesMes ?> días</span>
        </div>
      </div>
    </div>

    <!-- Balance Comparativo Inferior -->
    <div style="padding-top: 0.75rem; border-top: 1px solid var(--border-dark); font-size: 0.8rem;">
      <?php if ($totalMesAntCompleto > 0): ?>
        <?php $difVsAnt = $proyeccionCierrePonderada - $totalMesAntCompleto; ?>
        <?php $porcVsAnt = round(($difVsAnt / $totalMesAntCompleto) * 100, 1); ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="color: var(--text-muted);">Proyección vs. <?= $nombreMesAnterior ?>:</span>
          <strong style="color: <?= $difVsAnt >= 0 ? '#10b981' : '#f59e0b' ?>;">
            <?= ($difVsAnt >= 0 ? '+' : '') . formatCLP($difVsAnt) ?> (<?= ($porcVsAnt >= 0 ? '+' : '') . $porcVsAnt ?>%)
          </strong>
        </div>
      <?php else: ?>
        <div style="display: flex; align-items: center; gap: 0.4rem; color: #38bdf8; font-size: 0.76rem;">
          <i class="fa-solid fa-circle-info"></i>
          <span>Primer mes de registro en la base de datos local.</span>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Accesos Rápidos & Últimas Ventas -->
<div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem;">
  
  <!-- Accesos directos -->
  <div class="table-card" style="padding: 1.25rem;">
    <h2 style="font-size: 1.1rem; margin-bottom: 1rem; font-weight: 600;">Módulos del Sistema</h2>
    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
      <a href="pos.php" class="btn btn-primary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-cart-shopping" style="font-size: 1.1rem;"></i> Caja Registradora (POS)
      </a>
      <?php if ($esSupervisorNav): ?>
      <a href="compras.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-truck-ramp-box" style="font-size: 1.1rem;"></i> Recepción de Compras
      </a>
      <?php endif; ?>
      <a href="caja.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-vault" style="font-size: 1.1rem;"></i> Turnos y Arqueo de Caja
      </a>
      <?php if ($esSupervisorNav): ?>
      <a href="productos.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-boxes-stacked" style="font-size: 1.1rem;"></i> Catálogo de Productos
      </a>
      <?php endif; ?>
      <a href="clientes.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-users" style="font-size: 1.1rem;"></i> Clientes y Puntos
      </a>
      <a href="alertas_stock.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i> Sugerencias de Reabastecimiento
      </a>
      <a href="ventas.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-receipt" style="font-size: 1.1rem;"></i> Historial de Ventas
      </a>
    </div>
  </div>

  <!-- Últimas Ventas -->
  <div class="table-card">
    <div class="table-header">
      <h2 style="font-size: 1.1rem; font-weight: 600;">Últimas Ventas Registradas</h2>
      <a href="ventas.php" style="color: #818cf8; text-decoration: none; font-size: 0.85rem;">Ver todas &rarr;</a>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>ID Venta</th>
          <th>Fecha / Hora</th>
          <th>Documento</th>
          <th>Total</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ultimasVentas)): ?>
          <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay ventas registradas aún.</td></tr>
        <?php else: ?>
          <?php foreach ($ultimasVentas as $v): ?>
            <tr>
              <td>#<?= $v['VentaID'] ?></td>
              <td><?= date('d/m/Y H:i', strtotime($v['FechaVenta'])) ?></td>
              <td><?= htmlspecialchars($v['TipoDocumento']) ?></td>
              <td style="font-weight: 700; color: var(--success);"><?= formatCLP($v['MontoTotal']) ?></td>
              <td>
                <span class="badge <?= $v['Estado'] == 'Completada' ? 'badge-success' : 'badge-danger' ?>">
                  <?= $v['Estado'] ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Script de Chart.js y Configuración del Gráfico Semanal -->
<script src="assets/vendor/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const canvas = document.getElementById('chartVentasSemanales');
  if (!canvas || typeof Chart === 'undefined') return;

  const ctx = canvas.getContext('2d');

  // Gradiente suave para el área de la semana en curso
  const gradientActual = ctx.createLinearGradient(0, 0, 0, 260);
  gradientActual.addColorStop(0, 'rgba(16, 185, 129, 0.32)');
  gradientActual.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($diasNombres) ?>,
      datasets: [
        {
          label: 'Esta Semana',
          data: <?= json_encode($serieActual) ?>,
          borderColor: '#10b981',
          backgroundColor: gradientActual,
          borderWidth: 2.8,
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#10b981',
          pointBorderColor: '#0f172a',
          pointBorderWidth: 2,
          pointRadius: 4.5,
          pointHoverRadius: 7,
          spanGaps: false
        },
        {
          label: 'Semana Anterior',
          data: <?= json_encode($serieAnterior) ?>,
          borderColor: '#64748b',
          borderDash: [5, 4],
          backgroundColor: 'transparent',
          borderWidth: 1.8,
          fill: false,
          tension: 0.35,
          pointBackgroundColor: '#64748b',
          pointBorderColor: '#0f172a',
          pointBorderWidth: 1.5,
          pointRadius: 3.5,
          pointHoverRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 10,
            boxHeight: 10,
            color: '#94a3b8',
            font: { size: 11, family: 'Inter, sans-serif', weight: '600' },
            usePointStyle: true,
            padding: 12
          }
        },
        tooltip: {
          backgroundColor: 'rgba(15, 23, 42, 0.94)',
          titleColor: '#f8fafc',
          bodyColor: '#cbd5e1',
          borderColor: '#334155',
          borderWidth: 1,
          padding: 10,
          boxPadding: 4,
          usePointStyle: true,
          titleFont: { size: 12, weight: '700' },
          bodyFont: { size: 12 },
          callbacks: {
            title: function (items) {
              const diasCompletos = <?= json_encode($diasNombresCompletos) ?>;
              const idx = items[0].dataIndex;
              return diasCompletos[idx] || items[0].label;
            },
            label: function (context) {
              const val = context.parsed.y !== null && context.parsed.y !== undefined ? context.parsed.y : 0;
              return ` ${context.dataset.label}: $${val.toLocaleString('es-CL')}`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            color: 'rgba(51, 65, 85, 0.3)',
            drawBorder: false
          },
          ticks: {
            color: '#94a3b8',
            font: { family: 'Inter', size: 11, weight: '500' }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(51, 65, 85, 0.3)',
            drawBorder: false
          },
          ticks: {
            color: '#94a3b8',
            font: { family: 'Inter', size: 11 },
            callback: function (value) {
              if (value >= 1000000) return '$' + (value / 1000000).toFixed(1) + 'M';
              if (value >= 1000) return '$' + (value / 1000).toFixed(0) + 'k';
              return '$' + value;
            }
          }
        }
      }
    }
  });
});
</script>

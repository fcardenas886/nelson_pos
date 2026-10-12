<div class="report-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
      <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i> Centro de Reportes y Finanzas
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.25rem 0 0 0;">
      Inteligencia de negocios, auditoría de ventas, rotación de mercadería y control de márgenes.
    </p>
  </div>

  <div class="report-actions" style="display: flex; gap: 0.6rem; align-items: center;">
    <!-- Botón Exportar a Excel -->
    <a href="reportes.php?tab=<?= urlencode($tab) ?>&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?><?= !empty($catId) ? '&categoria_id=' . (int)$catId : '' ?><?= !empty($filtroStock) && $filtroStock !== 'todos' ? '&filtro_stock=' . urlencode($filtroStock) : '' ?><?= !empty($buscar) ? '&q=' . urlencode($buscar) : '' ?>&export=csv" 
       class="btn btn-secondary" style="padding: 0.6rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.45rem; border-color: rgba(34, 197, 94, 0.3); color: #22c55e;"
       title="Descargar este reporte formateado para Microsoft Excel">
      <i class="fa-solid fa-file-excel"></i> Exportar a Excel (CSV)
    </a>

    <!-- Botón Imprimir -->
    <button type="button" onclick="window.print()" class="btn btn-secondary" style="padding: 0.6rem 1rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.45rem;">
      <i class="fa-solid fa-print"></i> Imprimir
    </button>
  </div>
</div>

<!-- Barra de Pestañas Principales -->
<div class="report-tabs-bar" style="display: flex; gap: 0.4rem; overflow-x: auto; border-bottom: 1px solid var(--border-dark); margin-bottom: 1.5rem; padding-bottom: 0.5rem;">
  <a href="reportes.php?tab=ventas&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'ventas' ? 'active' : '' ?>">
    <i class="fa-solid fa-cash-register"></i> Ventas y Medios
  </a>
  <a href="reportes.php?tab=productos&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'productos' ? 'active' : '' ?>">
    <i class="fa-solid fa-cubes-stacked"></i> Ranking y Rotación
  </a>
  <a href="reportes.php?tab=inventario" 
     class="report-tab-btn <?= $tab === 'inventario' ? 'active' : '' ?>">
    <i class="fa-solid fa-warehouse"></i> Valorización Inventario
  </a>
  <a href="reportes.php?tab=creditos&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'creditos' ? 'active' : '' ?>">
    <i class="fa-solid fa-handshake"></i> Cartera y Fiados
  </a>
  <a href="reportes.php?tab=compras&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'compras' ? 'active' : '' ?>">
    <i class="fa-solid fa-truck-ramp-box"></i> Compras y Proveedores
  </a>
  <a href="reportes.php?tab=cajeros&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'cajeros' ? 'active' : '' ?>">
    <i class="fa-solid fa-users-gear"></i> Rendimiento Cajeros
  </a>
  <a href="reportes.php?tab=utilidades&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'utilidades' ? 'active' : '' ?>">
    <i class="fa-solid fa-sack-dollar"></i> Utilidades y Márgenes
  </a>
  <a href="reportes.php?tab=mermas&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" 
     class="report-tab-btn <?= $tab === 'mermas' ? 'active' : '' ?>">
    <i class="fa-solid fa-trash-can"></i> Mermas y Pérdidas
  </a>
</div>

<!-- Filtros de Período (Visible en pestañas con rango de tiempo) -->
<?php if ($tab !== 'inventario'): ?>
<div class="report-filters-card" style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
  
  <!-- Presets Rápidos -->
  <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
    <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 0.4rem;">
      <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i> Período:
    </span>
    <a href="reportes.php?tab=<?= $tab ?>&preset=hoy" class="report-preset-chip <?= $preset === 'hoy' || ($fechaInicio === $hoy && $fechaFin === $hoy && empty($_GET['inicio'])) ? 'active' : '' ?>">Hoy</a>
    <a href="reportes.php?tab=<?= $tab ?>&preset=ayer" class="report-preset-chip <?= $preset === 'ayer' ? 'active' : '' ?>">Ayer</a>
    <a href="reportes.php?tab=<?= $tab ?>&preset=semana" class="report-preset-chip <?= $preset === 'semana' ? 'active' : '' ?>">Últimos 7 Días</a>
    <a href="reportes.php?tab=<?= $tab ?>&preset=mes" class="report-preset-chip <?= $preset === 'mes' || (empty($preset) && empty($_GET['inicio']) && $fechaInicio === date('Y-m-01')) ? 'active' : '' ?>">Este Mes</a>
    <a href="reportes.php?tab=<?= $tab ?>&preset=mes_anterior" class="report-preset-chip <?= $preset === 'mes_anterior' ? 'active' : '' ?>">Mes Anterior</a>
    <a href="reportes.php?tab=<?= $tab ?>&preset=ano" class="report-preset-chip <?= $preset === 'ano' ? 'active' : '' ?>">Año <?= date('Y') ?></a>
  </div>

  <!-- Formulario de Rango Libre -->
  <form method="GET" action="reportes.php" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <?php if (!empty($catId)): ?>
      <input type="hidden" name="categoria_id" value="<?= (int)$catId ?>">
    <?php endif; ?>

    <div style="display: flex; align-items: center; gap: 0.4rem;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Desde</label>
      <input type="date" name="inicio" value="<?= htmlspecialchars($fechaInicio) ?>" class="form-control" style="padding: 0.35rem 0.6rem; font-size: 0.85rem; width: auto;" required>
    </div>
    <div style="display: flex; align-items: center; gap: 0.4rem;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Hasta</label>
      <input type="date" name="fin" value="<?= htmlspecialchars($fechaFin) ?>" class="form-control" style="padding: 0.35rem 0.6rem; font-size: 0.85rem; width: auto;" required>
    </div>
    <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.85rem;">
      <i class="fa-solid fa-filter"></i> Aplicar
    </button>
  </form>

</div>
<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 1: VENTAS Y MEDIOS DE PAGO -->
<!-- ============================================================== -->
<?php if ($tab === 'ventas'): ?>

  <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
    <a href="ventas.php?desde=<?= urlencode($fechaInicio) ?>&hasta=<?= urlencode($fechaFin) ?>" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem;">
      <i class="fa-solid fa-receipt" style="color: var(--primary);"></i> Ver Historial, Detalle y Reimpresión de Boletas
    </a>
  </div>

  <!-- Resumen Ejecutivo en Tarjetas -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Ventas Brutas</h3>
        <div class="stat-value" style="color: var(--success);"><?= formatCLP($kpiVentas['TotalVentas']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Ingresos totales en el período</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
        <i class="fa-solid fa-money-bill-wave"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Venta Neta / IVA (19%)</h3>
        <div class="stat-value" style="font-size: 1.35rem;"><?= formatCLP($kpiVentas['TotalNeto']) ?></div>
        <small style="color: #fbbf24; font-size: 0.75rem;">IVA Fiscal: <?= formatCLP($kpiVentas['TotalIva']) ?></small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
        <i class="fa-solid fa-calculator"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Transacciones / Boletas</h3>
        <div class="stat-value"><?= number_format($kpiVentas['TotalTransacciones'], 0, ',', '.') ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Ventas completadas con éxito</small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Ticket Promedio</h3>
        <div class="stat-value" style="color: #38bdf8;"><?= formatCLP($ticketPromedio) ?></div>
        <small style="color: var(--danger); font-size: 0.75rem;">Dctos: -<?= formatCLP($kpiVentas['TotalDescuentos']) ?></small>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
        <i class="fa-solid fa-chart-pie"></i>
      </div>
    </div>
  </div>

  <!-- Cuadrícula de 2 Columnas: Medios de Pago y Horas Peak -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <!-- Tarjeta: Medios de Pago -->
    <div class="table-card">
      <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.45rem;">
          <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> Distribución por Medio de Pago
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-muted);">Total: <?= formatCLP($kpiVentas['TotalVentas']) ?></span>
      </div>

      <table class="table">
        <thead>
          <tr>
            <th>Medio de Pago</th>
            <th style="text-align: center;">Operaciones</th>
            <th style="text-align: right;">Total Recaudado</th>
            <th style="text-align: right;">Participación</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pagosMetodos)): ?>
            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Sin pagos registrados en este período.</td></tr>
          <?php else: ?>
            <?php foreach ($pagosMetodos as $p): ?>
              <?php 
                $pct = $kpiVentas['TotalVentas'] > 0 ? round(($p['TotalRecaudado'] / $kpiVentas['TotalVentas']) * 100, 1) : 0;
                $icon = 'fa-money-bill';
                $iconColor = '#10b981';
                if (stripos($p['MetodoPago'], 'debito') !== false) { $icon = 'fa-credit-card'; $iconColor = '#3b82f6'; }
                elseif (stripos($p['MetodoPago'], 'credito') !== false) { $icon = 'fa-credit-card'; $iconColor = '#8b5cf6'; }
                elseif (stripos($p['MetodoPago'], 'transfer') !== false) { $icon = 'fa-building-columns'; $iconColor = '#06b6d4'; }
                elseif (stripos($p['MetodoPago'], 'interno') !== false || stripos($p['MetodoPago'], 'fiado') !== false) { $icon = 'fa-handshake'; $iconColor = '#f59e0b'; }
                elseif (stripos($p['MetodoPago'], 'vale') !== false) { $icon = 'fa-ticket'; $iconColor = '#ec4899'; }
              ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid <?= $icon ?>" style="color: <?= $iconColor ?>; font-size: 1rem; width: 18px; text-align: center;"></i>
                    <strong style="color: var(--text-main);"><?= htmlspecialchars(str_replace('_', ' ', $p['MetodoPago'])) ?></strong>
                  </div>
                </td>
                <td style="text-align: center; font-weight: 600; color: var(--text-muted);">
                  <?= number_format($p['Transacciones'], 0, ',', '.') ?>
                </td>
                <td style="text-align: right; font-weight: 700; color: var(--text-main); font-family: monospace;">
                  <?= formatCLP($p['TotalRecaudado']) ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.4rem;">
                    <div style="width: 50px; background: rgba(255,255,255,0.08); height: 6px; border-radius: 3px; overflow: hidden;">
                      <div style="width: <?= min(100, $pct) ?>%; background: <?= $iconColor ?>; height: 100%;"></div>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); min-width: 40px; text-align: right;"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Tarjeta: Horas Peak y Comprobantes -->
    <div class="table-card">
      <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.45rem;">
          <i class="fa-solid fa-clock" style="color: #fbbf24;"></i> Horas Peak de Mayor Venta
        </h2>
        <span style="font-size: 0.75rem; color: var(--text-muted);">Distribución 24h</span>
      </div>

      <div style="padding: 1.25rem;">
        <?php if (empty($ventasPorHora)): ?>
          <p style="text-align: center; color: var(--text-muted); margin: 2rem 0;">No hay datos horarios para este período.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php 
              // Mostrar las horas con ventas ordenadas por hora
              foreach ($ventasPorHora as $h => $vh): 
                $porcBar = $maxMontoHora > 0 ? round(($vh['MontoTotal'] / $maxMontoHora) * 100) : 0;
                $horaLabel = sprintf("%02d:00 - %02d:59", $h, $h);
            ?>
              <div style="display: grid; grid-template-columns: 110px 1fr 100px; align-items: center; gap: 0.75rem; font-size: 0.82rem;">
                <span style="color: var(--text-muted); font-family: monospace; font-weight: 600;"><?= $horaLabel ?></span>
                <div style="background: rgba(255,255,255,0.06); height: 18px; border-radius: 6px; overflow: hidden; position: relative;">
                  <div style="background: linear-gradient(90deg, var(--primary), #818cf8); width: <?= max(4, $porcBar) ?>%; height: 100%; border-radius: 6px;"></div>
                  <span style="position: absolute; left: 8px; top: 1px; font-size: 0.72rem; color: #fff; font-weight: 700;">
                    <?= $vh['TotalVentas'] ?> venta<?= $vh['TotalVentas'] > 1 ? 's' : '' ?>
                  </span>
                </div>
                <span style="text-align: right; font-weight: 700; color: var(--text-main); font-family: monospace;">
                  <?= formatCLP($vh['MontoTotal']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Desglose por Comprobante -->
        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-dark);">
          <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem;">
            Por Tipo de Comprobante:
          </div>
          <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <?php foreach ($docsDesglose as $doc): ?>
              <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.5rem 0.85rem; flex: 1; min-width: 110px;">
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($doc['TipoDocumento']) ?></div>
                <div style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-top: 0.15rem;"><?= formatCLP($doc['TotalMonto']) ?></div>
                <small style="font-size: 0.72rem; color: var(--text-muted);"><?= $doc['Cantidad'] ?> emitidas</small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Tabla: Detalle de Ventas en el Período -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Últimas Ventas en el Período Seleccionado
      </h2>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Mostrando hasta 100 registros</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>N° Venta</th>
          <th>Fecha y Hora</th>
          <th>Documento</th>
          <th>Cajero</th>
          <th>Cliente</th>
          <th style="text-align: right;">Total</th>
          <th style="text-align: center;">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ventasList)): ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay ventas registradas en el período.</td></tr>
        <?php else: ?>
          <?php foreach ($ventasList as $v): ?>
            <tr>
              <td><strong style="color: #818cf8;">#<?= $v['VentaID'] ?></strong></td>
              <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y H:i', strtotime($v['FechaVenta'])) ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($v['TipoDocumento']) ?></span></td>
              <td style="font-weight: 600;"><?= htmlspecialchars($v['Cajero']) ?></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($v['Cliente'] ?: 'Cliente Ocasional') ?></td>
              <td style="text-align: right; font-weight: 700; color: var(--success); font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($v['MontoTotal']) ?>
              </td>
              <td style="text-align: center;">
                <a href="ventas.php?fecha=<?= substr($v['FechaVenta'], 0, 10) ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Ver en módulo ventas">
                  <i class="fa-solid fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 2: RANKING Y ROTACIÓN DE PRODUCTOS -->
<!-- ============================================================== -->
<?php if ($tab === 'productos'): ?>

  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
    <div>
      <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
        <i class="fa-solid fa-cubes-stacked" style="color: var(--primary);"></i> Desempeño y Rotación de Mercadería
      </h2>
      <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
        Conoce cuáles son tus artículos estrella y cuáles no se han movido para liberar capital.
      </p>
    </div>

    <!-- Filtro por Categoría -->
    <form method="GET" action="reportes.php" style="display: flex; align-items: center; gap: 0.5rem;">
      <input type="hidden" name="tab" value="productos">
      <input type="hidden" name="inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
      <input type="hidden" name="fin" value="<?= htmlspecialchars($fechaFin) ?>">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Filtrar Categoría:</label>
      <select name="categoria_id" class="form-control" onchange="this.form.submit()" style="padding: 0.35rem 0.65rem; font-size: 0.85rem; width: auto;">
        <option value="0">Todas las Categorías</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['CategoriaID'] ?>" <?= $catId == $c['CategoriaID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['Nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <!-- Tarjeta 1: Top 30 Productos Más Vendidos -->
  <div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        🏆 Top Productos Más Vendidos (Líderes en Unidades y Recaudación)
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($rankingProductos) ?> productos listados</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th style="width: 50px; text-align: center;">#</th>
          <th>Producto</th>
          <th>Categoría</th>
          <th style="text-align: center;">Unidades Vendidas</th>
          <th style="text-align: right;">Total Venta ($)</th>
          <th style="text-align: right;">Utilidad Aportada</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rankingProductos)): ?>
          <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay ventas registradas para este filtro y período.</td></tr>
        <?php else: ?>
          <?php $i = 1; foreach ($rankingProductos as $rp): ?>
            <?php 
              $rankBadge = 'background: rgba(255,255,255,0.06); color: var(--text-muted);';
              if ($i === 1) $rankBadge = 'background: #f59e0b; color: #000; font-weight: 800;';
              elseif ($i === 2) $rankBadge = 'background: #94a3b8; color: #000; font-weight: 800;';
              elseif ($i === 3) $rankBadge = 'background: #b45309; color: #fff; font-weight: 800;';
            ?>
            <tr>
              <td style="text-align: center;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; font-size: 0.8rem; <?= $rankBadge ?>">
                  <?= $i++ ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 700; color: #fff;"><?= htmlspecialchars($rp['Nombre']) ?></div>
                <small style="color: var(--text-muted); font-family: monospace; font-size: 0.75rem;">
                  <?= $rp['CodigoBarras'] ? '#' . $rp['CodigoBarras'] : 'Sin código' ?>
                </small>
              </td>
              <td><?= htmlspecialchars($rp['Categoria'] ?: 'General') ?></td>
              <td style="text-align: center;">
                <strong style="color: #38bdf8; font-size: 0.95rem;"><?= number_format($rp['UnidadesVendidas'], 0, ',', '.') ?></strong>
                <span style="font-size: 0.75rem; color: var(--text-muted);">u</span>
              </td>
              <td style="text-align: right; font-weight: 700; font-family: monospace; color: var(--text-main);">
                <?= formatCLP($rp['MontoTotalVentas']) ?>
              </td>
              <td style="text-align: right; font-weight: 700; font-family: monospace; color: var(--success);">
                <?= formatCLP($rp['UtilidadTotal']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tarjeta 2: Productos Sin Rotación (Huesos / Capital Estancado) -->
  <div class="table-card" style="border-color: rgba(239, 68, 68, 0.3);">
    <div class="table-header" style="background: rgba(239, 68, 68, 0.08); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
      <div>
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #ef4444; display: flex; align-items: center; gap: 0.45rem;">
          <i class="fa-solid fa-triangle-exclamation"></i> Productos Sin Rotación (Stock Estancado / "Huesos")
        </h3>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
          Productos con stock físico disponible pero que <strong>NO tuvieron ventas</strong> en el período seleccionado.
        </p>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Capital Inmovilizado Estimado</span>
        <strong style="font-size: 1.1rem; color: #ef4444; font-family: monospace;"><?= formatCLP($totalCapitalEstancado) ?></strong>
      </div>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Categoría</th>
          <th style="text-align: center;">Stock Parado</th>
          <th style="text-align: right;">Costo Compra</th>
          <th style="text-align: right;">Precio Venta</th>
          <th style="text-align: right;">Capital Inmovilizado</th>
          <th style="text-align: center;">Sugerencia</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($productosSinRotacion)): ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--success); padding: 2rem;">
              <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; display: block; margin-bottom: 0.4rem;"></i>
              ¡Excelente! Toda tu mercadería activa ha registrado ventas en este período.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($productosSinRotacion as $h): ?>
            <tr>
              <td>
                <strong style="color: var(--text-main);"><?= htmlspecialchars($h['Nombre']) ?></strong>
                <small style="display: block; color: var(--text-muted); font-family: monospace; font-size: 0.72rem;">
                  <?= $h['CodigoBarras'] ?: 'Sin código' ?>
                </small>
              </td>
              <td><?= htmlspecialchars($h['Categoria'] ?: 'General') ?></td>
              <td style="text-align: center;">
                <span class="badge badge-warning"><?= (float)$h['Stock'] ?> u</span>
              </td>
              <td style="text-align: right; color: var(--text-muted); font-family: monospace;"><?= formatCLP($h['CostoCompra']) ?></td>
              <td style="text-align: right; color: var(--text-main); font-family: monospace;"><?= formatCLP($h['PrecioVenta']) ?></td>
              <td style="text-align: right; font-weight: 700; color: #ef4444; font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($h['CapitalInmovilizado']) ?>
              </td>
              <td style="text-align: center;">
                <a href="promociones.php" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.72rem;" title="Crear oferta o pack con descuento">
                  <i class="fa-solid fa-tag"></i> Armar Promo
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 3: VALORIZACIÓN DE INVENTARIO -->
<!-- ============================================================== -->
<?php if ($tab === 'inventario'): ?>

  <div style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
      <i class="fa-solid fa-warehouse" style="color: var(--primary);"></i> Valorización del Inventario Físico en Bodega
    </h2>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Cálculo del capital total invertido a costo de adquisición y el retorno proyectado a precio de venta al público.
    </p>
  </div>

  <!-- KPIs Globales de Inventario -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Capital en Bodega (Costo)</h3>
        <div class="stat-value" style="color: #fbbf24;"><?= formatCLP($kpiInventario['ValorTotalCosto']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Total invertido en mercadería</small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
        <i class="fa-solid fa-boxes-packing"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Valor Total a Venta</h3>
        <div class="stat-value" style="color: var(--success);"><?= formatCLP($kpiInventario['ValorTotalVenta']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Retorno estimado al vender todo</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
        <i class="fa-solid fa-shop"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Margen Potencial Global</h3>
        <div class="stat-value" style="color: #818cf8;"><?= formatCLP($margenPotencialTotal) ?></div>
        <small style="color: #818cf8; font-size: 0.75rem;">Rentabilidad s/costo: <strong><?= $kpiInventario['ValorTotalCosto'] > 0 ? '+' . $margenPotencialPorc . '%' : 'S/C' ?></strong></small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
        <i class="fa-solid fa-percent"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Unidades Físicas en Stock</h3>
        <div class="stat-value"><?= number_format($kpiInventario['TotalUnidadesFisicas'], 0, ',', '.') ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $kpiInventario['TotalProductos'] ?> artículos activos</small>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
        <i class="fa-solid fa-barcode"></i>
      </div>
    </div>
  </div>

  <!-- Alerta de Salud del Inventario -->
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <i class="fa-solid fa-shield-heart" style="font-size: 1.5rem; color: var(--primary);"></i>
      <div>
        <strong style="color: var(--text-main); font-size: 0.95rem;">Estado de Disponibilidad de Catálogo</strong>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">Monitoreo preventivo de quiebres de stock</p>
      </div>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <span class="badge badge-success" style="padding: 0.4rem 0.75rem; font-size: 0.82rem;">
        <i class="fa-solid fa-check-circle"></i> <?= $kpiInventario['ProductosConStock'] ?> Con Stock OK
      </span>
      <span class="badge badge-warning" style="padding: 0.4rem 0.75rem; font-size: 0.82rem;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= $kpiInventario['ProductosBajoStock'] ?> Bajo Mínimo
      </span>
      <span class="badge badge-danger" style="padding: 0.4rem 0.75rem; font-size: 0.82rem;">
        <i class="fa-solid fa-circle-xmark"></i> <?= $kpiInventario['ProductosAgotados'] ?> Agotados
      </span>
      <?php if (!empty($kpiInventario['ProductosSinCosto'])): ?>
      <button type="button" onclick="setFiltroStock('sin_costo')" class="badge badge-warning" style="padding: 0.4rem 0.75rem; font-size: 0.82rem; border: none; cursor: pointer; background: rgba(245, 158, 11, 0.2); color: #fbbf24;" title="Ver productos que no tienen costo cargado">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= $kpiInventario['ProductosSinCosto'] ?> Sin Costo Registrado
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabla 1: Desglose y Valorización por Categoría -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-folder-tree" style="color: var(--primary);"></i> Desglose y Capital por Categoría
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($categoriasInventario) ?> categorías</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Categoría</th>
          <th style="text-align: center;">N° Artículos</th>
          <th style="text-align: center;">Stock Físico</th>
          <th style="text-align: right;">Valor Costo ($)</th>
          <th style="text-align: right;">Valor Venta ($)</th>
          <th style="text-align: right;">Margen Estimado ($)</th>
          <th style="text-align: right;">Margen % s/Costo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categoriasInventario as $ci): ?>
          <?php 
            $margenCat = $ci['ValorVenta'] - $ci['ValorCosto'];
            $pctCat = $ci['ValorCosto'] > 0 ? round(($margenCat / $ci['ValorCosto']) * 100, 1) : null;
          ?>
          <tr>
            <td>
              <a href="javascript:void(0)" onclick="filtrarPorCategoria(<?= (int)$ci['CategoriaID'] ?>)" style="color: var(--text-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;" title="Filtrar listado de productos por esta categoría">
                <i class="fa-solid fa-filter" style="font-size: 0.75rem; color: var(--primary);"></i>
                <?= htmlspecialchars($ci['Categoria']) ?>
              </a>
            </td>
            <td style="text-align: center;"><?= $ci['TotalItems'] ?></td>
            <td style="text-align: center; font-weight: 600;"><?= number_format($ci['StockTotal'], 0, ',', '.') ?> u</td>
            <td style="text-align: right; color: #fbbf24; font-family: monospace; font-weight: 600;"><?= formatCLP($ci['ValorCosto']) ?></td>
            <td style="text-align: right; color: var(--text-main); font-family: monospace; font-weight: 600;"><?= formatCLP($ci['ValorVenta']) ?></td>
            <td style="text-align: right; color: var(--success); font-family: monospace; font-weight: 700;"><?= formatCLP($margenCat) ?></td>
            <td style="text-align: right;">
              <?php if ($pctCat === null): ?>
                <span class="badge badge-warning" title="Categoría sin costo cargado">S/C</span>
              <?php elseif ($pctCat > 0): ?>
                <span class="badge badge-success">+<?= $pctCat ?>%</span>
              <?php elseif ($pctCat < 0): ?>
                <span class="badge badge-danger"><?= $pctCat ?>%</span>
              <?php else: ?>
                <span class="badge badge-secondary">0%</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ============================================================== -->
  <!-- TABLA 2: LISTADO DETALLADO DE PRODUCTOS Y VALORIZACIÓN CON COSTOS -->
  <!-- ============================================================== -->
  <div class="table-card" style="margin-top: 2rem;" id="seccionProductosInventario">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-boxes-stacked" style="color: var(--primary);"></i> Detalle Valorizado por Producto y Costos
        </h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
          Listado individual de artículos con stock físico, costo unitario de compra, precio de venta y capital total inmovilizado.
        </p>
      </div>

      <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <?php if (!empty($kpiInventario['ProductosSinCosto']) && $kpiInventario['ProductosSinCosto'] > 0): ?>
          <a href="actualizar_precios.php?filtro=sin_costo" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; color: #fbbf24; border-color: rgba(245, 158, 11, 0.5); background: rgba(245, 158, 11, 0.1); font-weight: 700;" title="Abrir actualizador rápido con todos los productos sin costo cargados">
            <i class="fa-solid fa-triangle-exclamation"></i> Resolver <?= $kpiInventario['ProductosSinCosto'] ?> Sin Costo
          </a>
        <?php endif; ?>
        <a href="actualizar_precios.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">
          <i class="fa-solid fa-bolt"></i> Actualizador Rápido Precios/Costos
        </a>
      </div>
    </div>

    <!-- Barra de Filtros y Búsqueda en Vivo -->
    <div style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-dark); padding: 0.85rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.85rem;">
      
      <!-- Buscador y Selector de Categoría -->
      <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
        <div style="position: relative;">
          <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);"></i>
          <input type="text" id="buscadorProdInv" class="form-control" placeholder="Buscar por nombre o código..." 
                 value="<?= htmlspecialchars($buscar) ?>"
                 style="padding-left: 2rem; font-size: 0.85rem; width: 250px;" 
                 oninput="filtrarProductosInventario()">
        </div>

        <select id="filtroCatInv" class="form-control" style="font-size: 0.85rem; width: auto; padding: 0.4rem 0.75rem;" onchange="filtrarProductosInventario()">
          <option value="0">Todas las Categorías</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['CategoriaID'] ?>" <?= $catId == $cat['CategoriaID'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['Nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Chips de Filtro Rápido de Estado -->
      <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;" id="chipsEstadoFiltro">
        <button type="button" class="btn btn-secondary chip-inv-btn active" data-filtro="todos" onclick="setFiltroStock('todos', this)" style="padding: 0.3rem 0.65rem; font-size: 0.78rem;">
          Todos (<?= $kpiInventario['TotalProductos'] ?>)
        </button>
        <button type="button" class="btn btn-secondary chip-inv-btn" data-filtro="con_stock" onclick="setFiltroStock('con_stock', this)" style="padding: 0.3rem 0.65rem; font-size: 0.78rem;">
          <i class="fa-solid fa-check" style="color: var(--success);"></i> Con Stock (<?= $kpiInventario['ProductosConStock'] ?>)
        </button>
        <button type="button" class="btn btn-secondary chip-inv-btn" data-filtro="sin_costo" onclick="setFiltroStock('sin_costo', this)" style="padding: 0.3rem 0.65rem; font-size: 0.78rem; border-color: rgba(245, 158, 11, 0.4); color: #fbbf24;">
          <i class="fa-solid fa-triangle-exclamation"></i> Sin Costo $0 (<?= $kpiInventario['ProductosSinCosto'] ?>)
        </button>
        <?php if ($kpiInventario['ProductosAgotados'] > 0): ?>
        <button type="button" class="btn btn-secondary chip-inv-btn" data-filtro="agotados" onclick="setFiltroStock('agotados', this)" style="padding: 0.3rem 0.65rem; font-size: 0.78rem; color: var(--danger);">
          Agotados (<?= $kpiInventario['ProductosAgotados'] ?>)
        </button>
        <?php endif; ?>
      </div>

    </div>

    <!-- Indicador / Contador en Vivo -->
    <div style="padding: 0.6rem 1.25rem; background: rgba(0,0,0,0.15); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; font-size: 0.82rem; color: var(--text-muted); border-bottom: 1px solid var(--border-dark);">
      <div>
        <span id="contadorItemsInv" style="font-weight: 700; color: var(--text-main);"><?= count($productosInventario) ?></span> productos visibles
      </div>
      <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
        <span>Stock: <strong id="sumStockInv" style="color: var(--text-main); font-family: monospace;">0</strong> u</span>
        <span>Costo Total: <strong id="sumCostoInv" style="color: #fbbf24; font-family: monospace;">$0</strong></span>
        <span>Venta Total: <strong id="sumVentaInv" style="color: var(--success); font-family: monospace;">$0</strong></span>
        <span>Margen: <strong id="sumMargenInv" style="color: #818cf8; font-family: monospace;">$0</strong></span>
      </div>
    </div>

    <!-- Tabla de Productos -->
    <div style="overflow-x: auto;">
      <table class="table" id="tablaDetalleInventario">
        <thead>
          <tr>
            <th style="width: 45px; text-align: center;">#</th>
            <th style="min-width: 125px;">Código</th>
            <th style="min-width: 200px;">Producto</th>
            <th>Categoría</th>
            <th style="text-align: center;">Stock</th>
            <th style="text-align: right;">Costo Unit.</th>
            <th style="text-align: right;">Precio Venta</th>
            <th style="text-align: right; color: #fbbf24;">Total Costo ($)</th>
            <th style="text-align: right; color: var(--success);">Total Venta ($)</th>
            <th style="text-align: right; color: #818cf8;">Margen Est.</th>
            <th style="text-align: right;">Margen % s/Costo</th>
            <th style="text-align: center; width: 75px;">Acción</th>
          </tr>
        </thead>
        <tbody id="tbodyProdsInv">
          <?php if (empty($productosInventario)): ?>
            <tr id="filaSinProds"><td colspan="12" style="text-align: center; color: var(--text-muted); padding: 2rem;">No se encontraron productos para los filtros seleccionados.</td></tr>
          <?php else: ?>
            <?php $num = 1; foreach ($productosInventario as $p): ?>
              <?php 
                $costo = (int)$p['CostoCompra'];
                $precio = (int)$p['PrecioVenta'];
                $stock = (float)$p['Stock'];
                $totCosto = (int)$p['TotalCosto'];
                $totVenta = (int)$p['TotalVenta'];
                $margen = $totVenta - $totCosto;
                $pctMargen = $costo > 0 ? round((($precio - $costo) / $costo) * 100, 1) : null;
                $sinCosto = ($costo === 0);
              ?>
              <tr class="fila-prod-inv" 
                  data-nombre="<?= htmlspecialchars(mb_strtolower($p['Nombre'])) ?>"
                  data-codigo="<?= htmlspecialchars(mb_strtolower($p['CodigoBarras'] . ' ' . ($p['CodigoPLU'] ?? ''))) ?>"
                  data-categoria-id="<?= (int)$p['CategoriaID'] ?>"
                  data-stock="<?= $stock ?>"
                  data-costo="<?= $costo ?>"
                  data-sincosto="<?= $sinCosto ? '1' : '0' ?>"
                  data-total-costo="<?= $totCosto ?>"
                  data-total-venta="<?= $totVenta ?>"
                  data-margen="<?= $margen ?>">
                
                <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;"><?= $num++ ?></td>
                
                <td>
                  <code style="font-size: 0.8rem;"><?= htmlspecialchars($p['CodigoBarras'] ?: ($p['CodigoPLU'] ? 'PLU:'.$p['CodigoPLU'] : 'S/C')) ?></code>
                </td>
                
                <td>
                  <strong style="color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($p['Nombre']) ?></strong>
                  <?php if (!empty($p['EsPesable'])): ?>
                    <span class="badge badge-warning" style="font-size: 0.65rem; padding: 0.1rem 0.3rem;">Kg</span>
                  <?php endif; ?>
                  <?php if (!empty($p['EsPrecioVariable'])): ?>
                    <span class="badge badge-info" style="font-size: 0.65rem; padding: 0.1rem 0.3rem;">Variable</span>
                  <?php endif; ?>
                </td>
                
                <td style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($p['Categoria']) ?></td>
                
                <td style="text-align: center;">
                  <?php if ($stock <= 0): ?>
                    <span class="badge badge-danger">0 u</span>
                  <?php elseif ($stock <= $p['StockMinimo']): ?>
                    <span class="badge badge-warning"><?= $stock ?> u</span>
                  <?php else: ?>
                    <strong style="color: #38bdf8;"><?= $stock ?></strong> <span style="font-size: 0.75rem; color: var(--text-muted);">u</span>
                  <?php endif; ?>
                </td>
                
                <td style="text-align: right; font-family: monospace; font-size: 0.88rem;">
                  <?php if ($sinCosto): ?>
                    <span class="badge badge-warning" title="Este producto no tiene costo de compra asignado" style="font-size: 0.75rem; font-family: sans-serif;">
                      ⚠️ $0
                    </span>
                  <?php else: ?>
                    <span style="color: #fbbf24;"><?= formatCLP($costo) ?></span>
                  <?php endif; ?>
                </td>
                
                <td style="text-align: right; font-family: monospace; font-size: 0.88rem; color: var(--text-main);">
                  <?= formatCLP($precio) ?>
                </td>
                
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #fbbf24; font-size: 0.9rem;">
                  <?= formatCLP($totCosto) ?>
                </td>
                
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--success); font-size: 0.9rem;">
                  <?= formatCLP($totVenta) ?>
                </td>
                
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #818cf8; font-size: 0.9rem;">
                  <?= formatCLP($margen) ?>
                </td>
                
                <td style="text-align: right; font-size: 0.85rem;">
                  <?php if ($sinCosto): ?>
                    <span class="badge badge-warning" title="Sin costo de compra cargado" style="font-size: 0.75rem;">S/C</span>
                  <?php elseif ($pctMargen > 0): ?>
                    <span class="badge badge-success">+<?= $pctMargen ?>%</span>
                  <?php elseif ($pctMargen < 0): ?>
                    <span class="badge badge-danger"><?= $pctMargen ?>%</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">0%</span>
                  <?php endif; ?>
                </td>
                
                <td style="text-align: center;">
                  <a href="productos.php?q=<?= urlencode($p['CodigoBarras'] ?: $p['Nombre']) ?>" 
                     class="btn btn-secondary" 
                     style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" 
                     title="Editar este producto en el catálogo">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot style="background: rgba(0,0,0,0.3); font-weight: 700; border-top: 2px solid var(--border-dark);">
          <tr>
            <td colspan="4" style="text-align: right; color: var(--text-main);" id="tfootCount">
              Total Visible
            </td>
            <td style="text-align: center; color: #38bdf8;" id="tfootStock">
              0 u
            </td>
            <td colspan="2" style="text-align: right; color: var(--text-muted); font-size: 0.8rem;">
              SUBTOTALES VALORIZADOS:
            </td>
            <td style="text-align: right; color: #fbbf24; font-family: monospace; font-size: 0.95rem;" id="tfootCosto">
              $0
            </td>
            <td style="text-align: right; color: var(--success); font-family: monospace; font-size: 0.95rem;" id="tfootVenta">
              $0
            </td>
            <td style="text-align: right; color: #818cf8; font-family: monospace; font-size: 0.95rem;" id="tfootMargen">
              $0
            </td>
            <td style="text-align: right;" id="tfootMargenPct">
              <span class="badge badge-success">0%</span>
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <script>
  let filtroStockActivo = 'todos';

  function setFiltroStock(tipo, btn) {
    filtroStockActivo = tipo;
    document.querySelectorAll('#chipsEstadoFiltro .chip-inv-btn').forEach(b => {
      b.classList.remove('active');
      b.style.background = '';
      b.style.color = '';
    });
    if (btn) {
      btn.classList.add('active');
      btn.style.background = 'var(--primary)';
      btn.style.color = '#fff';
    } else {
      const matchBtn = document.querySelector(`#chipsEstadoFiltro [data-filtro="${tipo}"]`);
      if (matchBtn) {
        matchBtn.classList.add('active');
        matchBtn.style.background = 'var(--primary)';
        matchBtn.style.color = '#fff';
      }
    }
    filtrarProductosInventario();
    document.getElementById('seccionProductosInventario')?.scrollIntoView({ behavior: 'smooth' });
  }

  function filtrarPorCategoria(catId) {
    const sel = document.getElementById('filtroCatInv');
    if (sel) {
      sel.value = catId;
      filtrarProductosInventario();
      document.getElementById('seccionProductosInventario')?.scrollIntoView({ behavior: 'smooth' });
    }
  }

  function filtrarProductosInventario() {
    const q = (document.getElementById('buscadorProdInv')?.value || '').toLowerCase().trim();
    const cat = document.getElementById('filtroCatInv')?.value || '0';
    const filas = document.querySelectorAll('.fila-prod-inv');
    
    let count = 0;
    let sumStock = 0;
    let sumCosto = 0;
    let sumVenta = 0;
    let sumMargen = 0;

    filas.forEach((tr) => {
      const nombre = tr.getAttribute('data-nombre') || '';
      const codigo = tr.getAttribute('data-codigo') || '';
      const catId = tr.getAttribute('data-categoria-id') || '0';
      const stock = parseFloat(tr.getAttribute('data-stock') || '0');
      const costo = parseFloat(tr.getAttribute('data-costo') || '0');
      const sinCosto = tr.getAttribute('data-sincosto') === '1';
      const totCosto = parseFloat(tr.getAttribute('data-total-costo') || '0');
      const totVenta = parseFloat(tr.getAttribute('data-total-venta') || '0');
      const margen = parseFloat(tr.getAttribute('data-margen') || '0');

      let visible = true;

      // Filtro texto
      if (q && !nombre.includes(q) && !codigo.includes(q)) {
        visible = false;
      }

      // Filtro categoría
      if (visible && cat !== '0' && catId !== cat) {
        visible = false;
      }

      // Filtro chips
      if (visible) {
        if (filtroStockActivo === 'con_stock' && stock <= 0) visible = false;
        if (filtroStockActivo === 'sin_costo' && !sinCosto) visible = false;
        if (filtroStockActivo === 'agotados' && stock > 0) visible = false;
      }

      if (visible) {
        tr.style.display = '';
        count++;
        sumStock += stock;
        sumCosto += totCosto;
        sumVenta += totVenta;
        sumMargen += margen;
      } else {
        tr.style.display = 'none';
      }
    });

    const fmtCLP = (num) => '$' + Math.round(num).toLocaleString('es-CL');

    // Actualizar indicadores
    const contEl = document.getElementById('contadorItemsInv');
    if (contEl) contEl.textContent = count;
    const sStockEl = document.getElementById('sumStockInv');
    if (sStockEl) sStockEl.textContent = sumStock.toLocaleString('es-CL');
    const sCostoEl = document.getElementById('sumCostoInv');
    if (sCostoEl) sCostoEl.textContent = fmtCLP(sumCosto);
    const sVentaEl = document.getElementById('sumVentaInv');
    if (sVentaEl) sVentaEl.textContent = fmtCLP(sumVenta);
    const sMargenEl = document.getElementById('sumMargenInv');
    if (sMargenEl) sMargenEl.textContent = fmtCLP(sumMargen);

    // Actualizar fila tfoot
    const tfCount = document.getElementById('tfootCount');
    if (tfCount) tfCount.textContent = count + ' productos';
    const tfStock = document.getElementById('tfootStock');
    if (tfStock) tfStock.textContent = sumStock.toLocaleString('es-CL') + ' u';
    const tfCosto = document.getElementById('tfootCosto');
    if (tfCosto) tfCosto.textContent = fmtCLP(sumCosto);
    const tfVenta = document.getElementById('tfootVenta');
    if (tfVenta) tfVenta.textContent = fmtCLP(sumVenta);
    const tfMargen = document.getElementById('tfootMargen');
    if (tfMargen) tfMargen.textContent = fmtCLP(sumMargen);
    const pctG = sumCosto > 0 ? '+' + ((sumMargen / sumCosto) * 100).toFixed(1) + '%' : (sumVenta > 0 ? 'S/C' : '0%');
    const tfPct = document.getElementById('tfootMargenPct');
    if (tfPct) tfPct.innerHTML = `<span class="badge ${sumCosto > 0 && sumMargen >= 0 ? 'badge-success' : (sumCosto > 0 ? 'badge-danger' : 'badge-warning')}">${pctG}</span>`;
  }

  // Inicializar al cargar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', filtrarProductosInventario);
  } else {
    filtrarProductosInventario();
  }
  </script>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 4: RENDIMIENTO DE CAJEROS Y CUADRATURAS -->
<!-- ============================================================== -->
<?php if ($tab === 'cajeros'): ?>

  <div style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
      <i class="fa-solid fa-users-gear" style="color: var(--primary);"></i> Rendimiento de Personal y Cuadraturas de Turno
    </h2>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Auditoría de ventas por operador y control de diferencias en arqueos de caja (sobrantes y faltantes).
    </p>
  </div>

  <!-- Resumen de Cuadratura en Tarjetas -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Cajeros Activos en Turno</h3>
        <div class="stat-value"><?= count($cajerosRendimiento) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Personal con movimientos en el período</small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
        <i class="fa-solid fa-user-check"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Faltantes de Caja</h3>
        <div class="stat-value" style="color: var(--danger);">-<?= formatCLP($totalFaltantes) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Descuadres negativos en cierres</small>
      </div>
      <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
        <i class="fa-solid fa-circle-minus"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Sobrantes de Caja</h3>
        <div class="stat-value" style="color: var(--success);">+<?= formatCLP($totalSobrantes) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Excedentes declarados en cierres</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
        <i class="fa-solid fa-circle-plus"></i>
      </div>
    </div>
  </div>

  <!-- Tabla 1: Desempeño por Cajero -->
  <div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-user-tie" style="color: var(--primary);"></i> Resumen de Ventas por Cajero
      </h3>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Cajero / Usuario</th>
          <th>Rol</th>
          <th style="text-align: center;">Turnos Atendidos</th>
          <th style="text-align: center;">Ventas Realizadas</th>
          <th style="text-align: right;">Total Recaudado</th>
          <th style="text-align: right;">Ticket Promedio</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cajerosRendimiento)): ?>
          <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay actividad de cajeros en el período.</td></tr>
        <?php else: ?>
          <?php foreach ($cajerosRendimiento as $c): ?>
            <?php 
              $avgTicketCajero = $c['TotalVentas'] > 0 ? round($c['MontoTotalVentas'] / $c['TotalVentas']) : 0;
            ?>
            <tr>
              <td>
                <strong style="color: #fff;"><?= htmlspecialchars($c['Cajero']) ?></strong>
                <small style="display: block; color: var(--text-muted);">@<?= htmlspecialchars($c['Usuario']) ?></small>
              </td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($c['Rol']) ?></span></td>
              <td style="text-align: center; font-weight: 600;"><?= $c['TurnosRealizados'] ?></td>
              <td style="text-align: center; font-weight: 600;"><?= $c['TotalVentas'] ?></td>
              <td style="text-align: right; font-weight: 700; color: var(--success); font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($c['MontoTotalVentas']) ?>
              </td>
              <td style="text-align: right; font-family: monospace; color: var(--text-muted);"><?= formatCLP($avgTicketCajero) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabla 2: Historial de Cuadraturas de Turnos -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-scale-balanced" style="color: var(--primary);"></i> Historial de Arqueos y Cuadraturas de Turno
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Últimos 50 turnos cerrados</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>N° Turno</th>
          <th>Cajero</th>
          <th>Apertura</th>
          <th>Cierre</th>
          <th style="text-align: right;">Esperado Sistema</th>
          <th style="text-align: right;">Declarado Físico</th>
          <th style="text-align: right;">Diferencia</th>
          <th style="text-align: center;">Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cuadraturasTurnos)): ?>
          <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay turnos cerrados registrados en este período.</td></tr>
        <?php else: ?>
          <?php foreach ($cuadraturasTurnos as $ct): ?>
            <?php 
              $dif = (int)$ct['Diferencia'];
              $difColor = 'var(--text-muted)';
              $difBadge = '<span class="badge badge-success">Cuadrado</span>';
              if ($dif < 0) {
                $difColor = 'var(--danger)';
                $difBadge = '<span class="badge badge-danger">Faltante</span>';
              } elseif ($dif > 0) {
                $difColor = 'var(--warning)';
                $difBadge = '<span class="badge badge-warning">Sobrante</span>';
              }
            ?>
            <tr>
              <td><strong style="color: #818cf8;">#<?= $ct['TurnoID'] ?></strong></td>
              <td><?= htmlspecialchars($ct['Cajero']) ?></td>
              <td style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($ct['FechaApertura'])) ?></td>
              <td style="font-size: 0.8rem; color: var(--text-muted);"><?= $ct['FechaCierre'] ? date('d/m/Y H:i', strtotime($ct['FechaCierre'])) : '—' ?></td>
              <td style="text-align: right; font-family: monospace;"><?= formatCLP($ct['MontoCierreSistema']) ?></td>
              <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--text-main);"><?= formatCLP($ct['TotalDeclarado']) ?></td>
              <td style="text-align: right; font-family: monospace; font-weight: 700; color: <?= $difColor ?>;">
                <?= ($dif > 0 ? '+' : '') . formatCLP($dif) ?>
              </td>
              <td style="text-align: center;"><?= $difBadge ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 5: UTILIDADES Y MÁRGENES (INTEGRADA) -->
<!-- ============================================================== -->
<?php if ($tab === 'utilidades'): ?>

  <div style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
      <i class="fa-solid fa-sack-dollar" style="color: var(--primary);"></i> Márgenes de Ganancia Real y Rentabilidad
    </h2>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Cálculo de ganancia neta comparando el Precio de Venta facturado vs. Costo de Compra de cada artículo.
    </p>
  </div>

  <?php 
    $margenPromedioUtil = $totalCostoUtil > 0 ? round(($totalUtilidadMonto / $totalCostoUtil) * 100, 1) : null;
  ?>
  <div class="grid-stats" style="margin-bottom: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Ingresos Venta</h3>
        <div class="stat-value"><?= formatCLP($totalVentasUtil) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Facturado en el período</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);"><i class="fa-solid fa-chart-line"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Costo Mercadería</h3>
        <div class="stat-value" style="color: var(--warning);"><?= formatCLP($totalCostoUtil) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Costo de lo vendido</small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);"><i class="fa-solid fa-boxes-stacked"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Utilidad Bruta Ventas</h3>
        <div class="stat-value" style="color: #818cf8;"><?= formatCLP($totalUtilidadMonto) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Ganancia s/ventas</small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;"><i class="fa-solid fa-sack-dollar"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>(-) Pérdidas por Mermas</h3>
        <div class="stat-value" style="color: var(--danger);"><?= formatCLP($totalMermasCosto) ?></div>
        <small>
          <a href="reportes.php?tab=mermas&inicio=<?= urlencode($fechaInicio) ?>&fin=<?= urlencode($fechaFin) ?>" style="color: #f87171; text-decoration: none; font-size: 0.75rem; font-weight: 600;">
            <?= $totalMermasUnidades ?> u mermadas <i class="fa-solid fa-arrow-right" style="font-size: 0.65rem;"></i>
          </a>
        </small>
      </div>
      <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);"><i class="fa-solid fa-trash-can"></i></div>
    </div>

    <div class="stat-card" style="border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.05);">
      <div class="stat-info">
        <h3 style="color: var(--success); font-weight: 700;">Utilidad Real Neta</h3>
        <div class="stat-value" style="color: #34d399; font-weight: 800; font-size: 1.6rem;"><?= formatCLP($utilidadRealNeta) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Limpio (Ventas - Mermas)</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: var(--success);"><i class="fa-solid fa-wallet"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Margen Prom. s/Costo</h3>
        <div class="stat-value" style="color: #38bdf8;"><?= $margenPromedioUtil !== null ? '+' . $margenPromedioUtil . '%' : 'S/C' ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Rentabilidad comercial</small>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;"><i class="fa-solid fa-percent"></i></div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-folder-tree" style="color: var(--primary);"></i> Desglose de Utilidades por Categoría y Productos
        </h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
          Haz clic en cualquier categoría para expandir o colapsar sus productos vendidos.
        </p>
      </div>

      <!-- Controles de Filtro y Expansión -->
      <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <input type="text" id="buscadorUtilLive" oninput="filtrarUtilidadesLive()" class="form-control" placeholder="Buscar producto o código..." style="padding: 0.4rem 0.75rem; font-size: 0.82rem; width: 210px;">
        
        <select id="filtroCategoriaUtil" onchange="onFiltroCategoriaUtilChange()" class="form-control" style="padding: 0.4rem 0.75rem; font-size: 0.82rem; width: auto; font-weight: 600;">
          <option value="todas">Todas las categorías (<?= count($utilidadesPorCategoria) ?>)</option>
          <?php foreach ($categoriasUtilSelector as $catSel): ?>
            <option value="<?= $catSel['CategoriaID'] ?>"><?= htmlspecialchars($catSel['Nombre']) ?> (<?= $catSel['ItemsCount'] ?>)</option>
          <?php endforeach; ?>
        </select>

        <button type="button" onclick="toggleTodasCategoriasUtil(true)" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.35rem;" title="Expandir todas las categorías">
          <i class="fa-solid fa-angles-down"></i> Expandir
        </button>
        <button type="button" onclick="toggleTodasCategoriasUtil(false)" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.35rem;" title="Colapsar todas las categorías">
          <i class="fa-solid fa-angles-up"></i> Colapsar
        </button>
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" id="tablaUtilidadesCategorias">
        <thead>
          <tr>
            <th style="min-width: 250px;">Categoría / Producto</th>
            <th style="text-align: center; width: 110px;">Unidades</th>
            <th style="text-align: right; width: 140px;">Venta Total ($)</th>
            <th style="text-align: right; width: 140px;">Costo Total ($)</th>
            <th style="text-align: right; width: 140px; color: var(--success);">Ganancia Neta</th>
            <th style="text-align: right; width: 130px;">Margen % s/Costo</th>
          </tr>
        </thead>
        <tbody id="tbodyUtilidades">
          <?php if (empty($utilidadesPorCategoria)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No hay ventas registradas en el período seleccionado.</td></tr>
          <?php else: ?>
            <?php foreach ($utilidadesPorCategoria as $cId => $cat): ?>
              <?php 
                $margenCat = $cat['TotalCosto'] > 0 ? round(($cat['UtilidadEstimada'] / $cat['TotalCosto']) * 100, 1) : null;
              ?>
              <!-- Fila Maestra de Categoría (Clickeable para expandir/colapsar) -->
              <tr class="fila-categoria-util" 
                  data-cat-id="<?= $cId ?>"
                  onclick="toggleCategoriaUtil(<?= $cId ?>)"
                  style="background: rgba(30, 41, 59, 0.75); border-left: 4px solid var(--primary); cursor: pointer; user-select: none;">
                <td>
                  <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <i class="fa-solid fa-chevron-right chevron-cat-util" id="chevron_cat_<?= $cId ?>" style="font-size: 0.75rem; color: var(--primary); transition: transform 0.2s ease;"></i>
                    <i class="fa-solid fa-folder" style="color: var(--primary); font-size: 0.95rem;"></i>
                    <strong style="color: #fff; font-size: 0.92rem;"><?= htmlspecialchars($cat['Categoria']) ?></strong>
                    <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 0.15rem 0.45rem; opacity: 0.85;">
                      <?= $cat['ProductosCount'] ?> <?= $cat['ProductosCount'] === 1 ? 'artículo' : 'artículos' ?>
                    </span>
                  </div>
                </td>
                <td style="text-align: center; font-weight: 700; color: #38bdf8;">
                  <?= number_format($cat['CantidadVendida'], 0, ',', '.') ?> u
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--text-main);">
                  <?= formatCLP($cat['TotalVentas']) ?>
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 600; color: #fbbf24;">
                  <?= formatCLP($cat['TotalCosto']) ?>
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 800; color: var(--success); font-size: 0.95rem;">
                  <?= formatCLP($cat['UtilidadEstimada']) ?>
                </td>
                <td style="text-align: right;">
                  <?php if ($margenCat === null): ?>
                    <span class="badge badge-warning" title="Sin costo registrado">S/C</span>
                  <?php elseif ($margenCat > 0): ?>
                    <span class="badge badge-success" style="font-size: 0.82rem; font-weight: 700;">+<?= $margenCat ?>%</span>
                  <?php elseif ($margenCat < 0): ?>
                    <span class="badge badge-danger"><?= $margenCat ?>%</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">0%</span>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- Filas Hijas: Productos pertenecientes a esta categoría -->
              <?php foreach ($cat['Productos'] as $prod): ?>
                <?php 
                  $margenProd = $prod['TotalCosto'] > 0 ? round(($prod['UtilidadEstimada'] / $prod['TotalCosto']) * 100, 1) : null;
                  $codProd = $prod['CodigoBarras'] ?: ($prod['CodigoPLU'] ? 'PLU:'.$prod['CodigoPLU'] : '');
                ?>
                <tr class="fila-producto-util cat-child-<?= $cId ?>" 
                    data-cat-id="<?= $cId ?>"
                    data-nombre="<?= htmlspecialchars(mb_strtolower($prod['Producto'])) ?>"
                    data-codigo="<?= htmlspecialchars(mb_strtolower($codProd)) ?>"
                    data-unidades="<?= (float)$prod['CantidadVendida'] ?>"
                    data-venta="<?= (int)$prod['TotalVentas'] ?>"
                    data-costo="<?= (int)$prod['TotalCosto'] ?>"
                    data-utilidad="<?= (int)$prod['UtilidadEstimada'] ?>"
                    style="display: none; background: rgba(15, 23, 42, 0.45); border-bottom: 1px solid rgba(255,255,255,0.04);">
                  <td style="padding-left: 2.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                      <i class="fa-solid fa-angle-right" style="color: var(--text-muted); font-size: 0.75rem; opacity: 0.5;"></i>
                      <span style="color: #cbd5e1; font-weight: 500; font-size: 0.875rem;"><?= htmlspecialchars($prod['Producto']) ?></span>
                      <?php if ($codProd): ?>
                        <code style="font-size: 0.75rem; opacity: 0.7;"><?= htmlspecialchars($codProd) ?></code>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                    <?= (float)$prod['CantidadVendida'] ?> u
                  </td>
                  <td style="text-align: right; font-family: monospace; font-size: 0.85rem; color: var(--text-main);">
                    <?= formatCLP($prod['TotalVentas']) ?>
                  </td>
                  <td style="text-align: right; font-family: monospace; font-size: 0.85rem; color: #fbbf24;">
                    <?= formatCLP($prod['TotalCosto']) ?>
                  </td>
                  <td style="text-align: right; font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--success);">
                    <?= formatCLP($prod['UtilidadEstimada']) ?>
                  </td>
                  <td style="text-align: right; font-size: 0.82rem;">
                    <?php if ($margenProd === null): ?>
                      <span class="badge badge-warning" style="font-size: 0.72rem;">S/C</span>
                    <?php elseif ($margenProd > 0): ?>
                      <span class="badge badge-success" style="font-size: 0.75rem;">+<?= $margenProd ?>%</span>
                    <?php elseif ($margenProd < 0): ?>
                      <span class="badge badge-danger" style="font-size: 0.75rem;"><?= $margenProd ?>%</span>
                    <?php else: ?>
                      <span class="badge badge-secondary" style="font-size: 0.75rem;">0%</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot style="background: rgba(15,23,42,0.85); font-weight: 700; border-top: 2px solid var(--border-dark);">
          <tr>
            <td style="padding: 0.85rem 1rem;">TOTAL CONSOLIDADO:</td>
            <td style="text-align: center; color: #38bdf8;" id="tfUtilUnidades">
              <?= number_format(array_sum(array_column($reporteUtilidades, 'CantidadVendida')), 0, ',', '.') ?> u
            </td>
            <td style="text-align: right; font-family: monospace; color: var(--text-main);" id="tfUtilVenta"><?= formatCLP($totalVentasUtil) ?></td>
            <td style="text-align: right; font-family: monospace; color: #fbbf24;" id="tfUtilCosto"><?= formatCLP($totalCostoUtil) ?></td>
            <td style="text-align: right; font-family: monospace; color: var(--success); font-size: 1rem;" id="tfUtilGanancia"><?= formatCLP($totalUtilidadMonto) ?></td>
            <td style="text-align: right;" id="tfUtilMargen">
              <?php if ($margenPromedioUtil !== null): ?>
                <span class="badge badge-success" style="font-size: 0.85rem;">+<?= $margenPromedioUtil ?>%</span>
              <?php else: ?>
                <span class="badge badge-warning">S/C</span>
              <?php endif; ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <script>
  const estadoCategoriasUtil = {};

  function toggleCategoriaUtil(catId, forzarEstado = null) {
    const filasHijas = document.querySelectorAll(`.cat-child-${catId}`);
    const chevron = document.getElementById(`chevron_cat_${catId}`);
    
    const estaAbierto = forzarEstado !== null ? !forzarEstado : (estadoCategoriasUtil[catId] || false);
    const nuevoEstado = !estaAbierto;
    estadoCategoriasUtil[catId] = nuevoEstado;

    filasHijas.forEach(f => {
      f.style.display = nuevoEstado ? 'table-row' : 'none';
    });

    if (chevron) {
      chevron.style.transform = nuevoEstado ? 'rotate(90deg)' : 'rotate(0deg)';
    }
  }

  function toggleTodasCategoriasUtil(abrir) {
    const filasCat = document.querySelectorAll('.fila-categoria-util');
    filasCat.forEach(fc => {
      const catId = fc.getAttribute('data-cat-id');
      if (fc.style.display !== 'none') {
        toggleCategoriaUtil(catId, abrir);
      }
    });
  }

  function onFiltroCategoriaUtilChange() {
    const selVal = document.getElementById('filtroCategoriaUtil').value;
    const filasCat = document.querySelectorAll('.fila-categoria-util');
    const filasProd = document.querySelectorAll('.fila-producto-util');

    if (selVal === 'todas') {
      filasCat.forEach(fc => fc.style.display = '');
      filasProd.forEach(fp => {
        const catId = fp.getAttribute('data-cat-id');
        fp.style.display = estadoCategoriasUtil[catId] ? 'table-row' : 'none';
      });
    } else {
      filasCat.forEach(fc => {
        const cId = fc.getAttribute('data-cat-id');
        if (cId === selVal) {
          fc.style.display = '';
          toggleCategoriaUtil(cId, true);
        } else {
          fc.style.display = 'none';
          document.querySelectorAll(`.cat-child-${cId}`).forEach(fp => fp.style.display = 'none');
        }
      });
    }
  }

  function filtrarUtilidadesLive() {
    const q = (document.getElementById('buscadorUtilLive')?.value || '').toLowerCase().trim();
    const selCat = document.getElementById('filtroCategoriaUtil')?.value || 'todas';
    const filasCat = document.querySelectorAll('.fila-categoria-util');

    filasCat.forEach(fc => {
      const catId = fc.getAttribute('data-cat-id');
      const hijas = document.querySelectorAll(`.cat-child-${catId}`);
      
      if (selCat !== 'todas' && selCat !== catId) {
        fc.style.display = 'none';
        hijas.forEach(h => h.style.display = 'none');
        return;
      }

      if (!q) {
        fc.style.display = '';
        hijas.forEach(h => {
          h.style.display = estadoCategoriasUtil[catId] ? 'table-row' : 'none';
        });
        return;
      }

      let matchesEnCat = 0;
      hijas.forEach(h => {
        const nombre = h.getAttribute('data-nombre') || '';
        const codigo = h.getAttribute('data-codigo') || '';
        if (nombre.includes(q) || codigo.includes(q)) {
          h.style.display = 'table-row';
          matchesEnCat++;
        } else {
          h.style.display = 'none';
        }
      });

      if (matchesEnCat > 0) {
        fc.style.display = '';
        const chevron = document.getElementById(`chevron_cat_${catId}`);
        if (chevron) chevron.style.transform = 'rotate(90deg)';
        estadoCategoriasUtil[catId] = true;
      } else {
        fc.style.display = 'none';
      }
    });
  }
  </script>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 6: CARTERA DE CLIENTES Y FIADOS (CUENTAS POR COBRAR) -->
<!-- ============================================================== -->
<?php if ($tab === 'creditos'): ?>

  <div style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
      <i class="fa-solid fa-handshake" style="color: var(--primary);"></i> Cartera de Clientes, Cuentas por Cobrar y Fiados
    </h2>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Auditoría de crédito interno, saldos pendientes de pago, control de cupos y flujo de cobranza.
    </p>
  </div>

  <!-- KPIs de Cartera y Cobranza -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Deuda por Cobrar</h3>
        <div class="stat-value" style="color: var(--danger);"><?= formatCLP($kpiCreditos['TotalDeudaGlobal']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Capital de la tienda en la calle</small>
      </div>
      <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
        <i class="fa-solid fa-hand-holding-dollar"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Clientes Deudores</h3>
        <div class="stat-value" style="color: #fbbf24;"><?= number_format($kpiCreditos['ClientesDeudoresCount'], 0, ',', '.') ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Cupo Total: <?= formatCLP($kpiCreditos['TotalCupoGlobal']) ?></small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
        <i class="fa-solid fa-users"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Abonos Recaudados</h3>
        <div class="stat-value" style="color: var(--success);"><?= formatCLP($kpiAbonos['TotalAbonosPeriodo']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $kpiAbonos['CantidadAbonos'] ?> pagos recibidos en el período</small>
      </div>
      <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
        <i class="fa-solid fa-money-bill-trend-up"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Ventas Fiadas en Período</h3>
        <div class="stat-value" style="color: #818cf8;"><?= formatCLP($kpiFiado['TotalFiadoPeriodo']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $kpiFiado['TransaccionesFiado'] ?> compras a crédito</small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
        <i class="fa-solid fa-file-invoice-dollar"></i>
      </div>
    </div>
  </div>

  <!-- Tabla 1: Cartera de Clientes Deudores (Ranking de Deuda) -->
  <div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-user-clock" style="color: var(--primary);"></i> Cartera de Clientes con Deuda Activa (Ranking)
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($carteraDeudores) ?> clientes con saldo deudor</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Cliente</th>
          <th>RUT</th>
          <th>Teléfono</th>
          <th style="text-align: right;">Cupo Máximo ($)</th>
          <th style="text-align: right;">Deuda Pendiente ($)</th>
          <th style="width: 140px; text-align: center;">% Cupo Usado</th>
          <th style="text-align: center;">Estado</th>
          <th style="text-align: center;">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($carteraDeudores)): ?>
          <tr>
            <td colspan="8" style="text-align: center; color: var(--success); padding: 2rem;">
              <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; display: block; margin-bottom: 0.4rem;"></i>
              ¡Sin deudas pendientes! Todos los clientes están al día con sus pagos.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($carteraDeudores as $cd): ?>
            <?php 
              $limite = (int)$cd['LimiteCredito'];
              $deuda = (int)$cd['SaldoDeudor'];
              $pct = $limite > 0 ? round(($deuda / $limite) * 100, 1) : 100;
              
              $barColor = '#10b981';
              $badgeEstado = '<span class="badge badge-success">OK</span>';
              if ($pct >= 100) {
                $barColor = '#ef4444';
                $badgeEstado = '<span class="badge badge-danger">Bloqueado (100%)</span>';
              } elseif ($pct >= 75) {
                $barColor = '#f59e0b';
                $badgeEstado = '<span class="badge badge-warning">Cerca del Límite</span>';
              }
              $rutFmt = $cd['RutCuerpo'] ? number_format($cd['RutCuerpo'], 0, '', '.') . '-' . $cd['RutDv'] : 'Sin RUT';
            ?>
            <tr>
              <td>
                <strong style="color: var(--text-main);"><?= htmlspecialchars($cd['Nombre']) ?></strong>
                <?php if (!empty($cd['Email'])): ?>
                  <small style="display: block; color: var(--text-muted); font-size: 0.72rem;"><?= htmlspecialchars($cd['Email']) ?></small>
                <?php endif; ?>
              </td>
              <td style="font-family: monospace; color: var(--text-muted);"><?= $rutFmt ?></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($cd['Telefono'] ?: '—') ?></td>
              <td style="text-align: right; font-family: monospace;"><?= formatCLP($limite) ?></td>
              <td style="text-align: right; font-weight: 700; color: var(--danger); font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($deuda) ?>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <div style="flex: 1; background: rgba(255,255,255,0.08); height: 7px; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= min(100, $pct) ?>%; background: <?= $barColor ?>; height: 100%; border-radius: 4px;"></div>
                  </div>
                  <span style="font-size: 0.78rem; font-weight: 700; color: <?= $barColor ?>; min-width: 40px; text-align: right;"><?= $pct ?>%</span>
                </div>
              </td>
              <td style="text-align: center;"><?= $badgeEstado ?></td>
              <td style="text-align: center;">
                <a href="clientes.php" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;" title="Registrar abono en módulo clientes">
                  <i class="fa-solid fa-hand-holding-dollar"></i> Abonar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabla 2: Historial de Abonos Recibidos en el Período -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-receipt" style="color: var(--primary);"></i> Historial de Abonos y Recuperación de Cartera en el Período
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Total recuperado: <?= formatCLP($kpiAbonos['TotalAbonosPeriodo']) ?></span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>N° Abono</th>
          <th>Fecha y Hora</th>
          <th>Cliente</th>
          <th>Atendido Por</th>
          <th>Medio de Pago</th>
          <th style="text-align: right;">Monto Abonado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($abonosList)): ?>
          <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No se han registrado abonos en el período seleccionado.</td></tr>
        <?php else: ?>
          <?php foreach ($abonosList as $ab): ?>
            <tr>
              <td><strong style="color: #818cf8;">#<?= $ab['AbonoID'] ?></strong></td>
              <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($ab['FechaAbono'])) ?></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($ab['Cliente']) ?></strong></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($ab['Cajero'] ?: 'Administración') ?></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($ab['MetodoPago']) ?></span></td>
              <td style="text-align: right; font-weight: 700; color: var(--success); font-family: monospace; font-size: 0.95rem;">
                +<?= formatCLP($ab['Monto']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 7: COMPRAS Y GASTOS POR PROVEEDOR (EGRESOS) -->
<!-- ============================================================== -->
<?php if ($tab === 'compras'): ?>

  <div style="margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main);">
      <i class="fa-solid fa-truck-ramp-box" style="color: var(--primary);"></i> Reporte de Compras, Proveedores y Abastecimiento
    </h2>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
      Auditoría de egresos en mercadería, volumen de compra por distribuidor y recepción de facturas.
    </p>
  </div>

  <!-- KPIs de Compras -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Egresos Compras</h3>
        <div class="stat-value" style="color: var(--warning);"><?= formatCLP($kpiCompras['TotalEgresosCompras']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Gasto total facturado en mercadería</small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
        <i class="fa-solid fa-truck-arrow-right"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Neto / IVA Crédito Fiscal</h3>
        <div class="stat-value" style="font-size: 1.35rem;"><?= formatCLP($kpiCompras['TotalNetoCompras']) ?></div>
        <small style="color: #38bdf8; font-size: 0.75rem;">IVA Crédito: <?= formatCLP($kpiCompras['TotalIvaCompras']) ?></small>
      </div>
      <div class="stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
        <i class="fa-solid fa-file-invoice"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Documentos / Facturas</h3>
        <div class="stat-value"><?= number_format($kpiCompras['TotalCompras'], 0, ',', '.') ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;"><?= $kpiCompras['TotalProveedoresActivos'] ?> distribuidores activos</small>
      </div>
      <div class="stat-icon" style="background: rgba(79, 70, 229, 0.15); color: #818cf8;">
        <i class="fa-solid fa-boxes-stacked"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Compra Promedio</h3>
        <div class="stat-value" style="color: #a78bfa;"><?= formatCLP($compraPromedio) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Promedio por factura recibida</small>
      </div>
      <div class="stat-icon" style="background: rgba(167, 139, 250, 0.15); color: #a78bfa;">
        <i class="fa-solid fa-calculator"></i>
      </div>
    </div>
  </div>

  <!-- Tabla 1: Resumen y Ranking de Compras por Proveedor -->
  <div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-dolly" style="color: var(--primary);"></i> Compras por Proveedor (Ranking de Gasto)
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($rankingProveedores) ?> proveedores en el período</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Proveedor / Razón Social</th>
          <th>RUT</th>
          <th>Teléfono</th>
          <th style="text-align: center;">Facturas</th>
          <th style="text-align: right;">Monto Neto ($)</th>
          <th style="text-align: right;">IVA Crédito ($)</th>
          <th style="text-align: right;">Total Comprado ($)</th>
          <th style="text-align: right;">Participación %</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rankingProveedores)): ?>
          <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay compras registradas en el período seleccionado.</td></tr>
        <?php else: ?>
          <?php foreach ($rankingProveedores as $pr): ?>
            <?php 
              $rutProv = $pr['RutCuerpo'] ? number_format($pr['RutCuerpo'], 0, '', '.') . '-' . $pr['RutDv'] : '—';
              $pctGasto = $kpiCompras['TotalEgresosCompras'] > 0 ? round(($pr['TotalComprado'] / $kpiCompras['TotalEgresosCompras']) * 100, 1) : 0;
            ?>
            <tr>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($pr['RazonSocial']) ?></strong></td>
              <td style="font-family: monospace; color: var(--text-muted);"><?= $rutProv ?></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($pr['Telefono'] ?: '—') ?></td>
              <td style="text-align: center; font-weight: 600;"><?= $pr['CantidadFacturas'] ?></td>
              <td style="text-align: right; font-family: monospace; color: var(--text-muted);"><?= formatCLP($pr['MontoNeto']) ?></td>
              <td style="text-align: right; font-family: monospace; color: #38bdf8;"><?= formatCLP($pr['MontoIva']) ?></td>
              <td style="text-align: right; font-weight: 700; color: var(--warning); font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($pr['TotalComprado']) ?>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.4rem;">
                  <div style="width: 45px; background: rgba(255,255,255,0.08); height: 6px; border-radius: 3px; overflow: hidden;">
                    <div style="width: <?= min(100, $pctGasto) ?>%; background: var(--warning); height: 100%;"></div>
                  </div>
                  <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); min-width: 40px; text-align: right;"><?= $pctGasto ?>%</span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabla 2: Historial de Recepciones de Compra -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">
        <i class="fa-solid fa-clipboard-check" style="color: var(--primary);"></i> Historial de Recepciones de Mercadería
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);">Mostrando hasta 100 compras</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>N° Compra</th>
          <th>Fecha</th>
          <th>Proveedor</th>
          <th>N° Factura / Guía</th>
          <th>Recepcionado Por</th>
          <th style="text-align: right;">Neto ($)</th>
          <th style="text-align: right;">IVA ($)</th>
          <th style="text-align: right;">Total Factura</th>
          <th style="text-align: center;">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($comprasList)): ?>
          <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay recepciones de compra en el período.</td></tr>
        <?php else: ?>
          <?php foreach ($comprasList as $comp): ?>
            <tr>
              <td><strong style="color: #818cf8;">#<?= $comp['CompraID'] ?></strong></td>
              <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y H:i', strtotime($comp['FechaCompra'])) ?></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($comp['Proveedor']) ?></strong></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($comp['NumeroDocumento'] ?: 'S/N') ?></span></td>
              <td style="color: var(--text-muted);"><?= htmlspecialchars($comp['Usuario']) ?></td>
              <td style="text-align: right; font-family: monospace; color: var(--text-muted);"><?= formatCLP($comp['MontoNeto']) ?></td>
              <td style="text-align: right; font-family: monospace; color: #38bdf8;"><?= formatCLP($comp['MontoIva']) ?></td>
              <td style="text-align: right; font-weight: 700; color: var(--warning); font-family: monospace; font-size: 0.95rem;">
                <?= formatCLP($comp['MontoTotal']) ?>
              </td>
              <td style="text-align: center;">
                <a href="compras.php" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Ver en módulo de compras">
                  <i class="fa-solid fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>

<!-- ============================================================== -->
<!-- PESTAÑA 8: AUDITORÍA DE MERMAS Y PÉRDIDAS DE MERCADERÍA -->
<!-- ============================================================== -->
<?php if ($tab === 'mermas'): ?>

  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
    <div>
      <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-trash-can" style="color: var(--danger);"></i> Auditoría de Mermas, Roturas y Pérdidas
      </h2>
      <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
        Control de mercadería dada de baja por vencimiento, roturas, consumo interno o descuadres valorizados al costo real.
      </p>
    </div>
    <div>
      <a href="ajustes.php" class="btn btn-secondary" style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.45rem;">
        <i class="fa-solid fa-sliders" style="color: var(--primary);"></i> Ir a Ajustes Manuales
      </a>
    </div>
  </div>

  <!-- KPIs de Mermas -->
  <div class="grid-stats" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Unidades Mermadas</h3>
        <div class="stat-value" style="color: var(--danger);"><?= number_format((float)$kpiMermas['TotalUnidadesMermadas'], 0, ',', '.') ?> <span style="font-size: 1rem; color: var(--text-muted);">u</span></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;"><?= (int)$kpiMermas['TotalLineasAfectadas'] ?> líneas / artículos afectados</small>
      </div>
      <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
        <i class="fa-solid fa-box-archive"></i>
      </div>
    </div>

    <div class="stat-card" style="border-color: rgba(239, 68, 68, 0.3);">
      <div class="stat-info">
        <h3>Pérdida Total al Costo</h3>
        <div class="stat-value" style="color: #ef4444; font-weight: 800;"><?= formatCLP($kpiMermas['TotalPerdidaCosto']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Capital propio del negocio perdido</small>
      </div>
      <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
        <i class="fa-solid fa-circle-dollar-to-slot"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Venta Perdida No Percibida</h3>
        <div class="stat-value" style="color: var(--warning);"><?= formatCLP($kpiMermas['TotalPerdidaVenta']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Ingreso potencial que no entró a caja</small>
      </div>
      <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Ajustes por Merma</h3>
        <div class="stat-value" style="color: #818cf8;"><?= (int)$kpiMermas['TotalEventosMermas'] ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Operaciones de baja en el período</small>
      </div>
      <div class="stat-icon" style="background: rgba(129, 140, 248, 0.15); color: #818cf8;">
        <i class="fa-solid fa-clipboard-list"></i>
      </div>
    </div>
  </div>

  <!-- Tabla 1: Desglose por Motivo de Merma -->
  <div class="table-card" style="margin-bottom: 2rem;">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Pérdidas Agrupadas por Causa / Motivo
      </h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($mermasPorMotivo) ?> motivos identificados</span>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Causa / Motivo de la Merma</th>
          <th style="text-align: center;">N° Artículos</th>
          <th style="text-align: center;">Unidades</th>
          <th style="text-align: right;">Pérdida al Costo ($)</th>
          <th style="text-align: right;">Venta No Percibida ($)</th>
          <th style="width: 200px; text-align: center;">% de la Pérdida Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mermasPorMotivo)): ?>
          <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay pérdidas registradas en este período. ¡Excelente control de stock!</td></tr>
        <?php else: ?>
          <?php foreach ($mermasPorMotivo as $mp): ?>
            <?php 
              $totalCostoBase = (int)$kpiMermas['TotalPerdidaCosto'];
              $pctPerdida = $totalCostoBase > 0 ? round(($mp['PerdidaCosto'] / $totalCostoBase) * 100, 1) : 0;
            ?>
            <tr>
              <td>
                <strong style="color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($mp['GrupoMotivo']) ?></strong>
              </td>
              <td style="text-align: center;"><?= $mp['CantidadItems'] ?></td>
              <td style="text-align: center; font-weight: 600; color: #38bdf8;"><?= number_format($mp['TotalUnidades'], 0, ',', '.') ?> u</td>
              <td style="text-align: right; color: var(--danger); font-family: monospace; font-weight: 700;"><?= formatCLP($mp['PerdidaCosto']) ?></td>
              <td style="text-align: right; color: var(--warning); font-family: monospace; font-weight: 600;"><?= formatCLP($mp['PerdidaVenta']) ?></td>
              <td>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <div style="flex: 1; height: 7px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= min(100, $pctPerdida) ?>%; height: 100%; background: var(--danger); border-radius: 4px;"></div>
                  </div>
                  <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-main); min-width: 45px; text-align: right;"><?= $pctPerdida ?>%</span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabla 2: Listado Detallado de Incidentes de Merma -->
  <div class="table-card">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Detalle de Artículos Mermados en el Período
        </h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
          Trazabilidad completa con responsable, fecha exacta y costo unitario.
        </p>
      </div>

      <!-- Filtros en vivo -->
      <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
        <input type="text" id="buscadorMermas" oninput="filtrarMermasLive()" class="form-control" placeholder="Buscar producto o código..." style="padding: 0.4rem 0.75rem; font-size: 0.82rem; width: 220px;">
        <select id="filtroMotivoMerma" onchange="filtrarMermasLive()" class="form-control" style="padding: 0.4rem 0.75rem; font-size: 0.82rem; width: auto;">
          <option value="">Todos los motivos</option>
          <option value="vencimiento">Vencimiento</option>
          <option value="daño">Daño / Rotura</option>
          <option value="pérdida">Pérdida / Descuadre</option>
          <option value="consumo">Consumo Interno</option>
          <option value="conteo">Conteo Físico</option>
        </select>
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" id="tablaDetalleMermas">
        <thead>
          <tr>
            <th style="width: 45px; text-align: center;">#</th>
            <th style="min-width: 130px;">Fecha</th>
            <th style="min-width: 120px;">Código</th>
            <th style="min-width: 180px;">Producto</th>
            <th>Categoría</th>
            <th style="text-align: center;">Cant. Baja</th>
            <th style="text-align: right;">Costo Unit.</th>
            <th style="text-align: right; color: var(--danger);">Pérdida Costo ($)</th>
            <th style="text-align: right; color: var(--warning);">Venta Perdida</th>
            <th>Motivo Registrado</th>
            <th>Usuario</th>
          </tr>
        </thead>
        <tbody id="tbodyMermas">
          <?php if (empty($detalleMermas)): ?>
            <tr><td colspan="11" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No se encontraron mermas para el período seleccionado.</td></tr>
          <?php else: ?>
            <?php $i = 1; foreach ($detalleMermas as $dm): ?>
              <?php 
                $motivoLower = mb_strtolower($dm['Motivo']);
                $badgeMotivo = 'badge-secondary';
                if (str_contains($motivoLower, 'vencimiento')) $badgeMotivo = 'badge-danger';
                elseif (str_contains($motivoLower, 'daño') || str_contains($motivoLower, 'rotura')) $badgeMotivo = 'badge-warning';
                elseif (str_contains($motivoLower, 'consumo')) $badgeMotivo = 'badge-info';
              ?>
              <tr class="fila-merma" 
                  data-nombre="<?= htmlspecialchars(mb_strtolower($dm['Producto'])) ?>" 
                  data-codigo="<?= htmlspecialchars(mb_strtolower($dm['CodigoBarras'] . ' ' . ($dm['CodigoPLU'] ?? ''))) ?>"
                  data-motivo="<?= htmlspecialchars($motivoLower) ?>"
                  data-cant="<?= (float)$dm['Cantidad'] ?>"
                  data-costo-total="<?= (int)$dm['PerdidaCosto'] ?>"
                  data-venta-total="<?= (int)$dm['PerdidaVenta'] ?>">
                
                <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;"><?= $i++ ?></td>
                <td style="color: var(--text-muted); font-size: 0.82rem; white-space: nowrap;">
                  <?= date('d/m/Y H:i', strtotime($dm['FechaAjuste'])) ?>
                  <span style="font-size: 0.72rem; color: #818cf8; display: block;">#Ajuste <?= $dm['AjusteStockID'] ?></span>
                </td>
                <td>
                  <code style="font-size: 0.8rem;"><?= htmlspecialchars($dm['CodigoBarras'] ?: ($dm['CodigoPLU'] ? 'PLU:'.$dm['CodigoPLU'] : 'S/C')) ?></code>
                </td>
                <td>
                  <strong style="color: var(--text-main); font-size: 0.88rem;"><?= htmlspecialchars($dm['Producto']) ?></strong>
                </td>
                <td style="color: var(--text-muted); font-size: 0.82rem;"><?= htmlspecialchars($dm['Categoria']) ?></td>
                <td style="text-align: center;">
                  <span class="badge badge-danger" style="font-weight: 700; font-size: 0.82rem;">-<?= (float)$dm['Cantidad'] ?> u</span>
                </td>
                <td style="text-align: right; font-family: monospace; font-size: 0.85rem; color: #fbbf24;">
                  <?= formatCLP($dm['CostoUnitario']) ?>
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--danger); font-size: 0.9rem;">
                  <?= formatCLP($dm['PerdidaCosto']) ?>
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 600; color: var(--warning); font-size: 0.85rem;">
                  <?= formatCLP($dm['PerdidaVenta']) ?>
                </td>
                <td>
                  <span class="badge <?= $badgeMotivo ?>" style="font-size: 0.75rem;">
                    <?= htmlspecialchars($dm['Motivo']) ?>
                  </span>
                  <?php if (!empty($dm['DocReferencia'])): ?>
                    <small style="color: var(--text-muted); display: block; font-size: 0.72rem;">Ref: <?= htmlspecialchars($dm['DocReferencia']) ?></small>
                  <?php endif; ?>
                </td>
                <td style="color: var(--text-muted); font-size: 0.82rem;"><?= htmlspecialchars($dm['Usuario']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot style="background: rgba(15,23,42,0.6); font-weight: 700; border-top: 1px solid var(--border-dark);">
          <tr>
            <td colspan="5" style="padding: 0.85rem 1rem;">TOTAL VISIBLE:</td>
            <td style="text-align: center; color: var(--danger);" id="tfMermasCant"><?= number_format((float)$kpiMermas['TotalUnidadesMermadas'], 0, ',', '.') ?> u</td>
            <td></td>
            <td style="text-align: right; color: var(--danger); font-family: monospace; font-size: 1rem;" id="tfMermasCosto"><?= formatCLP($kpiMermas['TotalPerdidaCosto']) ?></td>
            <td style="text-align: right; color: var(--warning); font-family: monospace;" id="tfMermasVenta"><?= formatCLP($kpiMermas['TotalPerdidaVenta']) ?></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <script>
  function filtrarMermasLive() {
    const q = (document.getElementById('buscadorMermas')?.value || '').toLowerCase().trim();
    const motivoFiltro = (document.getElementById('filtroMotivoMerma')?.value || '').toLowerCase().trim();
    const filas = document.querySelectorAll('.fila-merma');
    
    let sumCant = 0;
    let sumCosto = 0;
    let sumVenta = 0;

    filas.forEach((tr) => {
      const nombre = tr.getAttribute('data-nombre') || '';
      const codigo = tr.getAttribute('data-codigo') || '';
      const motivo = tr.getAttribute('data-motivo') || '';
      const cant = parseFloat(tr.getAttribute('data-cant') || '0');
      const costo = parseFloat(tr.getAttribute('data-costo-total') || '0');
      const venta = parseFloat(tr.getAttribute('data-venta-total') || '0');

      let visible = true;
      if (q && !nombre.includes(q) && !codigo.includes(q)) visible = false;
      if (visible && motivoFiltro && !motivo.includes(motivoFiltro)) visible = false;

      if (visible) {
        tr.style.display = '';
        sumCant += cant;
        sumCosto += costo;
        sumVenta += venta;
      } else {
        tr.style.display = 'none';
      }
    });

    const fmtCLP = (num) => '$' + Math.round(num).toLocaleString('es-CL');
    const tfCant = document.getElementById('tfMermasCant');
    if (tfCant) tfCant.textContent = sumCant.toLocaleString('es-CL') + ' u';
    const tfCosto = document.getElementById('tfMermasCosto');
    if (tfCosto) tfCosto.textContent = fmtCLP(sumCosto);
    const tfVenta = document.getElementById('tfMermasVenta');
    if (tfVenta) tfVenta.textContent = fmtCLP(sumVenta);
  }
  </script>

<?php endif; ?>



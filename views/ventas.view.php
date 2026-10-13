<style>
.cuadratura-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  border-color: rgba(99, 102, 241, 0.6) !important;
}
</style>

<!-- Encabezado y Filtros Rápidos -->
<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.35rem 0; display: flex; align-items: center; gap: 0.5rem;">
      <i class="fa-solid fa-receipt" style="color: var(--primary);"></i> Historial de Ventas y Comprobantes
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
      Consulta detallada de operaciones, reimpresión de tickets térmicos (80mm), descarga de boletas electrónicas DTE y anulación autorizada.
    </p>
  </div>

  <!-- Botones de Rango Rápido -->
  <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;" onclick="setPresetFecha('hoy')">
      <i class="fa-regular fa-calendar"></i> Hoy
    </button>
    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;" onclick="setPresetFecha('ayer')">
      Ayer
    </button>
    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;" onclick="setPresetFecha('semana')">
      Últimos 7 Días
    </button>
    <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;" onclick="setPresetFecha('mes')">
      Este Mes
    </button>
  </div>
</div>

<!-- Tarjetas de Resumen / KPIs -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
  <div class="stat-card" style="border-left: 4px solid var(--success);">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Recaudado</span>
      <i class="fa-solid fa-cash-register" style="color: var(--success); font-size: 1.25rem;"></i>
    </div>
    <div style="font-size: 1.5rem; font-weight: 700; color: var(--success); margin: 0.4rem 0 0.2rem 0;">
      <?= formatCLP($totalRecaudado) ?>
    </div>
    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= $totalVentasCount ?> ventas completadas</span>
  </div>

  <div class="stat-card" style="border-left: 4px solid #6366f1;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Ticket Promedio</span>
      <i class="fa-solid fa-chart-line" style="color: #6366f1; font-size: 1.25rem;"></i>
    </div>
    <div style="font-size: 1.5rem; font-weight: 700; color: #6366f1; margin: 0.4rem 0 0.2rem 0;">
      <?= formatCLP($ticketPromedio) ?>
    </div>
    <span style="font-size: 0.8rem; color: var(--text-muted);">Por compra registrada</span>
  </div>

  <div class="stat-card" style="border-left: 4px solid #f59e0b;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Ventas Anuladas</span>
      <i class="fa-solid fa-ban" style="color: #f59e0b; font-size: 1.25rem;"></i>
    </div>
    <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin: 0.4rem 0 0.2rem 0;">
      <?= $totalAnuladasCount ?>
    </div>
    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= formatCLP($totalAnuladoMonto) ?> anulado</span>
  </div>

  <div class="stat-card" style="border-left: 4px solid #0284c7;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Período Consultado</span>
      <i class="fa-solid fa-calendar-days" style="color: #0284c7; font-size: 1.25rem;"></i>
    </div>
    <div style="font-size: 1.05rem; font-weight: 700; color: var(--text); margin: 0.6rem 0 0.2rem 0;">
      <?= date('d/m/Y', strtotime($desde)) ?> &rarr; <?= date('d/m/Y', strtotime($hasta)) ?>
    </div>
    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= count($ventas) ?> transacciones totales</span>
  </div>
</div>

<!-- Barra de Resumen de Cuadratura por Medio de Pago -->
<div class="table-card" style="margin-bottom: 1.5rem; padding: 1.15rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
    <div style="display: flex; align-items: center; gap: 0.6rem;">
      <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99, 102, 241, 0.15); display: flex; align-items: center; justify-content: center; color: #818cf8; font-size: 0.95rem;">
        <i class="fa-solid fa-scale-balanced"></i>
      </div>
      <div>
        <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.4rem;">
          Cuadratura de Medios de Pago
        </h3>
        <span style="font-size: 0.75rem; color: var(--text-muted);">
          Total recaudado en ventas completadas del período (haz clic en cualquier tarjeta para filtrar)
        </span>
      </div>
    </div>
    
    <div style="font-size: 0.8rem; color: var(--text-muted); background: rgba(255,255,255,0.03); padding: 0.3rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-dark);">
      <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($desde)) ?> &rarr; <?= date('d/m/Y', strtotime($hasta)) ?>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem;">
    <!-- Tarjeta Todos -->
    <div onclick="filtrarPorMedio('Todos')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Todos' ? 'rgba(99, 102, 241, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Todos' ? '#6366f1' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: <?= $metodoPago === 'Todos' ? '#818cf8' : 'var(--text-muted)' ?>;">
          <i class="fa-solid fa-list-check"></i> Todos
        </span>
        <?php if ($metodoPago === 'Todos'): ?>
          <i class="fa-solid fa-circle-check" style="color: #818cf8; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: var(--text);">
        <?= formatCLP($totalRecaudado) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalVentasCount ?> venta(s)
      </div>
    </div>

    <!-- Efectivo -->
    <div onclick="filtrarPorMedio('Efectivo')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Efectivo' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Efectivo' ? '#10b981' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #10b981;">
          <i class="fa-solid fa-money-bill-wave"></i> Efectivo
        </span>
        <?php if ($metodoPago === 'Efectivo'): ?>
          <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #10b981;">
        <?= formatCLP($totalesCuadratura['Efectivo']['total']) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalesCuadratura['Efectivo']['cant'] ?> pago(s) en caja
      </div>
    </div>

    <!-- Tarjetas -->
    <div onclick="filtrarPorMedio('Tarjeta')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Tarjeta' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Tarjeta' ? '#3b82f6' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #3b82f6;">
          <i class="fa-solid fa-credit-card"></i> Tarjetas
        </span>
        <?php if ($metodoPago === 'Tarjeta'): ?>
          <i class="fa-solid fa-circle-check" style="color: #3b82f6; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #3b82f6;">
        <?= formatCLP($totalesCuadratura['Tarjeta']['total']) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalesCuadratura['Tarjeta']['cant'] ?> transb./tarjetas
      </div>
    </div>

    <!-- Transferencia -->
    <div onclick="filtrarPorMedio('Transferencia')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Transferencia' ? 'rgba(6, 182, 212, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Transferencia' ? '#06b6d4' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #06b6d4;">
          <i class="fa-solid fa-building-columns"></i> Transferencia
        </span>
        <?php if ($metodoPago === 'Transferencia'): ?>
          <i class="fa-solid fa-circle-check" style="color: #06b6d4; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #06b6d4;">
        <?= formatCLP($totalesCuadratura['Transferencia']['total']) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalesCuadratura['Transferencia']['cant'] ?> transf.
      </div>
    </div>

    <!-- Fiado / Crédito -->
    <div onclick="filtrarPorMedio('Credito Interno')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Credito Interno' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Credito Interno' ? '#f59e0b' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #f59e0b;">
          <i class="fa-solid fa-handshake"></i> Fiado / Crédito
        </span>
        <?php if ($metodoPago === 'Credito Interno'): ?>
          <i class="fa-solid fa-circle-check" style="color: #f59e0b; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #f59e0b;">
        <?= formatCLP($totalesCuadratura['Credito Interno']['total']) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalesCuadratura['Credito Interno']['cant'] ?> fiado(s)
      </div>
    </div>

    <?php if ($totalesCuadratura['Puntos']['total'] > 0 || $totalesCuadratura['Vale Devolucion']['total'] > 0): ?>
      <?php if ($totalesCuadratura['Puntos']['total'] > 0): ?>
        <div onclick="filtrarPorMedio('Puntos')" 
             style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Puntos' ? 'rgba(234, 179, 8, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Puntos' ? '#eab308' : 'var(--border-dark)' ?>; transition: all 0.2s;"
             class="cuadratura-chip">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #eab308;">
              <i class="fa-solid fa-star"></i> Puntos
            </span>
            <?php if ($metodoPago === 'Puntos'): ?>
              <i class="fa-solid fa-circle-check" style="color: #eab308; font-size: 0.75rem;"></i>
            <?php endif; ?>
          </div>
          <div style="font-size: 1.15rem; font-weight: 700; color: #eab308;">
            <?= formatCLP($totalesCuadratura['Puntos']['total']) ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
            <?= $totalesCuadratura['Puntos']['cant'] ?> canje(s)
          </div>
        </div>
      <?php endif; ?>

      <?php if ($totalesCuadratura['Vale Devolucion']['total'] > 0): ?>
        <div onclick="filtrarPorMedio('Vale Devolucion')" 
             style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Vale Devolucion' ? 'rgba(236, 72, 153, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Vale Devolucion' ? '#ec4899' : 'var(--border-dark)' ?>; transition: all 0.2s;"
             class="cuadratura-chip">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #ec4899;">
              <i class="fa-solid fa-ticket"></i> Vales Dev.
            </span>
            <?php if ($metodoPago === 'Vale Devolucion'): ?>
              <i class="fa-solid fa-circle-check" style="color: #ec4899; font-size: 0.75rem;"></i>
            <?php endif; ?>
          </div>
          <div style="font-size: 1.15rem; font-weight: 700; color: #ec4899;">
            <?= formatCLP($totalesCuadratura['Vale Devolucion']['total']) ?>
          </div>
          <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
            <?= $totalesCuadratura['Vale Devolucion']['cant'] ?> vale(s)
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Pagos Mixtos -->
    <div onclick="filtrarPorMedio('Mixto')" 
         style="cursor: pointer; padding: 0.75rem; border-radius: 10px; background: <?= $metodoPago === 'Mixto' ? 'rgba(139, 92, 246, 0.15)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $metodoPago === 'Mixto' ? '#8b5cf6' : 'var(--border-dark)' ?>; transition: all 0.2s;"
         class="cuadratura-chip">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #a78bfa;">
          <i class="fa-solid fa-arrows-split-up-and-left"></i> Pagos Mixtos
        </span>
        <?php if ($metodoPago === 'Mixto'): ?>
          <i class="fa-solid fa-circle-check" style="color: #a78bfa; font-size: 0.75rem;"></i>
        <?php endif; ?>
      </div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #a78bfa;">
        <?= formatCLP($totalesCuadratura['Mixto']['total']) ?>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
        <?= $totalesCuadratura['Mixto']['cant'] ?> venta(s) divididas
      </div>
    </div>
  </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="table-card" style="margin-bottom: 1.5rem; padding: 1.15rem;">
  <form method="GET" action="ventas.php" id="formFiltroVentas" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: flex-end;">
    <div style="flex: 1; min-width: 130px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">DESDE</label>
      <input type="date" name="desde" id="filtroDesde" value="<?= htmlspecialchars($desde) ?>" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
    </div>

    <div style="flex: 1; min-width: 130px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">HASTA</label>
      <input type="date" name="hasta" id="filtroHasta" value="<?= htmlspecialchars($hasta) ?>" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
    </div>

    <div style="min-width: 150px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">MEDIO DE PAGO</label>
      <select name="metodo_pago" id="selectMetodoPago" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" onchange="document.getElementById('formFiltroVentas').submit()">
        <option value="Todos" <?= $metodoPago === 'Todos' ? 'selected' : '' ?>>Todos los medios</option>
        <option value="Efectivo" <?= $metodoPago === 'Efectivo' ? 'selected' : '' ?>>💵 Efectivo</option>
        <option value="Tarjeta" <?= $metodoPago === 'Tarjeta' ? 'selected' : '' ?>>💳 Tarjetas (Débito/Crédito)</option>
        <option value="Transferencia" <?= $metodoPago === 'Transferencia' ? 'selected' : '' ?>>🏦 Transferencia</option>
        <option value="Credito Interno" <?= $metodoPago === 'Credito Interno' ? 'selected' : '' ?>>🤝 Fiado / Crédito</option>
        <option value="Puntos" <?= $metodoPago === 'Puntos' ? 'selected' : '' ?>>⭐ Puntos Fidelidad</option>
        <option value="Vale Devolucion" <?= $metodoPago === 'Vale Devolucion' ? 'selected' : '' ?>>🎟️ Vale Devolución</option>
        <option value="Mixto" <?= $metodoPago === 'Mixto' ? 'selected' : '' ?>>🔀 Pago Mixto (Combinado)</option>
      </select>
    </div>

    <div style="min-width: 130px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">TIPO DOC.</label>
      <select name="tipo_doc" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
        <option value="Todos" <?= $tipoDoc === 'Todos' ? 'selected' : '' ?>>Todos los docs</option>
        <option value="Boleta" <?= $tipoDoc === 'Boleta' ? 'selected' : '' ?>>Boleta</option>
        <option value="Factura" <?= $tipoDoc === 'Factura' ? 'selected' : '' ?>>Factura</option>
        <option value="Ticket" <?= $tipoDoc === 'Ticket' ? 'selected' : '' ?>>Ticket Interno</option>
      </select>
    </div>

    <div style="min-width: 120px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">ESTADO</label>
      <select name="estado" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
        <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todos</option>
        <option value="Completada" <?= $estado === 'Completada' ? 'selected' : '' ?>>Completadas</option>
        <option value="Anulada" <?= $estado === 'Anulada' ? 'selected' : '' ?>>Anuladas</option>
      </select>
    </div>

    <div style="flex: 2; min-width: 200px;">
      <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.35rem;">BUSCAR POR N° / FOLIO / CLIENTE / RUT</label>
      <div style="position: relative;">
        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: #92, 51798, Juan, 12345678..." class="form-control" style="padding: 0.45rem 0.75rem 0.45rem 2.1rem; font-size: 0.85rem;">
      </div>
    </div>

    <div style="display: flex; gap: 0.4rem;">
      <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.85rem;">
        <i class="fa-solid fa-filter"></i> Filtrar
      </button>
      <?php if ($q !== '' || $tipoDoc !== 'Todos' || $estado !== 'Todos' || $metodoPago !== 'Todos' || $desde !== date('Y-m-d') || $hasta !== date('Y-m-d')): ?>
        <a href="ventas.php" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;" title="Restablecer filtros">
          <i class="fa-solid fa-rotate-left"></i>
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Tabla de Ventas -->
<div class="table-card">
  <div class="table-header">
    <div style="display: flex; align-items: center; gap: 0.6rem;">
      <h2 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Registro de Ventas</h2>
      <span class="badge" style="background: var(--card-sub); color: var(--text-muted); font-size: 0.8rem;">
        <?= count($ventas) ?> encontrados
      </span>
    </div>
    <span style="color: var(--text-muted); font-size: 0.85rem;">
      Del <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?>
    </span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th style="width: 110px;">N° Venta / Folio</th>
          <th>Fecha y Hora</th>
          <th>Cajero</th>
          <th>Cliente</th>
          <th>Documento</th>
          <th>Medio de Pago</th>
          <th style="text-align: right;">Total</th>
          <th style="text-align: center;">Estado</th>
          <th style="text-align: right; min-width: 240px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ventas)): ?>
          <tr>
            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
              <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; opacity: 0.5;"></i>
              No se encontraron ventas para los filtros seleccionados.<br>
              <span style="font-size: 0.85rem;">Prueba ampliando el rango de fechas o limpiando los criterios de búsqueda.</span>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($ventas as $v): ?>
            <?php 
              $isDte = in_array($v['TipoDocumento'], ['Boleta', 'Factura'], true);
              $folioDisplay = !empty($v['FolioDoc']) ? $v['FolioDoc'] : null;
              $hasPdf = !empty($v['DtePdfUrl']);
            ?>
            <tr>
              <td>
                <strong style="color: var(--text);">#<?= $v['VentaID'] ?></strong>
                <?php if ($folioDisplay): ?>
                  <div style="font-size: 0.72rem; color: var(--primary); font-weight: 600;">
                    <i class="fa-solid fa-file-invoice"></i> Folio <?= htmlspecialchars($folioDisplay) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-size: 0.85rem;">
                <div style="font-weight: 600;"><?= date('d/m/Y', strtotime($v['FechaVenta'])) ?></div>
                <div style="color: var(--text-muted); font-size: 0.78rem;"><?= date('H:i:s', strtotime($v['FechaVenta'])) ?></div>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem;">
                  <i class="fa-solid fa-user-tie" style="color: var(--text-muted); font-size: 0.8rem;"></i>
                  <span><?= htmlspecialchars($v['Cajero']) ?></span>
                </div>
              </td>
              <td>
                <?php if (!empty($v['ClienteNombre'])): ?>
                  <div style="font-weight: 600; font-size: 0.85rem; color: var(--text);"><?= htmlspecialchars($v['ClienteNombre']) ?></div>
                  <?php if (!empty($v['RutCuerpo'])): ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= number_format((int)$v['RutCuerpo'], 0, '', '.') . '-' . $v['RutDv'] ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: var(--text-muted); font-size: 0.85rem;">Público General</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($v['TipoDocumento'] === 'Factura'): ?>
                  <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">
                    <i class="fa-solid fa-file-invoice"></i> Factura
                  </span>
                <?php elseif ($v['TipoDocumento'] === 'Boleta'): ?>
                  <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fa-solid fa-receipt"></i> Boleta
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3);">
                    Ticket Interno
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-size: 0.82rem;">
                <?php
                  $desgloseRaw = $v['DesglosePagos'] ?? '';
                  $itemsPago = [];
                  if (!empty($desgloseRaw)) {
                      $partes = explode('||', $desgloseRaw);
                      foreach ($partes as $pRaw) {
                          $sub = explode(':', $pRaw, 2);
                          if (count($sub) === 2) {
                              $itemsPago[] = ['metodo' => trim($sub[0]), 'monto' => (int)$sub[1]];
                          }
                      }
                  }
                  if (empty($itemsPago)) {
                      $itemsPago[] = ['metodo' => $v['MetodosPago'] ?: 'Efectivo', 'monto' => (int)$v['MontoTotal']];
                  }
                  $esMixto = count($itemsPago) > 1;
                ?>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                  <?php if ($esMixto): ?>
                    <span class="badge" style="background: rgba(139, 92, 246, 0.18); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.35); font-size: 0.7rem; align-self: flex-start; padding: 0.15rem 0.4rem;">
                      <i class="fa-solid fa-arrows-split-up-and-left"></i> Pago Mixto
                    </span>
                  <?php endif; ?>

                  <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                    <?php foreach ($itemsPago as $ip): ?>
                      <?php
                        $mLower = strtolower($ip['metodo']);
                        $badgeBg = 'rgba(148, 163, 184, 0.15)';
                        $badgeColor = '#94a3b8';
                        $badgeBorder = 'rgba(148, 163, 184, 0.3)';
                        $icon = 'fa-money-bill';

                        if (strpos($mLower, 'efectivo') !== false) {
                            $badgeBg = 'rgba(16, 185, 129, 0.15)';
                            $badgeColor = '#34d399';
                            $badgeBorder = 'rgba(16, 185, 129, 0.3)';
                            $icon = 'fa-money-bill-wave';
                        } elseif (strpos($mLower, 'debito') !== false) {
                            $badgeBg = 'rgba(59, 130, 246, 0.15)';
                            $badgeColor = '#60a5fa';
                            $badgeBorder = 'rgba(59, 130, 246, 0.3)';
                            $icon = 'fa-credit-card';
                        } elseif (strpos($mLower, 'credito') !== false && (strpos($mLower, 'tarjeta') !== false || strpos($mLower, 'transbank') !== false)) {
                            $badgeBg = 'rgba(99, 102, 241, 0.15)';
                            $badgeColor = '#818cf8';
                            $badgeBorder = 'rgba(99, 102, 241, 0.3)';
                            $icon = 'fa-credit-card';
                        } elseif (strpos($mLower, 'tarjeta') !== false || strpos($mLower, 'transbank') !== false) {
                            $badgeBg = 'rgba(59, 130, 246, 0.15)';
                            $badgeColor = '#60a5fa';
                            $badgeBorder = 'rgba(59, 130, 246, 0.3)';
                            $icon = 'fa-credit-card';
                        } elseif (strpos($mLower, 'transfer') !== false) {
                            $badgeBg = 'rgba(6, 182, 212, 0.15)';
                            $badgeColor = '#22d3ee';
                            $badgeBorder = 'rgba(6, 182, 212, 0.3)';
                            $icon = 'fa-building-columns';
                        } elseif (strpos($mLower, 'interno') !== false || strpos($mLower, 'fiado') !== false || $mLower === 'credito') {
                            $badgeBg = 'rgba(245, 158, 11, 0.15)';
                            $badgeColor = '#fbbf24';
                            $badgeBorder = 'rgba(245, 158, 11, 0.3)';
                            $icon = 'fa-handshake';
                        } elseif (strpos($mLower, 'punto') !== false) {
                            $badgeBg = 'rgba(234, 179, 8, 0.15)';
                            $badgeColor = '#fde047';
                            $badgeBorder = 'rgba(234, 179, 8, 0.3)';
                            $icon = 'fa-star';
                        } elseif (strpos($mLower, 'vale') !== false) {
                            $badgeBg = 'rgba(236, 72, 153, 0.15)';
                            $badgeColor = '#f472b6';
                            $badgeBorder = 'rgba(236, 72, 153, 0.3)';
                            $icon = 'fa-ticket';
                        }
                      ?>
                      <span class="badge" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border: 1px solid <?= $badgeBorder ?>; font-size: 0.76rem; font-weight: 600; padding: 0.2rem 0.5rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="<?= htmlspecialchars($ip['metodo']) ?>: <?= formatCLP($ip['monto']) ?>">
                        <i class="fa-solid <?= $icon ?>"></i>
                        <span><?= htmlspecialchars($ip['metodo']) ?></span>
                        <span style="opacity: 0.95; font-weight: 700;"><?= formatCLP($ip['monto']) ?></span>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </td>
              <td style="text-align: right; font-weight: 700; font-size: 0.95rem; color: <?= $v['Estado'] === 'Completada' ? 'var(--success)' : 'var(--danger)' ?>;">
                <?= formatCLP($v['MontoTotal']) ?>
              </td>
              <td style="text-align: center;">
                <span class="badge <?= $v['Estado'] == 'Completada' ? 'badge-success' : 'badge-danger' ?>">
                  <?= $v['Estado'] ?>
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <!-- Ver Detalle Completo -->
                <button type="button" onclick="verDetalleVenta(<?= $v['VentaID'] ?>)" class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; margin-right: 0.2rem;" title="Ver detalle completo de la venta">
                  <i class="fa-solid fa-eye" style="color: var(--primary);"></i> Ver
                </button>

                <!-- Reimprimir Ticket Térmico -->
                <button type="button" onclick="reimprimirTicketVenta(<?= $v['VentaID'] ?>)" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; margin-right: 0.2rem;" title="Imprimir Ticket Térmico (80mm)">
                  <i class="fa-solid fa-print"></i> Ticket
                </button>

                <!-- Botón Boleta SII si existe PDF -->
                <?php if ($hasPdf): ?>
                  <button type="button" onclick="imprimirPdfDirecto('<?= htmlspecialchars($v['DtePdfUrl']) ?>')" class="btn btn-success" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; margin-right: 0.2rem;" title="Ver o Imprimir Boleta Electrónica SII (PDF)">
                    <i class="fa-solid fa-file-pdf"></i> DTE
                  </button>
                <?php endif; ?>

                <!-- Anular Venta -->
                <?php if ($v['Estado'] === 'Completada'): ?>
                  <button type="button" onclick="anularVenta(<?= $v['VentaID'] ?>)" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.8rem;" title="Anular venta y devolver stock">
                    <i class="fa-solid fa-ban"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ==========================================================================
     MODAL DETALLE COMPLETO DE VENTA (#modalDetalleVenta)
     ========================================================================== -->
<div id="modalDetalleVenta" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(6px); z-index: 2000; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 850px; max-width: 100%; max-height: 92vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); overflow: hidden;">
    
    <!-- Modal Header -->
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-dark); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2);">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99, 102, 241, 0.15); display: flex; align-items: center; justify-content: center; color: #818cf8; font-size: 1.2rem;">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
          <h2 id="modalDetalleVentaTitulo" style="font-size: 1.2rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            Venta #00
          </h2>
          <span id="modalDetalleVentaFecha" style="color: var(--text-muted); font-size: 0.8rem;"></span>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 0.6rem;">
        <span id="modalDetalleVentaBadgeEstado" class="badge"></span>
        <button type="button" onclick="cerrarDetalleVenta()" style="background: none; border: none; color: var(--text-muted); font-size: 1.35rem; cursor: pointer; padding: 0.25rem 0.5rem; line-height: 1;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>

    <!-- Modal Body (Desplazable) -->
    <div style="padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1.25rem;">
      
      <!-- Fila de Tarjetas Informativas (Venta, Cliente, Documento) -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
        <!-- Tarjeta Venta / Cajero -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1rem;">
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-cash-register"></i> Operación de Caja
          </div>
          <div style="font-size: 0.85rem; line-height: 1.6;">
            <div><strong>Cajero:</strong> <span id="mDetCajero"></span></div>
            <div><strong>Turno N°:</strong> <span id="mDetTurno"></span></div>
            <div><strong>Documento:</strong> <span id="mDetDocTipo" class="badge badge-primary"></span></div>
          </div>
        </div>

        <!-- Tarjeta Cliente -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1rem;">
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-user"></i> Datos del Cliente
          </div>
          <div style="font-size: 0.85rem; line-height: 1.6;" id="mDetClienteBox">
            <div><strong>Nombre:</strong> <span id="mDetClienteNombre">Público General</span></div>
            <div id="mDetClienteRutRow" style="display: none;"><strong>RUT:</strong> <span id="mDetClienteRut"></span></div>
            <div id="mDetClienteFonoRow" style="display: none;"><strong>Fono:</strong> <span id="mDetClienteFono"></span></div>
          </div>
        </div>

        <!-- Tarjeta Documento Tributario (DTE) -->
        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1rem;">
          <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-stamp"></i> Emisión Tributaria SII
          </div>
          <div style="font-size: 0.85rem; line-height: 1.6;" id="mDetDteBox">
            <div><strong>Folio DTE:</strong> <span id="mDetDteFolio">-</span></div>
            <div><strong>Estado SII:</strong> <span id="mDetDteEstado" class="badge badge-success">Aceptado</span></div>
            <div id="mDetDteTrackRow" style="display: none;"><strong>Track ID:</strong> <span id="mDetDteTrack" style="font-family: monospace; font-size: 0.75rem;"></span></div>
          </div>
        </div>
      </div>

      <!-- Tabla de Productos de la Venta -->
      <div>
        <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center;">
          <span><i class="fa-solid fa-boxes-stacked"></i> Productos Vendidos</span>
          <span id="mDetTotalItems" style="color: var(--text-muted); font-size: 0.8rem;">0 ítems</span>
        </div>
        <div style="border: 1px solid var(--border-dark); border-radius: 10px; overflow: hidden;">
          <table class="table" style="margin: 0; font-size: 0.85rem;">
            <thead style="background: rgba(0,0,0,0.25);">
              <tr>
                <th>Producto</th>
                <th style="width: 90px; text-align: center;">Cantidad</th>
                <th style="width: 110px; text-align: right;">Precio Unit.</th>
                <th style="width: 100px; text-align: right;">Dcto.</th>
                <th style="width: 120px; text-align: right;">Subtotal</th>
              </tr>
            </thead>
            <tbody id="mDetTablaProductos">
              <!-- Renderizado dinámico -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Fila de Desglose Económico y Medios de Pago -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">
        
        <!-- Desglose de Medios de Pago y Fiado -->
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1.15rem;">
          <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-credit-card"></i> Desglose de Pagos
          </div>

          <div id="mDetPagosLista" style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 0.85rem;">
            <!-- Renderizado dinámico -->
          </div>

          <div style="border-top: 1px dashed var(--border-dark); padding-top: 0.6rem; display: flex; justify-content: space-between; font-size: 0.85rem;">
            <span>Monto Recibido:</span>
            <strong id="mDetPagado"></strong>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-top: 0.25rem;">
            <span>Vuelto Entregado:</span>
            <strong id="mDetVuelto" style="color: #38bdf8;"></strong>
          </div>

          <!-- Cuadro de Crédito / Fiado si aplica -->
          <div id="mDetCreditoBox" style="display: none; margin-top: 0.85rem; padding: 0.75rem; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; font-size: 0.8rem;">
            <div style="font-weight: 700; color: #f59e0b; margin-bottom: 0.35rem;">
              <i class="fa-solid fa-handshake"></i> Venta con Crédito Interno (Fiado)
            </div>
            <div>Monto Cargado al Cliente: <strong id="mDetCredMonto"></strong></div>
            <div>Saldo Deudor Actual: <strong id="mDetCredSaldo"></strong></div>
            <div>Cupo Disponible: <strong id="mDetCredCupo"></strong></div>
          </div>
        </div>

        <!-- Totales e Impuestos (Neto, IVA, Total) -->
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between;">
          <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-calculator"></i> Resumen de Totales
          </div>

          <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Monto Neto (Afecto):</span>
              <span id="mDetNeto"></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">IVA (19%):</span>
              <span id="mDetIva"></span>
            </div>
            <div id="mDetDescuentoRow" style="display: none; justify-content: space-between; color: var(--danger);">
              <span>Descuento Global:</span>
              <span id="mDetDescuento"></span>
            </div>
            
            <div style="border-top: 2px solid var(--border-dark); margin-top: 0.5rem; padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 1.1rem; font-weight: 700;">TOTAL FINAL:</span>
              <span id="mDetTotal" style="font-size: 1.4rem; font-weight: 800; color: var(--success);"></span>
            </div>
          </div>

          <div id="mDetPuntosBox" style="margin-top: 0.85rem; font-size: 0.78rem; color: #818cf8; text-align: right;">
            <i class="fa-solid fa-star"></i> Puntos generados: <strong id="mDetPuntos"></strong> pts
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Footer Actions -->
    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-dark); background: rgba(0,0,0,0.25); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
      <div style="display: flex; gap: 0.5rem;">
        <button type="button" id="btnModalAnular" class="btn btn-danger" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
          <i class="fa-solid fa-ban"></i> Anular Venta
        </button>
      </div>

      <div style="display: flex; gap: 0.5rem;">
        <button type="button" id="btnModalDtePdf" class="btn btn-success" style="font-size: 0.85rem; padding: 0.5rem 1rem; display: none;">
          <i class="fa-solid fa-file-pdf"></i> Boleta Electrónica SII
        </button>
        <button type="button" id="btnModalTicket" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
          <i class="fa-solid fa-print"></i> Reimprimir Ticket Térmico
        </button>
        <button type="button" onclick="cerrarDetalleVenta()" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
          Cerrar
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ==========================================================================
     MODAL DE REIMPRESIÓN TÉRMICA (80mm) (#ticketModal)
     ========================================================================== -->
<div id="ticketModal" class="ticket-overlay" style="display: none;" onclick="if (event.target === this) cerrarTicket()">
  <div class="ticket-modal__card">
    <button type="button" class="ticket-modal__close-btn no-print" onclick="cerrarTicket()" title="Cerrar comprobante (Esc)">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="ticket-paper" id="ticketPaper">
      <!-- Encabezado del Local -->
      <div class="tk-center">
        <div class="tk-strong tk-lg" id="tkEmpresaNombre"><?= htmlspecialchars($cfgLocal['MINIMARKET_NOMBRE'] ?? 'MINIMARKET') ?></div>
        <div id="tkEmpresaGiro"><?= htmlspecialchars($cfgLocal['MINIMARKET_GIRO'] ?? '') ?></div>
        <div id="tkEmpresaRut"><?php if (!empty($cfgLocal['MINIMARKET_RUT'])): ?>RUT: <?= htmlspecialchars($cfgLocal['MINIMARKET_RUT']) ?><?php endif; ?></div>
        <div id="tkEmpresaDir"><?= htmlspecialchars($cfgLocal['MINIMARKET_DIRECCION'] ?? '') ?></div>
        <div id="tkEmpresaFono"><?php if (!empty($cfgLocal['MINIMARKET_TELEFONO'])): ?>Fono: <?= htmlspecialchars($cfgLocal['MINIMARKET_TELEFONO']) ?><?php endif; ?></div>
      </div>

      <div class="tk-sep"></div>
      <div class="tk-row">
        <span>Comprobante de Venta</span>
        <span id="ticketVentaNum" class="tk-strong"></span>
      </div>
      <div class="tk-row">
        <span id="ticketFecha"></span>
        <span id="ticketCajero"></span>
      </div>
      <div class="tk-sep"></div>

      <!-- Detalle de Ítems -->
      <div id="ticketDetalle" class="tk-items"></div>

      <div class="tk-sep"></div>
      <div class="tk-row" id="ticketSubtotalRow" style="display: none;">
        <span>Subtotal</span>
        <span id="ticketSubtotal"></span>
      </div>
      <div class="tk-row" id="ticketDescuentoRow" style="display: none; color: #555;">
        <span>Descuento</span>
        <span id="ticketDescuento"></span>
      </div>
      <div class="tk-row tk-strong tk-lg">
        <span>TOTAL</span>
        <span id="ticketTotal"></span>
      </div>

      <div class="tk-sep"></div>
      <div id="ticketPagos" class="tk-pagos"></div>
      <div class="tk-row" id="ticketVueltoRow">
        <span>Vuelto</span>
        <span id="ticketVuelto"></span>
      </div>

      <!-- Comprobante de Crédito Interno / Fiado -->
      <div id="ticketCreditoBox" class="tk-credito" style="display: none;">
        <div class="tk-sep--strong"></div>
        <div class="tk-center tk-strong">COMPROBANTE DE CRÉDITO INTERNO</div>
        <div class="tk-row"><span>Cliente</span><span id="tkCredCliente"></span></div>
        <div class="tk-row" id="tkCredRutRow"><span>RUT</span><span id="tkCredRut"></span></div>
        <div class="tk-row"><span>Compra a crédito</span><span id="tkCredMonto"></span></div>
        <div class="tk-row tk-strong"><span>Saldo total adeudado</span><span id="tkCredSaldo"></span></div>
        <div class="tk-row"><span>Cupo disponible</span><span id="tkCredCupo"></span></div>
        <div class="tk-firma">
          <div class="tk-firma__line">&nbsp;</div>
          <div class="tk-firma__label">Firma cliente</div>
          <div class="tk-firma__line">&nbsp;</div>
          <div class="tk-firma__label">Nombre y RUT</div>
        </div>
        <div class="tk-center" style="font-size: 0.85em; margin-top: 0.35rem;">
          Declaro recibir la mercadería conforme y adeudar el monto indicado.
        </div>
      </div>

      <!-- Banner DTE Electrónico -->
      <div id="ticketDteInfo" class="tk-dte tk-center" style="display: none; margin-top: 0.6rem;">
        <div class="tk-sep"></div>
        <div class="tk-strong" id="ticketDteTitulo">BOLETA ELECTRÓNICA</div>
        <div id="ticketDteFolio">Folio: -</div>
        <div style="font-size: 0.75em; color: #555;">Timbre Electrónico SII - Res. 80 de 2014</div>
      </div>

      <div class="tk-sep"></div>
      <div class="tk-center tk-pie" id="ticketPieTexto"><?= htmlspecialchars($cfgLocal['TICKET_PIE_PAGINA'] ?? '¡Gracias por su preferencia!') ?></div>
    </div>

    <!-- Acciones del Modal de Ticket -->
    <div class="ticket-modal__actions no-print">
      <a id="ticketDtePdfBtn" href="#" target="_blank" class="btn btn-success" style="display: none;">
        <i class="fa-solid fa-file-pdf"></i> Ver PDF SII
      </a>
      <button type="button" onclick="window.print()" class="btn btn-primary">
        <i class="fa-solid fa-print"></i> Imprimir Ticket (80mm)
      </button>
      <button type="button" id="btnCerrarTicket" onclick="cerrarTicket()" class="btn btn-secondary">
        <i class="fa-solid fa-xmark"></i> Cerrar (Esc)
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================================
     SCRIPTS DE CONTROL Y REIMPRESIÓN
     ========================================================================== -->
<script>
let ventaActualData = null;

// Presets rápidos de fechas
function setPresetFecha(tipo) {
  const hoy = new Date();
  const pad = n => String(n).padStart(2, '0');
  const formatYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

  let desde = '', hasta = formatYmd(hoy);

  if (tipo === 'hoy') {
    desde = formatYmd(hoy);
  } else if (tipo === 'ayer') {
    const ayer = new Date(hoy);
    ayer.setDate(hoy.getDate() - 1);
    desde = formatYmd(ayer);
    hasta = formatYmd(ayer);
  } else if (tipo === 'semana') {
    const semana = new Date(hoy);
    semana.setDate(hoy.getDate() - 7);
    desde = formatYmd(semana);
  } else if (tipo === 'mes') {
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    desde = formatYmd(primerDia);
  }

  document.getElementById('filtroDesde').value = desde;
  document.getElementById('filtroHasta').value = hasta;
  document.getElementById('formFiltroVentas').submit();
}

// Filtro rápido por medio de pago desde la barra de cuadratura
function filtrarPorMedio(metodo) {
  const sel = document.getElementById('selectMetodoPago');
  if (sel) {
    sel.value = metodo;
    document.getElementById('formFiltroVentas').submit();
  }
}

function formatPesos(num) {
  return `$${new Intl.NumberFormat('es-CL').format(Math.round(num || 0))}`;
}

function escapeStr(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, function(m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
  });
}

// ----------------------------------------------------
// VER DETALLE COMPLETO DE VENTA (MODAL)
// ----------------------------------------------------
async function verDetalleVenta(id) {
  try {
    const res = await fetch(`api/ver_venta.php?id=${id}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    ventaActualData = data;
    const v = data.venta;
    const cli = data.cliente;
    const dte = data.dte;

    // Encabezado
    document.getElementById('modalDetalleVentaTitulo').innerHTML = `Venta #${v.id} ${v.folio ? `<span style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">(Folio ${v.folio})</span>` : ''}`;
    document.getElementById('modalDetalleVentaFecha').textContent = `${v.fecha} - Turno #${v.turno_id}`;

    const badgeEstado = document.getElementById('modalDetalleVentaBadgeEstado');
    badgeEstado.textContent = v.estado;
    badgeEstado.className = `badge ${v.estado === 'Completada' ? 'badge-success' : 'badge-danger'}`;

    // Datos de Operación
    document.getElementById('mDetCajero').textContent = v.cajero || 'Cajero';
    document.getElementById('mDetTurno').textContent = `#${v.turno_id}`;
    document.getElementById('mDetDocTipo').textContent = v.tipo_documento;

    // Datos del Cliente
    if (cli) {
      document.getElementById('mDetClienteNombre').textContent = cli.nombre;
      if (cli.rut) {
        document.getElementById('mDetClienteRut').textContent = cli.rut;
        document.getElementById('mDetClienteRutRow').style.display = 'block';
      } else {
        document.getElementById('mDetClienteRutRow').style.display = 'none';
      }
      if (cli.telefono) {
        document.getElementById('mDetClienteFono').textContent = cli.telefono;
        document.getElementById('mDetClienteFonoRow').style.display = 'block';
      } else {
        document.getElementById('mDetClienteFonoRow').style.display = 'none';
      }
    } else {
      document.getElementById('mDetClienteNombre').textContent = 'Público General';
      document.getElementById('mDetClienteRutRow').style.display = 'none';
      document.getElementById('mDetClienteFonoRow').style.display = 'none';
    }

    // Datos DTE
    if (dte) {
      document.getElementById('mDetDteFolio').textContent = dte.folio || '-';
      document.getElementById('mDetDteEstado').textContent = dte.estado_sii || 'Emitido';
      if (dte.track_id) {
        document.getElementById('mDetDteTrack').textContent = dte.track_id;
        document.getElementById('mDetDteTrackRow').style.display = 'block';
      } else {
        document.getElementById('mDetDteTrackRow').style.display = 'none';
      }
    } else {
      document.getElementById('mDetDteFolio').textContent = v.tipo_documento === 'Ticket' ? 'N/A (Ticket Interno)' : 'Sin folio registrado';
      document.getElementById('mDetDteEstado').textContent = 'Interno';
      document.getElementById('mDetDteTrackRow').style.display = 'none';
    }

    // Tabla de Productos
    const tbody = document.getElementById('mDetTablaProductos');
    document.getElementById('mDetTotalItems').textContent = `${data.detalles.length} producto(s)`;

    tbody.innerHTML = data.detalles.map((item, idx) => {
      return `
        <tr>
          <td>
            <div style="font-weight: 600;">${escapeStr(item.nombre)}</div>
            ${item.codigo_barra ? `<div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">EAN: ${escapeStr(item.codigo_barra)}</div>` : ''}
          </td>
          <td style="text-align: center; font-weight: 700;">${item.cantidad}</td>
          <td style="text-align: right;">${formatPesos(item.precio)}</td>
          <td style="text-align: right; color: ${item.descuento > 0 ? 'var(--danger)' : 'var(--text-muted)'};">
            ${item.descuento > 0 ? `-${formatPesos(item.descuento)}` : '$0'}
          </td>
          <td style="text-align: right; font-weight: 700; color: var(--text);">${formatPesos(item.subtotal)}</td>
        </tr>
      `;
    }).join('');

    // Desglose de Pagos
    const pagosLista = document.getElementById('mDetPagosLista');
    pagosLista.innerHTML = data.pagos.map(p => `
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-check-circle" style="color: var(--success); font-size: 0.8rem;"></i>
          ${escapeStr(p.metodo)}
        </span>
        <strong style="color: var(--text);">${formatPesos(p.monto)}</strong>
      </div>
    `).join('');

    document.getElementById('mDetPagado').textContent = formatPesos(v.pagado || v.total);
    document.getElementById('mDetVuelto').textContent = formatPesos(v.vuelto);

    // Crédito Box
    const creditoBox = document.getElementById('mDetCreditoBox');
    if (data.credito) {
      document.getElementById('mDetCredMonto').textContent = formatPesos(data.credito.monto);
      document.getElementById('mDetCredSaldo').textContent = formatPesos(data.credito.saldo_deudor);
      document.getElementById('mDetCredCupo').textContent = formatPesos(data.credito.cupo_disponible);
      creditoBox.style.display = 'block';
    } else {
      creditoBox.style.display = 'none';
    }

    // Totales
    document.getElementById('mDetNeto').textContent = formatPesos(v.monto_neto);
    document.getElementById('mDetIva').textContent = formatPesos(v.monto_iva);
    if (v.descuento_global > 0) {
      document.getElementById('mDetDescuento').textContent = `-${formatPesos(v.descuento_global)}`;
      document.getElementById('mDetDescuentoRow').style.display = 'flex';
    } else {
      document.getElementById('mDetDescuentoRow').style.display = 'none';
    }
    document.getElementById('mDetTotal').textContent = formatPesos(v.total);
    document.getElementById('mDetPuntos').textContent = v.puntos_ganados || 0;

    // Botones de acción en Modal
    const btnAnular = document.getElementById('btnModalAnular');
    if (v.estado === 'Completada') {
      btnAnular.style.display = 'inline-flex';
      btnAnular.onclick = () => { cerrarDetalleVenta(); anularVenta(v.id); };
    } else {
      btnAnular.style.display = 'none';
    }

    const btnTicket = document.getElementById('btnModalTicket');
    btnTicket.onclick = () => {
      cerrarDetalleVenta();
      reimprimirTicketVenta(v.id);
    };

    const btnDtePdf = document.getElementById('btnModalDtePdf');
    if (dte && dte.pdf_url) {
      btnDtePdf.style.display = 'inline-flex';
      btnDtePdf.onclick = () => imprimirPdfDirecto(dte.pdf_url);
    } else {
      btnDtePdf.style.display = 'none';
    }

    document.getElementById('modalDetalleVenta').style.display = 'flex';

  } catch (err) {
    alert('Error al consultar detalle de venta: ' + err.message);
  }
}

function cerrarDetalleVenta() {
  document.getElementById('modalDetalleVenta').style.display = 'none';
}

// ----------------------------------------------------
// REIMPRESIÓN DE TICKET TÉRMICO (80mm)
// ----------------------------------------------------
async function reimprimirTicketVenta(id) {
  try {
    let data = ventaActualData;
    if (!data || data.venta.id !== id) {
      const res = await fetch(`api/ver_venta.php?id=${id}`);
      data = await res.json();
      if (!data.success) throw new Error(data.error);
    }

    const v = data.venta;
    const emp = data.empresa || {};

    // Encabezado empresa
    if (emp.nombre) document.getElementById('tkEmpresaNombre').textContent = emp.nombre;
    document.getElementById('tkEmpresaGiro').textContent = emp.giro || '';
    document.getElementById('tkEmpresaRut').textContent = emp.rut ? `RUT: ${emp.rut}` : '';
    document.getElementById('tkEmpresaDir').textContent = emp.direccion || '';
    document.getElementById('tkEmpresaFono').textContent = emp.telefono ? `Fono: ${emp.telefono}` : '';
    document.getElementById('ticketPieTexto').textContent = emp.pie_pagina || '¡Gracias por su preferencia!';

    // Metadata Venta
    document.getElementById('ticketVentaNum').textContent = `#${v.id}`;
    document.getElementById('ticketFecha').textContent = v.fecha;
    document.getElementById('ticketCajero').textContent = `Caja: ${v.cajero}`;

    // Ítems
    const detalleEl = document.getElementById('ticketDetalle');
    detalleEl.innerHTML = data.detalles.map(i => {
      const lineTotal = Math.round(i.cantidad * i.precio);
      let promoLabel = '';
      if (i.descuento > 0) {
        promoLabel = `<div class="tk-item-sub" style="color: #666;">Dcto: -${formatPesos(i.descuento)}</div>`;
      }
      return `
        <div style="margin-bottom: 0.2rem;">
          <div class="tk-item-line">
            <span>${escapeStr(i.nombre).substring(0, 24)}</span>
            <span>${formatPesos(i.subtotal)}</span>
          </div>
          <div class="tk-item-sub">${i.cantidad} x ${formatPesos(i.precio)}</div>
          ${promoLabel}
        </div>
      `;
    }).join('');

    // Subtotal / Descuento / Total
    const subRow = document.getElementById('ticketSubtotalRow');
    const descRow = document.getElementById('ticketDescuentoRow');
    if (v.descuento_global > 0) {
      subRow.style.display = 'flex';
      document.getElementById('ticketSubtotal').textContent = formatPesos(v.total + v.descuento_global);
      descRow.style.display = 'flex';
      document.getElementById('ticketDescuento').textContent = `-${formatPesos(v.descuento_global)}`;
    } else {
      subRow.style.display = 'none';
      descRow.style.display = 'none';
    }

    document.getElementById('ticketTotal').textContent = formatPesos(v.total);

    // Pagos
    const pagosEl = document.getElementById('ticketPagos');
    pagosEl.innerHTML = data.pagos.map(p => `
      <div class="tk-row">
        <span>${escapeStr(p.metodo)}</span>
        <span>${formatPesos(p.monto)}</span>
      </div>
    `).join('');

    document.getElementById('ticketVuelto').textContent = formatPesos(v.vuelto);
    document.getElementById('ticketVueltoRow').style.display = v.vuelto > 0 ? 'flex' : 'none';

    // Crédito Interno / Fiado
    const credBox = document.getElementById('ticketCreditoBox');
    if (data.credito) {
      const c = data.credito;
      document.getElementById('tkCredCliente').textContent = c.cliente || '';
      const rutRow = document.getElementById('tkCredRutRow');
      if (c.rut) {
        rutRow.style.display = 'flex';
        document.getElementById('tkCredRut').textContent = c.rut;
      } else {
        rutRow.style.display = 'none';
      }
      document.getElementById('tkCredMonto').textContent = formatPesos(c.monto);
      document.getElementById('tkCredSaldo').textContent = formatPesos(c.saldo_deudor);
      document.getElementById('tkCredCupo').textContent = formatPesos(c.cupo_disponible);
      credBox.style.display = 'block';
    } else {
      credBox.style.display = 'none';
    }

    // Sección DTE
    const dteInfo = document.getElementById('ticketDteInfo');
    const dteFolio = document.getElementById('ticketDteFolio');
    const dteTitulo = document.getElementById('ticketDteTitulo');
    const dtePdfBtn = document.getElementById('ticketDtePdfBtn');

    if (data.dte && (data.dte.folio || data.dte.pdf_url)) {
      dteTitulo.textContent = `${(data.dte.tipo_documento || 'BOLETA').toUpperCase()} ELECTRÓNICA`;
      dteFolio.textContent = `Folio: ${data.dte.folio || v.id}`;
      dteInfo.style.display = 'block';

      if (data.dte.pdf_url) {
        dtePdfBtn.href = data.dte.pdf_url;
        dtePdfBtn.style.display = 'inline-flex';
      } else {
        dtePdfBtn.style.display = 'none';
      }
    } else {
      dteInfo.style.display = 'none';
      dtePdfBtn.style.display = 'none';
    }

    document.getElementById('ticketModal').style.display = 'flex';

  } catch (err) {
    alert('Error al generar ticket: ' + err.message);
  }
}

function cerrarTicket() {
  document.getElementById('ticketModal').style.display = 'none';
}

// ----------------------------------------------------
// IMPRESIÓN DIRECTA O APERTURA DE BOLETA ELECTRÓNICA (PDF)
// ----------------------------------------------------
function imprimirPdfDirecto(pdfUrl) {
  if (!pdfUrl) {
    alert('No se encuentra la ruta del PDF de la boleta.');
    return;
  }

  const oldIframe = document.getElementById('printIframe');
  if (oldIframe) {
    oldIframe.parentNode.removeChild(oldIframe);
  }

  const iframe = document.createElement('iframe');
  iframe.id = 'printIframe';
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = '0';
  iframe.src = pdfUrl;

  iframe.onload = function() {
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      console.warn("Apertura directa de PDF en nueva pestaña:", e);
      window.open(pdfUrl, '_blank');
    }
  };

  document.body.appendChild(iframe);
}

// ----------------------------------------------------
// ANULACIÓN DE VENTA AUTORIZADA
// ----------------------------------------------------
async function anularVenta(id) {
  const motivo = prompt(`¿Motivo de anulación para la venta #${id}?`, 'Error en marcado / Solicitud de cliente');
  if (motivo === null) return;

  const pass = prompt('Clave de Supervisor / Administrador para autorizar anulación (dejar en blanco si eres Admin):', '');

  try {
    const res = await fetch('api/anular_venta.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({
        venta_id: id,
        motivo: motivo,
        supervisor_pass: pass
      })
    });
    const data = await res.json();

    if (!data.success) throw new Error(data.error);

    alert(data.mensaje);
    location.reload();
  } catch (err) {
    alert('Error al anular venta: ' + err.message);
  }
}
</script>


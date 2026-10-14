<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$esSupervisorNav = in_array($user['rol'], ['Administrador', 'Supervisor'], true);

// Cargar personalización visual y estado del asistente IA
$pdoHeader = getDB();
$stmtAppCfg = $pdoHeader->query("SELECT Clave, Valor FROM configuraciones WHERE Clave IN ('TEMA_MODO', 'TEMA_COLOR_ACENTO', 'MINIMARKET_LOGO_URL', 'MINIMARKET_NOMBRE', 'ASISTENTE_IA_ACTIVO')");
$appCfg = [];
while ($r = $stmtAppCfg->fetch(PDO::FETCH_ASSOC)) {
    $appCfg[$r['Clave']] = $r['Valor'];
}
$temaModo = $appCfg['TEMA_MODO'] ?? 'dark';
$temaAcento = $appCfg['TEMA_COLOR_ACENTO'] ?? 'indigo';
$logoUrl = $appCfg['MINIMARKET_LOGO_URL'] ?? '';
$nombreEmpresa = $appCfg['MINIMARKET_NOMBRE'] ?? 'Minimarket POS';
$iaActivoGlobal = ($appCfg['ASISTENTE_IA_ACTIVO'] ?? 'true') !== 'false';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($temaModo) ?>" data-accent="<?= htmlspecialchars($temaAcento) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($nombreEmpresa) ?> - Sistema Web</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0f172a">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="MinimarketPOS">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
  <script>
    (function() {
      try {
        const localTheme = localStorage.getItem('theme_mode');
        if (localTheme) document.documentElement.setAttribute('data-theme', localTheme);
      } catch (e) {}
    })();

    // Registro automático del Service Worker PWA
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js')
          .then((reg) => console.log('[PWA] Service Worker registrado correctamente:', reg.scope))
          .catch((err) => console.warn('[PWA] No se pudo registrar el Service Worker:', err));
      });
    }
  </script>
</head>
<body>

<?php
$changelogActual = APP_CHANGELOG[APP_VERSION] ?? [];
?>
<!-- Pantalla de bienvenida tras actualizar: barra de progreso + novedades reales -->
<div id="updateOverlay" class="update-overlay" style="display: none;">
  <div class="update-card">

    <div class="update-loader">
      <div class="update-loader__ring"></div>
      <i class="fa-solid fa-cloud-arrow-down update-loader__icon"></i>
    </div>

    <div>
      <h2 class="update-title">Actualizando Sistema</h2>
      <p class="update-subtitle">Aplicando la versión <strong><?= htmlspecialchars(APP_VERSION) ?></strong>. Esto tomará solo unos segundos&hellip;</p>
    </div>

    <div class="update-progress">
      <div id="updateProgressBar" class="update-progress__bar"></div>
    </div>
    <span id="updateProgressText" class="update-progress__text">0%</span>

    <?php if ($changelogActual): ?>
    <div class="update-changelog">
      <div class="update-changelog__label">Novedades de esta versión</div>
      <ul>
        <?php foreach ($changelogActual as $item): ?>
          <li><i class="fa-solid fa-circle-check"></i><span><?= htmlspecialchars($item) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
  (function() {
    const CURRENT_VERSION = '<?= APP_VERSION ?>';
    let lastSeen = null;
    try { lastSeen = localStorage.getItem('last_seen_version'); } catch (e) {}
    if (lastSeen === CURRENT_VERSION) return;

    const overlay = document.getElementById('updateOverlay');
    const bar = document.getElementById('updateProgressBar');
    const txt = document.getElementById('updateProgressText');
    const bullets = Array.from(document.querySelectorAll('.update-changelog li'));
    overlay.style.display = 'flex';

    let progress = 0;
    const interval = setInterval(() => {
      progress = Math.min(100, progress + Math.floor(Math.random() * 8) + 4);
      bar.style.width = progress + '%';
      txt.textContent = progress + '%';

      // Revelar las novedades a medida que avanza la barra
      const revelar = Math.floor((progress / 100) * bullets.length);
      bullets.forEach((li, i) => { if (i < revelar) li.classList.add('is-visible'); });

      if (progress === 100) {
        clearInterval(interval);
        bullets.forEach(li => li.classList.add('is-visible'));
        setTimeout(() => {
          overlay.classList.add('is-hiding');
          setTimeout(() => {
            overlay.style.display = 'none';
            try { localStorage.setItem('last_seen_version', CURRENT_VERSION); } catch (e) {}
          }, 400);
        }, 900);
      }
    }, 120);
  })();
</script>

<script>window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;</script>
<script src="assets/js/ui.js?v=<?= APP_VERSION ?>"></script>

<nav class="navbar">
  <a href="index.php" class="brand" style="display: flex; align-items: center; gap: 0.6rem; text-decoration: none;">
    <?php if (!empty($logoUrl)): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="brand-logo-img">
    <?php else: ?>
      <div class="brand-icon">
        <i class="fa-solid fa-store"></i>
      </div>
    <?php endif; ?>
    <span title="<?= htmlspecialchars($nombreEmpresa) ?>"><?= htmlspecialchars($nombreEmpresa) ?></span>
  </a>

  <ul class="nav-links">
    <!-- Inicio -->
    <li class="nav-item">
      <a href="index.php" class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-pie"></i> Inicio
      </a>
    </li>

    <!-- Caja POS -->
    <li class="nav-item">
      <a href="pos.php" class="nav-link <?= $currentPage == 'pos.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-shopping"></i> Caja POS
      </a>
    </li>

    <!-- Grupo: Caja y Ventas -->
    <li class="nav-item has-dropdown">
      <div class="nav-link <?= in_array($currentPage, ['caja.php', 'ventas.php', 'devoluciones.php', 'cotizaciones.php', 'rcv_sii.php']) ? 'active' : '' ?>" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-cash-register"></i> Caja y Ventas <i class="fa-solid fa-chevron-down nav-caret"></i>
      </div>
      <ul class="dropdown-menu" style="min-width: 230px;">
        <!-- Sección: Caja -->
        <li class="dropdown-section" style="--section-color: #f59e0b;">
          <i class="fa-solid fa-wallet"></i> Operaciones de Caja
        </li>
        <li><a href="caja.php" class="dropdown-item"><i class="fa-solid fa-wallet"></i> Turnos y Arqueo</a></li>
        
        <li class="dropdown-divider"></li>

        <!-- Sección: Ventas -->
        <li class="dropdown-section" style="--section-color: #3b82f6;">
          <i class="fa-solid fa-receipt"></i> Ventas y Devoluciones
        </li>
        <li><a href="ventas.php" class="dropdown-item"><i class="fa-solid fa-receipt"></i> Ventas y Comprobantes</a></li>
        <li><a href="devoluciones.php" class="dropdown-item"><i class="fa-solid fa-rotate-left"></i> Devoluciones y Vales</a></li>
        <li><a href="cotizaciones.php" class="dropdown-item"><i class="fa-solid fa-file-lines"></i> Cotizaciones</a></li>
        <?php if ($esSupervisorNav): ?>
        <li><a href="rcv_sii.php" class="dropdown-item"><i class="fa-solid fa-file-invoice-dollar" style="color: #10b981;"></i> Registro RCV (SII)</a></li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Grupo: Mantenedores -->
    <li class="nav-item has-dropdown">
      <div class="nav-link <?= in_array($currentPage, ['productos.php', 'actualizar_precios.php', 'imprimir_codigos_a4.php', 'categorias.php', 'clientes.php', 'promociones.php']) ? 'active' : '' ?>" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-folder-open"></i> Mantenedores <i class="fa-solid fa-chevron-down nav-caret"></i>
      </div>
      <ul class="dropdown-menu">
        <?php if ($esSupervisorNav): ?>
        <li><a href="productos.php" class="dropdown-item"><i class="fa-solid fa-box"></i> Productos y Precios</a></li>
        <li><a href="actualizar_precios.php" class="dropdown-item"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Actualizador Rápido Precios</a></li>
        <li><a href="imprimir_codigos_a4.php" class="dropdown-item"><i class="fa-solid fa-print" style="color: #38bdf8;"></i> Imprimir Códigos A4</a></li>
        <li><a href="categorias.php" class="dropdown-item"><i class="fa-solid fa-folder"></i> Categorías</a></li>
        <?php endif; ?>
        <li><a href="clientes.php" class="dropdown-item"><i class="fa-solid fa-users"></i> Clientes y Fiado</a></li>
        <?php if ($esSupervisorNav): ?>
        <li><a href="promociones.php" class="dropdown-item"><i class="fa-solid fa-tags"></i> Ofertas y Promociones</a></li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Grupo: Stock y Compras -->
    <li class="nav-item has-dropdown">
      <div class="nav-link <?= in_array($currentPage, ['kardex.php', 'ajustes.php', 'alertas_stock.php', 'inventario.php', 'notaspedido.php', 'compras.php', 'proveedores.php', 'rcv_sii.php']) ? 'active' : '' ?>" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-boxes-stacked"></i> Stock y Compras <i class="fa-solid fa-chevron-down nav-caret"></i>
      </div>
      <ul class="dropdown-menu" style="min-width: 250px;">
        <!-- Sección: Inventario -->
        <li class="dropdown-section" style="--section-color: #818cf8;">
          <i class="fa-solid fa-warehouse"></i> Control de Stock
        </li>
        <li><a href="kardex.php" class="dropdown-item"><i class="fa-solid fa-arrow-right-arrow-left"></i> Kardex de Movimientos</a></li>
        <?php if ($esSupervisorNav): ?>
        <li><a href="ajustes.php" class="dropdown-item"><i class="fa-solid fa-sliders"></i> Ajustes de Stock</a></li>
        <li><a href="inventario.php" class="dropdown-item"><i class="fa-solid fa-clipboard-list"></i> Toma de Inventario</a></li>
        <?php endif; ?>
        <li><a href="alertas_stock.php" class="dropdown-item"><i class="fa-solid fa-triangle-exclamation"></i> Alertas de Stock</a></li>

        <!-- Separador y Compras (solo para Supervisor) -->
        <?php if ($esSupervisorNav): ?>
        <li class="dropdown-divider"></li>
        
        <li class="dropdown-section" style="--section-color: #10b981;">
          <i class="fa-solid fa-truck-ramp-box"></i> Compras y Proveedores
        </li>
        <li><a href="notaspedido.php" class="dropdown-item"><i class="fa-solid fa-file-signature"></i> Notas de Pedido</a></li>
        <li><a href="compras.php" class="dropdown-item"><i class="fa-solid fa-truck-arrow-right"></i> Recepción de Compras</a></li>
        <li><a href="proveedores.php" class="dropdown-item"><i class="fa-solid fa-truck"></i> Proveedores</a></li>
        <li><a href="rcv_sii.php" class="dropdown-item"><i class="fa-solid fa-file-invoice-dollar" style="color: #10b981;"></i> Registro RCV (SII)</a></li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Grupo: Reportes -->
    <?php if ($esSupervisorNav): ?>
    <li class="nav-item has-dropdown">
      <div class="nav-link <?= in_array($currentPage, ['reportes.php', 'reporte_utilidades.php', 'reportes_z.php']) ? 'active' : '' ?>" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-chart-line"></i> Reportes <i class="fa-solid fa-chevron-down nav-caret"></i>
      </div>
      <ul class="dropdown-menu dropdown-menu-end" style="min-width: 250px;">
        <li><a href="reportes.php" class="dropdown-item"><i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> <strong>Centro de Reportes</strong></a></li>
        <li class="dropdown-divider"></li>
        <li><a href="reportes.php?tab=ventas" class="dropdown-item"><i class="fa-solid fa-money-bill-wave"></i> Ventas y Medios de Pago</a></li>
        <li><a href="reportes.php?tab=productos" class="dropdown-item"><i class="fa-solid fa-cubes-stacked"></i> Ranking y Rotación</a></li>
        <li><a href="reportes.php?tab=inventario" class="dropdown-item"><i class="fa-solid fa-warehouse"></i> Valorización Inventario</a></li>
        <li><a href="reportes.php?tab=creditos" class="dropdown-item"><i class="fa-solid fa-handshake"></i> Cartera y Fiados</a></li>
        <li><a href="reportes.php?tab=compras" class="dropdown-item"><i class="fa-solid fa-truck-ramp-box"></i> Compras y Proveedores</a></li>
        <li><a href="reportes.php?tab=cajeros" class="dropdown-item"><i class="fa-solid fa-users-gear"></i> Rendimiento de Cajeros</a></li>
        <li><a href="reportes.php?tab=utilidades" class="dropdown-item"><i class="fa-solid fa-sack-dollar"></i> Utilidades y Márgenes</a></li>
        <li class="dropdown-divider"></li>
        <li><a href="reportes_z.php" class="dropdown-item"><i class="fa-solid fa-file-invoice-dollar"></i> Cierre Z Fiscal</a></li>
      </ul>
    </li>
    <?php endif; ?>

    <!-- Grupo: Administración -->
    <?php if (in_array($user['rol'], ['Administrador', 'Supervisor'])): ?>
    <li class="nav-item has-dropdown">
      <div class="nav-link <?= in_array($currentPage, ['usuarios.php', 'cajas.php', 'configuracion.php', 'ayuda.php']) ? 'active' : '' ?>" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fa-solid fa-shield-halved"></i> Admin <i class="fa-solid fa-chevron-down nav-caret"></i>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a href="usuarios.php" class="dropdown-item"><i class="fa-solid fa-user-gear"></i> Usuarios y Roles</a></li>
        <li><a href="cajas.php" class="dropdown-item"><i class="fa-solid fa-cash-register"></i> Cajas Físicas</a></li>
        <li><a href="configuracion.php" class="dropdown-item"><i class="fa-solid fa-sliders"></i> Ajustes Generales</a></li>
        <li><a href="ayuda.php" class="dropdown-item"><i class="fa-solid fa-circle-question"></i> Soporte / Ayuda</a></li>
        <li><a href="acerca.php" class="dropdown-item"><i class="fa-solid fa-circle-info"></i> Acerca de y Novedades</a></li>
      </ul>
    </li>
    <?php endif; ?>
  </ul>

  <div style="display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0;">
    <!-- Botón Modo Claro / Oscuro -->
    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleThemeLive()" title="Alternar Modo Claro / Oscuro">
      <i class="fa-solid fa-sun" id="themeToggleIcon"></i>
    </button>

    <div class="user-pill">
      <i class="fa-solid fa-user"></i>
      <span><?= htmlspecialchars($user['nombre']) ?></span>
      <span class="user-badge"><?= htmlspecialchars($user['rol']) ?></span>
      <a href="logout.php" title="Cerrar Sesión" style="color: var(--danger); margin-left: 0.5rem;">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>
  </div>
</nav>

<script>
function toggleThemeLive() {
  const cur = document.documentElement.getAttribute('data-theme') || 'dark';
  const next = (cur === 'dark') ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  try { localStorage.setItem('theme_mode', next); } catch(e) {}
  updateThemeIcon();
}
function updateThemeIcon() {
  const cur = document.documentElement.getAttribute('data-theme') || 'dark';
  const icon = document.getElementById('themeToggleIcon');
  if (icon) {
    icon.className = (cur === 'dark') ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  }
}
document.addEventListener('DOMContentLoaded', updateThemeIcon);

// Control interactivo de menús desplegables (Hover fluido + Clic + Touch + Teclado)
document.addEventListener('DOMContentLoaded', function() {
  const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');

  dropdownItems.forEach(function(item) {
    const trigger = item.querySelector('.nav-link');
    if (!trigger) return;

    // Apertura y alternancia con 1 Clic o Toque en pantalla táctil
    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const wasOpen = item.classList.contains('is-open');

      // Cerrar cualquier otro menú abierto
      dropdownItems.forEach(function(other) {
        if (other !== item) {
          other.classList.remove('is-open');
          const otherTrigger = other.querySelector('.nav-link');
          if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
        }
      });

      // Alternar estado del actual
      if (wasOpen) {
        item.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      } else {
        item.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });

    // Soporte para teclado (Enter o Espacio)
    trigger.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        trigger.click();
      }
    });
  });

  // Cerrar menús al hacer clic fuera
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-item.has-dropdown')) {
      dropdownItems.forEach(function(item) {
        item.classList.remove('is-open');
        const trigger = item.querySelector('.nav-link');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      });
    }
  });

  // Cerrar con tecla Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      dropdownItems.forEach(function(item) {
        item.classList.remove('is-open');
        const trigger = item.querySelector('.nav-link');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      });
    }
  });
});
</script>

<main class="container">

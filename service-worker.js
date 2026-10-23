// Service Worker Autónomo para Minimarket POS
// Versión de caché alineada con el sistema
const CACHE_NAME = 'minimarket-pos-cache-v4.11.1';

const PRECACHE_ASSETS = [
  'pos.php',
  'manifest.json',
  'assets/css/style.css',
  'assets/js/pos.js',
  'assets/js/ui.js',
  'assets/js/pos-offline-db.js',
  'assets/js/printer-direct.js',
  'assets/vendor/chart.umd.min.js',
  'assets/vendor/fontawesome/css/all.min.css',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'assets/icons/icon-maskable-192.png',
  'assets/icons/icon-maskable-512.png'
];

// Instalación: Precarga de recursos estáticos básicos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Precargando App Shell y recursos estáticos...');
      return Promise.allSettled(
        PRECACHE_ASSETS.map((url) =>
          fetch(url)
            .then((res) => {
              if (res.ok) return cache.put(url, res);
            })
            .catch((err) => console.warn('[SW] No se pudo precargar:', url, err))
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// Activación: Limpieza de cachés antiguas al actualizar versión
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[SW] Eliminando caché antigua:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Intercepción de peticiones (Fetch)
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Las peticiones no GET no se cachean en el Cache Storage
  if (req.method !== 'GET') {
    return;
  }

  // 1. Navegación (Páginas HTML como pos.php): Network First con fallback a Cache
  if (req.mode === 'navigate' || req.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.status === 200) {
            const resClone = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, resClone));
          }
          return res;
        })
        .catch(async () => {
          console.log('[SW] Sin conexión: Sirviendo pos.php desde caché');
          const cached = await caches.match(req);
          if (cached) return cached;
          const cachedPos = await caches.match('pos.php');
          if (cachedPos) return cachedPos;
          return new Response(
            '<html><head><meta charset="utf-8"><title>Modo Offline</title></head><body style="background:#0f172a;color:#fff;font-family:sans-serif;text-align:center;padding:3rem;"><h2>Modo Contingencia Activo</h2><p>Abriendo terminal de caja...</p><script>window.location.href="pos.php";</script></body></html>',
            { headers: { 'Content-Type': 'text/html' } }
          );
        })
    );
    return;
  }

  // 2. Archivos estáticos (CSS, JS, Fuentes, Imágenes, Iconos): Cache First con actualización en segundo plano
  if (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.ttf') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.ico')
  ) {
    event.respondWith(
      caches.match(req).then((cached) => {
        const fetchPromise = fetch(req)
          .then((networkRes) => {
            if (networkRes.status === 200) {
              const clone = networkRes.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
            }
            return networkRes;
          })
          .catch(() => null);

        return cached || fetchPromise;
      })
    );
    return;
  }

  // Por defecto: ir a la red
  event.respondWith(fetch(req));
});

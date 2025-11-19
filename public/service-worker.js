// Service Worker para Interflow PWA
const CACHE_NAME = 'interflow-v1';
const urlsToCache = [
  '/',
  '/img/logo_fondotrasnparente.png',
  '/img/logo.PNG',
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache abierto');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Borrando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Intercepción de peticiones (Network First para APIs, Cache First para assets)
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar URLs que no sean HTTP/HTTPS (extensiones de Chrome, etc.)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Ignorar peticiones a dominios externos
  if (url.origin !== location.origin) {
    return;
  }

  // IMPORTANTE: No interceptar peticiones POST (formularios, login, etc.)
  if (request.method !== 'GET') {
    return;
  }

  // No cachear rutas de admin/login
  if (url.pathname.startsWith('/login') || url.pathname.startsWith('/admin')) {
    event.respondWith(fetch(request));
    return;
  }

  // Network First para APIs
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request)
        .catch(() => {
          return new Response(
            JSON.stringify({ error: 'Sin conexión a internet' }),
            { headers: { 'Content-Type': 'application/json' } }
          );
        })
    );
    return;
  }

  // Cache First para assets estáticos (solo GET)
  event.respondWith(
    caches.match(request)
      .then((response) => {
        if (response) {
          return response;
        }
        return fetch(request).then((response) => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseToCache);
          });
          return response;
        });
      })
  );
});

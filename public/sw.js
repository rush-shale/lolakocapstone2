const CACHE_NAME = 'osca-manolo-v3';
// Resolve asset paths relative to the service worker scope (e.g., /lolakocapstone2/public/)
const SCOPE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const urlsToCache = [
  // Don't cache '/' because scope may not be site root and it can fail addAll
  `${SCOPE_PATH}/assets/government-portal.css`,
  `${SCOPE_PATH}/assets/app.js`,
  `${SCOPE_PATH}/assets/sidebar-toggle.js`,
  `${SCOPE_PATH}/images/OSCA MAIN LOGO.png`,
  `${SCOPE_PATH}/images/MANOLO FORTICH LOGO.png`,
  `${SCOPE_PATH}/images/BACK ID.png`
];

// Install event - cache resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        // Cache each URL individually so one failure doesn't abort the whole install
        return Promise.allSettled(
          urlsToCache.map((u) => cache.add(u).catch(() => {}))
        );
      })
      .catch((error) => {
        console.error('Cache install failed:', error);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch event - smarter strategy:
// - Never cache non-GET (e.g., POST) requests
// - For HTML navigations: network-first (so updates show without hard refresh)
// - For static assets: cache-first
self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Always go to network for non-GET (e.g., API/POST), don't attempt to cache
  if (request.method !== 'GET') {
    event.respondWith(fetch(request));
    return;
  }

  // For navigations (pages / HTML), prefer fresh network content
  const isNavigation = request.mode === 'navigate' || request.destination === 'document';
  if (isNavigation) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          // Optionally update the cache for offline support
          const copy = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
          return networkResponse;
        })
        .catch(() => {
          // Fallback to cached page (if available)
          return caches.match(request).then((cached) => cached || caches.match('/'));
        })
    );
    return;
  }

  // For static GET assets: cache-first, then network fallback
  event.respondWith(
    (async () => {
      // If the request expects JSON or contains action parameters (dynamic), bypass cache
      const accept = request.headers.get('Accept') || '';
      const isJsonLike = accept.includes('application/json') || /\baction=/.test(new URL(request.url).search);
      if (isJsonLike) {
        try {
          return await fetch(request);
        } catch (_) {
          // fall back to cache if available
          const cached = await caches.match(request);
          if (cached) return cached;
          throw _;
        }
      }
      const cached = await caches.match(request);
      if (cached) return cached;
      const networkResponse = await fetch(request);
      const copy = networkResponse.clone();
      caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
      return networkResponse;
    })()
  );
});


const CACHE_NAME = 'osca-manolo-v4';
// Resolve asset paths relative to the service worker scope (e.g., /lolakocapstone2/public/)
const SCOPE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const urlsToCache = [
  // Cache the main entry point
  `${SCOPE_PATH}/index.php`,
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
    const url = new URL(request.url);
    const pathname = url.pathname;
    
    // Normalize root path requests to index.php
    const normalizedPath = pathname.replace(/\/$/, '');
    const isRootOrScope = normalizedPath === SCOPE_PATH || normalizedPath === SCOPE_PATH.replace(/\/$/, '') || normalizedPath === '' || pathname === '/';
    
    // If accessing root/scope path without a file, use index.php
    if (isRootOrScope && !pathname.includes('.php') && !pathname.includes('.html')) {
      const indexUrl = `${SCOPE_PATH}/index.php`;
      event.respondWith(
        (async () => {
          try {
            // Try network first
            const networkResponse = await fetch(indexUrl);
            if (networkResponse.ok) {
              const copy = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, copy);
                cache.put(indexUrl, copy);
              }).catch(() => {});
              return networkResponse;
            }
          } catch (e) {
            console.log('Network fetch failed, trying cache:', e);
          }
          
          // Try cache
          const cached = await caches.match(indexUrl) || await caches.match(`${SCOPE_PATH}/index.php`);
          if (cached) return cached;
          
          // Last resort: return a redirect page that will load index.php
          return new Response(
            `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Loading...</title><script>window.location.href='${indexUrl}';</script><meta http-equiv="refresh" content="0;url=${indexUrl}"></head><body><p>Loading application...</p><script>setTimeout(function(){window.location.href='${indexUrl}';},100);</script></body></html>`,
            {
              headers: { 
                'Content-Type': 'text/html; charset=utf-8',
                'Cache-Control': 'no-cache'
              }
            }
          );
        })()
      );
      return;
    }
    
    // For other navigation requests, try network first
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          // Only cache successful responses
          if (networkResponse.ok) {
            const copy = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {});
          }
          return networkResponse;
        })
        .catch(() => {
          // Fallback to cached page (if available)
          return caches.match(request)
            .then((cached) => {
              if (cached) return cached;
              // Try fallback to index.php
              const fallbackUrl = `${SCOPE_PATH}/index.php`;
              return caches.match(fallbackUrl)
                .then((indexCached) => {
                  if (indexCached) return indexCached;
                  // If still no cache, try to fetch index.php from network as last resort
                  return fetch(fallbackUrl).catch(() => {
                    // Return a basic HTML response if all else fails
                    return new Response('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Loading...</title><meta http-equiv="refresh" content="0;url=' + fallbackUrl + '"></head><body><p>Redirecting...</p></body></html>', {
                      headers: { 'Content-Type': 'text/html' }
                    });
                  });
                });
            });
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


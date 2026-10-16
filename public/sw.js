const CACHE_NAME = 'krishna-engineers-v1';

const OFFLINE_URL = '/offline.html';

const STATIC_ASSET_PREFIXES = ['/build/', '/icons/'];

const EXCLUDED_PATH_PREFIXES = [
    '/api/',
    '/login',
    '/logout',
    '/register',
    '/password',
    '/forgot-password',
    '/reset-password',
    '/verification',
];

// Install
self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => {
                return cache.add(OFFLINE_URL);
            }),
            self.skipWaiting(),
        ]),
    );
});

// Activate
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name)),
                );
            })
            .then(() => {
                return self.clients.claim();
            }),
    );
});

// Fetch
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Only handle same-origin requests.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Never cache authentication or API requests.
    if (
        EXCLUDED_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix))
    ) {
        return;
    }

    // Static assets use cache-first strategy.
    if (
        STATIC_ASSET_PREFIXES.some((prefix) =>
            url.pathname.startsWith(prefix),
        ) ||
        url.pathname === '/manifest.json'
    ) {
        event.respondWith(cacheFirst(request));

        return;
    }

    // Navigation requests use network-first strategy.
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));

        return;
    }

    // Other GET requests use network-first strategy.
    event.respondWith(networkFirst(request));
});

// Cache first
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const response = await fetch(request);

        if (isCacheableResponse(response)) {
            const cache = await caches.open(CACHE_NAME);

            await cache.put(request, response.clone());
        }

        return response;
    } catch {
        return new Response('', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

// Network first navigation
async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        const offlineResponse = await caches.match(OFFLINE_URL);

        if (offlineResponse) {
            return offlineResponse;
        }

        return new Response('You are currently offline.', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'text/plain; charset=utf-8',
            },
        });
    }
}

// Network first
async function networkFirst(request) {
    try {
        return await fetch(request);
    } catch {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return new Response('', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

// Cacheable response
function isCacheableResponse(response) {
    return response && response.status === 200 && response.type === 'basic';
}

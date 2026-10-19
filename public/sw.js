const CACHE_NAME = 'krishna-engineers-v3';

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

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)),
            self.skipWaiting(),
        ]),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) return;

    if (
        EXCLUDED_PATH_PREFIXES.some((prefix) =>
            url.pathname.startsWith(prefix),
        )
    ) {
        return;
    }

    // Always fetch the latest manifest when online.
    if (url.pathname === '/manifest.json') {
        event.respondWith(networkFirst(request));
        return;
    }

    // Static assets use the current cache only.
    if (
        STATIC_ASSET_PREFIXES.some((prefix) =>
            url.pathname.startsWith(prefix),
        )
    ) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(request));
        return;
    }

    event.respondWith(networkFirst(request));
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) return cachedResponse;

    try {
        const response = await fetch(request);

        if (isCacheableResponse(response)) {
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

async function networkFirstNavigation(request) {
    try {
        return await fetch(request);
    } catch {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) return cachedResponse;

        const offlineResponse = await caches.match(OFFLINE_URL);

        if (offlineResponse) return offlineResponse;

        return new Response('You are currently offline.', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'text/plain; charset=utf-8',
            },
        });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);

        if (isCacheableResponse(response)) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) return cachedResponse;

        return new Response('', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

function isCacheableResponse(response) {
    return response && response.status === 200 && response.type === 'basic';
}

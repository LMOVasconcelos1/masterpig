/* Sui Control / MasterPig - Service Worker (PWA básico)
 * - Instala: cacheia manifest, ícones, fontes e assets críticos locais
 * - Estratégia:
 *     HTML (navegação)      → network-first (mantém atualizado; offline cai no /dashboard)
 *     Assets estáticos (.css, .js, fonts, imagens locais) → cache-first com revalida em background (Stale-While-Revalidate)
 *     API/JSON/XHR (.json, /api/*)  → network-first com fallback de cache (offline ok, sem cache agressivo)
 * - Cache versionado por data; trocar SW_VERSION para invalidar caches antigos.
 */

const SW_VERSION = '2026-08-13-01';
const CACHE_PREFIX = 'sui-control';
const STATIC_CACHE = `${CACHE_PREFIX}-static-${SW_VERSION}`;
const RUNTIME_CACHE = `${CACHE_PREFIX}-runtime-${SW_VERSION}`;

const PRECACHE_URLS = [
    '/manifest.json',
    '/',
    '/login',
    '/dashboard',
    '/logo.png',
    '/logoSemFundo.png',
    '/logoSemPalavra.png',
    '/login.png',
    '/favicon.ico',
    '/robots.txt',
    '/js/vendor/alpine.min.js',
    '/js/vendor/alpine-collapse.min.js',
    '/js/vendor/chart.min.js',
    '/js/vendor/tailwind.min.js',
    '/js/check-alpine.js',
];

const CORE_ASSETS_RE = /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|otf|map)(\?|$)/i;
const LOCAL_RE = /^\/[^/]/i;
const API_RE = /^\/api\//i;
const JSON_RE = /\.json(\?|$)/i;

const MAX_RUNTIME_ENTRIES = 150;
const MAX_AGE_SECONDS = 60 * 60 * 24 * 14; /* 14 dias */

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(STATIC_CACHE);
            try {
                await cache.addAll(PRECACHE_URLS.map((u) => new Request(u, { credentials: 'same-origin' })));
            } catch (err) {
                console.warn('[SW] Precache parcialmente falhou (algum asset não existe ainda):', err);
            }
        })()
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            const removals = keys
                .filter((k) => k.startsWith(CACHE_PREFIX) && k !== STATIC_CACHE && k !== RUNTIME_CACHE)
                .map((k) => caches.delete(k));
            await Promise.all(removals);
        })()
    );
    self.clients.claim();
});

async function putInRuntimeCache(request, response) {
    if (!response || response.status !== 200 || response.type === 'opaque' || request.method !== 'GET') return;
    try {
        const cache = await caches.open(RUNTIME_CACHE);
        const toStore = response.clone();
        const headers = new Headers(toStore.headers);
        headers.set('sw-cache-time', String(Date.now()));
        const stamped = new Response(toStore.body, { status: toStore.status, statusText: toStore.statusText, headers });
        await cache.put(request, stamped);

        /* Limpeza Least-Recently-Added simples: mantém <= MAX_RUNTIME_ENTRIES */
        const reqs = await cache.keys();
        if (reqs.length > MAX_RUNTIME_ENTRIES) {
            for (let i = 0; i < reqs.length - MAX_RUNTIME_ENTRIES; i++) cache.delete(reqs[i]);
        }
    } catch (_) { /* ignore */ }
}

function isStale(cached) {
    if (!cached) return true;
    const t = Number(cached.headers.get('sw-cache-time') || 0);
    if (!t) return false;
    return (Date.now() - t) > MAX_AGE_SECONDS * 1000;
}

async function staleWhileRevalidate(request) {
    const cached = await caches.match(request);
    const fetchPromise = fetch(request)
        .then((resp) => { putInRuntimeCache(request, resp.clone()); return resp; })
        .catch(() => cached);
    if (cached && !isStale(cached)) return cached;
    if (cached) return cached; /* even if stale, prefer over network wait */
    return fetchPromise;
}

async function networkFirst(request, fallbackCache = true, fallbackFallback = null) {
    try {
        const response = await fetch(request);
        putInRuntimeCache(request, response.clone());
        return response;
    } catch (err) {
        const cached = await caches.match(request);
        if (cached) return cached;
        if (fallbackFallback) return fallbackFallback;
        if (fallbackCache) {
            if (request.mode === 'navigate') {
                const dash = await caches.match('/dashboard');
                if (dash) return dash;
                const root = await caches.match('/');
                if (root) return root;
            }
        }
        throw err;
    }
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    /* Navegações: network-first com fallback offline */
    if (req.mode === 'navigate' && req.method === 'GET') {
        event.respondWith(networkFirst(req, true));
        return;
    }

    /* Apenas requests GET da mesma origem daqui em diante */
    if (req.method !== 'GET' || url.origin !== self.location.origin) return;

    const path = url.pathname;

    /* API/JSON: network-first, cacheia apenas leituras bem-sucedidas */
    if (API_RE.test(path) || JSON_RE.test(path)) {
        event.respondWith(networkFirst(req, true));
        return;
    }

    /* Assets estáticos (css, js, imagens, fonts) → cache-first SWR */
    if (CORE_ASSETS_RE.test(path) || PRECACHE_URLS.includes(path)) {
        event.respondWith(staleWhileRevalidate(req));
        return;
    }

    /* Demais GETs locais (rotas Blade indexadas por histórico) → network-first */
    if (LOCAL_RE.test(path)) {
        event.respondWith(networkFirst(req, true));
    }
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

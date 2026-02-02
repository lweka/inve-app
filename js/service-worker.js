/**
 * SERVICE WORKER - CartelPlus Congo PWA
 * Gestion du cache offline et synchronisation en arrière-plan
 * Version: 1.0.0
 */

const CACHE_VERSION = 'cartelplus-v1.0.0';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const API_CACHE = `${CACHE_VERSION}-api`;

// Ressources critiques à mettre en cache immédiatement
const STATIC_ASSETS = [
    '/inve-app/',
    '/inve-app/index.php',
    '/inve-app/pagesweb_cn/admin_login_form.php',
    '/inve-app/css/bootstrap.min.css',
    '/inve-app/css/bootstrap-icons.css',
    '/inve-app/js/bootstrap.min.js',
    '/inve-app/js/jquery.min.js',
    '/inve-app/js/offline-db.js',
    '/inve-app/js/sync-manager.js',
    '/inve-app/js/offline-status.js',
    '/inve-app/images/logos/logo.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
];

// Pages principales de l'app
const APP_PAGES = [
    '/inve-app/codeSAvSUp/houses.php',
    '/inve-app/codeSAvSUp/create_sale.php',
    '/inve-app/codeSAvSUp/historyglob_prod.php',
    '/inve-app/pagesweb_cn/admin_subscription_manager.php'
];

// Routes API à gérer spécialement
const API_ROUTES = [
    '/inve-app/codeSAvSUp/api/',
    '/inve-app/pagesweb_cn/sync_api.php'
];

// ======================
// INSTALLATION
// ======================
self.addEventListener('install', event => {
    console.log('[Service Worker] Installation...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[Service Worker] Mise en cache des assets statiques');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
            .catch(err => console.error('[Service Worker] Erreur installation:', err))
    );
});

// ======================
// ACTIVATION
// ======================
self.addEventListener('activate', event => {
    console.log('[Service Worker] Activation...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name.startsWith('cartelplus-') && name !== STATIC_CACHE && name !== DYNAMIC_CACHE && name !== API_CACHE)
                        .map(name => {
                            console.log('[Service Worker] Suppression ancien cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
            .catch(err => console.error('[Service Worker] Erreur activation:', err))
    );
});

// ======================
// FETCH - Stratégies de cache
// ======================
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-HTTP
    if (!request.url.startsWith('http')) {
        return;
    }

    // Stratégie selon le type de ressource
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    } else if (isAPIRequest(url)) {
        event.respondWith(networkFirstWithOfflineSupport(request));
    } else if (isAppPage(url)) {
        event.respondWith(networkFirst(request, DYNAMIC_CACHE));
    } else {
        event.respondWith(staleWhileRevalidate(request, DYNAMIC_CACHE));
    }
});

// ======================
// BACKGROUND SYNC
// ======================
self.addEventListener('sync', event => {
    console.log('[Service Worker] Background Sync:', event.tag);
    
    if (event.tag === 'sync-pending-data') {
        event.waitUntil(syncPendingData());
    }
});

// ======================
// MESSAGES
// ======================
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(clearAllCaches());
    }
    
    if (event.data && event.data.type === 'SYNC_NOW') {
        event.waitUntil(syncPendingData());
    }
});

// ======================
// STRATÉGIES DE CACHE
// ======================

/**
 * Cache First - Pour assets statiques
 * Cherche d'abord dans le cache, sinon réseau
 */
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[Service Worker] Fetch error:', error);
        return new Response('Offline - Asset non disponible', { status: 503 });
    }
}

/**
 * Network First - Pour pages dynamiques
 * Essaie réseau d'abord, sinon cache
 */
async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return offlinePage();
    }
}

/**
 * Network First avec support offline complet
 * Pour requêtes API - enregistre en queue si offline
 */
async function networkFirstWithOfflineSupport(request) {
    try {
        const response = await fetch(request);
        
        // Mettre en cache les GET réussis
        if (request.method === 'GET' && response.ok) {
            const cache = await caches.open(API_CACHE);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.log('[Service Worker] Mode offline détecté pour API');
        
        // Si GET, retourner du cache
        if (request.method === 'GET') {
            const cached = await caches.match(request);
            if (cached) {
                return cached;
            }
        }
        
        // Si POST/PUT/DELETE, enregistrer pour sync ultérieure
        if (['POST', 'PUT', 'DELETE'].includes(request.method)) {
            await queueOfflineRequest(request);
            return new Response(
                JSON.stringify({
                    success: true,
                    offline: true,
                    message: 'Données enregistrées localement. Synchronisation au retour de la connexion.'
                }),
                {
                    status: 202,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        return new Response(
            JSON.stringify({ error: 'Connexion impossible et pas de cache disponible' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

/**
 * Stale While Revalidate
 * Retourne cache immédiatement, met à jour en arrière-plan
 */
async function staleWhileRevalidate(request, cacheName) {
    const cached = await caches.match(request);
    
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            const cache = caches.open(cacheName);
            cache.then(c => c.put(request, response.clone()));
        }
        return response;
    }).catch(() => cached);
    
    return cached || fetchPromise;
}

// ======================
// HELPERS
// ======================

function isStaticAsset(url) {
    const extensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return extensions.some(ext => url.pathname.endsWith(ext)) ||
           url.hostname.includes('cdnjs.cloudflare.com') ||
           url.hostname.includes('cdn.jsdelivr.net');
}

function isAPIRequest(url) {
    return API_ROUTES.some(route => url.pathname.includes(route)) ||
           url.pathname.includes('/api/') ||
           url.pathname.includes('_api.php') ||
           url.search.includes('action=');
}

function isAppPage(url) {
    return APP_PAGES.some(page => url.pathname.includes(page)) ||
           url.pathname.endsWith('.php');
}

async function queueOfflineRequest(request) {
    try {
        const body = await request.clone().text();
        const queueItem = {
            url: request.url,
            method: request.method,
            headers: [...request.headers.entries()],
            body: body,
            timestamp: Date.now()
        };
        
        // Enregistrer dans IndexedDB via message
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({
                    type: 'QUEUE_OFFLINE_REQUEST',
                    data: queueItem
                });
            });
        });
        
        // Programmer background sync
        if ('sync' in self.registration) {
            await self.registration.sync.register('sync-pending-data');
        }
    } catch (error) {
        console.error('[Service Worker] Erreur queue offline:', error);
    }
}

async function syncPendingData() {
    console.log('[Service Worker] Synchronisation des données offline...');
    
    try {
        // Notifier les clients pour lancer la sync
        const clients = await self.clients.matchAll();
        clients.forEach(client => {
            client.postMessage({
                type: 'START_SYNC',
                timestamp: Date.now()
            });
        });
        
        return Promise.resolve();
    } catch (error) {
        console.error('[Service Worker] Erreur sync:', error);
        return Promise.reject(error);
    }
}

function offlinePage() {
    const html = `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Mode Hors Ligne - CartelPlus</title>
            <style>
                body {
                    font-family: system-ui, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #0070e0 0%, #003087 100%);
                    color: white;
                    text-align: center;
                    padding: 20px;
                }
                .offline-container {
                    max-width: 500px;
                }
                .offline-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                }
                h1 {
                    font-size: 28px;
                    margin-bottom: 16px;
                }
                p {
                    font-size: 16px;
                    opacity: 0.9;
                    margin-bottom: 24px;
                }
                button {
                    background: white;
                    color: #0070e0;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                }
                button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
                }
            </style>
        </head>
        <body>
            <div class="offline-container">
                <div class="offline-icon">📡</div>
                <h1>Mode Hors Ligne</h1>
                <p>Cette page nécessite une connexion internet. Vérifiez votre connexion et réessayez.</p>
                <button onclick="location.reload()">🔄 Réessayer</button>
            </div>
        </body>
        </html>
    `;
    
    return new Response(html, {
        headers: { 'Content-Type': 'text/html' }
    });
}

async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames.map(name => caches.delete(name))
    );
    console.log('[Service Worker] Tous les caches supprimés');
}

console.log('[Service Worker] Chargé - Version:', CACHE_VERSION);

/**
 * SYNCHRONIZATION MANAGER
 * Gestion de la synchronisation online/offline
 * CartelPlus Congo PWA
 */

class SyncManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.isSyncing = false;
        this.syncInterval = null;
        this.listeners = {
            'online': [],
            'offline': [],
            'syncStart': [],
            'syncComplete': [],
            'syncError': []
        };
    }

    /**
     * Initialise le gestionnaire de synchronisation
     */
    async init() {
        console.log('[SyncManager] Initialisation...');

        // Écouter les changements de connexion
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Écouter les messages du Service Worker
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.addEventListener('message', event => {
                this.handleServiceWorkerMessage(event.data);
            });
        }

        // Synchronisation périodique si online
        if (this.isOnline) {
            this.startPeriodicSync();
        }

        // Synchronisation initiale
        await this.syncIfNeeded();

        console.log('[SyncManager] Initialisé - État:', this.isOnline ? 'ONLINE' : 'OFFLINE');
    }

    /**
     * Gestionnaire connexion rétablie
     */
    async handleOnline() {
        console.log('[SyncManager] 🌐 Connexion rétablie');
        this.isOnline = true;
        this.emit('online');
        this.updateUI('online');
        
        // Lancer la synchronisation
        await this.sync();
        
        // Démarrer sync périodique
        this.startPeriodicSync();
    }

    /**
     * Gestionnaire connexion perdue
     */
    handleOffline() {
        console.log('[SyncManager] 📡 Connexion perdue');
        this.isOnline = false;
        this.emit('offline');
        this.updateUI('offline');
        
        // Arrêter sync périodique
        this.stopPeriodicSync();
    }

    /**
     * Messages du Service Worker
     */
    handleServiceWorkerMessage(data) {
        if (data.type === 'START_SYNC') {
            this.sync();
        } else if (data.type === 'QUEUE_OFFLINE_REQUEST') {
            this.handleOfflineRequest(data.data);
        }
    }

    /**
     * Synchronisation complète
     */
    async sync() {
        if (this.isSyncing) {
            console.log('[SyncManager] Synchronisation déjà en cours');
            return;
        }

        if (!this.isOnline) {
            console.log('[SyncManager] Synchronisation impossible - Offline');
            return;
        }

        this.isSyncing = true;
        this.emit('syncStart');
        this.updateUI('syncing');

        try {
            console.log('[SyncManager] 🔄 Début de la synchronisation...');

            // 1. Synchroniser les ventes offline
            await this.syncSales();

            // 2. Synchroniser la queue générale
            await this.syncQueue();

            // 3. Mettre à jour les caches
            await this.updateCaches();

            // 4. Marquer la dernière sync
            await offlineDB.setMetadata('last_sync_at', Date.now());

            console.log('[SyncManager] ✅ Synchronisation terminée avec succès');
            this.emit('syncComplete', { success: true });
            this.updateUI('online');

        } catch (error) {
            console.error('[SyncManager] ❌ Erreur synchronisation:', error);
            this.emit('syncError', error);
            this.updateUI('error');
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Synchronise les ventes offline
     */
    async syncSales() {
        const sales = await offlineDB.getUnsyncedSales();
        
        if (sales.length === 0) {
            console.log('[SyncManager] Aucune vente à synchroniser');
            return;
        }

        console.log(`[SyncManager] Synchronisation de ${sales.length} vente(s)...`);

        for (const sale of sales) {
            try {
                const response = await fetch('/inve-app/pagesweb_cn/sync_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'sync_sale',
                        data: sale
                    })
                });

                const result = await response.json();

                if (result.success) {
                    await offlineDB.markSaleSynced(sale.id, result.server_id);
                    console.log(`[SyncManager] ✅ Vente ${sale.offline_id} synchronisée`);
                } else {
                    throw new Error(result.error || 'Erreur inconnue');
                }

            } catch (error) {
                console.error(`[SyncManager] ❌ Erreur sync vente ${sale.offline_id}:`, error);
                // Continuer avec les autres ventes
            }
        }
    }

    /**
     * Synchronise la queue générale
     */
    async syncQueue() {
        const queue = await offlineDB.getSyncQueue();
        
        if (queue.length === 0) {
            console.log('[SyncManager] Queue de synchronisation vide');
            return;
        }

        console.log(`[SyncManager] Traitement de ${queue.length} élément(s) en queue...`);

        for (const item of queue) {
            try {
                const response = await fetch('/inve-app/pagesweb_cn/sync_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: `sync_${item.type}`,
                        data: item.data
                    })
                });

                const result = await response.json();

                if (result.success) {
                    await offlineDB.markQueueItemProcessed(item.id);
                    console.log(`[SyncManager] ✅ Queue item ${item.id} traité`);
                } else {
                    throw new Error(result.error || 'Erreur inconnue');
                }

            } catch (error) {
                console.error(`[SyncManager] ❌ Erreur queue item ${item.id}:`, error);
                await offlineDB.markQueueItemFailed(item.id, error.message);
            }
        }
    }

    /**
     * Met à jour les caches locaux
     */
    async updateCaches() {
        try {
            // Mettre à jour le cache des produits
            const productsResponse = await fetch('/inve-app/pagesweb_cn/sync_api.php?action=get_products');
            const productsData = await productsResponse.json();
            
            if (productsData.success && productsData.products) {
                await offlineDB.cacheProducts(productsData.products);
                console.log('[SyncManager] Cache produits mis à jour');
            }

            // Mettre à jour le cache des clients
            const clientsResponse = await fetch('/inve-app/pagesweb_cn/sync_api.php?action=get_clients');
            const clientsData = await clientsResponse.json();
            
            if (clientsData.success && clientsData.clients) {
                await offlineDB.cacheClients(clientsData.clients);
                console.log('[SyncManager] Cache clients mis à jour');
            }

        } catch (error) {
            console.error('[SyncManager] Erreur mise à jour caches:', error);
        }
    }

    /**
     * Synchronisation périodique (toutes les 5 minutes si online)
     */
    startPeriodicSync() {
        if (this.syncInterval) {
            return;
        }

        this.syncInterval = setInterval(() => {
            this.syncIfNeeded();
        }, 5 * 60 * 1000); // 5 minutes

        console.log('[SyncManager] Synchronisation périodique activée');
    }

    stopPeriodicSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
            console.log('[SyncManager] Synchronisation périodique désactivée');
        }
    }

    /**
     * Synchronise seulement si nécessaire
     */
    async syncIfNeeded() {
        const stats = await offlineDB.getStats();
        
        if (stats.unsynced_sales > 0 || stats.queue_length > 0) {
            console.log('[SyncManager] Données non synchronisées détectées');
            await this.sync();
        }
    }

    /**
     * Gestion d'une requête offline
     */
    async handleOfflineRequest(requestData) {
        console.log('[SyncManager] Requête offline enregistrée:', requestData);
        
        // L'enregistrement est déjà géré par le Service Worker
        // On pourrait ajouter une notification UI ici
        this.updateUI('offline-request');
    }

    /**
     * Mise à jour de l'interface
     */
    updateUI(status) {
        const statusElement = document.getElementById('offline-status');
        if (!statusElement) return;

        const statusConfig = {
            'online': {
                icon: '🌐',
                text: 'En ligne',
                class: 'status-online'
            },
            'offline': {
                icon: '📡',
                text: 'Hors ligne',
                class: 'status-offline'
            },
            'syncing': {
                icon: '🔄',
                text: 'Synchronisation...',
                class: 'status-syncing'
            },
            'error': {
                icon: '⚠️',
                text: 'Erreur sync',
                class: 'status-error'
            },
            'offline-request': {
                icon: '💾',
                text: 'Sauvegardé localement',
                class: 'status-saved'
            }
        };

        const config = statusConfig[status] || statusConfig.online;
        
        statusElement.innerHTML = `
            <span class="${config.class}">
                ${config.icon} ${config.text}
            </span>
        `;

        // Mettre à jour le compteur de données en attente
        this.updatePendingCounter();
    }

    /**
     * Met à jour le compteur de données en attente
     */
    async updatePendingCounter() {
        const counterElement = document.getElementById('pending-count');
        if (!counterElement) return;

        const stats = await offlineDB.getStats();
        const total = stats.unsynced_sales + stats.queue_length;

        if (total > 0) {
            counterElement.textContent = total;
            counterElement.style.display = 'inline-block';
        } else {
            counterElement.style.display = 'none';
        }
    }

    /**
     * Système d'événements
     */
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    emit(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => callback(data));
        }
    }

    /**
     * Force une synchronisation manuelle
     */
    async forceSync() {
        console.log('[SyncManager] Synchronisation manuelle demandée');
        await this.sync();
    }

    /**
     * Obtient l'état actuel
     */
    getStatus() {
        return {
            isOnline: this.isOnline,
            isSyncing: this.isSyncing
        };
    }
}

// Instance globale
const syncManager = new SyncManager();

// Auto-initialisation
if (typeof window !== 'undefined') {
    window.addEventListener('load', async () => {
        try {
            await syncManager.init();
            console.log('[SyncManager] Prêt à l\'utilisation');
        } catch (error) {
            console.error('[SyncManager] Erreur initialisation:', error);
        }
    });
}

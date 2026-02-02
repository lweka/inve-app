/**
 * OFFLINE DATABASE MANAGER - IndexedDB
 * Gestion du stockage local des données pour mode offline
 * CartelPlus Congo PWA
 */

class OfflineDB {
    constructor() {
        this.dbName = 'CartelPlusDB';
        this.dbVersion = 1;
        this.db = null;
    }

    /**
     * Initialise la base de données IndexedDB
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => {
                console.error('[OfflineDB] Erreur ouverture:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('[OfflineDB] Base de données initialisée');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                console.log('[OfflineDB] Mise à jour du schéma...');

                // Store: Ventes offline
                if (!db.objectStoreNames.contains('sales')) {
                    const salesStore = db.createObjectStore('sales', { keyPath: 'id', autoIncrement: true });
                    salesStore.createIndex('timestamp', 'timestamp', { unique: false });
                    salesStore.createIndex('synced', 'synced', { unique: false });
                    salesStore.createIndex('client_code', 'client_code', { unique: false });
                }

                // Store: Produits (cache)
                if (!db.objectStoreNames.contains('products')) {
                    const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                    productsStore.createIndex('name', 'name', { unique: false });
                    productsStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                // Store: Clients (cache)
                if (!db.objectStoreNames.contains('clients')) {
                    const clientsStore = db.createObjectStore('clients', { keyPath: 'client_code' });
                    clientsStore.createIndex('email', 'email', { unique: false });
                    clientsStore.createIndex('company_name', 'company_name', { unique: false });
                }

                // Store: Queue de synchronisation
                if (!db.objectStoreNames.contains('sync_queue')) {
                    const queueStore = db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
                    queueStore.createIndex('timestamp', 'timestamp', { unique: false });
                    queueStore.createIndex('type', 'type', { unique: false });
                    queueStore.createIndex('status', 'status', { unique: false });
                }

                // Store: Codes d'abonnement
                if (!db.objectStoreNames.contains('subscription_codes')) {
                    const codesStore = db.createObjectStore('subscription_codes', { keyPath: 'code' });
                    codesStore.createIndex('status', 'status', { unique: false });
                }

                // Store: Métadonnées
                if (!db.objectStoreNames.contains('metadata')) {
                    db.createObjectStore('metadata', { keyPath: 'key' });
                }
            };
        });
    }

    /**
     * Ajoute une vente offline
     */
    async addSale(saleData) {
        const transaction = this.db.transaction(['sales'], 'readwrite');
        const store = transaction.objectStore('sales');

        const sale = {
            ...saleData,
            timestamp: Date.now(),
            synced: false,
            offline_id: `OFFLINE_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
        };

        return new Promise((resolve, reject) => {
            const request = store.add(sale);
            request.onsuccess = () => {
                console.log('[OfflineDB] Vente ajoutée:', request.result);
                this.addToSyncQueue('sale', sale);
                resolve(request.result);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Récupère toutes les ventes non synchronisées
     */
    async getUnsyncedSales() {
        const transaction = this.db.transaction(['sales'], 'readonly');
        const store = transaction.objectStore('sales');
        const index = store.index('synced');

        return new Promise((resolve, reject) => {
            const request = index.getAll(false);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marque une vente comme synchronisée
     */
    async markSaleSynced(id, serverId) {
        const transaction = this.db.transaction(['sales'], 'readwrite');
        const store = transaction.objectStore('sales');

        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);
            getRequest.onsuccess = () => {
                const sale = getRequest.result;
                if (sale) {
                    sale.synced = true;
                    sale.server_id = serverId;
                    sale.synced_at = Date.now();
                    const updateRequest = store.put(sale);
                    updateRequest.onsuccess = () => resolve(true);
                    updateRequest.onerror = () => reject(updateRequest.error);
                } else {
                    resolve(false);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Cache des produits
     */
    async cacheProducts(products) {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');

        // Vider le store d'abord
        await new Promise((resolve, reject) => {
            const clearRequest = store.clear();
            clearRequest.onsuccess = () => resolve();
            clearRequest.onerror = () => reject(clearRequest.error);
        });

        // Ajouter les nouveaux produits
        const promises = products.map(product => {
            return new Promise((resolve, reject) => {
                const request = store.add({
                    ...product,
                    updated_at: Date.now()
                });
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        });

        await Promise.all(promises);
        console.log('[OfflineDB] Produits mis en cache:', products.length);
        await this.setMetadata('products_cached_at', Date.now());
    }

    /**
     * Récupère les produits en cache
     */
    async getProducts() {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Cache des clients
     */
    async cacheClients(clients) {
        const transaction = this.db.transaction(['clients'], 'readwrite');
        const store = transaction.objectStore('clients');

        const promises = clients.map(client => {
            return new Promise((resolve, reject) => {
                const request = store.put(client);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        });

        await Promise.all(promises);
        console.log('[OfflineDB] Clients mis en cache:', clients.length);
        await this.setMetadata('clients_cached_at', Date.now());
    }

    /**
     * Récupère les clients en cache
     */
    async getClients() {
        const transaction = this.db.transaction(['clients'], 'readonly');
        const store = transaction.objectStore('clients');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Ajoute un élément à la queue de synchronisation
     */
    async addToSyncQueue(type, data) {
        const transaction = this.db.transaction(['sync_queue'], 'readwrite');
        const store = transaction.objectStore('sync_queue');

        const queueItem = {
            type: type,
            data: data,
            timestamp: Date.now(),
            status: 'pending',
            retry_count: 0
        };

        return new Promise((resolve, reject) => {
            const request = store.add(queueItem);
            request.onsuccess = () => {
                console.log('[OfflineDB] Ajouté à la queue:', type);
                resolve(request.result);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Récupère la queue de synchronisation
     */
    async getSyncQueue() {
        const transaction = this.db.transaction(['sync_queue'], 'readonly');
        const store = transaction.objectStore('sync_queue');
        const index = store.index('status');

        return new Promise((resolve, reject) => {
            const request = index.getAll('pending');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marque un élément de la queue comme traité
     */
    async markQueueItemProcessed(id) {
        const transaction = this.db.transaction(['sync_queue'], 'readwrite');
        const store = transaction.objectStore('sync_queue');

        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);
            getRequest.onsuccess = () => {
                const item = getRequest.result;
                if (item) {
                    item.status = 'processed';
                    item.processed_at = Date.now();
                    const updateRequest = store.put(item);
                    updateRequest.onsuccess = () => resolve(true);
                    updateRequest.onerror = () => reject(updateRequest.error);
                } else {
                    resolve(false);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Marque un élément de la queue en erreur
     */
    async markQueueItemFailed(id, error) {
        const transaction = this.db.transaction(['sync_queue'], 'readwrite');
        const store = transaction.objectStore('sync_queue');

        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);
            getRequest.onsuccess = () => {
                const item = getRequest.result;
                if (item) {
                    item.retry_count = (item.retry_count || 0) + 1;
                    item.last_error = error;
                    item.last_retry_at = Date.now();
                    
                    // Après 3 tentatives, marquer comme échoué
                    if (item.retry_count >= 3) {
                        item.status = 'failed';
                    }
                    
                    const updateRequest = store.put(item);
                    updateRequest.onsuccess = () => resolve(true);
                    updateRequest.onerror = () => reject(updateRequest.error);
                } else {
                    resolve(false);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Stocke des métadonnées
     */
    async setMetadata(key, value) {
        const transaction = this.db.transaction(['metadata'], 'readwrite');
        const store = transaction.objectStore('metadata');

        return new Promise((resolve, reject) => {
            const request = store.put({ key, value, updated_at: Date.now() });
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Récupère des métadonnées
     */
    async getMetadata(key) {
        const transaction = this.db.transaction(['metadata'], 'readonly');
        const store = transaction.objectStore('metadata');

        return new Promise((resolve, reject) => {
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result ? request.result.value : null);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Statistiques offline
     */
    async getStats() {
        const [unsyncedSales, queueLength, products, clients] = await Promise.all([
            this.getUnsyncedSales(),
            this.getSyncQueue(),
            this.getProducts(),
            this.getClients()
        ]);

        return {
            unsynced_sales: unsyncedSales.length,
            queue_length: queueLength.length,
            cached_products: products.length,
            cached_clients: clients.length,
            products_cached_at: await this.getMetadata('products_cached_at'),
            clients_cached_at: await this.getMetadata('clients_cached_at'),
            last_sync_at: await this.getMetadata('last_sync_at')
        };
    }

    /**
     * Nettoie les données synchronisées anciennes
     */
    async cleanupOldData(daysToKeep = 30) {
        const cutoffTime = Date.now() - (daysToKeep * 24 * 60 * 60 * 1000);
        
        // Supprimer les ventes synchronisées anciennes
        const transaction = this.db.transaction(['sales', 'sync_queue'], 'readwrite');
        const salesStore = transaction.objectStore('sales');
        const queueStore = transaction.objectStore('sync_queue');

        let deletedSales = 0;
        let deletedQueue = 0;

        // Nettoyer les ventes
        const salesRequest = salesStore.openCursor();
        salesRequest.onsuccess = (event) => {
            const cursor = event.target.result;
            if (cursor) {
                const sale = cursor.value;
                if (sale.synced && sale.synced_at < cutoffTime) {
                    cursor.delete();
                    deletedSales++;
                }
                cursor.continue();
            }
        };

        // Nettoyer la queue
        const queueRequest = queueStore.openCursor();
        queueRequest.onsuccess = (event) => {
            const cursor = event.target.result;
            if (cursor) {
                const item = cursor.value;
                if (item.status === 'processed' && item.processed_at < cutoffTime) {
                    cursor.delete();
                    deletedQueue++;
                }
                cursor.continue();
            }
        };

        await new Promise(resolve => {
            transaction.oncomplete = () => {
                console.log(`[OfflineDB] Nettoyage: ${deletedSales} ventes, ${deletedQueue} éléments queue`);
                resolve();
            };
        });
    }
}

// Instance globale
const offlineDB = new OfflineDB();

// Auto-initialisation
if (typeof window !== 'undefined') {
    window.addEventListener('load', async () => {
        try {
            await offlineDB.init();
            console.log('[OfflineDB] Prêt à l\'utilisation');
        } catch (error) {
            console.error('[OfflineDB] Erreur initialisation:', error);
        }
    });
}

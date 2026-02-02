/**
 * OFFLINE STATUS WIDGET
 * Interface utilisateur pour le statut de connexion et la synchronisation
 * CartelPlus Congo PWA
 */

class OfflineStatusWidget {
    constructor() {
        this.container = null;
        this.isVisible = true;
        this.init();
    }

    /**
     * Initialise le widget
     */
    init() {
        this.createWidget();
        this.attachEventListeners();
        this.updateStatus();
        
        // Mise à jour périodique
        setInterval(() => this.updateStatus(), 10000); // Toutes les 10 secondes
    }

    /**
     * Crée l'élément HTML du widget
     */
    createWidget() {
        const widget = document.createElement('div');
        widget.id = 'offline-status-widget';
        widget.innerHTML = `
            <style>
                #offline-status-widget {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 8px 30px rgba(0, 48, 135, 0.15);
                    padding: 16px 20px;
                    z-index: 9999;
                    font-family: system-ui, sans-serif;
                    min-width: 280px;
                    max-width: 350px;
                    transition: all 0.3s ease;
                    border: 1px solid rgba(0, 112, 224, 0.1);
                }

                #offline-status-widget.collapsed {
                    padding: 12px 16px;
                    min-width: auto;
                }

                .status-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 12px;
                }

                .status-indicator {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 600;
                    font-size: 14px;
                }

                .status-icon {
                    font-size: 20px;
                    animation: pulse 2s infinite;
                }

                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }

                .status-online .status-icon {
                    animation: none;
                }

                .status-text {
                    color: #0b1f3a;
                }

                .status-online .status-text {
                    color: #1f8f6a;
                }

                .status-offline .status-text {
                    color: #dc2626;
                }

                .status-syncing .status-text {
                    color: #0070e0;
                }

                .toggle-btn {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 4px;
                    opacity: 0.6;
                    transition: opacity 0.2s;
                }

                .toggle-btn:hover {
                    opacity: 1;
                }

                .status-details {
                    display: none;
                    padding-top: 12px;
                    border-top: 1px solid rgba(0, 112, 224, 0.1);
                }

                .status-details.visible {
                    display: block;
                }

                .status-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 6px 0;
                    font-size: 13px;
                    color: #6b7280;
                }

                .status-item-label {
                    font-weight: 500;
                }

                .status-item-value {
                    font-weight: 600;
                    color: #0b1f3a;
                }

                .pending-badge {
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: 700;
                }

                .sync-button {
                    width: 100%;
                    margin-top: 12px;
                    padding: 10px;
                    background: linear-gradient(135deg, #0070e0 0%, #003087 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    font-size: 13px;
                }

                .sync-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(0, 112, 224, 0.4);
                }

                .sync-button:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                    transform: none;
                }

                .sync-button.syncing {
                    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
                }

                .last-sync {
                    text-align: center;
                    font-size: 11px;
                    color: #9ca3af;
                    margin-top: 8px;
                }

                @media (max-width: 768px) {
                    #offline-status-widget {
                        bottom: 10px;
                        right: 10px;
                        left: 10px;
                        max-width: none;
                    }
                }
            </style>

            <div class="status-header">
                <div id="offline-status" class="status-indicator status-online">
                    <span class="status-icon">🌐</span>
                    <span class="status-text">En ligne</span>
                </div>
                <button class="toggle-btn" id="toggle-details">▼</button>
            </div>

            <div id="status-details" class="status-details">
                <div class="status-item">
                    <span class="status-item-label">Ventes en attente</span>
                    <span class="status-item-value" id="pending-sales">0</span>
                </div>
                <div class="status-item">
                    <span class="status-item-label">Actions en queue</span>
                    <span class="status-item-value" id="pending-queue">0</span>
                </div>
                <div class="status-item">
                    <span class="status-item-label">Produits en cache</span>
                    <span class="status-item-value" id="cached-products">0</span>
                </div>
                <div class="status-item">
                    <span class="status-item-label">Clients en cache</span>
                    <span class="status-item-value" id="cached-clients">0</span>
                </div>

                <button id="sync-now-btn" class="sync-button">
                    🔄 Synchroniser maintenant
                </button>

                <div class="last-sync" id="last-sync-time">
                    Dernière sync : Jamais
                </div>
            </div>
        `;

        document.body.appendChild(widget);
        this.container = widget;
    }

    /**
     * Attache les événements
     */
    attachEventListeners() {
        // Toggle détails
        const toggleBtn = document.getElementById('toggle-details');
        const details = document.getElementById('status-details');
        
        toggleBtn.addEventListener('click', () => {
            details.classList.toggle('visible');
            toggleBtn.textContent = details.classList.contains('visible') ? '▲' : '▼';
        });

        // Bouton de synchronisation
        const syncBtn = document.getElementById('sync-now-btn');
        syncBtn.addEventListener('click', async () => {
            await this.manualSync();
        });

        // Écouter les événements du sync manager
        if (typeof syncManager !== 'undefined') {
            syncManager.on('online', () => this.updateStatus());
            syncManager.on('offline', () => this.updateStatus());
            syncManager.on('syncStart', () => this.onSyncStart());
            syncManager.on('syncComplete', () => this.onSyncComplete());
            syncManager.on('syncError', (error) => this.onSyncError(error));
        }
    }

    /**
     * Met à jour le statut
     */
    async updateStatus() {
        const statusElement = document.getElementById('offline-status');
        const isOnline = navigator.onLine;

        // Mise à jour de l'indicateur de connexion
        statusElement.className = `status-indicator ${isOnline ? 'status-online' : 'status-offline'}`;
        statusElement.innerHTML = `
            <span class="status-icon">${isOnline ? '🌐' : '📡'}</span>
            <span class="status-text">${isOnline ? 'En ligne' : 'Hors ligne'}</span>
        `;

        // Mise à jour des statistiques
        if (typeof offlineDB !== 'undefined' && offlineDB.db) {
            try {
                const stats = await offlineDB.getStats();
                
                document.getElementById('pending-sales').textContent = stats.unsynced_sales;
                document.getElementById('pending-queue').textContent = stats.queue_length;
                document.getElementById('cached-products').textContent = stats.cached_products;
                document.getElementById('cached-clients').textContent = stats.cached_clients;

                // Afficher badge si données en attente
                const totalPending = stats.unsynced_sales + stats.queue_length;
                if (totalPending > 0) {
                    this.showPendingBadge(totalPending);
                } else {
                    this.hidePendingBadge();
                }

                // Dernière sync
                if (stats.last_sync_at) {
                    const lastSync = new Date(stats.last_sync_at);
                    const timeAgo = this.getTimeAgo(lastSync);
                    document.getElementById('last-sync-time').textContent = `Dernière sync : ${timeAgo}`;
                }

            } catch (error) {
                console.error('[OfflineStatusWidget] Erreur récupération stats:', error);
            }
        }
    }

    /**
     * Synchronisation manuelle
     */
    async manualSync() {
        const syncBtn = document.getElementById('sync-now-btn');
        
        if (!navigator.onLine) {
            alert('❌ Impossible de synchroniser : vous êtes hors ligne');
            return;
        }

        syncBtn.disabled = true;
        syncBtn.textContent = '⏳ Synchronisation...';
        syncBtn.classList.add('syncing');

        try {
            if (typeof syncManager !== 'undefined') {
                await syncManager.forceSync();
            }
        } catch (error) {
            console.error('[OfflineStatusWidget] Erreur sync manuelle:', error);
            alert('❌ Erreur lors de la synchronisation');
        } finally {
            syncBtn.disabled = false;
            syncBtn.textContent = '🔄 Synchroniser maintenant';
            syncBtn.classList.remove('syncing');
        }
    }

    /**
     * Événements de synchronisation
     */
    onSyncStart() {
        const statusElement = document.getElementById('offline-status');
        statusElement.className = 'status-indicator status-syncing';
        statusElement.innerHTML = `
            <span class="status-icon">🔄</span>
            <span class="status-text">Synchronisation...</span>
        `;
    }

    onSyncComplete(data) {
        this.updateStatus();
        
        // Notification temporaire
        this.showNotification('✅ Synchronisation terminée avec succès', 'success');
    }

    onSyncError(error) {
        this.updateStatus();
        
        // Notification d'erreur
        this.showNotification('❌ Erreur lors de la synchronisation', 'error');
    }

    /**
     * Affiche un badge pour les données en attente
     */
    showPendingBadge(count) {
        const statusElement = document.getElementById('offline-status');
        let badge = statusElement.querySelector('.pending-badge');
        
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'pending-badge';
            statusElement.appendChild(badge);
        }
        
        badge.textContent = count;
    }

    hidePendingBadge() {
        const statusElement = document.getElementById('offline-status');
        const badge = statusElement.querySelector('.pending-badge');
        if (badge) {
            badge.remove();
        }
    }

    /**
     * Affiche une notification temporaire
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#1f8f6a' : type === 'error' ? '#dc2626' : '#0070e0'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            font-weight: 600;
            animation: slideInRight 0.3s ease;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Calcule le temps écoulé
     */
    getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        if (seconds < 60) return 'À l\'instant';
        if (seconds < 3600) return `Il y a ${Math.floor(seconds / 60)} min`;
        if (seconds < 86400) return `Il y a ${Math.floor(seconds / 3600)} h`;
        return `Il y a ${Math.floor(seconds / 86400)} j`;
    }
}

// Initialisation automatique
if (typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        new OfflineStatusWidget();
        console.log('[OfflineStatusWidget] Widget initialisé');
    });
}

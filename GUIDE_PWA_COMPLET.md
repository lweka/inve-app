# 📱 Guide Complet PWA - CartelPlus Congo

## 🎯 Système Offline-First Implémenté

### Vue d'ensemble
CartelPlus Congo est maintenant une **Progressive Web App (PWA)** complète qui fonctionne **avec ou sans connexion internet**. 

---

## 🚀 Installation & Configuration

### 1. Migration Base de Données

Exécutez cette migration pour supporter les ventes offline :

```sql
-- Ajouter colonnes pour tracking des ventes offline
ALTER TABLE sells 
ADD COLUMN IF NOT EXISTS offline_id VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS synced_from_offline TINYINT(1) DEFAULT 0,
ADD INDEX idx_offline_id (offline_id);
```

### 2. Activation du Service Worker

Ajoutez ces lignes dans le `<head>` de vos pages principales :

```html
<!-- Manifest PWA -->
<link rel="manifest" href="/inve-app/manifest.json">
<meta name="theme-color" content="#0070e0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="CartelPlus">

<!-- Scripts PWA -->
<script src="/inve-app/js/offline-db.js"></script>
<script src="/inve-app/js/sync-manager.js"></script>
<script src="/inve-app/js/offline-status.js"></script>

<!-- Enregistrement Service Worker -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/inve-app/js/service-worker.js');
            console.log('✅ Service Worker enregistré:', registration.scope);
            
            // Vérifier les mises à jour
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // Nouvelle version disponible
                        if (confirm('🔄 Nouvelle version disponible ! Recharger ?')) {
                            window.location.reload();
                        }
                    }
                });
            });
        } catch (error) {
            console.error('❌ Erreur Service Worker:', error);
        }
    });
}
</script>
```

---

## 📱 Installation sur Mobile

### Android (Chrome)
1. Ouvrez le site dans Chrome
2. Menu ⋮ → **Installer l'application**
3. L'icône apparaît sur votre écran d'accueil
4. Fonctionne comme une app native !

### iOS (Safari)
1. Ouvrez le site dans Safari
2. Icône Partager 📤 → **Sur l'écran d'accueil**
3. Nommez l'app → **Ajouter**
4. Lancez depuis l'écran d'accueil

---

## 💾 Fonctionnement Offline

### Que se passe-t-il sans internet ?

#### ✅ **Fonctionnalités disponibles offline :**
- ✔️ Créer des ventes
- ✔️ Consulter l'historique (caché)
- ✔️ Voir les produits (cache)
- ✔️ Voir les clients (cache)
- ✔️ Navigation complète de l'app
- ✔️ Génération de factures

#### ⏸️ **Fonctionnalités limitées :**
- ⚠️ Pas de nouvelles données serveur
- ⚠️ Pas de téléchargement d'images
- ⚠️ Synchronisation en attente

---

## 🔄 Synchronisation

### Synchronisation Automatique
- **Détection** : Dès que la connexion revient
- **Fréquence** : Toutes les 5 minutes si données en attente
- **Processus** : Invisible pour l'utilisateur

### Synchronisation Manuelle
1. Cliquez sur le **widget de statut** (coin bas-droit)
2. Bouton **"🔄 Synchroniser maintenant"**
3. Attendez la confirmation

### Statuts possibles
- 🌐 **En ligne** : Connexion active
- 📡 **Hors ligne** : Mode offline actif
- 🔄 **Synchronisation...** : Envoi des données
- ⚠️ **Erreur sync** : Problème détecté

---

## 🛠️ Architecture Technique

### Fichiers créés

```
inve-app/
├── manifest.json                    # Configuration PWA
├── js/
│   ├── service-worker.js           # Cache & offline
│   ├── offline-db.js               # Stockage IndexedDB
│   ├── sync-manager.js             # Gestion synchronisation
│   └── offline-status.js           # Widget interface
└── pagesweb_cn/
    └── sync_api.php                # API de synchronisation
```

### Stratégies de Cache

#### 1. **Cache First** (Assets statiques)
```
Requête → Cache → Réseau (si absent)
```
- CSS, JS, images, fonts
- Chargement ultra-rapide

#### 2. **Network First** (Pages dynamiques)
```
Requête → Réseau → Cache (si offline)
```
- Pages PHP
- Données à jour prioritaires

#### 3. **Network First + Queue** (API)
```
Requête → Réseau → Queue (si échec) → Sync plus tard
```
- Créations de ventes
- Modifications de données

---

## 📊 IndexedDB Structure

### Tables créées automatiquement

#### **sales** : Ventes offline
```javascript
{
    id: 1,
    client_code: "CLIENT_123",
    products: [...],
    total: 50000,
    discount: 5000,
    final_total: 45000,
    timestamp: 1738569600000,
    synced: false,
    offline_id: "OFFLINE_1738569600_abc123"
}
```

#### **products** : Cache produits
```javascript
{
    id: 42,
    name: "Produit X",
    price: 15000,
    stock: 50,
    currency: "CDF",
    updated_at: 1738569600000
}
```

#### **sync_queue** : File d'attente
```javascript
{
    id: 1,
    type: "sale",
    data: {...},
    timestamp: 1738569600000,
    status: "pending",
    retry_count: 0
}
```

---

## 🎯 Utilisation Pratique

### Scénario : Vendeur en Zone Sans Réseau

**9h00** - Arrivée au marché (pas de 4G)
```
✅ App se charge depuis le cache
✅ Produits affichés (cache local)
✅ Prêt à vendre !
```

**9h30** - Première vente
```
✅ Création vente → Stockée dans IndexedDB
✅ Message : "Enregistrée localement"
✅ Facture générée (PDF local)
```

**10h00** - 5 ventes effectuées
```
📊 Widget affiche : "5 ventes en attente"
📡 Statut : Hors ligne
```

**12h00** - Retour au bureau (WiFi disponible)
```
🌐 Détection connexion automatique
🔄 Synchronisation auto des 5 ventes
✅ "Synchronisation terminée"
```

---

## 🔧 API de Synchronisation

### Endpoints disponibles

#### `POST /pagesweb_cn/sync_api.php?action=sync_sale`
Synchronise une vente offline

**Request :**
```json
{
    "action": "sync_sale",
    "data": {
        "client_code": "CLIENT_123",
        "products": "[...]",
        "total": 50000,
        "offline_id": "OFFLINE_1738569600_abc"
    }
}
```

**Response :**
```json
{
    "success": true,
    "server_id": 42,
    "synced_at": "2026-02-02 12:30:45"
}
```

#### `GET /pagesweb_cn/sync_api.php?action=get_products`
Récupère les produits pour le cache

#### `GET /pagesweb_cn/sync_api.php?action=get_clients`
Récupère les clients pour le cache

#### `POST /pagesweb_cn/sync_api.php?action=batch_sync`
Synchronise plusieurs éléments en une fois

---

## ⚠️ Gestion des Conflits

### Situations possibles

#### 1. **Vente déjà synchronisée**
```
✅ Détection par offline_id
✅ Pas de doublon créé
✅ Marquée comme synced
```

#### 2. **Stock insuffisant au moment de la sync**
```
❌ Transaction annulée
📌 Vente reste en queue
⚠️ Alerte affichée
👤 Action manuelle requise
```

#### 3. **Client supprimé entre-temps**
```
❌ Sync échouée
🔄 Retry automatique (max 3 fois)
📧 Notification admin
```

---

## 📈 Monitoring & Debug

### Console Browser (F12)

#### Vérifier le Service Worker
```javascript
navigator.serviceWorker.getRegistration().then(reg => {
    console.log('Service Worker:', reg ? 'Actif' : 'Inactif');
});
```

#### Vérifier IndexedDB
```javascript
offlineDB.getStats().then(stats => {
    console.log('Stats:', stats);
});
```

#### Force une synchronisation
```javascript
syncManager.forceSync();
```

#### Vider le cache
```javascript
caches.keys().then(names => {
    names.forEach(name => caches.delete(name));
});
```

---

## 🔒 Sécurité

### Mesures implémentées

1. **Validation côté serveur** : Toutes les données synchronisées sont re-validées
2. **Tokens offline** : IDs uniques empêchent les doublons
3. **Transactions SQL** : Rollback automatique en cas d'erreur
4. **Session verification** : Authentification vérifiée à chaque sync
5. **HTTPS requis** : Service Workers nécessitent HTTPS

### Données sensibles
- ❌ Pas de mots de passe en cache
- ❌ Pas de tokens d'auth en IndexedDB
- ✅ Seulement données métier
- ✅ Nettoyage auto après 30 jours

---

## 🐛 Troubleshooting

### Problème : Service Worker ne s'installe pas
**Solution :**
```bash
# Vérifier HTTPS
# Service Worker nécessite HTTPS (ou localhost)
```

### Problème : Synchronisation ne démarre pas
**Solution :**
```javascript
// Console Browser
syncManager.forceSync();

// Ou réinitialiser
await offlineDB.cleanupOldData(0);
```

### Problème : Trop de données en cache
**Solution :**
```javascript
// Nettoyer les données synchronisées anciennes
await offlineDB.cleanupOldData(7); // 7 jours
```

### Problème : Ventes dupliquées
**Solution :**
```sql
-- Vérifier les doublons
SELECT offline_id, COUNT(*) 
FROM sells 
WHERE offline_id IS NOT NULL 
GROUP BY offline_id 
HAVING COUNT(*) > 1;

-- Supprimer doublons (garder le plus ancien)
DELETE s1 FROM sells s1
INNER JOIN sells s2 
WHERE s1.offline_id = s2.offline_id 
AND s1.id > s2.id;
```

---

## 📊 Performance

### Métriques attendues

| Métrique | Online | Offline |
|----------|--------|---------|
| Chargement initial | ~2s | ~0.5s |
| Création vente | ~1s | ~0.2s |
| Liste produits | ~1.5s | ~0.1s |
| Synchronisation | N/A | ~3s/vente |

### Optimisations

1. **Cache stratégique** : Seulement ressources critiques
2. **Lazy loading** : Images chargées à la demande
3. **Compression** : Gzip sur tous les assets
4. **IndexedDB** : Plus rapide que localStorage

---

## 🎓 Formation Utilisateurs

### Message aux vendeurs

> **📱 Votre app fonctionne maintenant partout !**
> 
> - ✅ Installez-la sur votre téléphone
> - ✅ Vendez même sans internet
> - ✅ Les données se synchronisent automatiquement
> - ✅ Widget en bas à droite = statut en temps réel
> 
> **En cas de doute :** Vérifiez le widget. S'il affiche des chiffres, vos ventes sont en attente de sync.

---

## 📞 Support

### Logs à fournir en cas de problème

```javascript
// Console Browser (F12)
const diagnostic = {
    serviceWorker: await navigator.serviceWorker.getRegistration(),
    dbStats: await offlineDB.getStats(),
    syncStatus: syncManager.getStatus(),
    caches: await caches.keys()
};

console.log('📋 Diagnostic:', JSON.stringify(diagnostic, null, 2));
```

---

## 🚀 Prochaines Améliorations

### Phase 2 (Optionnel)
- [ ] Synchronisation push notifications
- [ ] Mode photo offline (compression)
- [ ] Rapports offline avancés
- [ ] Multi-utilisateur conflict resolution
- [ ] Background sync périodique (même app fermée)

---

## ✅ Checklist Déploiement

- [ ] Migration SQL exécutée
- [ ] Service Worker enregistré sur toutes les pages
- [ ] Manifest.json accessible
- [ ] Icônes PWA générées (72x72 → 512x512)
- [ ] HTTPS activé (obligatoire)
- [ ] Test installation Android
- [ ] Test installation iOS
- [ ] Test scénario offline complet
- [ ] Formation équipe effectuée
- [ ] Documentation partagée

---

**Version:** 1.0.0  
**Date:** 2 Février 2026  
**Auteur:** Système PWA CartelPlus Congo  
**Licence:** Propriétaire

---

🎉 **Votre application est maintenant une PWA complète !**

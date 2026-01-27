# 🚀 INVE-APP - Système de Gestion Commerciale Complet

## 📋 Vue d'Ensemble

Système professionnel de gestion POS avec **système d'abonnement intégré**, **marges vendeurs**, **rapports journaliers** et **gestion des codes d'accès**.

---

## ✨ Fonctionnalités Implémentées

### 1. **Dashboard Marges** (`house_marge.php`)
- ✅ Bénéfices maison par produit
- ✅ Marges par vendeur
- ✅ Stock disponible en temps réel
- ✅ Filtrage avancé (maison, vendeur, dates)
- ✅ Affichage professionnel des statistiques

### 2. **Rapports Journaliers** (`reports.php`)
- ✅ Rapport complet des ventes du jour
- ✅ Filtrage par date, maison, vendeur
- ✅ **Export PDF** automatique
- ✅ Répartition par mode de paiement
- ✅ Statistiques en temps réel

### 3. **Système d'Abonnement Complet**

#### **Codes d'Essai (7 jours gratuit)**
- ✅ Formulaire d'inscription (`trial_form.php`)
- ✅ Génération code d'essai automatique
- ✅ Validation du code (`trial_verify.php`)
- ✅ Création client actif automatique
- ✅ Accès 7 jours avec expiration

#### **Codes d'Abonnement (1 mois payant)**
- ✅ Formulaire d'achat (`subscription_buy.php`)
- ✅ Montant configurable
- ✅ Génération code unique
- ✅ Page d'attente validation (`subscription_pending.php`)
- ✅ Validation admin avec création compte automatique

#### **Interface Admin Complète** (`admin_subscription_manager.php`)
- ✅ Dashboard codes d'essai
- ✅ Dashboard codes d'abonnement
- ✅ Gestion clients actifs
- ✅ Bouton "Valider" pour activer les abonnements payants
- ✅ Historique complet

#### **Système d'Authentification Clients**
- ✅ Middleware auth (`check_client_auth.php`)
- ✅ Vérification client actif
- ✅ Expiration automatique
- ✅ Session & Cookie management

### 4. **Portail d'Entrée** (`portal.php`)
- ✅ Page d'accueil professionnelle
- ✅ 3 options : Essai / Achat / Code existant
- ✅ Redirection client actif auto
- ✅ Design moderne avec animations

---

## 🗄️ Structure Base de Données

### Tables Créées

```sql
-- Codes d'essai (7 jours)
CREATE TABLE trial_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(36) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(100),
    status ENUM('unused', 'activated', 'expired'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    expired_at TIMESTAMP NULL
);

-- Codes d'abonnement (1 mois)
CREATE TABLE subscription_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(36) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(100),
    payment_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'validated', 'active', 'suspended', 'expired'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated_at TIMESTAMP NULL,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    notes TEXT
);

-- Clients actifs
CREATE TABLE active_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(36) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    company_name VARCHAR(100),
    subscription_type ENUM('trial', 'monthly'),
    trial_code_id INT,
    subscription_code_id INT,
    status ENUM('active', 'suspended'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (trial_code_id) REFERENCES trial_codes(id),
    FOREIGN KEY (subscription_code_id) REFERENCES subscription_codes(id)
);
```

---

## 🚀 Installation

### Étape 1: Créer les Tables
```
1. Aller à: http://localhost/inve-app/pagesweb_cn/migration_subscription_system.php
2. Cliquer sur le lien "Aller au Dashboard Admin"
```

### Étape 2: Vérifier les Fichiers Créés
```
pagesweb_cn/
├── migration_subscription_system.php  [Migration BD]
├── house_marge.php                    [Dashboard Marges]
├── reports.php                        [Rapports Journaliers]
├── admin_subscription_manager.php     [Admin Abonnements]
├── trial_form.php                     [Formulaire Essai]
├── trial_verify.php                   [Validation Essai]
├── subscription_buy.php               [Achat Abonnement]
├── subscription_pending.php           [Attente Validation]
└── check_client_auth.php              [Middleware Auth]

root/
├── portal.php                         [Portail d'Entrée]
```

---

## 📖 Flux Utilisateur

### **Scénario 1: Essai Gratuit**
```
1. Utilisateur → portal.php
2. Clique "Commencer Essai"
3. Remplit trial_form.php
4. Reçoit code: TRIAL-xxxxx
5. Clique lien ou va sur trial_verify.php
6. Code validé → Compte créé → Accès 7 jours
7. Redirigé vers seller_dashboard.php
```

### **Scénario 2: Abonnement Payant**
```
1. Utilisateur → portal.php
2. Clique "Acheter Abonnement"
3. Remplit subscription_buy.php
4. Reçoit code: SUB-xxxxx
5. Effectue paiement
6. Contacte admin avec code + preuve
7. Admin reçoit sur admin_subscription_manager.php
8. Admin clique "Valider"
9. Système crée client actif automatiquement
10. Client accède au système → seller_dashboard.php
```

### **Scénario 3: Code Existant**
```
1. Utilisateur → portal.php
2. Clique "J'ai un Code"
3. Va sur trial_verify.php (pour essai)
4. OU va sur subscription_pending.php (pour abonnement)
5. Entre son code
6. Compte activé immédiatement si code validé
```

---

## 🔐 Accès Admin

**URL Admin:** `http://localhost/inve-app/pagesweb_cn/admin_subscription_manager.php`

**Restriction:** Réservé à `$_SESSION['admin_id'] == 1`

**À adapter:** Remplacer le système de vérification admin avec votre login

---

## 💼 Utilisation Dashboards

### **Dashboard Marges** 
```
URL: http://localhost/inve-app/pagesweb_cn/house_marge.php
Accès: Admin seulement
Affiche:
  - Tableau marges produits (marge/unité, stock, vendeurs)
  - Tableau marges vendeurs (montant, profit, ventes)
Filtres: Maison, Vendeur, Dates
```

### **Rapports Journaliers**
```
URL: http://localhost/inve-app/pagesweb_cn/reports.php
Accès: Admin seulement
Affiche:
  - Statistiques globales (total ventes, remises, qtés)
  - Répartition par mode paiement
  - Détail complet des transactions
Filters: Dates, Maison, Vendeur
Actions: Export PDF
```

---

## 🛠️ Configuration

### Tarification Abonnement
**Fichier:** `pagesweb_cn/subscription_buy.php` ligne ~19
```php
$payment_amount = 50; // À adapter (actuellement 50 000 FC)
```

### Durée Essai
**Fichier:** `pagesweb_cn/trial_verify.php` ligne ~55
```php
$expires_at = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 jours
```

### Durée Abonnement
**Fichier:** `pagesweb_cn/admin_subscription_manager.php` ligne ~75
```php
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days')); // 30 jours
```

### Contact Info
À modifier dans les fichiers:
- `trial_form.php`
- `subscription_buy.php`  
- `subscription_pending.php`
- `portal.php`

```php
📧 Email: admin@cartelplus.cd
📱 WhatsApp: +243 123 456 789
📞 Téléphone: +243 123 456 789
```

---

## 📊 Statistiques & Monitoring

### Vérifier Clients Actifs
```sql
SELECT * FROM active_clients 
WHERE status = 'active' AND expires_at > NOW();
```

### Vérifier Codes d'Essai
```sql
SELECT * FROM trial_codes WHERE status = 'activated';
```

### Vérifier Codes Abonnement
```sql
SELECT * FROM subscription_codes WHERE status IN ('pending', 'validated', 'active');
```

---

## 🔄 Cycle de Vie Compte

```
ESSAI GRATUIT:
unused → activated → active (7j) → expired (auto)

ABONNEMENT PAYANT:
unused → pending → validated → active (30j) → expired (auto) OU suspended (admin)
```

---

## ⚙️ Intégration avec Systèmes Existants

### Auth Existante
Adapter `check_client_auth.php` pour utiliser votre système auth actuel:
```php
// Remplacer par votre logique
$client_code = $_SESSION['client_code'] ?? $_COOKIE['client_code'];
```

### Sessions
Les codes client sont stockés en:
- `$_SESSION['client_code']`
- `$_COOKIE['client_code']` (optionnel)

### Restrictions Accès
Modifier les vérifications:
```php
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: connect-parse.php?role=seller");
    exit;
}
```

---

## 📝 Notes Importantes

1. **Sécurité Admin:** La vérification admin utilise `$_SESSION['admin_id'] == 1`
   - À adapter avec votre système de login
   
2. **Génération Codes:** Utilise `uniqid()` + préfixe
   - TRIAL-xxxxx pour essai
   - SUB-xxxxx pour abonnement
   - CLI-xxxxx pour clients actifs

3. **Expiration Auto:** Les clients expirent automatiquement
   - Vérification sur chaque accès
   - Redirection vers `portal.php?message=access_expired`

4. **PDF Export:** Utilise TCPDF (déjà inclus)
   - Vérifie `vendor/autoload.php`

5. **Email:** Aucun email automatique implémenté
   - À intégrer selon vos besoins

---

## 📞 Support & Questions

Pour adapter ou modifier:
1. Marges produits → Modifier `house_marge.php`
2. Rapports → Modifier `reports.php`
3. Tarification → Modifier `subscription_buy.php`
4. Admin access → Adapter `admin_subscription_manager.php`
5. Durées → Adapter les `strtotime('+X days')`

---

## ✅ Checklist Implémentation

- [x] Tables BD créées
- [x] Formulaire essai (7j)
- [x] Formulaire achat (1m)
- [x] Validation codes
- [x] Interface admin
- [x] Dashboard marges
- [x] Rapports journaliers
- [x] Export PDF
- [x] Authentification clients
- [x] Redirection automatique
- [x] Portail d'accueil

---

**Version:** 1.0  
**Date:** 27 Janvier 2026  
**Statut:** ✅ Prêt pour déploiement

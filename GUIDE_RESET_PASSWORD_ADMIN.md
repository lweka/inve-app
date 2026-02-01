# 🔑 Système de Réinitialisation de Mot de Passe Admin

## Vue d'ensemble

Trois méthodes pour réinitialiser un mot de passe administrateur oublié :

1. **Méthode 1** : Via le formulaire web (sans token)
2. **Méthode 2** : Via un token sécurisé (email)
3. **Méthode 3** : API directe (pour intégration)

---

## 📋 Flux Utilisateur

### Scénario : L'administrateur a oublié son mot de passe

1. **Admin clique sur "Mot de passe oublié?"** sur la page de connexion
   - Lien : `/pagesweb_cn/admin_login_form.php`

2. **Accès à la page de réinitialisation**
   - URL : `/pagesweb_cn/admin_forgot_password.php`

3. **Étape 1 : Vérifier le code client**
   - Admin entre son **code client** (ex: `CLI-TRIAL-697F99F7CC646`)
   - Le système vérifie que le code existe en base et est lié à un compte admin

4. **Étape 2 : Créer un nouveau mot de passe**
   - Admin crée un nouveau mot de passe (min 6 caractères)
   - Confirme le mot de passe
   - Valide

5. **Résultat**
   - ✅ Mot de passe changé avec succès
   - Peut maintenant se connecter avec le nouveau mot de passe

---

## 🔒 Sécurité

### Session-based (Méthode actuelle)

```php
$_SESSION['password_reset_token'] = $token;
$_SESSION['password_reset_admin_id'] = $admin_id;
$_SESSION['password_reset_time'] = time();
```

**Validité du token** : 1 heure  
**Stockage** : Session serveur (sécurisé)  
**Risques** : Aucun si la session est sécurisée

### Token-based (Alternative)

Pour une intégration avec email ou SMS :

```sql
UPDATE admin_accounts 
SET reset_token = 'abc123...', reset_token_expires = NOW() + INTERVAL 1 HOUR
WHERE client_code = 'CLI-TRIAL-XXX'
```

**Lien** : `admin_reset_with_token.php?token=abc123`  
**Validité** : 1 heure  
**Stockage** : Base de données

---

## 📁 Fichiers Créés

| Fichier | Objectif |
|---------|----------|
| `admin_forgot_password.php` | Formulaire de réinitialisation (2 étapes) |
| `admin_reset_with_token.php` | Réinitialisation via token URL |
| `admin_password_reset_api.php` | API JSON pour intégrations |
| `migration_add_admin_reset_token.sql` | Ajouter colonnes BD |

---

## 🔧 Installation

### 1. Exécuter la migration

```sql
-- Ajoute les colonnes reset_token et reset_token_expires à admin_accounts
-- Exécutez le fichier :
migration_add_admin_reset_token.sql
```

### 2. Vérifier la structure (optionnel)

```sql
DESCRIBE admin_accounts;

-- Doit montrer :
-- reset_token VARCHAR(255) NULL
-- reset_token_expires DATETIME NULL
```

---

## 🧪 Test

### Test 1 : Réinitialisation simple (sans token)

1. Aller sur : `admin_login_form.php`
2. Cliquer : "🔑 Mot de passe oublié?"
3. Entrer le code client
4. Entrer nouveau mot de passe
5. ✅ Succès

### Test 2 : Réinitialisation avec token

```bash
# Générer un token via API
curl -X POST https://inve-app.cartelplus.site/pagesweb_cn/admin_password_reset_api.php \
  -d "action=request_reset&client_code=CLI-TRIAL-697F99F7CC646"

# Réponse :
# {
#   "success": true,
#   "reset_token": "abc123...",
#   "reset_url": "admin_reset_with_token.php?token=abc123"
# }

# Réinitialiser via token
curl -X POST https://inve-app.cartelplus.site/pagesweb_cn/admin_password_reset_api.php \
  -d "action=reset_with_token&token=abc123&new_password=NewPass123&confirm_password=NewPass123"
```

---

## 🔐 Différences de sécurité

### Session-based (Actuel)
```
✅ Plus simple
✅ Moins d'infrastructure
✅ Token en mémoire (pas en BD)
❌ Expire à la fermeture du navigateur
❌ Une session = un reset
```

### Token-based (Optionnel)
```
✅ Peut être envoyé par email/SMS
✅ Valide même après fermeture du navigateur
✅ Compatible avec OAuth/API
❌ Nécessite stockage en BD
❌ Risque si token compromis (mais temps limité)
```

---

## 💡 Cas d'usage

### Cas 1 : Admin sur site principal
1. Clic "Mot de passe oublié"
2. Remplit le formulaire
3. ✅ Mot de passe réinitialisé

### Cas 2 : Admin sur appareil différent
1. Reçoit un email avec lien token
2. Clique le lien : `admin_reset_with_token.php?token=XXX`
3. Remplit nouveau mot de passe
4. ✅ Réinitialisé

### Cas 3 : Admin via API/mobile
1. App mobile : POST à `admin_password_reset_api.php`
2. Récupère reset_token
3. POST avec nouveau mot de passe
4. ✅ Réinitialisé

---

## 🚨 Dépannage

### "Code client non trouvé"
- ✅ Vérifier que le code est exact (case-sensitive)
- ✅ Vérifier que le code existe dans `active_clients` ou `admin_accounts`
- ✅ Vérifier que un admin_account est lié à ce code

### "Token invalide ou expiré"
- ✅ Le token dure 1 heure
- ✅ Recommencer la procédure de réinitialisation
- ✅ Vérifier l'horloge du serveur (NTP)

### "Réinitialisation échouée"
- ✅ Vérifier que la migration a été exécutée
- ✅ Vérifier les logs PHP pour les erreurs PDO
- ✅ Vérifier les permissions MySQL sur la table

---

## 📊 API Endpoints

### Request Reset
```bash
POST /pagesweb_cn/admin_password_reset_api.php
Content-Type: application/x-www-form-urlencoded

action=request_reset&client_code=CLI-TRIAL-123
```

**Réponse (succès)** :
```json
{
  "success": true,
  "message": "Token généré avec succès",
  "reset_token": "abc123...",
  "admin_name": "Jean Dupont",
  "reset_url": "admin_reset_with_token.php?token=abc123"
}
```

### Reset with Token
```bash
POST /pagesweb_cn/admin_password_reset_api.php
Content-Type: application/x-www-form-urlencoded

action=reset_with_token&token=abc123&new_password=NewPass&confirm_password=NewPass
```

**Réponse (succès)** :
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès"
}
```

---

## 🔄 Flux complet

```
┌─────────────────────────────────────────────────────────┐
│ Admin oublie mot de passe                               │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│ Clique "Mot de passe oublié?" (admin_login_form.php)   │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│ admin_forgot_password.php - Étape 1                     │
│ Entrer le code client                                   │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
  ┌──────────────────────────┐
  │ Code valide?             │
  └──────┬──────────┬────────┘
         │ Non      │ Oui
         ▼          ▼
      ❌ Error   ✅ Succès
                   │
                   ▼
        ┌─────────────────────────┐
        │ Session token généré    │
        │ Étape 2 disponible      │
        └──────────┬──────────────┘
                   │
                   ▼
        ┌─────────────────────────┐
        │ Admin entre nouveau     │
        │ mot de passe            │
        │ & le confirme           │
        └──────────┬──────────────┘
                   │
                   ▼
        ┌─────────────────────────┐
        │ Validation côté serveur │
        │ - Min 6 caractères      │
        │ - Correspond            │
        │ - Non vide              │
        └──────┬──────────┬───────┘
               │ Non      │ Oui
               ▼          ▼
            ❌ Error   ✅ Update
                        │
                        ▼
                  ┌─────────────────┐
                  │ BD mise à jour  │
                  │ Session nettoyée│
                  └────────┬────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │ Page succès     │
                  │ Redirection     │
                  └─────────────────┘
```

---

## 📝 Notes importantes

1. **Pas d'email** : Actuellement, aucun email n'est envoyé. L'admin voit le token dans le navigateur.
2. **Session sécurisée** : Assurez-vous que `session.secure=1` et `session.httponly=1` en production.
3. **HTTPS requis** : En production, utilisez HTTPS pour tous ces endpoints.
4. **Logs** : Envisager de logger les tentatives de réinitialisation pour l'audit.
5. **Rate limiting** : Ajouter un rate limiting pour empêcher les attaques par force brute.

---

**Version** : 1.0  
**Date** : 1er février 2026  
**Auteur** : CartelPlus Congo

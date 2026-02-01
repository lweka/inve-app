# Credentials Google Cloud - reCAPTCHA Enterprise

Ce dossier contient les fichiers de configuration pour Google reCAPTCHA Enterprise v3.

## Configuration

### 1. Créer un fichier de Service Account

1. Accédez à [Google Cloud Console](https://console.cloud.google.com/)
2. Sélectionnez votre projet : **inve-app-486119**
3. Allez à **IAM & Admin** → **Service Accounts**
4. Créez une nouvelle Service Account ou sélectionnez une existante
5. Générez une clé JSON
6. Téléchargez le fichier JSON

### 2. Placer le fichier

Enregistrez le fichier JSON téléchargé en tant que :
```
pagesweb_cn/credentials/recaptcha-service-account.json
```

### 3. Permissions requises

Assurez-vous que la Service Account a les rôles suivants :
- `recaptchaenterprise.assessments.create`
- `roles/recaptchaenterprise.agent`

### 4. Configuration dans le code

Le fichier `admin_register.php` utilise automatiquement ce fichier pour vérifier les tokens reCAPTCHA.

## Structure du fichier JSON attendu

```json
{
  "type": "service_account",
  "project_id": "inve-app-486119",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "...",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "..."
}
```

## Variables utilisées

- **Site Key** : `6Ldbz1wsAAAAAD0W6Jx_zDpA-dikUxUj-3oBKSaC`
- **Project ID** : `inve-app-486119`

## Sécurité

⚠️ **Ne jamais commiter le fichier JSON dans Git !**

Ajoutez à `.gitignore` :
```
pagesweb_cn/credentials/recaptcha-service-account.json
```

## Vérification du fonctionnement

1. Allez sur le formulaire d'enregistrement
2. Remplissez les champs
3. Cliquez sur "Créer mon Compte"
4. Un indicateur de chargement devrait s'afficher : "⚙️ Vérification de sécurité en cours..."
5. Le formulaire devrait se soumettre automatiquement après la génération du token

## Troubleshooting

**Erreur "Undefined" sur le score** :
- Vérifier que le fichier credentials est présent et valide
- Vérifier les permissions de la Service Account

**Token non généré** :
- Vérifier la clé site key dans le code
- Vérifier la console du navigateur pour les erreurs

**Erreur CORS** :
- Ce n'est pas un problème CORS, la vérification se fait côté serveur
- Vérifier les logs du serveur

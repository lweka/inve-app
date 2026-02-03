# 📊 Système de Rapports - Guide d'Utilisation

## 🎯 Vue d'ensemble

Le système de rapports permet aux administrateurs de:
- 📋 Consulter les ventes du jour/période
- 📊 Voir les statistiques globales (total, remises, quantités)
- 📄 Exporter un rapport en PDF
- 📧 Envoyer le rapport par email

## 🌐 Accès à la Page

**URL Production:**
```
https://inve-app.cartelplus.site/pagesweb_cn/reports.php
```

**Authentification requise:**
- Session client_code valide
- Authentification admin via require_admin_auth.php

**Accès gratuit (diagnostic):**
```
https://inve-app.cartelplus.site/pagesweb_cn/diagnose_reports.php
```

## 🔍 Fonctionnalités

### 1. Filtrage des Données

| Filtre | Requis | Options |
|--------|--------|---------|
| Date de début | ✅ | Sélecteur de date (défaut: aujourd'hui) |
| Date de fin | ✅ | Sélecteur de date (défaut: aujourd'hui) |
| Maison | ❌ | Dropdown (toutes les maisons) |
| Vendeur | ❌ | Dropdown (tous les vendeurs) |

**Boutons disponibles:**
- 🔄 **Filtrer** - Appliquer les filtres
- 🔁 **Réinitialiser** - Revenir aux paramètres par défaut
- 📄 **PDF** - Télécharger le rapport en PDF
- ✉️ **Email** - Envoyer le rapport à l'administrateur

### 2. Statistiques Affichées

Après avoir cliqué "Filtrer", les statistiques suivantes s'affichent:

```
┌─────────────────────────────────────┐
│ STATISTIQUES GLOBALES              │
├─────────────────────────────────────┤
│ Total Ventes:        12,450,000 FC │
│ Remises Accordées:      150,000 FC │
│ Quantité Vendue:              245 u│
│ Nombre de Transactions:         35 │
└─────────────────────────────────────┘
```

**Formules de calcul:**
- **Total Ventes** = Σ(quantité × prix unitaire) - Σremises
- **Remises** = Σ montants de remise accordée
- **Quantité** = Σ quantités vendues (hors KIT)
- **Transactions** = nombre de lignes uniques

### 3. Export PDF

**Format du rapport:**
- Format: A4 portrait
- Marges: 14mm
- Header: Logo + titre + dates
- Section statistiques: Résumé clé
- Tableau détail: Tous les produits vendus
- Footer: Date/heure génération

**Contenu du PDF:**
```
Date | Produit | Qte | P.U. | Total | Rem. | Vendeur
──────────────────────────────────────────────────────
01/02 | Article 1 | 5 | 1000 | 5000 | 0 | Jean
02/02 | Article 2 | 3 | 2000 | 6000 | 500 | Marie
...
```

### 4. Envoi par Email

**Fonctionnement:**
1. Cliquer "Email"
2. Formulaire POST envoie la requête
3. PDF généré et attaché
4. Email envoyé à l'administrateur
5. Message de confirmation affichée

**Email reçu:**
```
De: Cartelplus Congo <cartelplus-congo@cartelplus.site>
À: [email administrateur]
Sujet: Rapport de Ventes - 01/02/2026 au 03/02/2026
Pièce jointe: rapport-2026-02-03-143025.pdf

Corps:
Bonjour,

Veuillez trouver ci-joint le rapport de ventes.

Résumé:
- Total Ventes: 12,450,000 FC
- Remises Accordées: 150,000 FC
- Quantité Vendue: 245
- Nombre de Transactions: 35

Cordialement,
Cartelplus Congo
```

## 🛠️ Troubleshooting

### Problème: Page ne charge pas

**Solutions:**
1. Vérifier l'authentification (require_admin_auth.php)
2. Vérifier la connexion BD (test_reports.php)
3. Consulter les logs du serveur
4. Accéder à diagnose_reports.php pour diagnostic

### Problème: Données ne s'affichent pas

**Solutions:**
1. Vérifier les dates sélectionnées (date_from ≤ date_to)
2. Vérifier qu'il y a des mouvements pour la période
3. Vérifier le filtre maison/vendeur (optionnel)
4. Essayer "Réinitialiser" pour revenir aux paramètres par défaut

### Problème: PDF ne se télécharge pas

**Solutions:**
1. Vérifier que TCPDF est disponible
2. Vérifier que le navigateur n'a pas bloqué le téléchargement
3. Vérifier que l'extension GD est activée (php -m | grep gd)
4. Essayer avec un autre navigateur

### Problème: Email ne s'envoie pas

**Solutions:**
1. Vérifier que l'email admin est défini (active_clients.email)
2. Vérifier les paramètres SMTP:
   - Host: smtp.titan.email
   - Port: 587
   - Security: STARTTLS
3. Vérifier les logs Hostinger
4. Tester la connectivité SMTP:
   ```php
   php pagesweb_cn/test_reports.php
   ```

### Problème: Bouton Email n'apparaît pas

**Solutions:**
1. Vider le cache du navigateur (Ctrl+Shift+Delete)
2. Actualiser la page (Ctrl+F5)
3. Tester avec un autre navigateur
4. Consulter la console du navigateur (F12 → Console)

## 📈 Interprétation des Données

### Total Ventes
- Montant réel après remises
- En francs congolais (FC)
- Incluent les frais (si applicables)
- Excluent les KIT (comptés séparément)

### Remises
- Cumul de toutes les remises accordées
- En francs congolais (FC)
- Déjà soustraites du total

### Quantité Vendue
- Nombre total d'unités vendues
- Exclut les KIT parents
- Inclut les composants KIT

### Nombre de Transactions
- Nombre de lignes distinctes
- Une transaction = 1 produit + 1 qty
- KIT parent et composants comptés

## 🔐 Sécurité

**Protections implémentées:**
- ✅ Session client_code requise
- ✅ Authentification admin requise
- ✅ Filtrage des données par client_code
- ✅ Protection CSRF (formulaire POST)
- ✅ Échappement des caractères spéciaux
- ✅ Requêtes PDO préparées

## 📞 Support

**En cas de problème:**
1. Consulter diagnose_reports.php
2. Vérifier les logs du serveur
3. Contacter l'administrateur Hostinger
4. Consulter CHECKLIST_REPORTS.md

**Fichiers utiles:**
- `diagnose_reports.php` - Diagnostic complet
- `test_reports.php` - Test des dépendances
- `CHANGES_REPORTS_20260203.md` - Changelog détaillé
- `CHECKLIST_REPORTS.md` - Checklist de vérification
- `SESSION_SUMMARY_20260203.txt` - Résumé de session

## 📊 Exemples de Rapports

### Rapport Quotidien
```
Période: 03/02/2026 au 03/02/2026
Maison: Toutes
Vendeur: Tous
Statistiques: 
- Total: 1,250,000 FC
- Remises: 15,000 FC
- Qte: 50 u
- Transactions: 8
```

### Rapport Mensuel
```
Période: 01/02/2026 au 28/02/2026
Maison: Kinshasa
Vendeur: Jean Dupont
Statistiques:
- Total: 45,500,000 FC
- Remises: 850,000 FC
- Qte: 2,150 u
- Transactions: 284
```

## 🎓 Tutoriels

### Générer un rapport PDF
1. Accéder à reports.php
2. Sélectionner les dates
3. Optionnel: Sélectionner maison/vendeur
4. Cliquer "Filtrer"
5. Vérifier les statistiques
6. Cliquer "PDF"
7. Le fichier se télécharge

### Envoyer un rapport par email
1. Suivre les étapes "Générer un rapport PDF"
2. Cliquer "Email"
3. Attendre la confirmation
4. Vérifier la boîte mail

### Comparer deux périodes
1. Générer rapport période 1 (ex: 01/02 au 15/02)
2. Exporter PDF
3. Réinitialiser
4. Générer rapport période 2 (ex: 16/02 au 28/02)
5. Exporter PDF
6. Comparer les deux rapports

## 📝 Notes Importantes

- Les données ne peuvent pas être modifiées depuis cette page
- Les rapports sont générés en temps réel (pas de cache)
- Les fichiers PDF téléchargés ne sont pas conservés sur le serveur
- Les emails sont envoyés immédiatement (pas de queue)
- Le fuseau horaire utilisé est celui du serveur

## 🔄 Mises à Jour Récentes

**Session du 3 février 2026:**
- ✅ PDF design refactorisé (marges, statistiques, page breaks)
- ✅ Bouton Email converti en bouton POST
- ✅ CSS amélioré avec !important pour visibilité
- ✅ Documentation complète ajoutée
- ✅ Script de diagnostic ajouté

---

**Version:** 1.0  
**Dernière mise à jour:** 3 février 2026  
**Support:** Cartelplus Congo  
**Contact:** cartelplus-congo@cartelplus.site

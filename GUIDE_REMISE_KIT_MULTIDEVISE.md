# Guide : Réduction sur Kits Multi-Devises

## Vue d'ensemble

Le système INVE-APP gère désormais intelligemment les réductions appliquées aux kits contenant des produits en différentes devises (CDF et USD).

## Comportement du système

### 1. Kit sans réduction (Multi-devises)

**Exemple :**
```
KIT PRODUITS
- MALTINA × 3 = 3000.00 CDF
- SOURIS HP × 2 = 20.00 USD

Total: 3000.00 CDF + 20.00 USD
```

➡️ Le total est affiché avec les deux devises séparées.

---

### 2. Kit avec réduction (Multi-devises)

**Exemple :**
```
KIT PRODUITS
- MALTINA × 3 = 3000.00 CDF  
- SOURIS HP × 2 = 20.00 USD

Sous-total: 3000.00 CDF + 20.00 USD
Taux de change: 1 USD = 2500 CDF

Conversion USD → CDF:
20.00 USD × 2500 = 50,000.00 CDF

Total converti: 3000.00 + 50,000.00 = 53,000.00 CDF
Réduction: -5,000.00 CDF

Total final: 48,000.00 CDF
```

➡️ **Lorsqu'une réduction est appliquée sur un kit multi-devises :**
1. Le système convertit tous les montants USD en CDF en utilisant le taux de change configuré par l'administrateur pour la maison
2. Additionne tous les montants en CDF
3. Applique la réduction sur le total converti
4. Le résultat final est affiché uniquement en CDF

---

### 3. Kit mono-devise avec réduction

**Exemple (tout en CDF) :**
```
KIT PRODUITS
- MALTINA × 3 = 3000.00 CDF
- COCA × 2 = 2000.00 CDF

Sous-total: 5000.00 CDF
Réduction: -500.00 CDF

Total final: 4500.00 CDF
```

**Exemple (tout en USD) :**
```
KIT PRODUITS
- SOURIS HP × 2 = 20.00 USD
- CLAVIER HP × 1 = 15.00 USD

Sous-total: 35.00 USD
Réduction: -5.00 USD

Total final: 30.00 USD
```

➡️ Pour les kits mono-devise, la devise originale est conservée.

---

## Configuration requise

### Taux de change

L'administrateur doit configurer le taux de change USD pour chaque maison via la page :
```
pagesweb_cn/exchange_rate_manage.php?house_id=X
```

**Table de base de données :**
```sql
CREATE TABLE exchange_rate (
    house_id INT PRIMARY KEY,
    usd_rate DECIMAL(10,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Taux par défaut :** Si aucun taux n'est configuré, le système utilise **2500 CDF** comme taux par défaut.

---

## Affichage dans les différentes sections

### 1. **Point de Vente (seller_dashboard.php)**
- Le vendeur voit le total multi-devises avant d'appliquer une réduction
- Dès qu'une réduction est appliquée, le système affiche le total converti en CDF

### 2. **Historique des ventes (seller_sales_history.php)**
- Les kits sans réduction affichent : `3000.00 CDF + 20.00 USD`
- Les kits avec réduction affichent : `48,000.00 CDF`

### 3. **Tickets d'impression (seller_ticket_pdf.php)**
- Le ticket affiche tous les composants du kit avec leurs devises d'origine
- Si réduction appliquée sur kit multi-devises, le total final est affiché en CDF
- Une ligne "Remise appliquée" apparaît sous les composants du kit

**Exemple de ticket :**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         INVE-APP
    CartelPlus Congo
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Ticket: RCP-67890
Maison: Maison Principale
Vendeur: Jean Dupont
Client: Marie Kabongo
Paiement: cash
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ARTICLE              QTE    MONTANT
───────────────────────────────────
KIT PRODUITS         1      48,000 CDF

  > MALTINA          3      3,000 CDF
  > SOURIS HP        2      20.00 USD

  Remise appliquée          -5,000 CDF

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:                     48,000 CDF
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

      Merci de votre visite !
```

---

## Fichiers modifiés

| Fichier | Modification |
|---------|-------------|
| `seller_dashboard.php` | Ajout de la récupération du taux USD, constante JavaScript `USD_RATE` |
| `create_sale.php` | Logique de conversion USD → CDF avant application de la réduction |
| `seller_ticket_pdf.php` | Affichage adapté pour kits avec réduction multi-devises |
| `seller_ticket_pdf_old.php` | Même logique pour l'ancien format de ticket |
| `seller_sales_history.php` | Affichage correct des totaux convertis dans l'historique |

---

## Migration base de données

**⚠️ IMPORTANT :** Vous devez exécuter le script de migration pour ajouter la colonne `sell_currency` si ce n'est pas encore fait :

```sql
-- Fichier: migration_add_sell_currency.sql

ALTER TABLE product_movements 
ADD COLUMN sell_currency VARCHAR(3) DEFAULT 'CDF' AFTER unit_sell_price;

UPDATE product_movements pm
JOIN products p ON pm.product_id = p.id
SET pm.sell_currency = p.sell_currency
WHERE pm.sell_currency = 'CDF' AND pm.product_id IS NOT NULL;

UPDATE product_movements pm
SET pm.sell_currency = (
  SELECT DISTINCT p.sell_currency
  FROM product_movements pm_child
  JOIN products p ON pm_child.product_id = p.id
  WHERE pm_child.kit_id = pm.id AND pm_child.type = 'sale'
  LIMIT 1
)
WHERE pm.is_kit = 1 AND pm.product_id IS NULL AND pm.sell_currency = 'CDF';
```

---

## Cas d'utilisation testés

| # | Scénario | Comportement attendu |
|---|----------|---------------------|
| 1 | Produit simple CDF | Affichage normal en CDF |
| 2 | Produit simple CDF avec réduction | Réduction appliquée en CDF |
| 3 | Produit simple USD | Affichage normal en USD |
| 4 | Produit simple USD avec réduction | Réduction appliquée en USD |
| 5 | Kit 2 produits CDF | Total en CDF |
| 6 | Kit 2 produits CDF avec réduction | Total en CDF avec réduction |
| 7 | Kit 2 produits USD | Total en USD |
| 8 | Kit 2 produits USD avec réduction | Total en USD avec réduction |
| 9 | **Kit mixte CDF+USD sans réduction** | **Affiche "3000.00 CDF + 20.00 USD"** |
| 10 | **Kit mixte CDF+USD avec réduction** | **Convertit tout en CDF, applique réduction, affiche total CDF** |

---

## Support

Pour toute question ou problème concernant cette fonctionnalité, vérifiez :

1. ✅ Le taux de change est bien configuré dans `exchange_rate` pour votre maison
2. ✅ La migration SQL a été exécutée (colonne `sell_currency` existe)
3. ✅ Les produits ont bien leur devise configurée dans la table `products`

---

**Date de mise à jour :** 1er février 2026  
**Version :** 1.0  
**Auteur :** CartelPlus Congo - INVE-APP

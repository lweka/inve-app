-- ============================================================
-- Migration: Ajouter la devise (sell_currency) à product_movements
-- Date: 2026-01-31
-- ============================================================

-- Vérifier si la colonne existe déjà (exécution idempotente)
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_movements'
    AND COLUMN_NAME = 'sell_currency'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE product_movements ADD COLUMN sell_currency VARCHAR(3) DEFAULT ''CDF'' AFTER unit_sell_price',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remplir les devises existantes en fonction des produits liés
UPDATE product_movements pm
JOIN products p ON pm.product_id = p.id
SET pm.sell_currency = p.sell_currency
WHERE pm.sell_currency = 'CDF' AND pm.product_id IS NOT NULL;

-- Pour les kits (où product_id est NULL), on devra remplir depuis les composants
-- Cette requête met à jour les kits avec une devise basée sur le premier composant
UPDATE product_movements pm
SET pm.sell_currency = (
  SELECT DISTINCT p.sell_currency
  FROM product_movements pm_child
  JOIN products p ON pm_child.product_id = p.id
  WHERE pm_child.kit_id = pm.id AND pm_child.type = 'sale'
  LIMIT 1
)
WHERE pm.is_kit = 1 AND pm.product_id IS NULL AND pm.sell_currency = 'CDF';

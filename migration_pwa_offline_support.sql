-- Migration: Support Offline PWA
-- Ajoute les colonnes nécessaires pour tracker les ventes offline et leur synchronisation
-- Date: 2 Février 2026

-- Vérifier et créer colonnes pour ventes offline
ALTER TABLE sells 
ADD COLUMN IF NOT EXISTS offline_id VARCHAR(100) DEFAULT NULL COMMENT 'ID unique généré côté client pour ventes offline',
ADD COLUMN IF NOT EXISTS synced_from_offline TINYINT(1) DEFAULT 0 COMMENT 'Indique si vente synchronisée depuis mode offline';

-- Créer index pour performance
ALTER TABLE sells
ADD INDEX IF NOT EXISTS idx_offline_id (offline_id),
ADD INDEX IF NOT EXISTS idx_synced_offline (synced_from_offline);

-- Commentaire sur la table
ALTER TABLE sells COMMENT = 'Ventes avec support synchronisation offline (PWA)';

SELECT 'Migration PWA terminée avec succès!' AS message;

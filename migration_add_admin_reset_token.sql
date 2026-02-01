-- ============================================================
-- Migration: Ajouter colonnes reset token pour admin_accounts
-- Date: 2026-02-01
-- ============================================================

-- Ajouter les colonnes pour gérer la réinitialisation de mot de passe
SET @col_exists_token := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admin_accounts'
    AND COLUMN_NAME = 'reset_token'
);

SET @col_exists_expires := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admin_accounts'
    AND COLUMN_NAME = 'reset_token_expires'
);

-- Ajouter reset_token si absent
SET @sql := IF(
  @col_exists_token = 0,
  'ALTER TABLE admin_accounts ADD COLUMN reset_token VARCHAR(255) NULL AFTER password_hash',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter reset_token_expires si absent
SET @sql := IF(
  @col_exists_expires = 0,
  'ALTER TABLE admin_accounts ADD COLUMN reset_token_expires DATETIME NULL AFTER reset_token',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Créer un index sur reset_token pour les recherches rapides
SET @index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admin_accounts'
    AND INDEX_NAME = 'idx_reset_token'
);

SET @sql := IF(
  @index_exists = 0,
  'ALTER TABLE admin_accounts ADD INDEX idx_reset_token (reset_token)',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

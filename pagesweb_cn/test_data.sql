-- ====================================
-- INVE-APP - DONNÉES TEST
-- ====================================
-- Données de test pour vérifier le système
-- À exécuter APRÈS la migration

-- ====================================
-- CODES D'ESSAI TEST
-- ====================================
INSERT INTO trial_codes (
    code, first_name, last_name, email, phone, company_name, status, created_at, activated_at
) VALUES
(
    'TRIAL-TEST001',
    'Jean',
    'Dupont',
    'jean.dupont@test.cd',
    '+243 123 456 789',
    'Entreprise Test',
    'activated',
    NOW(),
    NOW()
),
(
    'TRIAL-TEST002',
    'Marie',
    'Martin',
    'marie.martin@test.cd',
    '+243 987 654 321',
    'Commerce Test',
    'unused',
    NOW(),
    NULL
);

-- ====================================
-- CODES D'ABONNEMENT TEST
-- ====================================
INSERT INTO subscription_codes (
    code, first_name, last_name, email, phone, company_name, payment_amount, status, created_at, validated_at
) VALUES
(
    'SUB-TEST001',
    'Laurent',
    'Bernard',
    'laurent.bernard@test.cd',
    '+243 111 222 333',
    'Société Commerce',
    50000,
    'validated',
    NOW(),
    NOW()
),
(
    'SUB-TEST002',
    'Sophie',
    'Lefevre',
    'sophie.lefevre@test.cd',
    '+243 444 555 666',
    'Boutique Test',
    50000,
    'pending',
    NOW(),
    NULL
);

-- ====================================
-- CLIENTS ACTIFS TEST
-- ====================================
INSERT INTO active_clients (
    client_code, first_name, last_name, email, company_name, subscription_type, 
    trial_code_id, status, created_at, expires_at, last_login
) VALUES
(
    'CLI-TRIAL-TEST001',
    'Jean',
    'Dupont',
    'jean.dupont@test.cd',
    'Entreprise Test',
    'trial',
    1,
    'active',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    NOW()
),
(
    'CLI-SUB-TEST001',
    'Laurent',
    'Bernard',
    'laurent.bernard@test.cd',
    'Société Commerce',
    'monthly',
    NULL,
    'active',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    NOW()
);

-- ====================================
-- VÉRIFICATIONS
-- ====================================

-- Voir tous les codes d'essai
SELECT 'TRIAL CODES' as '===';
SELECT code, CONCAT(first_name, ' ', last_name) as nom, email, status, created_at FROM trial_codes;

-- Voir tous les codes d'abonnement
SELECT 'SUBSCRIPTION CODES' as '===';
SELECT code, CONCAT(first_name, ' ', last_name) as nom, email, payment_amount, status, created_at FROM subscription_codes;

-- Voir tous les clients actifs
SELECT 'ACTIVE CLIENTS' as '===';
SELECT client_code, CONCAT(first_name, ' ', last_name) as nom, subscription_type, status, expires_at FROM active_clients;

-- ====================================
-- STATISTIQUES
-- ====================================
SELECT 'STATS' as '===';
SELECT 
    'Trial Codes Total' as stat,
    COUNT(*) as count
FROM trial_codes
UNION ALL
SELECT 
    'Trial Codes Activated',
    COUNT(*)
FROM trial_codes
WHERE status = 'activated'
UNION ALL
SELECT 
    'Subscription Codes Total',
    COUNT(*)
FROM subscription_codes
UNION ALL
SELECT 
    'Subscription Codes Pending',
    COUNT(*)
FROM subscription_codes
WHERE status = 'pending'
UNION ALL
SELECT 
    'Subscription Codes Validated',
    COUNT(*)
FROM subscription_codes
WHERE status = 'validated'
UNION ALL
SELECT 
    'Active Clients',
    COUNT(*)
FROM active_clients
WHERE status = 'active' AND expires_at > NOW();

-- ====================================
-- NETTOYAGE (Si besoin de recommencer)
-- ====================================
/*
DELETE FROM active_clients;
DELETE FROM subscription_codes;
DELETE FROM trial_codes;
*/

-- ====================================
-- FIN
-- ====================================

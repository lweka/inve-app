<?php
/**
 * ===================================
 * DIAGNOSTIC - SYSTÈME ABONNEMENT
 * ===================================
 * Vérifier que tout fonctionne correctement
 * Accès public pour diagnostic
 */

require_once __DIR__ . '/connectDb.php';

$diagnostics = [];
$errors = [];
$warnings = [];

// =============================
// 1. BASE DE DONNÉES
// =============================
$diagnostics['database'] = [];

try {
    // Tester connexion
    $stmt = $pdo->prepare("SELECT 1");
    $stmt->execute();
    $diagnostics['database']['connection'] = '✅ Connexion BD OK';
} catch (Exception $e) {
    $errors[] = '❌ Erreur connexion BD: ' . $e->getMessage();
}

// Vérifier tables
$tables = ['trial_codes', 'subscription_codes', 'active_clients'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        $diagnostics['database']["table_$table"] = "✅ Table $table ($count rows)";
    } catch (Exception $e) {
        $errors[] = "❌ Table $table: " . $e->getMessage();
    }
}

// =============================
// 2. FICHIERS
// =============================
$diagnostics['files'] = [];

$files = [
    'trial_form.php',
    'trial_verify.php',
    'subscription_buy.php',
    'subscription_pending.php',
    'admin_subscription_manager.php',
    'check_client_auth.php',
    'config_subscription.php',
    'house_marge.php',
    'reports.php',
];

$dir = __DIR__;
foreach ($files as $file) {
    $path = $dir . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $diagnostics['files'][$file] = "✅ Présent ($size bytes)";
    } else {
        $errors[] = "❌ Fichier manquant: $file";
    }
}

// Vérifier portal.php
if (file_exists(__DIR__ . '/../portal.php')) {
    $diagnostics['files']['../portal.php'] = '✅ Présent';
} else {
    $errors[] = "❌ Fichier manquant: ../portal.php";
}

// =============================
// 3. SESSION & COOKIES
// =============================
$diagnostics['session'] = [];
if (session_id() == '') {
    session_start();
}
$diagnostics['session']['session_started'] = '✅ Session démarrée';

// =============================
// 4. DONNÉES TEST
// =============================
$diagnostics['test_data'] = [];

try {
    // Codes d'essai
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM trial_codes");
    $count = $stmt->fetch()['count'];
    $diagnostics['test_data']['trial_codes'] = "✅ $count code(s) d'essai";
} catch (Exception $e) {
    $warnings[] = "Erreur récupération trial_codes: " . $e->getMessage();
}

try {
    // Codes d'abonnement
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscription_codes");
    $count = $stmt->fetch()['count'];
    $diagnostics['test_data']['subscription_codes'] = "✅ $count code(s) d'abonnement";
} catch (Exception $e) {
    $warnings[] = "Erreur récupération subscription_codes: " . $e->getMessage();
}

try {
    // Clients actifs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM active_clients WHERE expires_at > NOW()");
    $count = $stmt->fetch()['count'];
    $diagnostics['test_data']['active_clients'] = "✅ $count client(s) actif(s)";
} catch (Exception $e) {
    $warnings[] = "Erreur récupération active_clients: " . $e->getMessage();
}

// =============================
// 5. PERMISSIONS FICHIERS
// =============================
$diagnostics['permissions'] = [];

$dirs_to_check = [
    __DIR__,
    __DIR__ . '/../'
];

foreach ($dirs_to_check as $dir) {
    if (is_writable($dir)) {
        $diagnostics['permissions'][$dir] = "✅ Lecture/Écriture OK";
    } else {
        $warnings[] = "⚠️ Répertoire non inscriptible: $dir";
    }
}

// =============================
// 6. CONFIGURATION
// =============================
$diagnostics['config'] = [];

if (file_exists(__DIR__ . '/config_subscription.php')) {
    require_once __DIR__ . '/config_subscription.php';
    $diagnostics['config']['essai_durée'] = '✅ ' . TRIAL_DURATION_DAYS . ' jours';
    $diagnostics['config']['abonnement_durée'] = '✅ ' . SUBSCRIPTION_DURATION_DAYS . ' jours';
    $diagnostics['config']['prix_abonnement'] = '✅ ' . format_currency(SUBSCRIPTION_PRICE);
    $diagnostics['config']['admin_email'] = '✅ ' . ADMIN_EMAIL;
    $diagnostics['config']['admin_phone'] = '✅ ' . ADMIN_PHONE;
} else {
    $errors[] = "❌ Config file not found";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Système</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #1a1a1a;
            color: #fff;
            font-family: 'Courier New', monospace;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #4CAF50;
            font-size: 28px;
        }

        .section {
            background: #2a2a2a;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #4CAF50;
        }

        .item {
            padding: 8px 0;
            border-bottom: 1px solid #3a3a3a;
        }

        .item:last-child {
            border-bottom: none;
        }

        .error {
            background: #2a1a1a;
            border-left: 4px solid #d32f2f;
            color: #ff6b6b;
        }

        .warning {
            background: #2a2415;
            border-left: 4px solid #ffa726;
            color: #ffb74d;
        }

        .success {
            color: #81c784;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            border-top: 3px solid #4CAF50;
        }

        .summary-card.error {
            border-top-color: #d32f2f;
        }

        .summary-card.warning {
            border-top-color: #ffa726;
        }

        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .summary-label {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 12px;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 3px;
            text-align: center;
            border: none;
            cursor: pointer;
            font-family: Arial;
        }

        .btn:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #d32f2f;
        }

        .btn-danger:hover {
            background: #c62828;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>🔍 Diagnostic Système CartelPlus</h1>

    <!-- RÉSUMÉ -->
    <div class="summary">
        <div class="summary-card <?= !empty($errors) ? 'error' : '' ?>">
            <div class="summary-number"><?= count($errors) ?></div>
            <div class="summary-label">Erreurs</div>
        </div>
        <div class="summary-card <?= !empty($warnings) ? 'warning' : '' ?>">
            <div class="summary-number"><?= count($warnings) ?></div>
            <div class="summary-label">Avertissements</div>
        </div>
        <div class="summary-card">
            <div class="summary-number"><?php 
                $total_checks = 0;
                foreach ($diagnostics as $section) {
                    if (is_array($section)) {
                        $total_checks += count($section);
                    }
                }
                echo $total_checks;
            ?></div>
            <div class="summary-label">Vérifications OK</div>
        </div>
    </div>

    <!-- ERREURS -->
    <?php if (!empty($errors)): ?>
    <div class="section error">
        <div class="section-title">❌ ERREURS (Actions requises)</div>
        <?php foreach ($errors as $error): ?>
        <div class="item"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- AVERTISSEMENTS -->
    <?php if (!empty($warnings)): ?>
    <div class="section warning">
        <div class="section-title">⚠️ AVERTISSEMENTS (Vérifier)</div>
        <?php foreach ($warnings as $warning): ?>
        <div class="item"><?= htmlspecialchars($warning) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- DIAGNOSTICS -->
    <?php foreach ($diagnostics as $category => $items): ?>
    <div class="section">
        <div class="section-title">
            <?php 
            $icons = [
                'database' => '💾',
                'files' => '📁',
                'session' => '🔐',
                'test_data' => '📊',
                'permissions' => '🔒',
                'config' => '⚙️'
            ];
            echo isset($icons[$category]) ? $icons[$category] : '✓';
            ?> 
            <?= ucfirst(str_replace('_', ' ', $category)) ?>
        </div>
        <?php foreach ($items as $name => $status): ?>
        <div class="item">
            <span class="success"><?= htmlspecialchars($status) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- ACTIONS -->
    <div style="margin: 30px 0; padding: 20px; background: #2a2a2a; border-radius: 5px;">
        <div style="font-size: 14px; margin-bottom: 15px; color: #888;">
            Actions rapides:
        </div>
        <div class="actions">
            <a href="migration_subscription_system.php" class="btn">
                → Créer Tables
            </a>
            <a href="../portal.php" class="btn">
                → Aller à Portal
            </a>
            <a href="admin_subscription_manager.php" class="btn">
                → Admin Dashboard
            </a>
            <a href="trial_form.php" class="btn">
                → Essai Test
            </a>
            <a href="subscription_buy.php" class="btn">
                → Achat Test
            </a>
            <a href="../DOCUMENTATION_ABONNEMENT.md" class="btn">
                → Documentation
            </a>
        </div>
    </div>

    <!-- STATUS FINAL -->
    <div style="margin: 30px 0; padding: 20px; background: <?= empty($errors) ? '#1a3a1a' : '#3a1a1a' ?>; border-radius: 5px; text-align: center;">
        <?php if (empty($errors)): ?>
            <div style="font-size: 24px; color: #4CAF50; margin-bottom: 10px;">✅ SYSTÈME OPÉRATIONNEL</div>
            <div style="color: #888;">Tous les tests sont passés avec succès!</div>
        <?php else: ?>
            <div style="font-size: 24px; color: #d32f2f; margin-bottom: 10px;">❌ ERREURS DÉTECTÉES</div>
            <div style="color: #ff6b6b;">Veuillez corriger les erreurs ci-dessus</div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Diagnostic généré: <?= date('Y-m-d H:i:s') ?></p>
        <p>Rafraîchir la page pour mettre à jour les données</p>
    </div>

</div>

</body>
</html>

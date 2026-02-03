<?php
/**
 * Diagnostic Script - Reports Module
 * URL: https://inve-app.cartelplus.site/pagesweb_cn/diagnose_reports.php
 * Vérification complète du système de rapports
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$diagnostics = array();
$errors = array();
$warnings = array();

// ===== TEST 1 : CONFIGURATION PHP =====
$diagnostics['php_version'] = phpversion();
$diagnostics['php_sapi'] = php_sapi_name();
$diagnostics['memory_limit'] = ini_get('memory_limit');
$diagnostics['max_execution_time'] = ini_get('max_execution_time');

if (version_compare(phpversion(), '7.4.0', '<')) {
    $errors[] = "PHP 7.4+ required, got " . phpversion();
}

// ===== TEST 2 : EXTENSIONS REQUISES =====
$required_extensions = array('pdo', 'gd', 'date', 'filter');
foreach ($required_extensions as $ext) {
    $diagnostics['ext_' . $ext] = extension_loaded($ext) ? 'OK' : 'MISSING';
    if (!extension_loaded($ext)) {
        $errors[] = "Extension manquante: $ext";
    }
}

// ===== TEST 3 : FICHIERS CRITIQUES =====
$critical_files = array(
    'connectDb.php' => __DIR__ . '/../../Downloads/connectDb.php',
    'require_admin_auth.php' => __DIR__ . '/require_admin_auth.php',
    'autoload.php' => __DIR__ . '/../../vendor/autoload.php',
    'reports.php' => __DIR__ . '/reports.php'
);

foreach ($critical_files as $name => $path) {
    if (file_exists($path)) {
        $diagnostics['file_' . $name] = 'OK (' . filesize($path) . ' bytes)';
    } else {
        $diagnostics['file_' . $name] = 'MISSING';
        $errors[] = "Fichier critique manquant: $name";
    }
}

// ===== TEST 4 : DÉPENDANCES (Composer) =====
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $diagnostics['composer'] = 'OK';
    
    // Test TCPDF
    if (class_exists('TCPDF')) {
        $tcpdf = new TCPDF();
        $diagnostics['tcpdf'] = 'OK (v' . TCPDF_VERSION . ')';
    } else {
        $diagnostics['tcpdf'] = 'MISSING';
        $errors[] = "TCPDF not found";
    }
    
    // Test PHPMailer
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $diagnostics['phpmailer'] = 'OK';
    } else {
        $diagnostics['phpmailer'] = 'MISSING';
        $errors[] = "PHPMailer not found";
    }
} catch (Exception $e) {
    $diagnostics['composer'] = 'ERROR: ' . $e->getMessage();
    $errors[] = "Composer autoload failed: " . $e->getMessage();
}

// ===== TEST 5 : AUTHENTIFICATION =====
if (!isset($_SESSION['client_code'])) {
    $warnings[] = "Client code not in session (but may be set by require_admin_auth)";
}

// ===== TEST 6 : DATABASE CONNECTION =====
try {
    require_once __DIR__ . '/../../Downloads/connectDb.php';
    
    // Test simple query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_movements LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $diagnostics['database'] = 'OK (connected)';
    
    // Check tables
    $tables = array('product_movements', 'products', 'agents', 'houses', 'active_clients');
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $diagnostics['table_' . $table] = 'OK (' . $stmt->fetchColumn() . ' rows)';
        } catch (Exception $e) {
            $errors[] = "Table missing or inaccessible: $table";
        }
    }
} catch (Exception $e) {
    $diagnostics['database'] = 'ERROR: ' . $e->getMessage();
    $errors[] = "Database connection failed: " . $e->getMessage();
}

// ===== TEST 7 : REPORT DATA =====
try {
    if (isset($pdo)) {
        $client_code = $_SESSION['client_code'] ?? null;
        
        if ($client_code) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM product_movements 
                WHERE client_code = ? 
                AND (type = 'out' OR type = 'sale')
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$client_code]);
            $today_movements = $stmt->fetchColumn();
            $diagnostics['today_movements'] = $today_movements . ' movements';
            
            // All movements
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM product_movements 
                WHERE client_code = ? 
                AND (type = 'out' OR type = 'sale')
            ");
            $stmt->execute([$client_code]);
            $all_movements = $stmt->fetchColumn();
            $diagnostics['total_movements'] = $all_movements . ' movements';
        } else {
            $warnings[] = "Client code not available - cannot check report data";
        }
    }
} catch (Exception $e) {
    $errors[] = "Cannot access report data: " . $e->getMessage();
}

// ===== TEST 8 : SMTP CONFIGURATION =====
$smtp_config = array(
    'host' => 'smtp.titan.email',
    'port' => 587,
    'encryption' => 'STARTTLS',
    'from' => 'cartelplus-congo@cartelplus.site'
);
$diagnostics['smtp_host'] = $smtp_config['host'];
$diagnostics['smtp_port'] = $smtp_config['port'];
$diagnostics['smtp_encryption'] = $smtp_config['encryption'];

// ===== TEST 9 : FILE PERMISSIONS =====
$test_write_dir = __DIR__ . '/../../uploads';
if (is_writable($test_write_dir)) {
    $diagnostics['write_permissions'] = 'OK';
} else {
    $warnings[] = "Upload directory may not be writable";
}

// ===== TEST 10 : CONSTANTS & SETTINGS =====
$diagnostics['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET';
$diagnostics['script_filename'] = $_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET';
$diagnostics['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'NOT SET';

// ===== GENERATE HTML REPORT =====
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic - Système de Rapports</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fb;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #0070e0, #003087);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 112, 224, 0.3);
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        
        .status-bar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .status-item {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-ok { background: #10b981; color: white; }
        .status-error { background: #ef4444; color: white; }
        .status-warning { background: #f59e0b; color: white; }
        
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .section h2 {
            color: #0070e0;
            border-bottom: 2px solid #0070e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .diag-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            align-items: center;
        }
        .diag-row:last-child { border-bottom: none; }
        .diag-label {
            font-weight: bold;
            color: #374151;
            min-width: 200px;
        }
        .diag-value {
            color: #6b7280;
            flex: 1;
            word-break: break-all;
        }
        .diag-value.ok { color: #10b981; font-weight: bold; }
        .diag-value.error { color: #ef4444; font-weight: bold; }
        
        .message-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .message-error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .message-warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        .message-success {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        
        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 40px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostic - Système de Rapports</h1>
            <p>Vérification complète du module de génération de rapports PDF/Email</p>
            <div class="status-bar">
                <div class="status-item status-<?= count($errors) > 0 ? 'error' : 'ok' ?>">
                    Erreurs: <?= count($errors) ?>
                </div>
                <div class="status-item status-<?= count($warnings) > 0 ? 'warning' : 'ok' ?>">
                    Avertissements: <?= count($warnings) ?>
                </div>
                <div class="status-item status-ok">
                    Tests passés: <?= count($diagnostics) ?>
                </div>
            </div>
        </div>
        
        <?php if (count($errors) > 0): ?>
        <div class="section">
            <h2>❌ Erreurs Critiques</h2>
            <?php foreach ($errors as $error): ?>
            <div class="message-box message-error">
                <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($warnings) > 0): ?>
        <div class="section">
            <h2>⚠️ Avertissements</h2>
            <?php foreach ($warnings as $warning): ?>
            <div class="message-box message-warning">
                <strong>Avertissement:</strong> <?= htmlspecialchars($warning) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>📊 Résultats du Diagnostic</h2>
            <?php foreach ($diagnostics as $key => $value): ?>
            <div class="diag-row">
                <div class="diag-label"><?= htmlspecialchars($key) ?></div>
                <div class="diag-value <?= strpos($value, 'MISSING') !== false || strpos($value, 'ERROR') !== false ? 'error' : 'ok' ?>">
                    <?= htmlspecialchars($value) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($errors) === 0): ?>
        <div class="section">
            <div class="message-box message-success">
                <strong>✅ Status:</strong> Tous les tests sont passés! Le système de rapports est prêt à l'emploi.
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <div class="message-box message-error">
                <strong>⛔ Status:</strong> Des erreurs critiques ont été détectées. Veuillez corriger les problèmes avant d'utiliser le système.
            </div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            Diagnostic généré le <?= date('d/m/Y H:i:s') ?> | 
            Server: <?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?> | 
            Script: <?= $_SERVER['SCRIPT_NAME'] ?? 'Unknown' ?>
        </div>
    </div>
</body>
</html>

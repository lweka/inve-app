<?php
/**
 * ===================================
 * ADMIN - GESTION DES ABONNEMENTS
 * ===================================
 * Réservé à l'administrateur principal
 * Gestion codes d'essai + codes d'abonnement
 */

session_start();

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirection vers le formulaire de connexion
    header('Location: admin_login_form');
    exit;
}

require_once __DIR__ . '/connectDb.php';

// Vérifier si admin PRINCIPAL (vous)
// À adapter selon votre système d'identification
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
$is_main_admin = true; // Vérification basée sur la session admin_logged_in

/* ===============================
   ACTION : VALIDER CODE ABONNEMENT
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'validate_subscription') {
        $sub_code_id = (int)$_POST['subscription_code_id'];
        
        // Récupérer les infos
        $stmt = $pdo->prepare("SELECT * FROM subscription_codes WHERE id = ?");
        $stmt->execute([$sub_code_id]);
        $sub_code = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sub_code && $sub_code['status'] === 'pending') {
            // Générer code client unique
            $client_code = 'CLI-' . strtoupper(uniqid());
            
            // Créer le client actif
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt_insert = $pdo->prepare("
                INSERT INTO active_clients (
                    client_code, first_name, last_name, email, company_name,
                    subscription_type, subscription_code_id, status, created_at, expires_at
                ) VALUES (?, ?, ?, ?, ?, 'monthly', ?, 'active', NOW(), ?)
            ");
            $stmt_insert->execute([
                $client_code,
                $sub_code['first_name'],
                $sub_code['last_name'],
                $sub_code['email'],
                $sub_code['company_name'],
                $sub_code_id,
                $expires_at
            ]);
            
            // Marquer le code comme validé
            $stmt_update = $pdo->prepare("
                UPDATE subscription_codes 
                SET status = 'validated', validated_at = NOW()
                WHERE id = ?
            ");
            $stmt_update->execute([$sub_code_id]);
            
            // Rediriger vers le formulaire d'enregistrement
            header('Location: admin_register.php?code=' . urlencode($client_code));
            exit;
            
            $success_message = "✅ Code abonnement validé ! Client créé: $client_code";
        }
    }
}

/* ===============================
   RÉCUPÉRATION DES DONNÉES
   =============================== */
// Codes d'essai
$stmt_trials = $pdo->query("
    SELECT * FROM trial_codes 
    ORDER BY created_at DESC
");
$trial_codes = $stmt_trials->fetchAll(PDO::FETCH_ASSOC);

// Codes d'abonnement
$stmt_subs = $pdo->query("
    SELECT * FROM subscription_codes 
    ORDER BY created_at DESC
");
$subscription_codes = $stmt_subs->fetchAll(PDO::FETCH_ASSOC);

// Clients actifs
$stmt_clients = $pdo->query("
    SELECT * FROM active_clients 
    ORDER BY created_at DESC
");
$active_clients = $stmt_clients->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Abonnements | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --pp-blue: #0070e0;
            --pp-blue-dark: #003087;
            --pp-cyan: #00a8ff;
            --pp-bg: #f5f7fb;
            --pp-white: #ffffff;
            --pp-text: #0b1f3a;
            --pp-border: #e1e8f0;
            --pp-success: #1f8f6a;
            --pp-danger: #dc2626;
            --pp-warning: #f59e0b;
            --pp-orange: #ff6b35;
            --pp-shadow: rgba(0, 48, 135, 0.08);
            --pp-shadow-lg: rgba(0, 48, 135, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--pp-text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.95); }
            100% { opacity: 1; transform: scale(1); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* ===== HEADER ===== */
        .admin-header {
            background: linear-gradient(135deg, var(--pp-blue) 0%, var(--pp-blue-dark) 100%);
            padding: 32px 0;
            box-shadow: 0 10px 30px var(--pp-shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(0, 168, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .admin-header .container-fluid {
            position: relative;
            z-index: 1;
        }

        .admin-header h1 {
            color: var(--pp-white);
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.6s ease;
        }

        .admin-header .header-icon {
            font-size: 40px;
            animation: bounceIn 0.8s ease;
        }

        .admin-header .header-info {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 8px;
        }

        .admin-header .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .admin-header .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* ===== CONTENT ===== */
        .admin-content {
            padding: 40px 20px;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            animation: fadeSlide 0.8s ease;
        }

        .stat-card {
            background: var(--pp-white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px var(--pp-shadow);
            transition: all 0.3s;
            border: 1px solid var(--pp-border);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--pp-blue), var(--pp-cyan));
            transform: scaleX(0);
            animation: slideInScale 0.6s ease forwards;
        }

        @keyframes slideInScale {
            to { transform: scaleX(1); }
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px var(--pp-shadow-lg);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--pp-blue);
        }

        /* ===== TABS ===== */
        .nav-tabs {
            display: flex;
            gap: 12px;
            border: none;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .nav-tabs .nav-link {
            background: var(--pp-white);
            border: 1px solid var(--pp-border);
            color: var(--pp-text);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            position: relative;
        }

        .nav-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--pp-blue), var(--pp-cyan));
            border-radius: 3px;
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .nav-tabs .nav-link:hover {
            background: var(--pp-bg);
            border-color: var(--pp-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--pp-shadow);
        }

        .nav-tabs .nav-link.active {
            background: var(--pp-blue);
            color: white;
            border-color: var(--pp-blue);
            box-shadow: 0 8px 24px rgba(0, 112, 224, 0.3);
        }

        .nav-tabs .nav-link.active::after {
            transform: scaleX(1);
        }

        .nav-badge {
            display: inline-block;
            background: rgba(0, 112, 224, 0.15);
            color: var(--pp-blue);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 8px;
        }

        /* ===== TABS CONTENT ===== */
        .tab-content {
            animation: fadeSlide 0.5s ease;
        }

        .tab-pane {
            animation: fadeSlide 0.5s ease;
        }

        /* ===== TABLE SECTION ===== */
        .table-section {
            background: var(--pp-white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 12px var(--pp-shadow);
            margin-bottom: 30px;
            border: 1px solid var(--pp-border);
            animation: fadeSlide 0.6s ease;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--pp-text);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title-icon {
            font-size: 24px;
        }

        /* ===== TABLE ===== */
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .data-table thead {
            background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
            border-bottom: 2px solid var(--pp-border);
        }

        .data-table thead th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            color: var(--pp-text);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr {
            border-bottom: 1px solid var(--pp-border);
            transition: all 0.3s;
        }

        .data-table tbody tr:hover {
            background: var(--pp-bg);
            box-shadow: inset 0 0 10px rgba(0, 112, 224, 0.05);
        }

        .data-table tbody td {
            padding: 16px;
            color: var(--pp-text);
            font-size: 14px;
        }

        .code-cell {
            font-family: 'Courier New', monospace;
            background: rgba(0, 112, 224, 0.08);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--pp-blue);
            font-size: 12px;
            word-break: break-all;
            user-select: all;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background: rgba(31, 143, 106, 0.15);
            color: var(--pp-success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .badge-danger {
            background: rgba(220, 38, 38, 0.15);
            color: var(--pp-danger);
        }

        .badge-info {
            background: rgba(0, 112, 224, 0.15);
            color: var(--pp-blue);
        }

        /* ===== BUTTONS ===== */
        .btn-action {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-validate {
            background: linear-gradient(135deg, var(--pp-success) 0%, #1a7a52 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(31, 143, 106, 0.3);
        }

        .btn-validate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 143, 106, 0.4);
        }

        .btn-copy {
            background: linear-gradient(135deg, var(--pp-blue) 0%, #0055b0 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 112, 224, 0.3);
        }

        .btn-copy:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 112, 224, 0.4);
        }

        .btn-copy:active {
            transform: translateY(0);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.6;
        }

        .empty-state-text {
            font-size: 15px;
        }

        /* ===== ALERTS ===== */
        .alert-success {
            background: linear-gradient(135deg, rgba(31, 143, 106, 0.1) 0%, rgba(31, 143, 106, 0.05) 100%);
            border: 1px solid var(--pp-success);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            color: var(--pp-success);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.4s ease;
        }

        .alert-success::before {
            content: '✅';
            font-size: 20px;
        }

        /* ===== FORM ===== */
        form {
            display: contents;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 24px;
            }

            .data-table thead th {
                font-size: 12px;
                padding: 12px;
            }

            .data-table tbody td {
                padding: 12px;
                font-size: 13px;
            }

            .stats-bar {
                grid-template-columns: 1fr;
            }

            .table-section {
                padding: 16px;
                overflow-x: auto;
            }

            .nav-tabs {
                flex-wrap: nowrap;
            }

            .nav-link {
                font-size: 12px;
                padding: 10px 16px !important;
            }

            .nav-badge {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="admin-header">
        <div class="container-fluid px-4 py-0">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><span class="header-icon">⚙️</span>Gestion des Abonnements</h1>
                    <div class="header-info">Bienvenue, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?> • CartelPlus Congo</div>
                </div>
                <a href="logout.php" class="btn-logout">🚪 Déconnexion</a>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="admin-content">
        <div class="container-fluid px-4">

            <!-- STATS -->
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-label">Codes d'Essai</div>
                    <div class="stat-value"><?= count($trial_codes) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💳</div>
                    <div class="stat-label">Codes Abonnement</div>
                    <div class="stat-value"><?= count($subscription_codes) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Clients Actifs</div>
                    <div class="stat-value"><?= count($active_clients) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-label">Codes Validés</div>
                    <div class="stat-value"><?= count(array_filter($subscription_codes, fn($s) => $s['status'] === 'validated')) ?></div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if (isset($success_message)): ?>
            <div class="alert-success">
                <?= $success_message ?>
            </div>
            <?php endif; ?>

            <!-- TABS -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-trials">
                        📋 Codes d'Essai
                        <span class="nav-badge"><?= count($trial_codes) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-subscriptions">
                        💳 Codes d'Abonnement
                        <span class="nav-badge"><?= count($subscription_codes) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-clients">
                        👥 Clients Actifs
                        <span class="nav-badge"><?= count($active_clients) ?></span>
                    </a>
                </li>
            </ul>

            <!-- TAB CONTENT -->
            <div class="tab-content">

                <!-- TAB 1: ESSAI -->
                <div id="tab-trials" class="tab-pane fade show active">
                    <div class="table-section">
                        <div class="section-title">
                            <span class="section-title-icon">📋</span>
                            Codes d'Essai Gratuit (7 jours)
                        </div>

                        <?php if (empty($trial_codes)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <div class="empty-state-text">Aucun code d'essai généré</div>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Entreprise</th>
                                            <th>Statut</th>
                                            <th>Créé le</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($trial_codes as $t): ?>
                                        <tr>
                                            <td><div class="code-cell"><?= htmlspecialchars($t['code']) ?></div></td>
                                            <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                            <td><?= htmlspecialchars($t['email']) ?></td>
                                            <td><?= htmlspecialchars($t['company_name'] ?? '—') ?></td>
                                            <td>
                                                <?php
                                                $status_class = match($t['status']) {
                                                    'unused' => 'badge-warning',
                                                    'activated' => 'badge-success',
                                                    'expired' => 'badge-danger',
                                                    default => 'badge-info'
                                                };
                                                $status_text = match($t['status']) {
                                                    'unused' => 'Non utilisé',
                                                    'activated' => 'Activé',
                                                    'expired' => 'Expiré',
                                                    default => ucfirst($t['status'])
                                                };
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                            <td><button type="button" class="btn-action btn-copy" onclick="copyToClipboard('<?= htmlspecialchars($t['code']) ?>')">📋 Copier</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 2: ABONNEMENT -->
                <div id="tab-subscriptions" class="tab-pane fade">
                    <div class="table-section">
                        <div class="section-title">
                            <span class="section-title-icon">💳</span>
                            Codes d'Abonnement Payants (1 mois)
                        </div>

                        <?php if (empty($subscription_codes)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <div class="empty-state-text">Aucun code d'abonnement généré</div>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th>Créé le</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($subscription_codes as $s): ?>
                                        <tr>
                                            <td><div class="code-cell"><?= htmlspecialchars($s['code']) ?></div></td>
                                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                            <td><?= htmlspecialchars($s['email']) ?></td>
                                            <td><strong style="color: var(--pp-blue);">$<?= number_format($s['payment_amount'] ?? 0, 2) ?></strong></td>
                                            <td>
                                                <?php
                                                $status_class = match($s['status']) {
                                                    'pending' => 'badge-warning',
                                                    'validated' => 'badge-success',
                                                    'active' => 'badge-success',
                                                    'suspended' => 'badge-danger',
                                                    'expired' => 'badge-danger',
                                                    default => 'badge-info'
                                                };
                                                $status_text = match($s['status']) {
                                                    'pending' => 'En Attente',
                                                    'validated' => 'Validé',
                                                    'active' => 'Actif',
                                                    'suspended' => 'Suspendu',
                                                    'expired' => 'Expiré',
                                                    default => ucfirst($s['status'])
                                                };
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                                            <td>
                                                <?php if ($s['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="validate_subscription">
                                                    <input type="hidden" name="subscription_code_id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn-action btn-validate">✅ Valider</button>
                                                </form>
                                                <?php else: ?>
                                                <button type="button" class="btn-action btn-copy" onclick="copyToClipboard('<?= htmlspecialchars($s['code']) ?>')">📋 Copier</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 3: CLIENTS -->
                <div id="tab-clients" class="tab-pane fade">
                    <div class="table-section">
                        <div class="section-title">
                            <span class="section-title-icon">👥</span>
                            Clients Actuellement Actifs
                        </div>

                        <?php if (empty($active_clients)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <div class="empty-state-text">Aucun client actuellement actif</div>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Code Client</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Statut</th>
                                            <th>Expire le</th>
                                            <th>Dernier Accès</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($active_clients as $c): 
                                            $expires_soon = strtotime($c['expires_at']) < strtotime('+7 days');
                                            $is_expired = strtotime($c['expires_at']) < time();
                                        ?>
                                        <tr>
                                            <td><div class="code-cell"><?= htmlspecialchars($c['client_code']) ?></div></td>
                                            <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                                            <td><?= htmlspecialchars($c['email']) ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= $c['subscription_type'] === 'trial' ? '📅 Essai 7j' : '💳 Abo 1m' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($is_expired): ?>
                                                    <span class="badge badge-danger">Expiré</span>
                                                <?php elseif ($expires_soon): ?>
                                                    <span class="badge badge-warning">⏰ Expire bientôt</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">✅ Actif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($c['expires_at'])) ?></td>
                                            <td><?= $c['last_login'] ? date('d/m/Y H:i', strtotime($c['last_login'])) : '—' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="../js/bootstrap.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copié !';
                btn.style.background = 'linear-gradient(135deg, var(--pp-success) 0%, #1a7a52 100%)';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }
    </script>

</body>
</html>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">⚙️ Gestion des Abonnements</h2>
            <a href="logout.php" class="btn btn-danger">Déconnexion</a>
        </div>
    </div>
</div>

<div class="container-fluid p-4">

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <?= $success_message ?>
    </div>
    <?php endif; ?>

    <!-- ONGLETS -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-trials">
                📋 Codes d'Essai (7 jours)
                <span class="stat-small"><strong><?= count($trial_codes) ?></strong> codes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-subscriptions">
                💳 Codes d'Abonnement (1 mois)
                <span class="stat-small"><strong><?= count($subscription_codes) ?></strong> codes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-clients">
                👥 Clients Actifs
                <span class="stat-small"><strong><?= count($active_clients) ?></strong> clients</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">

        <!-- TAB 1: CODES D'ESSAI -->
        <div id="tab-trials" class="tab-pane fade show active">
            <div class="section-title">📋 Codes d'Essai Générés (7 jours gratuit)</div>

            <div class="table-container">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Entreprise</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($trial_codes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun code d'essai</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($trial_codes as $t): ?>
                        <tr>
                            <td>
                                <div class="code-display"><?= htmlspecialchars($t['code']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td><?= htmlspecialchars($t['email']) ?></td>
                            <td><?= htmlspecialchars($t['company_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $status_badge = [
                                    'unused' => '<span class="badge badge-warning">Non utilisé</span>',
                                    'activated' => '<span class="badge badge-success">Activé</span>',
                                    'expired' => '<span class="badge badge-danger">Expiré</span>'
                                ];
                                echo $status_badge[$t['status']] ?? '';
                                ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                            <td>
                                <button class="btn-action btn-copy" onclick="copyToClipboard('<?= $t['code'] ?>')">
                                    📋 Copier
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: CODES D'ABONNEMENT -->
        <div id="tab-subscriptions" class="tab-pane fade">
            <div class="section-title">💳 Codes d'Abonnement Payants (1 mois)</div>

            <div class="table-container">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($subscription_codes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun code d'abonnement</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($subscription_codes as $s): ?>
                        <tr>
                            <td>
                                <div class="code-display"><?= htmlspecialchars($s['code']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><strong><?= number_format($s['payment_amount'], 0) ?> FC</strong></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => '<span class="badge badge-warning">En Attente</span>',
                                    'validated' => '<span class="badge badge-success">Validé</span>',
                                    'active' => '<span class="badge badge-success">Actif</span>',
                                    'suspended' => '<span class="badge badge-danger">Suspendu</span>',
                                    'expired' => '<span class="badge badge-danger">Expiré</span>'
                                ];
                                echo $status_badges[$s['status']] ?? '';
                                ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                            <td>
                                <?php if ($s['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="validate_subscription">
                                    <input type="hidden" name="subscription_code_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn-action btn-validate">
                                        ✅ Valider
                                    </button>
                                </form>
                                <?php else: ?>
                                <button class="btn-action btn-copy" onclick="copyToClipboard('<?= $s['code'] ?>')">
                                    📋 Copier
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: CLIENTS ACTIFS -->
        <div id="tab-clients" class="tab-pane fade">
            <div class="section-title">👥 Clients Actuellement Actifs</div>

            <div class="table-container">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code Client</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Expire le</th>
                            <th>Dernier Accès</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($active_clients)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun client actif</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($active_clients as $c): 
                            $expires_soon = strtotime($c['expires_at']) < strtotime('+7 days');
                            $is_expired = strtotime($c['expires_at']) < time();
                        ?>
                        <tr>
                            <td>
                                <div class="code-display" style="font-size: 11px;"><?= htmlspecialchars($c['client_code']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= ucfirst($c['subscription_type']) === 'Trial' ? 'Essai 7j' : 'Abonnement 1m' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span class="badge badge-danger">Expiré</span>
                                <?php elseif ($expires_soon): ?>
                                    <span class="badge badge-warning">Expire Bientôt</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($c['expires_at'])) ?></td>
                            <td><?= $c['last_login'] ? date('d/m/Y H:i', strtotime($c['last_login'])) : 'Jamais' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script src="../js/bootstrap.min.js"></script>
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Code copié !');
    });
}
</script>

</body>
</html>

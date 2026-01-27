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
    <title>Admin - Gestion Abonnements | CartelPlus</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0A6FB7;
            --orange: #F25C2A;
            --dark: #0B0E14;
            --white: #ffffff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            background: linear-gradient(180deg, #0B0E14, #05070B);
            color: var(--white);
            font-family: "Segoe UI", system-ui, sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: rgba(10, 111, 183, 0.1);
            border-bottom: 2px solid var(--blue);
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .nav-tabs {
            border-bottom: 2px solid var(--blue);
            margin-bottom: 30px;
        }

        .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 20px;
        }

        .nav-tabs .nav-link:hover {
            color: var(--white);
            border-bottom-color: var(--orange);
        }

        .nav-tabs .nav-link.active {
            color: var(--white);
            background: none;
            border-bottom-color: var(--orange);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--blue);
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: rgba(255, 255, 255, 0.1);
            font-size: 13px;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, 0.02);
        }

        .table-hover tbody tr:hover {
            background: rgba(242, 92, 42, 0.1);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: var(--success);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: black;
        }

        .badge-danger {
            background: var(--danger);
            color: white;
        }

        .badge-info {
            background: var(--blue);
            color: white;
        }

        .btn-action {
            padding: 5px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-validate {
            background: var(--success);
            color: white;
        }

        .btn-validate:hover {
            opacity: 0.9;
        }

        .btn-copy {
            background: var(--blue);
            color: white;
        }

        .btn-copy:hover {
            opacity: 0.9;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-left: 4px solid var(--success);
            color: #90EE90;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--orange);
        }

        .code-display {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            word-break: break-all;
        }

        .stat-small {
            display: inline-block;
            background: rgba(10, 111, 183, 0.2);
            padding: 8px 15px;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 13px;
        }

        .stat-small strong {
            color: var(--orange);
        }
    </style>
</head>

<body>

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">⚙️ Gestion des Abonnements</h2>
            <a href="dashboard.php" class="btn btn-outline-light">← Retour Dashboard</a>
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

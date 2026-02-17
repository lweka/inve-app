<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeDateInput(string $value, string $fallback): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        return $fallback;
    }

    return $value;
}

function formatDateTimeLabel(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return '-';
    }

    return date('d/m/Y H:i:s', $ts);
}

function formatAmountByCurrency(float $amount, string $currency): string
{
    $cur = strtoupper(trim($currency));
    if ($cur === '') {
        $cur = 'CDF';
    }

    $decimals = ($cur === 'USD') ? 2 : 0;
    if (strpos($cur, '/') !== false) {
        $decimals = 2;
    }

    return number_format($amount, $decimals) . ' ' . $cur;
}

function ticketPrintLogTableExists(PDO $pdo): bool
{
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'ticket_print_logs'
    ");
    return ((int)$stmt->fetchColumn()) > 0;
}

$today = date('Y-m-d');
$filterNotice = '';

$fromDate = normalizeDateInput((string)($_GET['from_date'] ?? ''), $today);
$toDate = normalizeDateInput((string)($_GET['to_date'] ?? ''), $today);

if ($fromDate > $toDate) {
    $tmp = $fromDate;
    $fromDate = $toDate;
    $toDate = $tmp;
    $filterNotice = 'Les dates ont ete inversees automatiquement.';
}

$houseId = max(0, (int)($_GET['house_id'] ?? 0));
$agentId = max(0, (int)($_GET['agent_id'] ?? 0));
$ticketSearch = trim((string)($_GET['ticket'] ?? ''));
if (strlen($ticketSearch) > 80) {
    $ticketSearch = substr($ticketSearch, 0, 80);
}

$stmtHouses = $pdo->prepare("SELECT id, name FROM houses WHERE client_code = ? ORDER BY name ASC");
$stmtHouses->execute([$client_code]);
$houses = $stmtHouses->fetchAll(PDO::FETCH_ASSOC);

$stmtAgents = $pdo->prepare("SELECT id, fullname FROM agents WHERE client_code = ? ORDER BY fullname ASC");
$stmtAgents->execute([$client_code]);
$agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);

$logs = [];
$ticketDetailsByReceipt = [];
$tableExists = false;
$loadError = '';

$totalPrints = 0;
$todayPrints = 0;
$uniqueTickets = 0;
$uniqueSellers = 0;

try {
    $tableExists = ticketPrintLogTableExists($pdo);

    if ($tableExists) {
        $sql = "
            SELECT
                tpl.id,
                tpl.printed_at,
                tpl.receipt_id,
                tpl.ticket_number,
                tpl.print_mode,
                tpl.sale_id,
                tpl.agent_id,
                tpl.house_id,
                COALESCE(a.fullname, 'Vendeur inconnu') AS agent_name,
                COALESCE(h.name, 'Maison inconnue') AS house_name
            FROM ticket_print_logs tpl
            LEFT JOIN agents a ON a.id = tpl.agent_id
            LEFT JOIN houses h ON h.id = tpl.house_id
            WHERE tpl.client_code = ?
              AND DATE(tpl.printed_at) >= ?
              AND DATE(tpl.printed_at) <= ?
        ";

        $params = [$client_code, $fromDate, $toDate];

        if ($houseId > 0) {
            $sql .= " AND tpl.house_id = ? ";
            $params[] = $houseId;
        }

        if ($agentId > 0) {
            $sql .= " AND tpl.agent_id = ? ";
            $params[] = $agentId;
        }

        if ($ticketSearch !== '') {
            $sql .= " AND (tpl.ticket_number LIKE ? OR tpl.receipt_id LIKE ?) ";
            $like = '%' . $ticketSearch . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY tpl.printed_at DESC, tpl.id DESC LIMIT 500 ";

        $stmtLogs = $pdo->prepare($sql);
        $stmtLogs->execute($params);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        $totalPrints = count($logs);
        $todayPrints = 0;
        $receiptSet = [];
        $sellerSet = [];

        foreach ($logs as $log) {
            $printDate = substr((string)$log['printed_at'], 0, 10);
            if ($printDate === $today) {
                $todayPrints++;
            }

            $receipt = (string)($log['receipt_id'] ?? '');
            if ($receipt !== '') {
                $receiptSet[$receipt] = true;
            }

            $seller = (int)($log['agent_id'] ?? 0);
            if ($seller > 0) {
                $sellerSet[$seller] = true;
            }
        }

        $uniqueTickets = count($receiptSet);
        $uniqueSellers = count($sellerSet);

        if ($uniqueTickets > 0) {
            $receiptIds = array_keys($receiptSet);
            $placeholders = implode(',', array_fill(0, count($receiptIds), '?'));

            $detailSql = "
                SELECT
                    pm.receipt_id,
                    pm.id,
                    pm.created_at,
                    pm.qty,
                    pm.unit_sell_price,
                    pm.discount,
                    pm.sell_currency,
                    pm.is_kit,
                    pm.kit_id,
                    CASE
                        WHEN pm.is_kit = 1 THEN 'KIT PRODUITS'
                        ELSE COALESCE(p.name, 'Produit inconnu')
                    END AS product_name
                FROM product_movements pm
                LEFT JOIN products p ON p.id = pm.product_id
                WHERE pm.client_code = ?
                  AND pm.receipt_id IN ($placeholders)
                ORDER BY pm.receipt_id DESC, pm.is_kit DESC, pm.kit_id ASC, pm.id ASC
            ";

            $detailParams = array_merge([$client_code], $receiptIds);
            $stmtDetails = $pdo->prepare($detailSql);
            $stmtDetails->execute($detailParams);
            $detailRows = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

            foreach ($detailRows as $row) {
                $receipt = (string)($row['receipt_id'] ?? '');
                if ($receipt === '') {
                    continue;
                }
                if (!isset($ticketDetailsByReceipt[$receipt])) {
                    $ticketDetailsByReceipt[$receipt] = [];
                }
                $ticketDetailsByReceipt[$receipt][] = $row;
            }
        }
    }
} catch (Throwable $e) {
    error_log('tickets_printed.php error: ' . $e->getMessage());
    $loadError = 'Impossible de charger les tickets imprimes pour le moment.';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tickets imprimes | Cartelplus Congo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --pp-blue: #0070e0;
            --pp-blue-dark: #003087;
            --pp-cyan: #00a8ff;
            --pp-bg: #f5f7fb;
            --pp-text: #0b1f3a;
            --pp-muted: #6b7a90;
            --pp-card: #ffffff;
            --pp-border: #e5e9f2;
            --pp-shadow: 0 12px 30px rgba(0, 48, 135, 0.08);
            --pp-success: #1f8f6a;
            --pp-warning: #f59e0b;
        }

        body {
            background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                        radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                        var(--pp-bg);
            color: var(--pp-text);
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        .page-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 16px 54px;
        }

        .page-hero {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border-radius: 18px;
            padding: 24px 24px;
            box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-hero h1 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 700;
        }

        .page-hero p {
            margin: 0;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }

        .btn-pp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .btn-pp-secondary {
            background: rgba(255,255,255,0.18);
            color: #fff;
            border-color: rgba(255,255,255,0.45);
        }

        .btn-pp-secondary:hover {
            transform: translateY(-1px);
            color: #fff;
            background: rgba(255,255,255,0.26);
        }

        .btn-pp-primary {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25);
        }

        .btn-pp-primary:hover {
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-pp-light {
            background: #fff;
            color: var(--pp-blue-dark);
            border-color: var(--pp-border);
        }

        .btn-pp-light:hover {
            transform: translateY(-1px);
            color: var(--pp-blue-dark);
            border-color: var(--pp-blue);
        }

        .filter-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--pp-shadow);
            margin-bottom: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: var(--pp-shadow);
        }

        .stat-label {
            font-size: 12px;
            color: var(--pp-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 25px;
            font-weight: 700;
            color: var(--pp-blue-dark);
            line-height: 1.2;
        }

        .table-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            box-shadow: var(--pp-shadow);
            overflow: hidden;
        }

        .table-main {
            margin: 0;
        }

        .table-main thead th {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border: none;
            padding: 13px 12px;
            font-size: 13px;
            vertical-align: middle;
        }

        .table-main tbody td {
            padding: 12px;
            vertical-align: middle;
            border-color: var(--pp-border);
        }

        .detail-cell {
            background: #f8fbff;
            padding: 0 !important;
        }

        .detail-wrap {
            padding: 14px;
            border-top: 1px dashed rgba(0, 112, 224, 0.3);
        }

        .detail-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--pp-blue-dark);
            margin-bottom: 10px;
        }

        .detail-table {
            margin-bottom: 0;
            font-size: 13px;
        }

        .detail-table thead th {
            background: #eaf3ff;
            color: #0b1f3a;
            border-color: #d4e5ff;
            font-size: 12px;
            padding: 8px;
        }

        .detail-table tbody td {
            padding: 8px;
            border-color: #edf2fb;
        }

        .mode-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(0, 112, 224, 0.12);
            color: var(--pp-blue-dark);
        }

        .mode-badge.manual {
            background: rgba(31, 143, 106, 0.14);
            color: var(--pp-success);
        }

        .mode-badge.autoprint {
            background: rgba(245, 158, 11, 0.15);
            color: #b45309;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #7b8ca3;
        }

        .empty-state i {
            font-size: 44px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .page-hero {
                padding: 20px;
            }

            .page-hero h1 {
                font-size: 22px;
            }

            .table-main {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<div class="page-wrap">
    <div class="page-hero">
        <div>
            <h1><i class="fa-solid fa-print"></i> Tickets imprimes</h1>
            <p>Suivi des impressions par jour, vendeur et maison.</p>
        </div>
        <a href="dashboard.php" class="btn-pp btn-pp-secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour dashboard
        </a>
    </div>

    <?php if ($filterNotice !== ''): ?>
        <div class="alert alert-warning"><?= h($filterNotice) ?></div>
    <?php endif; ?>

    <?php if ($loadError !== ''): ?>
        <div class="alert alert-danger"><?= h($loadError) ?></div>
    <?php endif; ?>

    <?php if (!$tableExists): ?>
        <div class="alert alert-info">
            Le journal d'impression est vide pour l'instant. Les tickets imprimes a partir de maintenant apparaitront ici.
        </div>
    <?php endif; ?>

    <div class="filter-card">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-12 col-md-2">
                <label class="form-label">Date debut</label>
                <input type="date" class="form-control" name="from_date" value="<?= h($fromDate) ?>" required>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Date fin</label>
                <input type="date" class="form-control" name="to_date" value="<?= h($toDate) ?>" required>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Maison</label>
                <select name="house_id" class="form-select">
                    <option value="0">Toutes</option>
                    <?php foreach ($houses as $house): ?>
                        <option value="<?= (int)$house['id'] ?>" <?= ((int)$house['id'] === $houseId) ? 'selected' : '' ?>>
                            <?= h($house['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Vendeur</label>
                <select name="agent_id" class="form-select">
                    <option value="0">Tous</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= (int)$agent['id'] ?>" <?= ((int)$agent['id'] === $agentId) ? 'selected' : '' ?>>
                            <?= h($agent['fullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Ticket / Recu</label>
                <input type="text" class="form-control" name="ticket" value="<?= h($ticketSearch) ?>" placeholder="TCK-...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn-pp btn-pp-primary">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
                <a href="tickets_printed.php" class="btn-pp btn-pp-light">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Impressions filtrees</div>
            <div class="stat-value"><?= number_format($totalPrints) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Impressions aujourd'hui</div>
            <div class="stat-value"><?= number_format($todayPrints) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tickets uniques</div>
            <div class="stat-value"><?= number_format($uniqueTickets) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Vendeurs concernes</div>
            <div class="stat-value"><?= number_format($uniqueSellers) ?></div>
        </div>
    </div>

    <div class="table-card">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <div>Aucun ticket imprime pour les filtres selectionnes.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-main">
                    <thead>
                    <tr>
                        <th>Imprime le</th>
                        <th>Ticket</th>
                        <th>Recu</th>
                        <th>Vendeur</th>
                        <th>Maison</th>
                        <th>Mode</th>
                        <th class="text-end">Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $rowId = (int)$log['id'];
                        $receiptId = (string)($log['receipt_id'] ?? '');
                        $details = $ticketDetailsByReceipt[$receiptId] ?? [];
                        $mode = strtolower((string)($log['print_mode'] ?? 'manual'));
                        $modeClass = ($mode === 'autoprint') ? 'autoprint' : 'manual';
                        ?>
                        <tr>
                            <td><?= h(formatDateTimeLabel($log['printed_at'] ?? null)) ?></td>
                            <td><?= h($log['ticket_number'] !== '' ? $log['ticket_number'] : '-') ?></td>
                            <td><code><?= h($receiptId) ?></code></td>
                            <td><?= h($log['agent_name'] ?? '-') ?></td>
                            <td><?= h($log['house_name'] ?? '-') ?></td>
                            <td><span class="mode-badge <?= h($modeClass) ?>"><?= h(strtoupper($mode)) ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#ticket-details-<?= $rowId ?>" aria-expanded="false">
                                    Voir
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="ticket-details-<?= $rowId ?>">
                            <td colspan="7" class="detail-cell">
                                <div class="detail-wrap">
                                    <div class="detail-title">
                                        Detail du ticket <?= h($log['ticket_number'] !== '' ? $log['ticket_number'] : $receiptId) ?>
                                    </div>

                                    <?php if (empty($details)): ?>
                                        <div class="text-muted">Aucun detail trouve pour ce recu.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table detail-table">
                                                <thead>
                                                <tr>
                                                    <th>Date vente</th>
                                                    <th>Type ligne</th>
                                                    <th>Produit</th>
                                                    <th class="text-center">Qte</th>
                                                    <th class="text-end">PU</th>
                                                    <th class="text-end">Remise</th>
                                                    <th class="text-end">Montant</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($details as $detail): ?>
                                                    <?php
                                                    $qty = (float)($detail['qty'] ?? 0);
                                                    $unitPrice = (float)($detail['unit_sell_price'] ?? 0);
                                                    $discount = (float)($detail['discount'] ?? 0);
                                                    $currency = (string)($detail['sell_currency'] ?? 'CDF');

                                                    $isKitParent = ((int)($detail['is_kit'] ?? 0) === 1) && empty($detail['kit_id']);
                                                    $isKitComponent = !empty($detail['kit_id']);

                                                    if ($isKitParent) {
                                                        $lineType = 'Kit principal';
                                                        $lineAmount = $unitPrice;
                                                    } elseif ($isKitComponent) {
                                                        $lineType = 'Composant kit';
                                                        $lineAmount = $qty * $unitPrice;
                                                        $discount = 0;
                                                    } else {
                                                        $lineType = 'Produit simple';
                                                        $lineAmount = ($qty * $unitPrice) - $discount;
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= h(formatDateTimeLabel($detail['created_at'] ?? null)) ?></td>
                                                        <td><?= h($lineType) ?></td>
                                                        <td><?= h($detail['product_name'] ?? '-') ?></td>
                                                        <td class="text-center"><?= h(number_format($qty, 0)) ?></td>
                                                        <td class="text-end"><?= h(formatAmountByCurrency($unitPrice, $currency)) ?></td>
                                                        <td class="text-end"><?= $discount > 0 ? h('-' . formatAmountByCurrency($discount, $currency)) : '-' ?></td>
                                                        <td class="text-end fw-bold"><?= h(formatAmountByCurrency($lineAmount, $currency)) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// expenses.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

// Create table if not exists (safe)
$pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(100) NOT NULL,
    expense_date DATE NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(120) NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'CDF',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_date (client_code, expense_date),
    INDEX idx_client_currency (client_code, currency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $expense_date = $_POST['expense_date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'CDF'));
        $notes = trim($_POST['notes'] ?? '');

        if (!$expense_date) $errors[] = 'La date est obligatoire.';
        if ($title === '' || strlen($title) < 3) $errors[] = 'Le titre doit contenir au moins 3 caracteres.';
        if ($amount <= 0) $errors[] = 'Le montant doit etre superieur a 0.';
        if (!in_array($currency, ['CDF', 'USD'], true)) $errors[] = 'La devise doit etre CDF ou USD.';

        if (!$errors) {
            $stmt = $pdo->prepare("INSERT INTO expenses (client_code, expense_date, title, category, amount, currency, notes)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_code, $expense_date, $title, $category, $amount, $currency, $notes]);
            header('Location: expenses.php?msg=created');
            exit;
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $expense_date = $_POST['expense_date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'CDF'));
        $notes = trim($_POST['notes'] ?? '');

        if ($id <= 0) $errors[] = 'Depense invalide.';
        if (!$expense_date) $errors[] = 'La date est obligatoire.';
        if ($title === '' || strlen($title) < 3) $errors[] = 'Le titre doit contenir au moins 3 caracteres.';
        if ($amount <= 0) $errors[] = 'Le montant doit etre superieur a 0.';
        if (!in_array($currency, ['CDF', 'USD'], true)) $errors[] = 'La devise doit etre CDF ou USD.';

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE expenses SET expense_date = ?, title = ?, category = ?, amount = ?, currency = ?, notes = ?
                                   WHERE id = ? AND client_code = ?");
            $stmt->execute([$expense_date, $title, $category, $amount, $currency, $notes, $id, $client_code]);
            header('Location: expenses.php?msg=updated');
            exit;
        }
    }
}

// Filters
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$category_filter = trim($_GET['category'] ?? '');
$currency_filter = strtoupper(trim($_GET['currency'] ?? ''));
$q = trim($_GET['q'] ?? '');

$where = " WHERE client_code = ?";
$params = [$client_code];

if ($date_from) {
    $where .= " AND expense_date >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where .= " AND expense_date <= ?";
    $params[] = $date_to;
}
if ($category_filter !== '') {
    $where .= " AND category = ?";
    $params[] = $category_filter;
}
if ($currency_filter !== '') {
    $where .= " AND currency = ?";
    $params[] = $currency_filter;
}
if ($q !== '') {
    $where .= " AND (title LIKE ? OR notes LIKE ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

$stmt = $pdo->prepare("SELECT * FROM expenses {$where} ORDER BY expense_date DESC, id DESC");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$totalStmt = $pdo->prepare("SELECT currency, SUM(amount) AS total_amount FROM expenses {$where} GROUP BY currency");
$totalStmt->execute($params);
$totals = $totalStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$categoriesStmt = $pdo->prepare("SELECT DISTINCT category FROM expenses WHERE client_code = ? AND category <> '' ORDER BY category ASC");
$categoriesStmt->execute([$client_code]);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

if (isset($headerPath) && is_file($headerPath)) {
    require_once $headerPath;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des depenses - Cartelplus Congo</title>

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
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 16px 60px;
}

.page-hero {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 20px;
    padding: 26px 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.page-hero h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    color: #fff;
}

.btn-pp {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 18px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.btn-pp-primary {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25);
}

.btn-pp-secondary {
    background: #fff;
    color: var(--pp-blue-dark);
    border-color: var(--pp-border);
}

.btn-pp-danger {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.25);
}

.btn-pp:hover {
    transform: translateY(-1px);
    opacity: 0.95;
}

.card-panel {
    background: var(--pp-card);
    border: 1px solid var(--pp-border);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--pp-shadow);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.totals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.total-card {
    background: #fff;
    border: 1px solid var(--pp-border);
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    box-shadow: var(--pp-shadow);
}

.total-card .label {
    color: var(--pp-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.total-card .value {
    font-size: 20px;
    font-weight: 700;
    color: var(--pp-blue-dark);
    margin-top: 6px;
}

.table thead th {
    background: #f0f4f9;
    color: var(--pp-blue-dark);
    border-bottom: 1px solid var(--pp-border);
}

.badge-currency {
    background: rgba(0,112,224,0.1);
    color: var(--pp-blue-dark);
    padding: 4px 8px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 12px;
}

@media (max-width: 768px) {
    .table-expenses thead {
        display: none;
    }
    .table-expenses,
    .table-expenses tbody,
    .table-expenses tr,
    .table-expenses td {
        display: block;
        width: 100%;
    }
    .table-expenses tr {
        margin-bottom: 14px;
        border: 1px solid var(--pp-border);
        border-radius: 12px;
        box-shadow: var(--pp-shadow);
        background: #fff;
        padding: 8px 12px;
    }
    .table-expenses td {
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }
    .table-expenses td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--pp-muted);
        margin-right: 12px;
    }
    .table-expenses td:last-child {
        padding-bottom: 4px;
    }
}
</style>
</head>

<body>

<div class="page-wrap">
    <div class="page-hero">
        <h3>Gestion des depenses</h3>
        <a href="<?= DASHBOARD_ADMIN ?>" class="btn-pp btn-pp-secondary">&larr; Retour</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success">Depense ajoutee avec succes.</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">Depense modifiee avec succes.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card-panel mb-4">
        <h5 class="mb-3">Ajouter une depense</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="add">
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="expense_date" class="form-control" required value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Titre</label>
                <input type="text" name="title" class="form-control" required minlength="3" maxlength="200" placeholder="Ex: Transport, Internet">
            </div>
            <div class="col-md-2">
                <label class="form-label">Categorie</label>
                <input type="text" name="category" class="form-control" maxlength="120" placeholder="Ex: Logistique">
            </div>
            <div class="col-md-2">
                <label class="form-label">Montant</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Devise</label>
                <select name="currency" class="form-select">
                    <option value="CDF">CDF</option>
                    <option value="USD">USD</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" maxlength="255" placeholder="Optionnel">
            </div>
            <div class="col-12">
                <button type="submit" class="btn-pp btn-pp-primary">Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="card-panel mb-4">
        <h5 class="mb-3">Filtres</h5>
        <form method="GET" class="filter-grid">
            <div>
                <label class="form-label">Du</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div>
                <label class="form-label">Au</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div>
                <label class="form-label">Categorie</label>
                <select name="category" class="form-select">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Devise</label>
                <select name="currency" class="form-select">
                    <option value="">Toutes</option>
                    <option value="CDF" <?= $currency_filter === 'CDF' ? 'selected' : '' ?>>CDF</option>
                    <option value="USD" <?= $currency_filter === 'USD' ? 'selected' : '' ?>>USD</option>
                </select>
            </div>
            <div>
                <label class="form-label">Recherche</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Mot-cle">
            </div>
            <div class="d-flex align-items-end gap-2">
                <button class="btn-pp btn-pp-primary" type="submit">Filtrer</button>
                <a href="expenses.php" class="btn-pp btn-pp-secondary">Reinitialiser</a>
            </div>
        </form>
    </div>

    <div class="totals-grid mb-4">
        <div class="total-card">
            <div class="label">Total CDF</div>
            <div class="value"><?= number_format((float)($totals['CDF'] ?? 0), 0) ?> CDF</div>
        </div>
        <div class="total-card">
            <div class="label">Total USD</div>
            <div class="value"><?= number_format((float)($totals['USD'] ?? 0), 2) ?> USD</div>
        </div>
    </div>

    <div class="card-panel">
        <h5 class="mb-3">Liste des depenses</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-expenses">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Titre</th>
                        <th>Categorie</th>
                        <th>Montant</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$expenses): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucune depense pour ce filtre.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td data-label="Date"><?= htmlspecialchars($e['expense_date']) ?></td>
                            <td data-label="Titre"><?= htmlspecialchars($e['title']) ?></td>
                            <td data-label="Categorie"><?= htmlspecialchars($e['category'] ?: '-') ?></td>
                            <td data-label="Montant">
                                <span class="badge-currency"><?= number_format((float)$e['amount'], $e['currency'] === 'USD' ? 2 : 0) ?> <?= htmlspecialchars($e['currency']) ?></span>
                            </td>
                            <td data-label="Notes"><?= htmlspecialchars($e['notes'] ?: '-') ?></td>
                            <td data-label="Action">
                                <button
                                    class="btn-pp btn-pp-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editExpenseModal"
                                    data-id="<?= (int)$e['id'] ?>"
                                    data-date="<?= htmlspecialchars($e['expense_date']) ?>"
                                    data-title="<?= htmlspecialchars($e['title']) ?>"
                                    data-category="<?= htmlspecialchars($e['category']) ?>"
                                    data-amount="<?= htmlspecialchars($e['amount']) ?>"
                                    data-currency="<?= htmlspecialchars($e['currency']) ?>"
                                    data-notes="<?= htmlspecialchars($e['notes']) ?>"
                                >Modifier</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal modification -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier une depense</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" id="edit_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required minlength="3" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categorie</label>
                        <input type="text" name="category" id="edit_category" class="form-control" maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Montant</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Devise</label>
                        <select name="currency" id="edit_currency" class="form-select">
                            <option value="CDF">CDF</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" id="edit_notes" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-pp btn-pp-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const editModal = document.getElementById('editExpenseModal');
if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;
                document.getElementById('edit_id').value = button.getAttribute('data-id') || '';
                document.getElementById('edit_date').value = button.getAttribute('data-date') || '';
                document.getElementById('edit_title').value = button.getAttribute('data-title') || '';
                document.getElementById('edit_category').value = button.getAttribute('data-category') || '';
                document.getElementById('edit_amount').value = button.getAttribute('data-amount') || '';
                document.getElementById('edit_currency').value = button.getAttribute('data-currency') || 'CDF';
                document.getElementById('edit_notes').value = button.getAttribute('data-notes') || '';
        });
}
</script>
</body>
</html>

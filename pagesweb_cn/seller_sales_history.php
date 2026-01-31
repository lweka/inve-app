<?php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    header("Location: connect-parse.php?role=seller");
    exit;
}

// Vérifier que le vendeur est toujours actif
$stmt = $pdo->prepare("SELECT id, status FROM agents WHERE id=? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();

if(!$agent || $agent['status'] !== 'active'){
    header("Location: account_disabled.php");
    exit;
}

$house_id = (int)$_SESSION['house_id'];
$agent_id = (int)$_SESSION['user_id'];

/* ===============================
   HISTORIQUE DES VENTES VENDEUR
   =============================== */
$stmt = $pdo->prepare("
SELECT
  pm.id,
  pm.created_at,
  pm.qty,
  pm.unit_sell_price,
  pm.discount,
  pm.payment_method,
  pm.customer_name,
  pm.is_kit,
  pm.kit_id,
  p.name AS product_name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
WHERE pm.house_id = ?
  AND pm.agent_id = ?
  AND pm.type = 'sale'
ORDER BY
  COALESCE(pm.kit_id, pm.id) DESC,
  pm.is_kit DESC
");
$stmt->execute([$house_id, $agent_id]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
$stmt = $pdo->prepare("
SELECT
    pm.created_at,
    p.name,
    pm.qty,
    pm.unit_sell_price,
    p.sell_currency,
    pm.discount,
    pm.payment_method,
    pm.customer_name,
    (pm.qty * pm.unit_sell_price - pm.discount) AS total
FROM product_movements pm
JOIN products p ON p.id = pm.product_id
WHERE pm.house_id = ?
  AND pm.agent_id = ?
  AND pm.type = 'sale'
ORDER BY pm.created_at DESC
");
$stmt->execute([$house_id, $agent_id]);
$sales = $stmt->fetchAll();*/
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Historique des ventes | Cartelplus Congo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
/* ================================================
   PAYPAL/HOSTINGER THEME - SELLER SALES HISTORY
================================================ */
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
  --pp-shadow: rgba(0, 48, 135, 0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  min-height: 100vh;
  background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  color: var(--pp-text);
  padding: 20px;
  overflow-x: hidden;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pulseGlow {
  0%, 100% { box-shadow: 0 4px 20px var(--pp-shadow); }
  50% { box-shadow: 0 8px 30px rgba(0, 112, 224, 0.15); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

/* ===== HEADER ===== */
.history-header {
  background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
  padding: 28px 32px;
  border-radius: 16px;
  margin-bottom: 28px;
  box-shadow: 0 10px 30px var(--pp-shadow);
  animation: fadeSlide 0.7s ease both;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.history-header h1 {
  color: var(--pp-white);
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.history-header h1 i {
  color: var(--pp-cyan);
  font-size: 32px;
}

/* ===== BOUTONS ===== */
.btn-pp {
  padding: 10px 22px;
  border-radius: 24px;
  font-weight: 600;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  text-decoration: none;
  font-size: 14px;
}

.btn-pp-secondary {
  background: var(--pp-white);
  color: var(--pp-blue);
  border: 2px solid var(--pp-border);
}

.btn-pp-secondary:hover {
  background: var(--pp-bg);
  border-color: var(--pp-blue);
  transform: translateY(-2px);
}

/* ===== TABLE CARD ===== */
.table-card {
  background: var(--pp-white);
  border-radius: 16px;
  padding: 0;
  box-shadow: 0 4px 20px var(--pp-shadow);
  border: 1px solid var(--pp-border);
  animation: fadeUp 0.6s ease both;
  overflow: hidden;
}

/* ===== TABLE ===== */
.table-pp {
  margin: 0;
}

.table-pp thead {
  background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
  border: none;
}

.table-pp thead th {
  color: var(--pp-white);
  font-weight: 700;
  padding: 16px 14px;
  border: none;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table-pp tbody tr {
  border-color: var(--pp-border);
  animation: slideIn 0.4s ease both;
  transition: all 0.2s ease;
}

.table-pp tbody tr:hover {
  background: var(--pp-bg);
  box-shadow: inset 0 0 0 1px var(--pp-border);
}

.table-pp tbody td {
  padding: 16px 14px;
  color: var(--pp-text);
  font-size: 14px;
  vertical-align: middle;
}

.table-pp .row-kit {
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.05), transparent);
  border-left: 3px solid var(--pp-warning) !important;
}

.table-pp .row-kit-item {
  background: rgba(0, 112, 224, 0.03);
  border-left: 3px solid var(--pp-blue) !important;
}

.badge-pp {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.badge-success {
  background: rgba(31, 143, 106, 0.15);
  color: var(--pp-success);
}

.badge-warning {
  background: rgba(245, 158, 11, 0.15);
  color: var(--pp-warning);
}

.badge-info {
  background: rgba(0, 112, 224, 0.15);
  color: var(--pp-blue);
}

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 60px 32px;
  color: #9ca3af;
}

.empty-state i {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state p {
  font-size: 16px;
  margin-top: 12px;
}

/* ===== PRICE COLORS ===== */
.price-high {
  color: var(--pp-success);
  font-weight: 700;
}

.price-medium {
  color: var(--pp-blue-dark);
  font-weight: 600;
}

.text-orange {
  color: var(--pp-warning);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .history-header {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }
  
  .history-header h1 {
    font-size: 22px;
  }
  
  .table-pp {
    font-size: 12px;
  }
  
  .table-pp thead th,
  .table-pp tbody td {
    padding: 12px 8px;
  }
}

/* ===== STAGGER ===== */
.table-pp tbody tr:nth-child(1) { animation-delay: 0.05s; }
.table-pp tbody tr:nth-child(2) { animation-delay: 0.1s; }
.table-pp tbody tr:nth-child(3) { animation-delay: 0.15s; }
.table-pp tbody tr:nth-child(n+4) { animation-delay: 0.2s; }
</style>
</head>

<body>

<div class="container-fluid" style="max-width: 1200px;">

<!-- HEADER -->
<div class="history-header">
  <h1>
    <i class="fa-solid fa-receipt"></i>
    Historique des ventes
  </h1>
  <a href="seller_dashboard.php" class="btn-pp btn-pp-secondary">
    <i class="fa-solid fa-arrow-left"></i> Retour au POS
  </a>
</div>

<!-- TABLE CARD -->
<div class="table-card">
  <div class="table-responsive">
    <table class="table table-pp">
      <thead>
        <tr>
          <th>Date & Heure</th>
          <th>Produit</th>
          <th class="text-center">Qté</th>
          <th class="text-end">Prix unitaire</th>
          <th class="text-end">Remise</th>
          <th>Paiement</th>
          <th>Client</th>
          <th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$sales): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>Aucune vente enregistrée</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>

        <?php
        $currentKit = null;
        foreach($sales as $s):

        /* ================= KIT (PARENT) ================= */
        if($s['is_kit'] && !$s['kit_id']):
          $currentKit = $s['id'];
        ?>
        <tr class="row-kit">
          <td>
            <strong><?= htmlspecialchars($s['created_at']) ?></strong>
          </td>
          <td>
            <span class="badge-pp badge-warning">
              <i class="fa-solid fa-boxes-stacked"></i> Kit Produits
            </span>
          </td>
          <td class="text-center">—</td>
          <td class="text-end">—</td>
          <td class="text-end">
            <?php if($s['discount'] > 0): ?>
              <span class="price-high">-<?= number_format((float)$s['discount'], 2) ?> CDF</span>
            <?php else: ?>
              <span>—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge-pp badge-info">
              <?php 
                $methods = ['cash' => '💵 Espèces', 'mobile' => '📱 Mobile', 'credit' => '💳 Crédit'];
                echo $methods[$s['payment_method']] ?? ucfirst($s['payment_method']);
              ?>
            </span>
          </td>
          <td><?= htmlspecialchars($s['customer_name'] ?: '—') ?></td>
          <td class="text-end price-high">
            <strong><?= number_format($s['unit_sell_price'], 2) ?> CDF</strong>
          </td>
        </tr>

        <?php continue; endif; ?>

        <?php
        /* ================= COMPOSANTS DU KIT ================= */
        if($s['kit_id'] && $s['kit_id'] == $currentKit):
        ?>
        <tr class="row-kit-item">
          <td></td>
          <td>
            <i class="fa-solid fa-arrow-right" style="color: var(--pp-blue);"></i>
            <?= htmlspecialchars($s['product_name']) ?>
          </td>
          <td class="text-center">
            <span class="price-medium">× <?= (int)$s['qty'] ?></span>
          </td>
          <td class="text-end price-medium">
            <?= number_format($s['unit_sell_price'], 2) ?> CDF
          </td>
          <td></td>
          <td></td>
          <td></td>
          <td class="text-end price-medium">
            <strong><?= number_format($s['unit_sell_price'] * $s['qty'], 2) ?> CDF</strong>
          </td>
        </tr>

        <?php continue; endif; ?>

        <?php
        /* ================= PRODUIT SIMPLE ================= */
        $currentKit = null;
        ?>
        <tr>
          <td><?= htmlspecialchars($s['created_at']) ?></td>
          <td>
            <span class="price-medium"><?= htmlspecialchars($s['product_name']) ?></span>
          </td>
          <td class="text-center">
            <strong><?= (int)$s['qty'] ?></strong>
          </td>
          <td class="text-end price-medium">
            <?= number_format($s['unit_sell_price'], 2) ?> CDF
          </td>
          <td class="text-end">
            <?php if($s['discount'] > 0): ?>
              <span class="price-high">-<?= number_format((float)$s['discount'], 2) ?> CDF</span>
            <?php else: ?>
              <span>—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge-pp badge-info">
              <?php 
                $methods = ['cash' => '💵 Espèces', 'mobile' => '📱 Mobile', 'credit' => '💳 Crédit'];
                echo $methods[$s['payment_method']] ?? ucfirst($s['payment_method']);
              ?>
            </span>
          </td>
          <td><?= htmlspecialchars($s['customer_name'] ?: '—') ?></td>
          <td class="text-end price-high">
            <strong><?= number_format(($s['unit_sell_price'] * $s['qty']) - (float)$s['discount'], 2) ?> CDF</strong>
          </td>
        </tr>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
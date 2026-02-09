<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/connectDb.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    exit('Acces refuse');
}

$stmtCheck = $pdo->prepare('SELECT status FROM agents WHERE id = ? LIMIT 1');
$stmtCheck->execute([$_SESSION['user_id']]);
$agentStatus = $stmtCheck->fetchColumn();
if ($agentStatus !== 'active') {
    header('Location: account_disabled.php');
    exit;
}

$agentId = (int)$_SESSION['user_id'];
$saleId = (int)($_GET['sale_id'] ?? 0);
if ($saleId <= 0) {
    exit('Vente invalide');
}

$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';
$previewMode = isset($_GET['preview']) && $_GET['preview'] === '1';

$houseColumns = [];
try {
    $houseColumns = $pdo->query('SHOW COLUMNS FROM houses')->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (Throwable $e) {
    error_log('House columns read error: ' . $e->getMessage());
}

$selectHouseAddress = in_array('address', $houseColumns, true)
    ? 'h.address AS house_address'
    : "'' AS house_address";
$selectHouseLogo = in_array('logo_path', $houseColumns, true)
    ? 'h.logo_path AS house_logo_path'
    : 'NULL AS house_logo_path';

$stmt = $pdo->prepare("\nSELECT pm.*, a.fullname AS agent_name, h.name AS house_name,\n       {$selectHouseAddress}, {$selectHouseLogo}\nFROM product_movements pm\nJOIN agents a ON a.id = pm.agent_id\nJOIN houses h ON h.id = pm.house_id\nWHERE pm.id = ? AND pm.agent_id = ?\nLIMIT 1\n");
$stmt->execute([$saleId, $agentId]);
$sale = $stmt->fetch();

if (!$sale) {
    exit('Vente introuvable');
}

$receiptId = $sale['receipt_id'];

$stmt = $pdo->prepare("\nSELECT\n    pm.id,\n    pm.is_kit,\n    pm.kit_id,\n    pm.qty,\n    pm.unit_sell_price,\n    pm.discount,\n    pm.sell_currency,\n    CASE\n        WHEN pm.is_kit = 1 THEN 'KIT PRODUITS'\n        ELSE COALESCE(p.name, 'Produit inconnu')\n    END AS name\nFROM product_movements pm\nLEFT JOIN products p ON p.id = pm.product_id\nWHERE pm.receipt_id = ?\nORDER BY pm.is_kit DESC, pm.id ASC\n");
$stmt->execute([$receiptId]);
$allItems = $stmt->fetchAll();

$kits = [];
$simpleProducts = [];
$kitComponents = [];

foreach ($allItems as $item) {
    if ((int)$item['is_kit'] === 1) {
        $kits[] = $item;
    } elseif (!empty($item['kit_id'])) {
        $kitComponents[$item['kit_id']][] = $item;
    } else {
        $simpleProducts[] = $item;
    }
}

function formatAmount($amount, $currency) {
    $decimals = ($currency === 'USD') ? 2 : 0;
    return number_format((float)$amount, $decimals) . ' ' . $currency;
}

function buildTotalsString(array $totalsByCurrency): string {
    $parts = [];
    foreach ($totalsByCurrency as $cur => $amt) {
        $parts[] = formatAmount($amt, $cur);
    }
    return implode(' + ', $parts);
}

function safeText($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cutText($value, int $max): string {
    $value = trim((string)$value);
    if ($max <= 3 || strlen($value) <= $max) return $value;
    return substr($value, 0, $max - 3) . '...';
}

function normalizeLogoPath($value): ?string {
    $value = trim(str_replace('\\', '/', (string)$value));
    $value = ltrim($value, '/');
    if ($value === '') return null;
    if (strpos($value, '..') !== false) return null;
    if (!preg_match('#^[A-Za-z0-9_./-]+$#', $value)) return null;
    return $value;
}

$logoPath = normalizeLogoPath($sale['house_logo_path'] ?? '');
$logoWebSrc = null;
if ($logoPath !== null) {
    $logoFsPath = __DIR__ . '/../images/' . str_replace('/', DIRECTORY_SEPARATOR, $logoPath);
    if (is_file($logoFsPath)) {
        $logoWebSrc = '../images/' . $logoPath;
    }
}

$ticketRows = [];
$totalsByCurrency = [];

foreach ($kits as $kit) {
    $kitDiscount = (float)($kit['discount'] ?? 0);
    $hasDiscount = $kitDiscount > 0;
    $kitCurrency = $kit['sell_currency'] ?? 'CDF';
    $isMultiCurrency = strpos((string)$kitCurrency, '/') !== false;

    if ($hasDiscount && $isMultiCurrency) {
        $kitTotal = (float)$kit['unit_sell_price'];
        $totalsByCurrency['CDF'] = ($totalsByCurrency['CDF'] ?? 0) + $kitTotal;

        $ticketRows[] = [
            'type' => 'main',
            'name' => 'KIT PRODUITS',
            'qty' => (string)$kit['qty'],
            'amount' => formatAmount($kitTotal, 'CDF')
        ];

        if (isset($kitComponents[$kit['id']])) {
            foreach ($kitComponents[$kit['id']] as $comp) {
                $compTotal = (float)$comp['qty'] * (float)$comp['unit_sell_price'];
                $compCurrency = $comp['sell_currency'] ?? 'CDF';
                $ticketRows[] = [
                    'type' => 'sub',
                    'name' => '> ' . cutText($comp['name'], 28),
                    'qty' => (string)$comp['qty'],
                    'amount' => formatAmount($compTotal, $compCurrency)
                ];
            }
        }

        $ticketRows[] = [
            'type' => 'sub_total',
            'name' => 'Remise appliquee',
            'qty' => '',
            'amount' => '-' . formatAmount($kitDiscount, 'CDF')
        ];
    } else {
        $kitTotals = [];
        if (isset($kitComponents[$kit['id']])) {
            foreach ($kitComponents[$kit['id']] as $comp) {
                $cur = $comp['sell_currency'] ?? 'CDF';
                $compTotal = (float)$comp['qty'] * (float)$comp['unit_sell_price'];
                $kitTotals[$cur] = ($kitTotals[$cur] ?? 0) + $compTotal;
            }
        }

        foreach ($kitTotals as $cur => $amt) {
            $totalsByCurrency[$cur] = ($totalsByCurrency[$cur] ?? 0) + $amt;
        }

        $ticketRows[] = [
            'type' => 'main',
            'name' => 'KIT PRODUITS',
            'qty' => (string)$kit['qty'],
            'amount' => buildTotalsString($kitTotals)
        ];

        if (isset($kitComponents[$kit['id']])) {
            foreach ($kitComponents[$kit['id']] as $comp) {
                $compTotal = (float)$comp['qty'] * (float)$comp['unit_sell_price'];
                $compCurrency = $comp['sell_currency'] ?? 'CDF';
                $ticketRows[] = [
                    'type' => 'sub',
                    'name' => '> ' . cutText($comp['name'], 28),
                    'qty' => (string)$comp['qty'],
                    'amount' => formatAmount($compTotal, $compCurrency)
                ];
            }
        }

        if ($hasDiscount && !$isMultiCurrency) {
            $ticketRows[] = [
                'type' => 'sub_total',
                'name' => 'Remise appliquee',
                'qty' => '',
                'amount' => '-' . formatAmount($kitDiscount, $kitCurrency)
            ];
            $totalsByCurrency[$kitCurrency] = ($totalsByCurrency[$kitCurrency] ?? 0) - $kitDiscount;
        }
    }
}

foreach ($simpleProducts as $product) {
    $prodTotal = (float)$product['qty'] * (float)$product['unit_sell_price'];
    $prodCurrency = $product['sell_currency'] ?? 'CDF';
    $totalsByCurrency[$prodCurrency] = ($totalsByCurrency[$prodCurrency] ?? 0) + $prodTotal;

    $ticketRows[] = [
        'type' => 'main',
        'name' => cutText($product['name'], 31),
        'qty' => (string)$product['qty'],
        'amount' => formatAmount($prodTotal, $prodCurrency)
    ];
}

$saleDiscount = (float)($sale['discount'] ?? 0);
if ($saleDiscount > 0 && (int)($sale['is_kit'] ?? 0) === 0) {
    $discountCurrency = $sale['sell_currency'] ?? 'CDF';
    $ticketRows[] = [
        'type' => 'discount',
        'name' => 'REMISE',
        'qty' => '',
        'amount' => '-' . formatAmount($saleDiscount, $discountCurrency)
    ];
    if (strpos((string)$discountCurrency, '/') === false) {
        $totalsByCurrency[$discountCurrency] = ($totalsByCurrency[$discountCurrency] ?? 0) - $saleDiscount;
    }
}

$paymentMethods = [
    'cash' => 'Especes',
    'mobile' => 'Mobile Money',
    'credit' => 'Credit'
];

$houseName = cutText($sale['house_name'] ?? 'Maison', 45);
$houseAddress = cutText($sale['house_address'] ?? '', 110);
$ticketNumber = $sale['ticket_number'] ?? '-';
$agentName = cutText($sale['agent_name'] ?? '-', 30);
$customerName = cutText($sale['customer_name'] ?? 'Client', 30);
$paymentLabel = $paymentMethods[$sale['payment_method']] ?? (string)$sale['payment_method'];
$createdAt = !empty($sale['created_at']) ? date('d/m/Y H:i', strtotime($sale['created_at'])) : '-';

$pdfUrl = 'seller_ticket_pdf.php?sale_id=' . urlencode((string)$saleId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket <?= safeText($ticketNumber) ?></title>
  <style>
    :root {
      --ticket-width: 80mm;
      --text: #0a0a0a;
      --muted: #666;
      --line: #111;
      --paper: #fff;
      --bg: #f1f3f5;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 16px;
      font-family: Arial, Helvetica, sans-serif;
      color: var(--text);
      background: var(--bg);
    }

    .toolbar {
      max-width: var(--ticket-width);
      margin: 0 auto 10px;
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .toolbar button,
    .toolbar a {
      border: 1px solid #cbd5e1;
      background: #fff;
      color: #0f172a;
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 13px;
      cursor: pointer;
    }

    .toolbar button.primary {
      background: #0f4c81;
      color: #fff;
      border-color: #0f4c81;
    }

    .ticket {
      width: var(--ticket-width);
      margin: 0 auto;
      background: var(--paper);
      padding: 7px 6px 9px;
      border: 1px solid #d5d9e0;
      border-radius: 6px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .header {
      text-align: center;
      margin-bottom: 8px;
    }

    .logo {
      display: block;
      margin: 0 auto 6px;
      max-width: 36mm;
      max-height: 20mm;
      object-fit: contain;
    }

    .house-name {
      font-size: 18px;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 3px;
    }

    .house-address {
      font-size: 12px;
      line-height: 1.3;
      color: #1f2937;
      margin-bottom: 3px;
    }

    .app-name {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 5px;
    }

    .rule {
      border-top: 2px solid var(--line);
      margin: 5px 0;
    }

    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }

    .meta td {
      font-size: 12px;
      padding: 2px 0;
      vertical-align: top;
    }

    .meta td:last-child {
      text-align: right;
      font-weight: 700;
      padding-left: 8px;
    }

    .items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }

    .items th {
      font-size: 12px;
      text-align: left;
      padding: 4px 0;
      border-bottom: 1px solid #444;
      border-top: 1px solid #444;
    }

    .items th:nth-child(2),
    .items td:nth-child(2) {
      width: 14%;
      text-align: center;
    }

    .items th:nth-child(3),
    .items td:nth-child(3) {
      width: 33%;
      text-align: right;
      white-space: nowrap;
    }

    .items td {
      font-size: 12px;
      padding: 3px 0;
      vertical-align: top;
    }

    .row-main td:first-child {
      font-weight: 700;
    }

    .row-sub td {
      font-size: 11px;
      color: #374151;
    }

    .row-sub td:first-child {
      padding-left: 7px;
    }

    .row-discount td:first-child,
    .row-sub-total td:first-child {
      font-weight: 700;
    }

    .totals {
      margin-top: 7px;
      border-top: 2px solid #111;
      padding-top: 6px;
    }

    .totals-title {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .total-line {
      display: flex;
      justify-content: flex-end;
      font-size: 13px;
      font-weight: 700;
      margin: 2px 0;
      gap: 8px;
    }

    .footer {
      border-top: 1px solid #555;
      margin-top: 8px;
      padding-top: 6px;
      text-align: center;
    }

    .footer-main {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 2px;
    }

    .footer-sub {
      font-size: 10px;
      color: #374151;
      line-height: 1.3;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .no-print {
        display: none !important;
      }

      .ticket {
        width: 80mm;
        margin: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding: 4px 3px 5px;
      }
    }
  </style>
</head>
<body>
  <?php if ($previewMode): ?>
    <div class="toolbar no-print">
      <button class="primary" onclick="window.print()">Imprimer</button>
      <a href="<?= safeText($pdfUrl) ?>" target="_blank" rel="noopener">Ouvrir PDF</a>
    </div>
  <?php endif; ?>

  <div class="ticket">
    <div class="header">
      <?php if ($logoWebSrc): ?>
        <img src="<?= safeText($logoWebSrc) ?>" alt="Logo maison" class="logo">
      <?php endif; ?>
      <div class="house-name"><?= safeText($houseName) ?></div>
      <?php if ($houseAddress !== ''): ?>
        <div class="house-address"><?= safeText($houseAddress) ?></div>
      <?php endif; ?>
      <div class="app-name">INVE-APP</div>
    </div>

    <div class="rule"></div>

    <table class="meta">
      <tr><td>Ticket</td><td><?= safeText($ticketNumber) ?></td></tr>
      <tr><td>Vendeur</td><td><?= safeText($agentName) ?></td></tr>
      <tr><td>Client</td><td><?= safeText($customerName) ?></td></tr>
      <tr><td>Paiement</td><td><?= safeText($paymentLabel) ?></td></tr>
      <tr><td>Date</td><td><?= safeText($createdAt) ?></td></tr>
    </table>

    <table class="items">
      <thead>
        <tr>
          <th>Article</th>
          <th>Qte</th>
          <th>Montant</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ticketRows as $row):
          $rowClass = 'row-main';
          if ($row['type'] === 'sub') $rowClass = 'row-sub';
          if ($row['type'] === 'discount') $rowClass = 'row-discount';
          if ($row['type'] === 'sub_total') $rowClass = 'row-sub-total';
        ?>
          <tr class="<?= safeText($rowClass) ?>">
            <td><?= safeText(cutText($row['name'], 36)) ?></td>
            <td><?= safeText($row['qty']) ?></td>
            <td><?= safeText($row['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="totals-title">TOTAL</div>
      <?php foreach ($totalsByCurrency as $cur => $amt): ?>
        <div class="total-line">
          <span><?= safeText($cur) ?></span>
          <span><?= safeText(formatAmount($amt, $cur)) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="footer">
      <div class="footer-main">Merci pour votre achat</div>
      <div class="footer-sub">Conservez ce recu pour reference</div>
    </div>
  </div>

  <script>
    (function () {
      const autoPrint = <?= $autoPrint ? 'true' : 'false' ?>;
      if (!autoPrint) return;

      window.addEventListener('load', function () {
        setTimeout(function () {
          try {
            window.print();
          } catch (e) {
            console.error('Print error:', e);
          }
        }, 350);
      });

      window.addEventListener('afterprint', function () {
        try {
          window.close();
        } catch (e) {
          console.error('Close error:', e);
        }
      });
    })();
  </script>
</body>
</html>

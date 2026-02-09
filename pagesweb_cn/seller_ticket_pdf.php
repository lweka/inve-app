<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF as TCPDF_Lib;

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
$logoFilePath = null;
if ($logoPath !== null) {
    $candidate = __DIR__ . '/../images/' . str_replace('/', DIRECTORY_SEPARATOR, $logoPath);
    if (is_file($candidate)) {
        $logoFilePath = $candidate;
    }
}

$pdf = new TCPDF_Lib('P', 'mm', [80, 240], true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(3, 3, 3);
$pdf->SetAutoPageBreak(true, 3);
$pdf->AddPage();
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

if ($logoFilePath) {
    $logoWidth = 26;
    $x = (80 - $logoWidth) / 2;
    $pdf->Image($logoFilePath, $x, $pdf->GetY(), $logoWidth, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
    $pdf->Ln(20);
}

$houseName = cutText($sale['house_name'] ?? 'Maison', 46);
$houseAddress = cutText($sale['house_address'] ?? '', 120);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->MultiCell(0, 5, $houseName, 0, 'C', false, 1);

if ($houseAddress !== '') {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 4, $houseAddress, 0, 'C', false, 1);
}

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, 'INVE-APP', 0, 1, 'C');

$pdf->SetDrawColor(80, 80, 80);
$pdf->SetLineWidth(0.2);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(2);

$paymentMethods = [
    'cash' => 'Especes',
    'mobile' => 'Mobile Money',
    'credit' => 'Credit'
];

$metaRows = [
    ['Ticket', $sale['ticket_number'] ?? '-'],
    ['Vendeur', cutText($sale['agent_name'] ?? '-', 28)],
    ['Client', cutText($sale['customer_name'] ?? 'Client', 28)],
    ['Paiement', $paymentMethods[$sale['payment_method']] ?? (string)$sale['payment_method']],
    ['Date', !empty($sale['created_at']) ? date('d/m/Y H:i', strtotime($sale['created_at'])) : '-']
];

foreach ($metaRows as $meta) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(30, 4, $meta[0] . ':', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(44, 4, cutText((string)$meta[1], 30), 0, 1, 'R');
}

$pdf->SetLineWidth(0.2);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(1);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(235, 235, 235);
$pdf->Cell(41, 4.5, 'ARTICLE', 0, 0, 'L', true);
$pdf->Cell(9, 4.5, 'QTE', 0, 0, 'C', true);
$pdf->Cell(24, 4.5, 'MONTANT', 0, 1, 'R', true);

$totalsByCurrency = [];
$pdf->SetFont('helvetica', '', 8);

foreach ($kits as $kit) {
    $kitDiscount = (float)($kit['discount'] ?? 0);
    $hasDiscount = $kitDiscount > 0;
    $kitCurrency = $kit['sell_currency'] ?? 'CDF';
    $isMultiCurrency = strpos((string)$kitCurrency, '/') !== false;

    if ($hasDiscount && $isMultiCurrency) {
        $kitTotal = (float)$kit['unit_sell_price'];
        $totalsByCurrency['CDF'] = ($totalsByCurrency['CDF'] ?? 0) + $kitTotal;

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(41, 4, 'KIT PRODUITS', 0, 0, 'L');
        $pdf->Cell(9, 4, $kit['qty'], 0, 0, 'C');
        $pdf->Cell(24, 4, cutText(formatAmount($kitTotal, 'CDF'), 20), 0, 1, 'R');

        $pdf->SetFont('helvetica', '', 7);
        if (isset($kitComponents[$kit['id']])) {
            foreach ($kitComponents[$kit['id']] as $comp) {
                $compTotal = (float)$comp['qty'] * (float)$comp['unit_sell_price'];
                $compCurrency = $comp['sell_currency'] ?? 'CDF';
                $pdf->Cell(41, 3.5, '  > ' . cutText($comp['name'], 24), 0, 0, 'L');
                $pdf->Cell(9, 3.5, $comp['qty'], 0, 0, 'C');
                $pdf->Cell(24, 3.5, cutText(formatAmount($compTotal, $compCurrency), 20), 0, 1, 'R');
            }
        }

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(41, 3.5, 'Remise appliquee', 0, 0, 'L');
        $pdf->Cell(9, 3.5, '', 0, 0, 'C');
        $pdf->Cell(24, 3.5, '-' . cutText(formatAmount($kitDiscount, 'CDF'), 18), 0, 1, 'R');
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

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(41, 4, 'KIT PRODUITS', 0, 0, 'L');
        $pdf->Cell(9, 4, $kit['qty'], 0, 0, 'C');
        $pdf->Cell(24, 4, cutText(buildTotalsString($kitTotals), 20), 0, 1, 'R');

        $pdf->SetFont('helvetica', '', 7);
        if (isset($kitComponents[$kit['id']])) {
            foreach ($kitComponents[$kit['id']] as $comp) {
                $compTotal = (float)$comp['qty'] * (float)$comp['unit_sell_price'];
                $compCurrency = $comp['sell_currency'] ?? 'CDF';
                $pdf->Cell(41, 3.5, '  > ' . cutText($comp['name'], 24), 0, 0, 'L');
                $pdf->Cell(9, 3.5, $comp['qty'], 0, 0, 'C');
                $pdf->Cell(24, 3.5, cutText(formatAmount($compTotal, $compCurrency), 20), 0, 1, 'R');
            }
        }

        if ($hasDiscount && !$isMultiCurrency) {
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(41, 3.5, 'Remise appliquee', 0, 0, 'L');
            $pdf->Cell(9, 3.5, '', 0, 0, 'C');
            $pdf->Cell(24, 3.5, '-' . cutText(formatAmount($kitDiscount, $kitCurrency), 18), 0, 1, 'R');
            $totalsByCurrency[$kitCurrency] = ($totalsByCurrency[$kitCurrency] ?? 0) - $kitDiscount;
        }
    }

    $pdf->SetFont('helvetica', '', 8);
}

foreach ($simpleProducts as $product) {
    $prodTotal = (float)$product['qty'] * (float)$product['unit_sell_price'];
    $prodCurrency = $product['sell_currency'] ?? 'CDF';
    $totalsByCurrency[$prodCurrency] = ($totalsByCurrency[$prodCurrency] ?? 0) + $prodTotal;

    $pdf->Cell(41, 4, cutText($product['name'], 24), 0, 0, 'L');
    $pdf->Cell(9, 4, $product['qty'], 0, 0, 'C');
    $pdf->Cell(24, 4, cutText(formatAmount($prodTotal, $prodCurrency), 20), 0, 1, 'R');
}

$saleDiscount = (float)($sale['discount'] ?? 0);
if ($saleDiscount > 0 && (int)($sale['is_kit'] ?? 0) === 0) {
    $discountCurrency = $sale['sell_currency'] ?? 'CDF';
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(41, 4, 'REMISE', 0, 0, 'L');
    $pdf->Cell(9, 4, '', 0, 0, 'C');
    $pdf->Cell(24, 4, '-' . cutText(formatAmount($saleDiscount, $discountCurrency), 20), 0, 1, 'R');

    if (strpos((string)$discountCurrency, '/') === false) {
        $totalsByCurrency[$discountCurrency] = ($totalsByCurrency[$discountCurrency] ?? 0) - $saleDiscount;
    }
}

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.4);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(1);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(41, 5, 'TOTAL', 0, 0, 'L');
$pdf->Cell(9, 5, '', 0, 0, 'C');
$pdf->Cell(24, 5, '', 0, 1, 'R');

$pdf->SetFont('helvetica', 'B', 9);
foreach ($totalsByCurrency as $cur => $amt) {
    $pdf->Cell(41, 4.5, '', 0, 0, 'L');
    $pdf->Cell(9, 4.5, '', 0, 0, 'C');
    $pdf->Cell(24, 4.5, cutText(formatAmount($amt, $cur), 20), 0, 1, 'R');
}

$pdf->SetLineWidth(0.15);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 3.5, 'Merci pour votre achat', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, 'Conservez ce recu pour reference', 0, 1, 'C');

$pdf->Output('ticket.pdf', 'I');
?>

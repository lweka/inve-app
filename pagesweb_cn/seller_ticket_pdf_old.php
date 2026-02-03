<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TCPDF as TCPDF_Lib;

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    exit('Accès refusé');
}

// Vérifier que le vendeur est toujours actif
$stmt_check = $pdo->prepare("SELECT status FROM agents WHERE id=? LIMIT 1");
$stmt_check->execute([$_SESSION['user_id']]);
$agent_status = $stmt_check->fetchColumn();

if($agent_status !== 'active'){
    header('Location: account_disabled.php');
    exit;
}

$agent_id = (int)$_SESSION['user_id'];
$sale_id  = (int)($_GET['sale_id'] ?? 0);
if ($sale_id <= 0) exit('Vente invalide');

/* ===== Vente parent ===== */
$stmt = $pdo->prepare("
SELECT pm.*, a.fullname agent_name, h.name house_name
FROM product_movements pm
JOIN agents a ON a.id = pm.agent_id
JOIN houses h ON h.id = pm.house_id
WHERE pm.id = ? AND pm.agent_id = ?
");
$stmt->execute([$sale_id, $agent_id]);
$sale = $stmt->fetch();
if (!$sale) exit('Vente introuvable');

// Helpers
function formatAmount($amount, $currency) {
    $decimals = ($currency === 'USD') ? 2 : 0;
    return number_format((float)$amount, $decimals) . ' ' . $currency;
}

function buildTotalsString($totalsByCurrency) {
    $parts = [];
    foreach ($totalsByCurrency as $cur => $amt) {
        $parts[] = formatAmount($amt, $cur);
    }
    return implode(' + ', $parts);
}

/* ===== Détails ===== */
// Vérifier si c'est une vente KIT ou simple
$stmt = $pdo->prepare("SELECT is_kit FROM product_movements WHERE id = ? LIMIT 1");
$stmt->execute([$sale_id]);
$saleType = $stmt->fetch();
$isKit = $saleType['is_kit'] == 1;

if($isKit) {
    // Si c'est un KIT: récupérer les composants avec devises
    $stmt = $pdo->prepare("
    SELECT COALESCE(p.name, 'Produit inconnu') as name, pm.qty, pm.unit_sell_price, pm.sell_currency
    FROM product_movements pm
    LEFT JOIN products p ON p.id = pm.product_id
    WHERE pm.kit_id = ?
    ORDER BY pm.id ASC
    ");
    $stmt->execute([$sale_id]);
    $kitComponents = $stmt->fetchAll();
    $items = [];
} else {
    // Si ce sont des ventes simples: afficher TOUS les produits
    $stmt = $pdo->prepare("
    SELECT p.name, pm.qty, pm.unit_sell_price, pm.sell_currency
    FROM product_movements pm
    LEFT JOIN products p ON p.id = pm.product_id
    WHERE pm.agent_id = ? AND pm.house_id = ? AND (pm.type = 'out' OR pm.type = 'sale') AND pm.is_kit = 0
    AND DATE(pm.created_at) = DATE(?)
    AND pm.created_at <= (SELECT created_at FROM product_movements WHERE id = ?)
    ORDER BY pm.created_at DESC
    LIMIT 100
    ");
    $stmt->execute([$agent_id, $sale['house_id'], $sale['created_at'], $sale_id]);
    $items = $stmt->fetchAll();
}

/* ===== PDF 80mm ===== */
$pdf = new TCPDF_Lib('P', 'mm', [80, 200]);
$pdf->SetMargins(5,5,5);
$pdf->AddPage();
$pdf->SetFont('helvetica','',9);

$pdf->Cell(0,5,'INVE-APP',0,1,'C');
$pdf->Cell(0,5,'CartelPlus Congo',0,1,'C');
$pdf->Ln(2);

$pdf->Cell(0,4,'Ticket: '.$sale['ticket_number'],0,1);
$pdf->Cell(0,4,'Maison: '.$sale['house_name'],0,1);
$pdf->Cell(0,4,'Vendeur: '.$sale['agent_name'],0,1);
$pdf->Cell(0,4,'Client: '.$sale['customer_name'],0,1);
$pdf->Cell(0,4,'Paiement: '.$sale['payment_method'],0,1);
$pdf->Ln(2);

$pdf->Cell(40,4,'Produit');
$pdf->Cell(10,4,'Qté',0,0,'C');
$pdf->Cell(20,4,'Total',0,1,'R');
$pdf->Ln(1);

$totalsByCurrency = [];

if ($isKit) {
    // Vérifier si le kit a une remise et est multi-devises
    $hasDiscount = (float)$sale['discount'] > 0;
    $kitCurrency = $sale['sell_currency'] ?? 'CDF';
    $isMultiCurrency = strpos($kitCurrency, '/') !== false;
    
    if ($hasDiscount && $isMultiCurrency) {
        // Kit avec réduction : tout est en CDF
        $kitTotal = (float)$sale['unit_sell_price'];
        $totalsByCurrency['CDF'] = ($totalsByCurrency['CDF'] ?? 0) + $kitTotal;
        
        $pdf->SetFont('helvetica','B',9);
        $pdf->Cell(40,4,'KIT PRODUITS');
        $pdf->Cell(10,4,1,0,0,'C');
        $pdf->Cell(20,4,formatAmount($kitTotal, 'CDF'),0,1,'R');

        // Composants du kit
        $pdf->SetFont('helvetica','',8);
        foreach ($kitComponents as $comp) {
            $cur = $comp['sell_currency'] ?? 'CDF';
            $line = $comp['qty'] * $comp['unit_sell_price'];
            $pdf->Cell(40,4,'  > '.($comp['name'] ?? 'Produit'),0,0);
            $pdf->Cell(10,4,$comp['qty'],0,0,'C');
            $pdf->Cell(20,4,formatAmount($line, $cur),0,1,'R');
        }
        
        // Afficher la remise
        $pdf->SetFont('helvetica','B',8);
        $pdf->Cell(40,4,'Remise appliquée',0,0);
        $pdf->Cell(10,4,'',0,0,'C');
        $pdf->Cell(20,4,'-' . formatAmount($sale['discount'], 'CDF'),0,1,'R');
        
    } else {
        // Kit normal sans remise ou mono-devise
        $kitTotals = [];
        foreach ($kitComponents as $comp) {
            $cur = $comp['sell_currency'] ?? 'CDF';
            $line = $comp['qty'] * $comp['unit_sell_price'];
            $kitTotals[$cur] = ($kitTotals[$cur] ?? 0) + $line;
            $totalsByCurrency[$cur] = ($totalsByCurrency[$cur] ?? 0) + $line;
        }

        $pdf->SetFont('helvetica','B',9);
        $pdf->Cell(40,4,'KIT PRODUITS');
        $pdf->Cell(10,4,1,0,0,'C');
        $pdf->Cell(20,4,buildTotalsString($kitTotals),0,1,'R');

        // Composants du kit
        $pdf->SetFont('helvetica','',8);
        foreach ($kitComponents as $comp) {
            $cur = $comp['sell_currency'] ?? 'CDF';
            $line = $comp['qty'] * $comp['unit_sell_price'];
            $pdf->Cell(40,4,'  > '.($comp['name'] ?? 'Produit'),0,0);
            $pdf->Cell(10,4,$comp['qty'],0,0,'C');
            $pdf->Cell(20,4,formatAmount($line, $cur),0,1,'R');
        }
        
        // Si kit mono-devise avec remise
        if ($hasDiscount && !$isMultiCurrency) {
            $pdf->SetFont('helvetica','B',8);
            $pdf->Cell(40,4,'Remise appliquée',0,0);
            $pdf->Cell(10,4,'',0,0,'C');
            $pdf->Cell(20,4,'-' . formatAmount($sale['discount'], $kitCurrency),0,1,'R');
            
            // Soustraire la remise du total
            $totalsByCurrency[$kitCurrency] = ($totalsByCurrency[$kitCurrency] ?? 0) - (float)$sale['discount'];
        }
    }
} else {
    foreach ($items as $it) {
        $cur = $it['sell_currency'] ?? 'CDF';
        $line = $it['qty'] * $it['unit_sell_price'];
        $totalsByCurrency[$cur] = ($totalsByCurrency[$cur] ?? 0) + $line;

        $pdf->Cell(40,4,$it['name'] ?? 'Produit');
        $pdf->Cell(10,4,$it['qty'],0,0,'C');
        $pdf->Cell(20,4,formatAmount($line, $cur),0,1,'R');
    }
    
    // Afficher la remise pour produits simples
    if ((float)$sale['discount'] > 0) {
        $discountCurrency = $sale['sell_currency'] ?? 'CDF';
        $pdf->SetFont('helvetica','B',8);
        $pdf->Cell(40,4,'Remise',0,0);
        $pdf->Cell(10,4,'',0,0,'C');
        $pdf->Cell(20,4,'-' . formatAmount($sale['discount'], $discountCurrency),0,1,'R');
        
        $totalsByCurrency[$discountCurrency] = ($totalsByCurrency[$discountCurrency] ?? 0) - (float)$sale['discount'];
    }
}

$pdf->Ln(2);
$pdf->Cell(50,5,'TOTAL');
$pdf->Cell(20,5,'',0,1,'R');
foreach ($totalsByCurrency as $cur => $amt) {
    $pdf->Cell(50,4,'');
    $pdf->Cell(20,4,formatAmount($amt, $cur),0,1,'R');
}

$pdf->Output('ticket.pdf','I');
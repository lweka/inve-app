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

/* ===== Détails ===== */
// Vérifier si c'est une vente KIT ou simple
$stmt = $pdo->prepare("SELECT is_kit FROM product_movements WHERE id = ? LIMIT 1");
$stmt->execute([$sale_id]);
$saleType = $stmt->fetch();
$isKit = $saleType['is_kit'] == 1;

if($isKit) {
    // Si c'est un KIT: afficher SEULEMENT la ligne du KIT (pas les composants)
    $stmt = $pdo->prepare("
    SELECT 'KIT' as name, pm.qty, pm.unit_sell_price
    FROM product_movements pm
    WHERE pm.id = ?
    ");
    $stmt->execute([$sale_id]);
} else {
    // Si ce sont des ventes simples: afficher TOUS les produits
    $stmt = $pdo->prepare("
    SELECT p.name, pm.qty, pm.unit_sell_price
    FROM product_movements pm
    LEFT JOIN products p ON p.id = pm.product_id
    WHERE pm.agent_id = ? AND pm.house_id = ? AND pm.type = 'sale' AND pm.is_kit = 0
    AND DATE(pm.created_at) = DATE(?)
    AND pm.created_at <= (SELECT created_at FROM product_movements WHERE id = ?)
    ORDER BY pm.created_at DESC
    LIMIT 100
    ");
    $stmt->execute([$agent_id, $sale['house_id'], $sale['created_at'], $sale_id]);
}

$items = $stmt->fetchAll();

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

$total = 0;
foreach ($items as $it) {
    $line = $it['qty'] * $it['unit_sell_price'];
    $total += $line;

    $pdf->Cell(40,4,$it['name'] ?? 'KIT');
    $pdf->Cell(10,4,$it['qty'],0,0,'C');
    $pdf->Cell(20,4,number_format($line,0),0,1,'R');
}

$pdf->Ln(2);
$pdf->Cell(50,5,'TOTAL');
$pdf->Cell(20,5,number_format($total,0),0,1,'R');

$pdf->Output('ticket.pdf','I');
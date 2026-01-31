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

/* ===== Récupérer la vente et son receipt_id ===== */
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

$receipt_id = $sale['receipt_id'];

/* ===== Récupérer TOUS les produits ===== */
$stmt = $pdo->prepare("
SELECT 
    pm.id,
    pm.is_kit,
    pm.kit_id,
    pm.qty,
    pm.unit_sell_price,
    CASE 
        WHEN pm.is_kit = 1 THEN 'KIT PRODUITS'
        ELSE COALESCE(p.name, 'Produit inconnu')
    END as name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
WHERE pm.receipt_id = ?
ORDER BY pm.is_kit DESC, pm.id ASC
");
$stmt->execute([$receipt_id]);
$allItems = $stmt->fetchAll();

// Séparer les kits des composants
$kits = [];
$simpleProducts = [];
$kitComponents = [];

foreach ($allItems as $item) {
    if ($item['is_kit'] == 1) {
        $kits[] = $item;
    } elseif (!empty($item['kit_id'])) {
        $kitComponents[$item['kit_id']][] = $item;
    } else {
        $simpleProducts[] = $item;
    }
}

/* ===== PDF 80mm PROFESSIONNEL ===== */
$pdf = new TCPDF_Lib('P', 'mm', [80, 200]);
$pdf->SetMargins(3, 3, 3);
$pdf->AddPage();
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

/* ===== HEADER PROFESSIONNEL ===== */
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'INVE-APP', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 4, 'CartelPlus Congo', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 3, 'Système de Gestion Commercial', 0, 1, 'C');

/* ===== SÉPARATEUR ===== */
$pdf->SetDrawColor(100, 100, 100);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(2);

/* ===== INFOS TICKET ===== */
$pdf->SetFont('helvetica', '', 7);
$pdf->SetFillColor(240, 240, 240);

$pdf->Cell(35, 3, 'Numéro Ticket:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(35, 3, $sale['ticket_number'], 0, 1, 'R');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(35, 3, 'Maison:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(35, 3, substr($sale['house_name'], 0, 20), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(35, 3, 'Vendeur:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(35, 3, substr($sale['agent_name'], 0, 20), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(35, 3, 'Client:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(35, 3, substr($sale['customer_name'] ?? 'Client', 0, 20), 0, 1, 'R');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(35, 3, 'Paiement:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$paymentMethods = ['cash' => 'Espèces', 'mobile' => 'Mobile Money', 'credit' => 'Crédit'];
$pdf->Cell(35, 3, $paymentMethods[$sale['payment_method']] ?? $sale['payment_method'], 0, 1, 'R');

$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(35, 3, 'Date/Heure:', 0, 0);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(35, 3, date('d/m/Y H:i', strtotime($sale['created_at'])), 0, 1, 'R');

/* ===== SÉPARATEUR ===== */
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(1);

/* ===== EN-TÊTES TABLEAU ===== */
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(35, 3.5, 'ARTICLE', 0, 0, 'L', true);
$pdf->Cell(10, 3.5, 'QTE', 0, 0, 'C', true);
$pdf->Cell(15, 3.5, 'MONTANT', 0, 1, 'R', true);

$pdf->SetFont('helvetica', '', 7);
$total = 0;

/* ===== AFFICHER LES KITS AVEC COMPOSANTS ===== */
foreach ($kits as $kit) {
    // ⚠️ IMPORTANT: Pour un KIT, unit_sell_price INCLUT déjà la remise appliquée
    // Donc pour afficher le prix original: unit_sell_price + discount
    $priceOriginal = $kit['unit_sell_price'] + $sale['discount'];
    $priceFinal = $kit['unit_sell_price'];
    
    // Ajouter le prix FINAL au total (avec remise appliquée)
    $total += $priceFinal;
    
    /* Afficher le KIT parent avec PRIX ORIGINAL (avant remise) */
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell(35, 3, 'KIT PRODUITS', 0, 0, 'L');
    $pdf->Cell(10, 3, $kit['qty'], 0, 0, 'C');
    $pdf->Cell(15, 3, number_format($priceOriginal, 0), 0, 1, 'R');
    
    /* Afficher les composants du KIT en indentation */
    $pdf->SetFont('helvetica', '', 6);
    if (isset($kitComponents[$kit['id']])) {
        foreach ($kitComponents[$kit['id']] as $comp) {
            $compTotal = $comp['qty'] * $comp['unit_sell_price'];
            $pdf->Cell(3, 2.5, '', 0, 0); // indentation
            $pdf->Cell(32, 2.5, '  > ' . substr($comp['name'], 0, 21), 0, 0, 'L');
            $pdf->Cell(10, 2.5, $comp['qty'], 0, 0, 'C');
            $pdf->Cell(15, 2.5, number_format($compTotal, 0), 0, 1, 'R');
        }
    }
    
    $pdf->SetFont('helvetica', '', 7);
}

/* ===== AFFICHER LES PRODUITS SIMPLES ===== */
foreach ($simpleProducts as $product) {
    $prodTotal = $product['qty'] * $product['unit_sell_price'];
    $total += $prodTotal;
    
    $pdf->Cell(35, 3, substr($product['name'], 0, 22), 0, 0, 'L');
    $pdf->Cell(10, 3, $product['qty'], 0, 0, 'C');
    $pdf->Cell(15, 3, number_format($prodTotal, 0), 0, 1, 'R');
}

/* ===== AFFICHER LA REMISE ===== */
// ⚠️ IMPORTANT: Ne la soustraire du total que si PAS de KIT
// Car pour les KITs, elle est déjà incluse dans unit_sell_price
if ($sale['discount'] > 0) {
    $pdf->SetFont('helvetica', 'B', 7);
    
    $pdf->Cell(35, 3, 'REMISE', 0, 0, 'L');
    $pdf->Cell(10, 3, '', 0, 0, 'C');
    $pdf->Cell(15, 3, '-' . number_format($sale['discount'], 0), 0, 1, 'R');
    
    // Soustraire la remise SEULEMENT s'il n'y a pas de KIT (sinon elle est déjà appliquée)
    if (empty($kits)) {
        $total -= $sale['discount'];
    }
}

/* ===== SÉPARATEUR FINAL ===== */
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.5);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(1);

/* ===== TOTAL ===== */
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(35, 5, 'TOTAL:', 0, 0, 'L');
$pdf->Cell(10, 5, '', 0, 0, 'C');
$pdf->Cell(15, 5, number_format($total, 0), 0, 1, 'R');

/* ===== SÉPARATEUR ===== */
$pdf->SetLineWidth(0.1);
$pdf->Line(3, $pdf->GetY(), 77, $pdf->GetY());
$pdf->Ln(2);

/* ===== FOOTER PROFESSIONNEL ===== */
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(0, 3, 'Merci de votre visite !', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(0, 2.5, '', 0, 1, 'C');
$pdf->Cell(0, 2.5, 'INVE-APP By Cartelplus Congo', 0, 1, 'C');
$pdf->Cell(0, 2.5, 'La solution numérique adaptée à vos besoins', 0, 1, 'C');

$pdf->Ln(1);
$pdf->SetFont('helvetica', 'I', 5);
$pdf->Cell(0, 2, '* Conservez ce recu *', 0, 1, 'C');

$pdf->Output('ticket.pdf', 'I');
?>

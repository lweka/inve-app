.? <?php
/**
 * ===================================
 * RAPPORT JOURNALIER COMPLET
 * ===================================
 * Pour l'administrateur
 * Affiche : ventes du jour, filtrage dates, export PDF
 */

require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Charger PHPMailer pour l'email (optionnel)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ===============================
   FONCTION : GENERER PDF
   =============================== */
function generateReportPDF($pdo, $client_code, $filter_date_from, $filter_date_to, $filter_house, $sales, $total_sales, $total_discount, $qty_total) {
    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configuration PDF
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(14, 16, 14);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('helvetica', '', 9);
        
        // Titre de la page
        $pdf->AddPage();
        
        // Récupérer le nom de la maison
        $house_name = 'RAPPORT JOURNALIER';
        if ($filter_house) {
            $stmt_house = $pdo->prepare("SELECT name FROM houses WHERE id = ? AND client_code = ?");
            $stmt_house->execute([$filter_house, $client_code]);
            $house = $stmt_house->fetch(PDO::FETCH_ASSOC);
            if ($house) {
                $house_name = 'RAPPORT JOURNALIER - ' . strtoupper($house['name']);
            }
        } else {
            $house_name = 'RAPPORT JOURNALIER - TOUTES LES MAISONS';
        }
        
        // === HEADER PRINCIPAL ===
        $pdf->SetFillColor(0, 112, 224);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $house_name, 0, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 4, 'Cartelplus Congo - Systeme de Gestion des Ventes', 0, 1, 'C');
        
        $pdf->SetTextColor(200, 220, 240);
        $date_str = 'Periode: ' . date('d/m/Y', strtotime($filter_date_from)) . ' au ' . date('d/m/Y', strtotime($filter_date_to)) . '   |   Genere le: ' . date('d/m/Y H:i');
        $pdf->Cell(0, 4, $date_str, 0, 1, 'C');
        $pdf->Ln(2);
        
        // === SECTION STATISTIQUES ===
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(0, 112, 224);
        $pdf->Cell(0, 5, 'STATISTIQUES GLOBALES', 0, 1);
        $pdf->Ln(1);
        
        // Boite stats avec bordures
        $pdf->SetDrawColor(0, 112, 224);
        $pdf->SetLineWidth(0.5);
        $pdf->SetFillColor(230, 242, 255);
        $pdf->SetTextColor(0, 48, 135);
        $pdf->SetFont('helvetica', 'B', 8);
        
        $stat_width = 46;
        $stats = array(
            array('label' => 'Total Ventes', 'value' => number_format($total_sales, 0) . ' FC'),
            array('label' => 'Remises', 'value' => number_format($total_discount, 0) . ' FC'),
            array('label' => 'Quantite', 'value' => $qty_total . ' u'),
            array('label' => 'Transactions', 'value' => count($sales))
        );
        
        foreach ($stats as $stat) {
            $pdf->SetFillColor(245, 251, 255);
            $pdf->Cell($stat_width, 6, $stat['label'], 1, 0, 'L', true);
            $pdf->SetFillColor(220, 237, 255);
            $pdf->Cell($stat_width, 6, $stat['value'], 1, 1, 'R', true);
        }
        
        $pdf->Ln(2);
        
        // === TABLEAU DES VENTES ===
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(0, 112, 224);
        $pdf->Cell(0, 5, 'DETAIL DES VENTES', 0, 1);
        $pdf->Ln(1);
        
        // Headers du tableau
        $pdf->SetDrawColor(0, 112, 224);
        $pdf->SetLineWidth(0.5);
        $pdf->SetFillColor(0, 112, 224);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7.5);
        
        $col_widths = array(14, 28, 10, 14, 16, 8, 27);
        $headers = array('Date', 'Produit', 'Qte', 'PU', 'Total', 'Rem', 'Vendeur');
        
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($col_widths[$i], 5.5, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Donnees du tableau
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(0, 0, 0);
        
        $alt = 0;
        $line_count = 0;
        
        foreach ($sales as $s) {
            if ($s['is_kit']) continue;
            
            // Verifier l'espace disponible sur la page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Re-afficher les headers
                $pdf->SetDrawColor(0, 112, 224);
                $pdf->SetFillColor(0, 112, 224);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 7.5);
                for ($i = 0; $i < count($headers); $i++) {
                    $pdf->Cell($col_widths[$i], 5.5, $headers[$i], 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 7.5);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $row_amount = ($s['qty'] * $s['unit_sell_price']) - (float)$s['discount'];
            
            // Alternance de couleurs
            if ($alt++ % 2 == 0) {
                $pdf->SetFillColor(240, 248, 255);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            $pdf->SetDrawColor(200, 220, 240);
            $pdf->SetLineWidth(0.3);
            
            $pdf->Cell($col_widths[0], 4.5, date('d/m', strtotime($s['created_at'])), 1, 0, 'C', true);
            $product_name = substr(htmlspecialchars($s['product_name'] ?? '-', ENT_QUOTES, 'UTF-8'), 0, 18);
            $pdf->Cell($col_widths[1], 4.5, $product_name, 1, 0, 'L', true);
            $pdf->Cell($col_widths[2], 4.5, $s['qty'], 1, 0, 'C', true);
            $pdf->Cell($col_widths[3], 4.5, number_format($s['unit_sell_price'], 0), 1, 0, 'R', true);
            $pdf->Cell($col_widths[4], 4.5, number_format($row_amount, 0), 1, 0, 'R', true);
            $pdf->Cell($col_widths[5], 4.5, number_format($s['discount'] ?? 0, 0), 1, 0, 'C', true);
            $agent_name = substr(htmlspecialchars($s['agent_fullname'] ?? '-', ENT_QUOTES, 'UTF-8'), 0, 15);
            $pdf->Cell($col_widths[6], 4.5, $agent_name, 1, 1, 'L', true);
            
            $line_count++;
        }
        
        // Footer
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY());
        
        $pdf->Ln(1);
        $pdf->Cell(0, 3, 'Cartelplus Congo © 2026 | Rapport genere automatiquement', 0, 1, 'C');
        $pdf->Cell(0, 2, 'Nombre de lignes: ' . $line_count, 0, 1, 'C');
        
        return $pdf;
    } catch (Exception $e) {
        die('Erreur PDF: ' . $e->getMessage());
    }
}
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

/* ===============================
   RÉCUPÉRATION DES PARAMÈTRES FILTRE
   =============================== */
$filter_date_from = isset($_REQUEST['date_from']) ? $_REQUEST['date_from'] : date('Y-m-d');
$filter_date_to = isset($_REQUEST['date_to']) ? $_REQUEST['date_to'] : date('Y-m-d');
$filter_house = isset($_REQUEST['house']) ? (int)$_REQUEST['house'] : null;
$filter_agent = isset($_REQUEST['agent']) ? (int)$_REQUEST['agent'] : null;

/* ===============================
   REQUÊTE : TOUTES LES VENTES
   =============================== */
$sql = "
SELECT
    pm.id,
    pm.created_at,
    pm.qty,
    pm.unit_sell_price,
    pm.discount,
    pm.payment_method,
    pm.customer_name,
    pm.is_kit,
    pm.receipt_id,
    p.name AS product_name,
    a.fullname AS agent_fullname,
    h.name as house_name
FROM product_movements pm
LEFT JOIN products p ON p.id = pm.product_id
LEFT JOIN agents a ON a.id = pm.agent_id
LEFT JOIN houses h ON h.id = pm.house_id
WHERE (pm.type = 'out' OR pm.type = 'sale')
    AND pm.client_code = ?
    AND DATE(pm.created_at) >= ?
    AND DATE(pm.created_at) <= ?
";

$params = [$client_code, $filter_date_from, $filter_date_to];

if ($filter_house) {
    $sql .= " AND pm.house_id = ? ";
    $params[] = $filter_house;
}
if ($filter_agent) {
    $sql .= " AND pm.agent_id = ? ";
    $params[] = $filter_agent;
}

$sql .= " ORDER BY pm.receipt_id DESC, pm.is_kit DESC, pm.created_at DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   STATISTIQUES GLOBALES
   =============================== */
$total_sales = 0;
$total_discount = 0;
$qty_total = 0;
$payment_methods = [];

foreach ($sales as $sale) {
    if (!$sale['is_kit']) {
        $total_sales += ($sale['qty'] * $sale['unit_sell_price']) - (float)$sale['discount'];
        $total_discount += (float)$sale['discount'];
    } else {
        $total_sales += $sale['unit_sell_price'];
    }
    
    $qty_total += $sale['qty'];
    
    $method = $sale['payment_method'] ?? 'inconnu';
    if (!isset($payment_methods[$method])) {
        $payment_methods[$method] = 0;
    }
    if (!$sale['is_kit']) {
        $payment_methods[$method] += ($sale['qty'] * $sale['unit_sell_price']) - (float)$sale['discount'];
    } else {
        $payment_methods[$method] += $sale['unit_sell_price'];
    }
}

/* ===============================
   LISTES POUR FILTRES
   =============================== */
$stmt_houses = $pdo->prepare("SELECT id, name FROM houses WHERE client_code = ? ORDER BY name");
$stmt_houses->execute([$client_code]);
$houses = $stmt_houses->fetchAll(PDO::FETCH_ASSOC);

$stmt_agents = $pdo->prepare("SELECT id, fullname FROM agents WHERE client_code = ? ORDER BY fullname");
$stmt_agents->execute([$client_code]);
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

/* ===============================
   RECUPERER EMAIL DE L'ADMIN
   =============================== */
$stmt_email = $pdo->prepare("SELECT email FROM active_clients WHERE client_code = ? AND status = 'active' LIMIT 1");
$stmt_email->execute([$client_code]);
$admin_data = $stmt_email->fetch(PDO::FETCH_ASSOC);
$admin_email = $admin_data['email'] ?? null;

/* ===============================
   EXPORT PDF
   =============================== */
if (isset($_POST['export_pdf'])) {
    $pdf = generateReportPDF($pdo, $client_code, $filter_date_from, $filter_date_to, $filter_house, $sales, $total_sales, $total_discount, $qty_total);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="rapport-' . date('Y-m-d-His') . '.pdf"');
    echo $pdf->Output('', 'S');
    exit;
}

/* ===============================
   ENVOYER PAR EMAIL
   =============================== */
if (isset($_POST['send_email'])) {
    if (!$admin_email) {
        $error_msg = "Email de l'administrateur non trouvé.";
    } else {
        // Générer le PDF
        $pdf = generateReportPDF($pdo, $client_code, $filter_date_from, $filter_date_to, $filter_house, $sales, $total_sales, $total_discount, $qty_total);
        $pdf_content = $pdf->Output('', 'S');
        $pdf_filename = 'rapport-' . date('Y-m-d-His') . '.pdf';
        
        // Sauvegarder temporairement
        $temp_file = sys_get_temp_dir() . '/' . $pdf_filename;
        file_put_contents($temp_file, $pdf_content);
        
        // Envoyer l'email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.titan.email';
            $mail->SMTPAuth = true;
            $mail->Username = 'cartelplus-congo@cartelplus.site';
            $mail->Password = 'Jo@Kin243';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('cartelplus-congo@cartelplus.site', 'Cartelplus Congo');
            $mail->addAddress($admin_email);
            $mail->addAttachment($temp_file, $pdf_filename);
            
            $mail->isHTML(true);
            $mail->Subject = 'Rapport de Ventes - ' . date('d/m/Y', strtotime($filter_date_from)) . ' au ' . date('d/m/Y', strtotime($filter_date_to));
            
            $mail->Body = "
            <html>
            <head><style>
            body { font-family: Segoe UI, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0070e0, #003087); color: white; padding: 20px; border-radius: 8px; text-align: center; }
            .content { padding: 20px; background: #f5f7fb; border-radius: 8px; margin-top: 20px; }
            .footer { text-align: center; font-size: 12px; color: #999; margin-top: 20px; }
            </style></head>
            <body>
            <div class='container'>
                <div class='header'>
                    <h2>Rapport de Ventes - Cartelplus Congo</h2>
                    <p>Période: " . date('d/m/Y', strtotime($filter_date_from)) . " au " . date('d/m/Y', strtotime($filter_date_to)) . "</p>
                </div>
                <div class='content'>
                    <p>Bonjour,</p>
                    <p>Veuillez trouver ci-joint le rapport de ventes du jour.</p>
                    <p><strong>Résumé:</strong></p>
                    <ul>
                        <li>Total Ventes: " . number_format($total_sales, 0) . " FC</li>
                        <li>Remises Accordées: " . number_format($total_discount, 0) . " FC</li>
                        <li>Quantité Vendue: " . $qty_total . "</li>
                        <li>Nombre de Transactions: " . count($sales) . "</li>
                    </ul>
                    <p>Cordialement,<br>Cartelplus Congo</p>
                </div>
                <div class='footer'>
                    <p>Ce rapport a été généré automatiquement. Ne pas répondre à cet email.</p>
                </div>
            </div>
            </body>
            </html>";
            
            $mail->send();
            $success_msg = "Rapport envoyé avec succès à " . $admin_email;
            
        } catch (Exception $e) {
            $error_msg = "Erreur lors de l'envoi: " . $mail->ErrorInfo;
        } finally {
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Journaliers | CartelPlus Congo</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
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
            --pp-orange: #f59e0b;
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
            padding: 32px 16px 60px;
        }

        .page-hero {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
            animation: fadeSlide 0.7s ease both;
        }

        .page-hero h2 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
        }

        .filter-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--pp-shadow);
            margin-bottom: 26px;
            animation: fadeUp 0.6s ease both;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-left: 4px solid var(--pp-blue);
            padding: 16px;
            border-radius: 12px;
            box-shadow: var(--pp-shadow);
            animation: fadeUp 0.6s ease both;
        }

        .stat-label {
            font-size: 12px;
            color: var(--pp-muted);
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--pp-blue-dark);
        }

        .table-container {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--pp-shadow);
            animation: fadeUp 0.7s ease both;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
            color: #fff;
            border: none;
            padding: 14px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 12px 14px;
            border-color: var(--pp-border);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(0,112,224,0.02);
        }

        .table-hover tbody tr:hover {
            background: rgba(0,112,224,0.06);
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
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

        .btn-pp-accent {
            background: linear-gradient(135deg, var(--pp-orange), #d97706);
            color: #fff;
            box-shadow: 0 10px 24px rgba(245, 158, 11, 0.25);
        }

        .btn-pp-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: #fff !important;
            box-shadow: 0 10px 24px rgba(16, 185, 129, 0.25) !important;
            display: inline-block !important;
            visibility: visible !important;
        }

        .btn-pp:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .payment-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .payment-item {
            background: var(--pp-card);
            border: 1px solid var(--pp-border);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--pp-shadow);
        }

        .payment-item-label {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--pp-muted);
            margin-bottom: 6px;
        }

        .payment-item-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--pp-blue-dark);
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

<div class="page-wrap">

    <div class="page-hero">
        <h2>📈 Rapports Journaliers</h2>
        <a href="dashboard.php" class="btn-pp btn-pp-secondary">← Retour Admin</a>
    </div>

<div class="page-wrap">

    <!-- FILTRES -->
    <div class="filter-card">
        <h5 style="margin-bottom: 15px;">🔍 Filtres & Export</h5>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Depuis</label>
                <input type="date" name="date_from" class="form-control form-control-sm" 
                    value="<?= htmlspecialchars($filter_date_from) ?>" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Jusqu'au</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="<?= htmlspecialchars($filter_date_to) ?>" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Maison</label>
                <select name="house" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <?php foreach($houses as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $filter_house == $h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Vendeur</label>
                <select name="agent" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <?php foreach($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $filter_agent == $a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['fullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-pp btn-pp-primary">Filtrer</button>
                <a href="reports.php" class="btn-pp btn-pp-secondary" style="text-decoration: none;">Réinitialiser</a>
                <button type="submit" name="export_pdf" value="1" class="btn-pp btn-pp-accent">📄 PDF</button>
                <button type="submit" name="send_email" value="1" class="btn-pp btn-pp-success">✉️ Email</button>
            </div>
        </form>
        
        <?php if (isset($success_msg)): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-top: 15px; border: 1px solid #c3e6cb;">
            ✅ <?= htmlspecialchars($success_msg) ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-top: 15px; border: 1px solid #f5c6cb;">
            ❌ <?= htmlspecialchars($error_msg) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATISTIQUES -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Ventes</div>
            <div class="stat-value"><?= number_format($total_sales, 0) ?> FC</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Remises Accordées</div>
            <div class="stat-value">-<?= number_format($total_discount, 0) ?> FC</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Quantité Vendue</div>
            <div class="stat-value"><?= number_format($qty_total, 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Nombre Transactions</div>
            <div class="stat-value"><?= count(array_unique(array_column($sales, 'receipt_id'))) ?></div>
        </div>
    </div>

    <!-- RÉPARTITION PAR MODE PAIEMENT -->
    <?php if (!empty($payment_methods)): ?>
    <div style="margin-bottom: 30px;">
        <h5 style="margin-bottom: 15px;">💳 Répartition par Mode de Paiement</h5>
        <div class="payment-breakdown">
            <?php foreach ($payment_methods as $method => $amount): ?>
            <div class="payment-item">
                <div class="payment-item-label"><?= ucfirst(htmlspecialchars($method)) ?></div>
                <div class="payment-item-value"><?= number_format($amount, 0) ?> FC</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABLEAU DÉTAIL VENTES -->
    <div>
        <h5 style="margin-bottom: 15px;">📋 Détail des Ventes</h5>
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Produit</th>
                        <th class="text-center">Qté</th>
                        <th class="text-end">PU</th>
                        <th class="text-end">Remise</th>
                        <th class="text-end">Total</th>
                        <th>Vendeur</th>
                        <th>Client</th>
                        <th>Paiement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sales)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Aucune vente pour cette période</td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    $current_receipt = null;
                    foreach($sales as $s): 
                        if ($s['is_kit']) continue;
                        
                        $row_amount = ($s['qty'] * $s['unit_sell_price']) - (float)$s['discount'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                        <td><?= htmlspecialchars($s['product_name']) ?></td>
                        <td class="text-center"><?= $s['qty'] ?></td>
                        <td class="text-end"><?= number_format($s['unit_sell_price'], 0) ?> FC</td>
                        <td class="text-end"><?= $s['discount'] > 0 ? '-' . number_format($s['discount'], 0) . ' FC' : '-' ?></td>
                        <td class="text-end fw-bold"><?= number_format($row_amount, 0) ?> FC</td>
                        <td><?= htmlspecialchars($s['agent_fullname'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['customer_name'] ?? 'N/A') ?></td>
                        <td><?= ucfirst(htmlspecialchars($s['payment_method'] ?? '-')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="../js/bootstrap.min.js"></script>

</body>
</html>

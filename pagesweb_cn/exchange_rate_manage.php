<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: ".PARSE_CONNECT."?role=admin");
    exit;
}

/* =========================
   MAISON
========================= */
$house_id = (int)($_GET['house_id'] ?? 0);
if ($house_id <= 0) {
    die('Maison invalide');
}

// vérifier que la maison appartient au client connecté
$stmt = $pdo->prepare("SELECT id FROM houses WHERE id = ? AND client_code = ?");
$stmt->execute([$house_id, $client_code]);
if (!$stmt->fetch()) {
  die('Maison non autorisée');
}

/* =========================
   RÉCUPÉRER LE TAUX DE CETTE MAISON
========================= */
$stmt = $pdo->prepare("
    SELECT usd_rate
    FROM exchange_rate
    WHERE house_id = ?
    LIMIT 1
");
$stmt->execute([$house_id]);
$current_rate = $stmt->fetchColumn();

/* =========================
   ENREGISTREMENT DU TAUX
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usd_rate = (float)($_POST['usd_rate'] ?? 0);

    if ($usd_rate <= 0) {
        header("Location: exchange_rate_manage.php?house_id={$house_id}&err=invalid");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO exchange_rate (house_id, usd_rate)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE usd_rate = VALUES(usd_rate)
    ");
    $stmt->execute([$house_id, $usd_rate]);

    header("Location: exchange_rate_manage.php?house_id={$house_id}&ok=1");
    exit;
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Taux USD – Cartelplus Congo</title>
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
}

body {
    background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                var(--pp-bg);
    color: var(--pp-text);
    min-height: 100vh;
    font-family: "Segoe UI", system-ui, sans-serif;
    padding: 32px 16px 60px;
}

.page-wrap {
    max-width: 520px;
    margin: 0 auto;
}

.page-hero {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    margin-bottom: 26px;
    text-align: center;
    animation: fadeSlide 0.7s ease both;
}

.page-hero h3 {
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.page-hero .subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.rate-card {
    background: var(--pp-card);
    border: 1px solid var(--pp-border);
    border-radius: 16px;
    padding: 28px;
    box-shadow: var(--pp-shadow);
    animation: fadeUp 0.6s ease both;
    margin-bottom: 20px;
}

.rate-display {
    background: rgba(0, 112, 224, 0.08);
    border: 2px solid var(--pp-border);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin-bottom: 24px;
}

.rate-display .current-label {
    font-size: 12px;
    text-transform: uppercase;
    color: var(--pp-muted);
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.rate-display .current-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--pp-blue-dark);
}

.rate-display .currency-suffix {
    font-size: 18px;
    color: var(--pp-muted);
}

.btn-pp {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    padding: 12px 20px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
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

.btn-pp:hover {
    transform: translateY(-1px);
    opacity: 0.95;
}

.form-control {
    border-radius: 12px;
    border: 1px solid var(--pp-border);
    padding: 14px 18px;
    font-size: 16px;
    text-align: center;
}

.form-control:focus {
    border-color: var(--pp-blue);
    box-shadow: 0 0 0 4px rgba(0,112,224,0.1);
}

.form-label {
    font-weight: 600;
    color: var(--pp-text);
    margin-bottom: 10px;
    display: block;
    text-align: center;
}

.alert {
    border-radius: 12px;
    padding: 14px 18px;
    animation: fadeSlide 0.5s ease both;
    border: none;
}

.alert-success {
    background: rgba(31, 143, 106, 0.15);
    color: var(--pp-success);
    border-left: 4px solid var(--pp-success);
}

.alert-danger {
    background: rgba(220, 38, 38, 0.15);
    color: #dc2626;
    border-left: 4px solid #dc2626;
}

.info-box {
    background: rgba(0, 168, 255, 0.08);
    border-left: 4px solid var(--pp-cyan);
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
    font-size: 13px;
    color: var(--pp-muted);
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
    <h3>💱 Taux de change USD</h3>
    <div class="subtitle">Maison #<?= (int)$house_id ?></div>
  </div>

  <?php if(isset($_GET['ok'])): ?>
    <div class="alert alert-success">
      <i class="fa-solid fa-check-circle"></i> Taux enregistré avec succès !
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['err'])): ?>
    <div class="alert alert-danger">
      <i class="fa-solid fa-exclamation-triangle"></i> Taux invalide, veuillez réessayer.
    </div>
  <?php endif; ?>

  <div class="rate-card">
    
    <?php if($current_rate): ?>
    <div class="rate-display">
      <div class="current-label">Taux actuel</div>
      <div class="current-value">
        <?= number_format($current_rate, 2) ?>
        <span class="currency-suffix">CDF</span>
      </div>
      <div style="font-size: 12px; color: var(--pp-muted); margin-top: 8px;">pour 1 USD</div>
    </div>
    <?php endif; ?>

    <form method="post">
      <label class="form-label">
        <i class="fa-solid fa-dollar-sign"></i> 1 USD = ? CDF
      </label>
      <input
        type="number"
        step="0.01"
        name="usd_rate"
        class="form-control"
        value="<?= htmlspecialchars($current_rate ?? '') ?>"
        placeholder="Ex : 2750.00"
        required
      >
      <button class="btn-pp btn-pp-primary mt-4 w-100">
        <i class="fa-solid fa-save"></i> Enregistrer le taux
      </button>
    </form>

    <div class="info-box">
      <i class="fa-solid fa-info-circle"></i> 
      Ce taux sera utilisé pour convertir automatiquement les prix entre USD et CDF lors de la création de produits.
    </div>
  </div>

  <a href="<?= PRODUCTS_MANAGE ?>?house_id=<?= (int)$house_id ?>"
     class="btn-pp btn-pp-secondary w-100">
     <i class="fa-solid fa-arrow-left"></i> Retour aux produits
  </a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

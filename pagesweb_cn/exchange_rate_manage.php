<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

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
<title>Taux USD</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">

<div class="container" style="max-width:420px">

  <h4 class="mb-3">Taux de change — Maison #<?= (int)$house_id ?></h4>

  <?php if(isset($_GET['ok'])): ?>
    <div class="alert alert-success">Taux enregistré avec succès</div>
  <?php endif; ?>

  <?php if(isset($_GET['err'])): ?>
    <div class="alert alert-danger">Taux invalide</div>
  <?php endif; ?>

  <form method="post">
    <label class="form-label">1 USD = ? CDF</label>
    <input
      type="number"
      step="0.01"
      name="usd_rate"
      class="form-control"
      value="<?= htmlspecialchars($current_rate ?? '') ?>"
      placeholder="Ex : 2300"
      required
    >
    <button class="btn btn-primary mt-3 w-100">Enregistrer</button>
  </form>

  <a href="<?= PRODUCTS_MANAGE ?>?house_id=<?= (int)$house_id ?>"
     class="btn btn-light mt-3 w-100">
     ← Retour aux produits
  </a>

</div>

</body>
</html>

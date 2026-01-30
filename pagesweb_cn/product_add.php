<?php
// pagesweb_cn/product_add.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: connect-parse.php?role=admin");
    exit;
}

function clean(string $v): string {
    return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
}

/* =========================
   RÉCUPÉRATION
========================= */
$house_id      = (int)($_POST['house_id'] ?? 0);
$name          = clean($_POST['name'] ?? '');
$currency      = $_POST['sell_currency'] ?? '';
$buy_price     = (float)($_POST['buy_price'] ?? 0);
$sell_price    = (float)($_POST['sell_price'] ?? 0);
$description   = clean($_POST['description'] ?? '');

/* =========================
   VALIDATION
========================= */
$errors = [];

if ($house_id <= 0) {
    $errors[] = "Maison invalide.";
}

if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = "Nom du produit invalide.";
}

if (!in_array($currency, ['CDF','USD'], true)) {
    $errors[] = "Devise invalide.";
}

if ($buy_price <= 0 || $sell_price <= 0) {
    $errors[] = "Prix invalides.";
}

if ($sell_price < $buy_price) {
    $errors[] = "Le prix de vente ne peut pas être inférieur au prix d'achat.";
}




if ($errors) {
    // ❗ avant toute redirection
    $_SESSION['flash_error'] = [
        "Veuillez configurer le taux USD pour cette maison."
    ];

    header("Location: products.php?house_id=".$house_id);
    exit;
}

// vérifier que la maison appartient au client connecté
$stmt = $pdo->prepare("SELECT id FROM houses WHERE id = ? AND client_code = ?");
$stmt->execute([$house_id, $client_code]);
if (!$stmt->fetch()) {
    $err = urlencode(json_encode(["Maison non autorisée."]));
    header("Location: products.php?house_id={$house_id}&err={$err}");
    exit;
}

/* =========================
   TAUX USD DE LA MAISON
========================= */
$stmt = $pdo->prepare("
    SELECT usd_rate 
    FROM exchange_rate 
    WHERE house_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$house_id]);
$usd_rate = (float)$stmt->fetchColumn();

if ($usd_rate <= 0) {
    $err = urlencode(json_encode(["Veuillez configurer le taux USD pour cette maison."]));
    header("Location: products.php?house_id={$house_id}&err={$err}");
    exit;
}

/* =========================
   CONVERSION SI USD
========================= */
$buy_price_cdf  = $currency === 'USD' ? $buy_price * $usd_rate : $buy_price;
$sell_price_cdf = $currency === 'USD' ? $sell_price * $usd_rate : $sell_price;

/* =========================
   INSERT PRODUIT
========================= */
$stmt = $pdo->prepare("
INSERT INTO products (
    client_code,
    house_id,
    name,
    buy_price,
    sell_price,
    buy_price_cdf,
    sell_price_cdf,
    sell_currency,
    usd_rate_at_creation,
    description,
    is_active
 ) VALUES (?,?,?,?,?,?,?,?,?,?,1)
");

$stmt->execute([
    $client_code,
    $house_id,
    $name,
    $buy_price,
    $sell_price,
    $buy_price_cdf,
    $sell_price_cdf,
    $currency,
    $usd_rate,
    $description
]);

$product_id = (int)$pdo->lastInsertId();

/* =========================
   INIT STOCK
========================= */
$stmt = $pdo->prepare("
    INSERT INTO house_stock (house_id, product_id, qty)
    VALUES (?, ?, 0)
");
$stmt->execute([$house_id, $product_id]);

/* =========================
   REDIRECTION OK
========================= */
header("Location: products.php?house_id={$house_id}&msg=created");
exit;
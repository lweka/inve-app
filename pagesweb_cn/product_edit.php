<?php
// pagesweb_cn/product_edit.php
require_once __DIR__ . '/connectDb.php';

/* ======================================================
   GET : infos produit (AJAX)
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get'])) {

    header('Content-Type: application/json; charset=utf-8');

    $product_id = (int)($_GET['product_id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode(['ok' => false, 'message' => 'ID produit invalide']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            buy_price,
            sell_price,
            sell_currency,
            description
        FROM products
        WHERE id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['ok' => false, 'message' => 'Produit introuvable']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'product' => $product
    ]);
    exit;
}

/* ======================================================
   POST : mise à jour produit
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: connect-parse.php?role=admin");
        exit;
    }

    $product_id   = (int)($_POST['product_id'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $buy_price    = (float)($_POST['buy_price'] ?? 0);
    $sell_price   = (float)($_POST['sell_price'] ?? 0);
    $description  = trim($_POST['description'] ?? '');

    if ($product_id <= 0 || $name === '' || $buy_price <= 0 || $sell_price <= 0) {
        exit;
    }

    // récupérer produit + maison + devise
    $stmt = $pdo->prepare("
        SELECT house_id, sell_currency
        FROM products
        WHERE id = ?
    ");
    $stmt->execute([$product_id]);
    $row = $stmt->fetch();

    if (!$row) exit;

    $house_id = (int)$row['house_id'];
    $currency = $row['sell_currency'];

    // taux de la maison
    $stmt = $pdo->prepare("
        SELECT usd_rate 
        FROM exchange_rate
        WHERE house_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$house_id]);
    $rate = (float)$stmt->fetchColumn();
    if ($rate <= 0) $rate = 1;

    // recalcul CDF
    $buy_price_cdf  = $currency === 'USD' ? $buy_price * $rate : $buy_price;
    $sell_price_cdf = $currency === 'USD' ? $sell_price * $rate : $sell_price;

    // update
    $stmt = $pdo->prepare("
        UPDATE products
        SET 
            name = ?,
            buy_price = ?,
            sell_price = ?,
            buy_price_cdf = ?,
            sell_price_cdf = ?,
            description = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $buy_price,
        $sell_price,
        $buy_price_cdf,
        $sell_price_cdf,
        $description,
        $product_id
    ]);

    exit;
}
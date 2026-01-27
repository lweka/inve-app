<?php
// pagesweb_cn/product_add.php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){
    header("Location: connect-parse.php?role=admin");
    exit;
}

function clean($v){ return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

$house_id = intval($_POST['house_id'] ?? 0);
$name = clean($_POST['name'] ?? '');
$price = trim($_POST['price'] ?? '0');
$description = clean($_POST['description'] ?? '');
$initial_stock = intval($_POST['initial_stock'] ?? 0);

$errors = [];
if($house_id <= 0) $errors[] = "Maison invalide.";
if($name === '' || strlen($name) < 2) $errors[] = "Nom produit invalide.";
if(!is_numeric($price) || floatval($price) < 0) $errors[] = "Prix invalide.";

if($errors){
    $err = urlencode(json_encode($errors));
    header("Location: products.php?house_id=".$house_id."&err=".$err);
    exit;
}

/* insert product */
$stmt = $pdo->prepare("INSERT INTO products (house_id, name, price, description) VALUES (?,?,?,?)");

$stmt->execute([$house_id, $name, floatval($price), $description]);
$product_id = $pdo->lastInsertId();

/* initial stock: if >0, insert or update house_stock and add movement */
if($initial_stock > 0){
    // upsert house_stock
    $stmt = $pdo->prepare("SELECT id FROM house_stock WHERE house_id=? AND product_id=?");
    $stmt->execute([$house_id, $product_id]);
    $row = $stmt->fetch();
    if($row){
        $pdo->prepare("UPDATE house_stock SET qty = qty + ? WHERE id = ?")->execute([$initial_stock, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO house_stock (house_id, product_id, qty) VALUES (?,?,?)")->execute([$house_id, $product_id, $initial_stock]);
    }
    // insert movement
    //$pdo->prepare("INSERT INTO product_movements (house_id, product_id, qty_change, type, note) VALUES (?,?,?,?,?)")
        //->execute([$house_id, $product_id, $initial_stock, 'in', 'Stock initial']);

        $pdo->prepare("INSERT INTO house_stock (house_id, product_id, qty) VALUES (?, ?, 0)")
            ->execute([$house_id, $product_id]);

}

header("Location: products.php?house_id=".$house_id."&msg=created");
exit;

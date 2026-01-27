<?php
require_once __DIR__.'/connectDb.php';
header('Content-Type: application/json');

if($_SESSION['user_role']!=='agent'){
    die(json_encode(['ok'=>false]));
}

$house_id = (int)$_POST['house_id'];
$items = json_decode($_POST['items'],true);

$pdo->beginTransaction();

foreach($items as $it){
    $pid = (int)$it['product_id'];
    $qty = (int)$it['qty'];

    $stmt = $pdo->prepare("
        SELECT id, qty, unit_buy_price, currency
        FROM product_movements
        WHERE product_id=? AND house_id=? AND type='in'
        ORDER BY created_at ASC
        FOR UPDATE
    ");
    $stmt->execute([$pid,$house_id]);

    foreach($stmt as $lot){
        if($qty<=0) break;

        $use = min($qty,$lot['qty']);
        $qty -= $use;

        $pdo->prepare("
            INSERT INTO product_movements
            (house_id, product_id, type, qty,
             unit_buy_price, currency, note)
            VALUES (?,?,?,?,?,?,?)
        ")->execute([
            $house_id,$pid,'sale',$use,
            $lot['unit_buy_price'],$lot['currency'],'Vente FIFO'
        ]);
    }
}

$pdo->commit();
echo json_encode(['ok'=>true]);

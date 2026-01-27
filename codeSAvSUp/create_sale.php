<?php
// pagesweb_cn/create_sale.php
require_once __DIR__ . '/connectDb.php';

if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

/* ============================================================
   SÉCURITÉ SESSION
============================================================ */
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    echo json_encode(['ok'=>false,'message'=>'Non autorisé']);
    exit;
}

if(!isset($_SESSION['user_id'])){
    echo json_encode(['ok'=>false,'message'=>'Session invalide']);
    exit;
}

$agent_id = intval($_SESSION['user_id']);

/* ============================================================
   VÉRIFIER STATUT VENDEUR (CORRECTION CRITIQUE)
============================================================ */
$check = $pdo->prepare("SELECT status FROM agents WHERE id = ? LIMIT 1");
$check->execute([$agent_id]);
$status = $check->fetchColumn();

if($status !== 'active'){
    echo json_encode(['ok'=>false, 'message'=>'Compte désactivé']);
    exit;
}

/* ============================================================
   RÉCUPÉRATION DES DONNÉES
============================================================ */
$house_id = intval($_POST['house_id'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);
$discount = floatval($_POST['discount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cash';
$customer_name = trim($_POST['customer_name'] ?? '');

if($house_id <= 0 || empty($items) || !is_array($items)){
    echo json_encode(['ok'=>false,'message'=>'Paramètres invalides']);
    exit;
}

/* ============================================================
   TRANSACTION
============================================================ */
try {
    $pdo->beginTransaction();

    // calcul du total
    $total = 0.0;

    foreach($items as $it){
        $pid = intval($it['product_id'] ?? 0);
        $qty = intval($it['qty'] ?? 0);

        if($pid <= 0 || $qty <= 0){
            throw new Exception('Article invalide');
        }

        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ? AND house_id = ?");
        $stmt->execute([$pid, $house_id]);
        $price = $stmt->fetchColumn();

        if($price === false){
            throw new Exception("Produit introuvable (ID $pid)");
        }

        $total += floatval($price) * $qty;
    }

    $grandTotal = max(0, $total - $discount);

    /* ========================================================
       CRÉATION VENTE
    ======================================================== */
    $ins = $pdo->prepare("
        INSERT INTO sales (house_id, agent_id, total_amount, discount, payment_method, customer_name)
        VALUES (?,?,?,?,?,?)
    ");
    $ins->execute([
        $house_id,
        $agent_id,
        $grandTotal,
        $discount,
        $payment_method,
        $customer_name
    ]);

    $sale_id = $pdo->lastInsertId();

    /* ========================================================
       ITEMS + STOCK + MOUVEMENTS
    ======================================================== */
    foreach($items as $it){
        $pid = intval($it['product_id']);
        $qty = intval($it['qty']);

        // prix
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $price = floatval($stmt->fetchColumn());

        $subtotal = $price * $qty;

        // sale_items
        $pdo->prepare("
            INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal)
            VALUES (?,?,?,?,?)
        ")->execute([
            $sale_id, $pid, $qty, $price, $subtotal
        ]);

        // stock maison
        $stmt = $pdo->prepare("
            SELECT qty FROM house_stock
            WHERE house_id = ? AND product_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$house_id, $pid]);
        $current = intval($stmt->fetchColumn());

        if($current < $qty){
            throw new Exception("Stock insuffisant (disponible : $current)");
        }

        $newQty = $current - $qty;

        $pdo->prepare("
            UPDATE house_stock SET qty = ?
            WHERE house_id = ? AND product_id = ?
        ")->execute([$newQty, $house_id, $pid]);

        // mouvement vente
        $pdo->prepare("
            INSERT INTO product_movements (house_id, product_id, type, qty, note)
            VALUES (?,?,?,?,?)
        ")->execute([
            $house_id,
            $pid,
            'sale',
            $qty,
            'Vente ID ' . $sale_id
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'      => true,
        'sale_id'=> $sale_id,
        'total'  => $grandTotal
    ]);
    exit;

} catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
    exit;
}

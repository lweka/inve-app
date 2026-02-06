<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code
header('Content-Type: application/json');

try {

    if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
        throw new Exception("Accès refusé");
    }

    $product_id = (int)($_POST['product_id'] ?? 0);
    $house_id   = (int)($_POST['house_id'] ?? 0);
    $type       = strtolower(trim($_POST['type'] ?? ''));
    $qty        = (int)($_POST['qty'] ?? 0);
    $note       = trim($_POST['note'] ?? '');
    $agent_id   = (int)($_POST['agent_id'] ?? 0);

    if(
        $product_id <= 0 ||
        $house_id <= 0 ||
        $qty <= 0 ||
        !in_array($type, ['in','out','transfer','transfert'])
    ){
        throw new Exception('Paramètres invalides');
    }

    if ($type === 'transfert') {
        $type = 'transfer';
    }

    if ($type === 'out' && $agent_id > 0) {
        $type = 'transfer';
    }

    // vérifier maison du client
    $stmt = $pdo->prepare("SELECT id FROM houses WHERE id = ? AND client_code = ?");
    $stmt->execute([$house_id, $client_code]);
    if(!$stmt->fetch()){
        throw new Exception('Maison non autorisée');
    }

    /* 🔴 transfert = vendeur obligatoire */
    if($type === 'transfer' && $agent_id <= 0){
        throw new Exception(
            "Veuillez sélectionner un vendeur pour l’envoi du stock."
        );
    }

    $pdo->beginTransaction();

    /* ===== PRODUIT ===== */
    $stmt = $pdo->prepare("
        SELECT buy_price, sell_price
        FROM products
        WHERE id = ? AND house_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$product_id, $house_id]);
    $product = $stmt->fetch();

    if(!$product){
        throw new Exception('Produit introuvable');
    }

    /* ===== STOCK MAISON ===== */
    $stmt = $pdo->prepare("
        SELECT id, qty
        FROM house_stock
        WHERE house_id=? AND product_id=?
        FOR UPDATE
    ");
    $stmt->execute([$house_id, $product_id]);
    $row = $stmt->fetch();

    $currentQty = $row ? (int)$row['qty'] : 0;

    if(in_array($type, ['out','transfer']) && $currentQty < $qty){
        throw new Exception('Stock maison insuffisant');
    }


    if ($type === 'in') {
    $newHouseQty = $currentQty + $qty;
    } else { // out OU transfer
        $newHouseQty = $currentQty - $qty;
    }


    if($row){
        $pdo->prepare("
            UPDATE house_stock SET qty=?
            WHERE id=?
        ")->execute([$newHouseQty, $row['id']]);
    } else {
        $pdo->prepare("
            INSERT INTO house_stock (house_id, product_id, qty)
            VALUES (?,?,?)
        ")->execute([$house_id, $product_id, $newHouseQty]);
    }

    /* ===== TRANSFERT → STOCK VENDEUR ===== */
    if ($type === 'transfer') {

    // vérifier vendeur appartenant à la maison du client
    $stmt = $pdo->prepare("SELECT a.id FROM agents a JOIN houses h ON h.id = a.house_id WHERE a.id = ? AND a.house_id = ? AND h.client_code = ?");
    $stmt->execute([$agent_id, $house_id, $client_code]);
    if(!$stmt->fetch()){
        throw new Exception('Vendeur non autorisé');
    }

    $stmt = $pdo->prepare("
        SELECT id, qty
        FROM agent_stock
        WHERE agent_id=? AND house_id=? AND product_id=?
        FOR UPDATE
    ");
    $stmt->execute([$agent_id, $house_id, $product_id]);
    $agentStock = $stmt->fetch();

    if ($agentStock) {
        $pdo->prepare("
            UPDATE agent_stock
            SET qty = qty + ?
            WHERE id = ?
        ")->execute([$qty, $agentStock['id']]);
    } else {
        $pdo->prepare("
            INSERT INTO agent_stock (agent_id, house_id, product_id, qty)
            VALUES (?,?,?,?)
        ")->execute([$agent_id, $house_id, $product_id, $qty]);
    }
}



    /* ===== HISTORIQUE ===== */
    if (!empty($note)) {
    $finalNote = $note;
    } else {
        if ($type === 'in') {
            $finalNote = 'Entrée stock';
        } elseif ($type === 'out') {
            $finalNote = 'Sortie interne';
        } else { // transfer
            $finalNote = 'Transfert vers vendeur';
        }
    }


    $pdo->prepare("
    INSERT INTO product_movements (
        client_code,
        house_id,
        product_id,
        agent_id,
        type,
        qty,
        unit_buy_price,
        unit_sell_price,
        note
    ) VALUES (?,?,?,?,?,?,?,?,?)
")->execute([
    $client_code,
    $house_id,
    $product_id,
    $type === 'transfer' ? $agent_id : null,
    $type,
    $qty,
    $product['buy_price'],
    $product['sell_price'],
    $finalNote
]);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'new_qty' => $newHouseQty
    ]);
    exit;

} catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'ok'=>false,
        'message'=>$e->getMessage()
    ]);
    exit;
}
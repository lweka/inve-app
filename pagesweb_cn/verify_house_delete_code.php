<?php
// pagesweb_cn/verify_house_delete_code.php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code


if (ob_get_length()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');





$request_id = intval($_POST['request_id'] ?? 0);
$code = trim($_POST['code'] ?? '');

if($request_id <= 0 || $code === ''){
    echo json_encode(['ok'=>false,'message'=>'Paramètres requis']); exit;
}

$stmt = $pdo->prepare("SELECT * FROM house_delete_requests WHERE id = ?");
$stmt->execute([$request_id]);
$req = $stmt->fetch();
if(!$req){ echo json_encode(['ok'=>false,'message'=>'Demande introuvable']); exit; }
if(time() > intval($req['expires_at'])){ echo json_encode(['ok'=>false,'message'=>'Code expiré']); exit; }
if($req['code'] !== $code){ echo json_encode(['ok'=>false,'message'=>'Code incorrect']); exit; }

// ready to delete house
$house_id = intval($req['house_id']);

// vérifier que la maison appartient au client connecté
$stmt = $pdo->prepare("SELECT id FROM houses WHERE id = ? AND client_code = ?");
$stmt->execute([$house_id, $client_code]);
if(!$stmt->fetch()){
    echo json_encode(['ok'=>false,'message'=>'Maison non autorisée']);
    exit;
}

try {
    $pdo->beginTransaction();

    // delete sale items and sales for the house (if present)
    $sales = $pdo->prepare("SELECT id FROM sales WHERE house_id = ?");
    $sales->execute([$house_id]);
    $saleIds = $sales->fetchAll(PDO::FETCH_COLUMN);
    if($saleIds){
        $in = str_repeat('?,', count($saleIds)-1).'?';
        $pdo->prepare("DELETE FROM sale_items WHERE sale_id IN ($in)")->execute($saleIds);
        $pdo->prepare("DELETE FROM sales WHERE id IN ($in)")->execute($saleIds);
    }

    // delete stock movements and house_stock
    $pdo->prepare("DELETE FROM stock_movements WHERE house_id = ?")->execute([$house_id]);
    $pdo->prepare("DELETE FROM house_stock WHERE house_id = ?")->execute([$house_id]);

    // finally delete the house
    $pdo->prepare("DELETE FROM houses WHERE id = ?")->execute([$house_id]);

    // remove the request
    $pdo->prepare("DELETE FROM house_delete_requests WHERE id = ?")->execute([$request_id]);

    $pdo->commit();
    echo json_encode(['ok'=>true]);
    exit;
} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'Erreur suppression: '.$e->getMessage()]);
    exit;
}

<?php
require_once __DIR__ . '/connectDb.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    echo json_encode(['ok' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier que le vendeur est toujours actif
$stmt_check = $pdo->prepare("SELECT status FROM agents WHERE id=? LIMIT 1");
$stmt_check->execute([$_SESSION['user_id']]);
$agent_status = $stmt_check->fetchColumn();

if($agent_status !== 'active'){
    echo json_encode(['ok' => false, 'message' => 'Compte désactivé', 'disabled' => true]);
    exit;
}

$lastSaleId = null;
$receipt_id = uniqid('RCP-', true); // Générer un ID unique pour cette transaction
$agent_id = (int)$_SESSION['user_id'];
$house_id = (int)($_POST['house_id'] ?? 0);
$items    = json_decode($_POST['items'] ?? '[]', true);

$discount        = (float)($_POST['discount'] ?? 0);
$payment_method  = $_POST['payment_method'] ?? 'cash';
$customer_name   = trim($_POST['customer_name'] ?? '');

if ($house_id <= 0 || empty($items) || !is_array($items)) {
    echo json_encode(['ok' => false, 'message' => 'Panier invalide']);
    exit;
}

$session_house_id = (int)($_SESSION['house_id'] ?? 0);
if ($session_house_id <= 0 || $house_id !== $session_house_id) {
    echo json_encode(['ok' => false, 'message' => 'Maison non autorisée']);
    exit;
}

// récupérer client_code de la maison
$stmt = $pdo->prepare("SELECT client_code FROM houses WHERE id = ?");
$stmt->execute([$house_id]);
$client_code = $stmt->fetchColumn();
if (!$client_code) {
    echo json_encode(['ok' => false, 'message' => 'Client invalide']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($items as $item) {

        /* =====================================================
           🔹 CAS 1 : VENTE KIT
           ===================================================== */
        if (!empty($item['is_kit']) && !empty($item['items'])) {

            // Calculer les totaux par devise
            $kitTotalsByCurrency = [];
            $kitCurrencies = [];

            // 🔒 Vérifier stock vendeur pour TOUS les composants
            foreach ($item['items'] as $k) {

                $pid = (int)$k['product_id'];
                $qty = (int)$k['qty'];
                $currency = $k['sell_currency'] ?? 'CDF';

                $stmt = $pdo->prepare("
                    SELECT qty
                    FROM agent_stock
                    WHERE agent_id = ?
                      AND house_id = ?
                      AND product_id = ?
                    FOR UPDATE
                ");
                $stmt->execute([$agent_id, $house_id, $pid]);
                $currentQty = (int)$stmt->fetchColumn();

                if ($currentQty < $qty) {
                    throw new Exception(
                        "Stock insuffisant pour le produit du kit : " . ($k['name'] ?? '')
                    );
                }

                // Calculer le total par devise
                if(!isset($kitTotalsByCurrency[$currency])) {
                    $kitTotalsByCurrency[$currency] = 0;
                    $kitCurrencies[] = $currency;
                }
                $kitTotalsByCurrency[$currency] += ($k['sell_price'] * $qty);
            }

            // Déterminer le total du kit (on prend la première devise ou CDF par défaut)
            $kitPrimaryCurrency = $kitCurrencies[0] ?? 'CDF';
            $kitTotalPrice = $kitTotalsByCurrency[$kitPrimaryCurrency] ?? 0;
            
            // Marquer comme kit mixte si plusieurs devises
            $isMixedCurrency = count($kitCurrencies) > 1;

            // 🔻 Appliquer remise sur le KIT
            if ($discount > 0) {
                $kitTotalPrice -= $discount;
                if ($kitTotalPrice < 0) {
                    $kitTotalPrice = 0;
                }
            }

            // 🧾 1️⃣ Enregistrer la vente KIT (parent)
            $stmt = $pdo->prepare("
                INSERT INTO product_movements
                (
                    client_code,
                    house_id,
                    agent_id,
                    type,
                    qty,
                    unit_sell_price,
                    discount,
                    payment_method,
                    customer_name,
                    is_kit,
                    receipt_id,
                    created_at
                )
                VALUES (?,?,?,?,?,?,?,?,?,1,?,NOW())
            ");
            $stmt->execute([
                $client_code,
                $house_id,
                $agent_id,
                'sale',
                1,
                $kitTotalPrice,
                $discount,
                $payment_method,
                $customer_name,
                $receipt_id
            ]);

            $kit_id = $pdo->lastInsertId();
            $lastSaleId = $kit_id;

            // 🧾 2️⃣ Enregistrer les composants du KIT
            foreach ($item['items'] as $k) {

                $pid  = (int)$k['product_id'];
                $qty  = (int)$k['qty'];
                $prix = (float)$k['sell_price'];

                // ➖ Décrémenter stock vendeur
                $pdo->prepare("
                    UPDATE agent_stock
                    SET qty = qty - ?
                    WHERE agent_id = ?
                      AND house_id = ?
                      AND product_id = ?
                ")->execute([$qty, $agent_id, $house_id, $pid]);

                // 📦 Mouvement composant
                $pdo->prepare("
                    INSERT INTO product_movements
                    (
                        client_code,
                        product_id,
                        house_id,
                        agent_id,
                        type,
                        qty,
                        unit_sell_price,
                        kit_id,
                        receipt_id,
                        created_at
                    )
                    VALUES (?,?,?,?,?,?,?,?,?,NOW())
                ")->execute([
                    $client_code,
                    $pid,
                    $house_id,
                    $agent_id,
                    'sale',
                    $qty,
                    $prix,
                    $kit_id,
                    $receipt_id
                ]);
            }

            continue; // 🔥 passer à l’item suivant
        }

        /* =====================================================
           🔹 CAS 2 : VENTE PRODUIT SIMPLE
           ===================================================== */

        $product_id    = (int)$item['product_id'];
        $qty           = (int)$item['qty'];
        $sell_price    = (float)$item['sell_price'];

        if ($product_id <= 0 || $qty <= 0) {
            continue;
        }

        // 🔒 Verrou stock vendeur
        $stmt = $pdo->prepare("
            SELECT qty
            FROM agent_stock
            WHERE agent_id = ?
              AND house_id = ?
              AND product_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$agent_id, $house_id, $product_id]);
        $currentQty = (int)$stmt->fetchColumn();

        if ($currentQty < $qty) {
            throw new Exception("Stock insuffisant pour un produit");
        }

        // ➖ Décrémenter stock vendeur
        $pdo->prepare("
            UPDATE agent_stock
            SET qty = qty - ?
            WHERE agent_id = ?
              AND house_id = ?
              AND product_id = ?
        ")->execute([$qty, $agent_id, $house_id, $product_id]);

        // 🧾 Historique vente simple
        $pdo->prepare("
            INSERT INTO product_movements
            (
                client_code,
                product_id,
                house_id,
                agent_id,
                type,
                qty,
                unit_sell_price,
                discount,
                payment_method,
                customer_name,
                is_kit,
                receipt_id,
                created_at
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,0,?,NOW())
        ")->execute([
            $client_code,
            $product_id,
            $house_id,
            $agent_id,
            'sale',
            $qty,
            $sell_price,
            $discount,
            $payment_method,
            $customer_name,
            $receipt_id
        ]);

        $lastSaleId = $pdo->lastInsertId();
    }

    $ticketNumber = 'TCK-' . date('Ymd') . '-' . str_pad($lastSaleId, 5, '0', STR_PAD_LEFT);

    $pdo->prepare("
    UPDATE product_movements
    SET ticket_number = ?
    WHERE receipt_id = ? OR id = ? OR kit_id = ?
    ")->execute([$ticketNumber, $receipt_id, $lastSaleId, $lastSaleId]);

    $pdo->commit();
    //echo json_encode(['ok' => true]);
    //echo json_encode(['ok' => true, 'sale_id' => $kit_id ?? $lastSaleId]);
    echo json_encode([
    'ok' => true,
    'sale_id' => $lastSaleId
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
}
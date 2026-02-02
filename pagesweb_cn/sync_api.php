<?php
/**
 * SYNC API - Synchronisation des données offline
 * Reçoit et traite les données enregistrées en mode offline
 * CartelPlus Congo PWA
 */

require_once '../Downloads/connectDb.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Connexion base de données
$pdo = connectDb();

// Récupération de l'action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'sync_sale':
            handleSyncSale($pdo);
            break;
            
        case 'sync_queue':
            handleSyncQueue($pdo);
            break;
            
        case 'get_products':
            handleGetProducts($pdo);
            break;
            
        case 'get_clients':
            handleGetClients($pdo);
            break;
            
        case 'batch_sync':
            handleBatchSync($pdo);
            break;
            
        case 'get_sync_status':
            handleGetSyncStatus($pdo);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Synchronise une vente offline
 */
function handleSyncSale($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['data'])) {
        throw new Exception('Données manquantes');
    }
    
    $saleData = $input['data'];
    $offline_id = $saleData['offline_id'] ?? null;
    
    // Vérifier si déjà synchronisé
    if ($offline_id) {
        $stmt = $pdo->prepare("SELECT id FROM sells WHERE offline_id = ?");
        $stmt->execute([$offline_id]);
        if ($existing = $stmt->fetch()) {
            echo json_encode([
                'success' => true,
                'message' => 'Vente déjà synchronisée',
                'server_id' => $existing['id'],
                'already_synced' => true
            ]);
            return;
        }
    }
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Vérifier le client
        $client_code = $saleData['client_code'] ?? null;
        if (!$client_code) {
            throw new Exception('Code client manquant');
        }
        
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE client_code = ?");
        $stmt->execute([$client_code]);
        if (!$stmt->fetch()) {
            throw new Exception('Client non trouvé');
        }
        
        // Insérer la vente
        $stmt = $pdo->prepare("
            INSERT INTO sells (
                client_code, products, total, discount, final_total, 
                payment_method, currency, created_at, offline_id, synced_from_offline
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $saleData['client_code'],
            $saleData['products'] ?? '[]',
            $saleData['total'] ?? 0,
            $saleData['discount'] ?? 0,
            $saleData['final_total'] ?? 0,
            $saleData['payment_method'] ?? 'cash',
            $saleData['currency'] ?? 'CDF',
            $saleData['created_at'] ?? date('Y-m-d H:i:s'),
            $offline_id,
        ]);
        
        $server_id = $pdo->lastInsertId();
        
        // Mettre à jour les stocks si nécessaire
        if (isset($saleData['products'])) {
            $products = is_string($saleData['products']) ? 
                json_decode($saleData['products'], true) : 
                $saleData['products'];
            
            if (is_array($products)) {
                foreach ($products as $product) {
                    if (isset($product['id']) && isset($product['quantity'])) {
                        $stmt = $pdo->prepare("
                            UPDATE products 
                            SET stock = stock - ? 
                            WHERE id = ? AND stock >= ?
                        ");
                        $stmt->execute([
                            $product['quantity'],
                            $product['id'],
                            $product['quantity']
                        ]);
                    }
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Vente synchronisée avec succès',
            'server_id' => $server_id,
            'synced_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Synchronise la queue générale
 */
function handleSyncQueue($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['data'])) {
        throw new Exception('Données manquantes');
    }
    
    $queueData = $input['data'];
    $type = $queueData['type'] ?? 'unknown';
    
    // Rediriger vers le handler approprié
    switch ($type) {
        case 'sale':
            $input['data'] = $queueData['data'];
            handleSyncSale($pdo);
            break;
            
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Type non géré',
                'skipped' => true
            ]);
    }
}

/**
 * Récupère les produits pour le cache
 */
function handleGetProducts($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, price, stock, currency, image, category, description
        FROM products 
        WHERE status = 'active'
        ORDER BY name ASC
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products),
        'cached_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Récupère les clients pour le cache
 */
function handleGetClients($pdo) {
    // Vérifier si l'utilisateur est authentifié
    session_start();
    $admin_id = $_SESSION['admin_id'] ?? null;
    
    $query = "
        SELECT client_code, first_name, last_name, email, phone, 
               company_name, subscription_type, expires_at, status
        FROM clients 
        WHERE status = 'active'
    ";
    
    // Si pas admin, filtrer par client
    if (!$admin_id) {
        $client_code = $_SESSION['client_code'] ?? null;
        if ($client_code) {
            $query .= " AND client_code = :client_code";
        }
    }
    
    $query .= " ORDER BY company_name ASC, last_name ASC";
    
    $stmt = $pdo->prepare($query);
    
    if (!$admin_id && isset($client_code)) {
        $stmt->execute(['client_code' => $client_code]);
    } else {
        $stmt->execute();
    }
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'count' => count($clients),
        'cached_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Synchronisation par batch (plusieurs éléments)
 */
function handleBatchSync($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['items']) || !is_array($input['items'])) {
        throw new Exception('Items manquants ou invalides');
    }
    
    $results = [
        'success' => true,
        'total' => count($input['items']),
        'synced' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    foreach ($input['items'] as $index => $item) {
        try {
            $type = $item['type'] ?? 'sale';
            
            if ($type === 'sale') {
                $tempInput = ['data' => $item['data']];
                file_put_contents('php://input', json_encode($tempInput));
                
                ob_start();
                handleSyncSale($pdo);
                $result = ob_get_clean();
                
                $decoded = json_decode($result, true);
                if ($decoded && $decoded['success']) {
                    $results['synced']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'error' => $decoded['error'] ?? 'Erreur inconnue'
                    ];
                }
            }
            
        } catch (Exception $e) {
            $results['failed']++;
            $results['errors'][] = [
                'index' => $index,
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode($results);
}

/**
 * Récupère le statut de synchronisation
 */
function handleGetSyncStatus($pdo) {
    session_start();
    $client_code = $_SESSION['client_code'] ?? null;
    
    $stats = [
        'success' => true,
        'server_time' => date('Y-m-d H:i:s'),
        'total_sales' => 0,
        'offline_sales' => 0,
        'last_sync' => null
    ];
    
    if ($client_code) {
        // Compter les ventes totales
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sells WHERE client_code = ?");
        $stmt->execute([$client_code]);
        $stats['total_sales'] = $stmt->fetchColumn();
        
        // Compter les ventes synchronisées depuis offline
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM sells 
            WHERE client_code = ? AND synced_from_offline = 1
        ");
        $stmt->execute([$client_code]);
        $stats['offline_sales'] = $stmt->fetchColumn();
        
        // Dernière synchronisation
        $stmt = $pdo->prepare("
            SELECT created_at 
            FROM sells 
            WHERE client_code = ? AND synced_from_offline = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$client_code]);
        $stats['last_sync'] = $stmt->fetchColumn();
    }
    
    echo json_encode($stats);
}

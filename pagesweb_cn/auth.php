<?php 
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

$role = $_POST['role'] ?? '';

/* ============================================================
   AUTH ADMIN (Nouvelle table admin_accounts)
   ============================================================ */
if($role === 'admin') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        header("Location: ".PARSE_CONNECT."?role=admin&err=1");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, client_code, password_hash, full_name, status 
            FROM admin_accounts 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            header("Location: ".PARSE_CONNECT."?role=admin&err=1");
            exit;
        }

        if ($admin['status'] !== 'active') {
            header("Location: ".PARSE_CONNECT."?role=admin&err=2");
            exit;
        }

        // Vérifier que le client_code est toujours actif
        $stmt = $pdo->prepare("
            SELECT id, expires_at FROM active_clients 
            WHERE client_code = ? AND status = 'active'
        ");
        $stmt->execute([$admin['client_code']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            header("Location: ".PARSE_CONNECT."?role=admin&err=3");
            exit;
        }

        if (strtotime($client['expires_at']) < time()) {
            header("Location: ".PARSE_CONNECT."?role=admin&err=4");
            exit;
        }

        // Connexion réussie
        $_SESSION['user_role'] = 'admin';
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['client_code'] = $admin['client_code'];

        // Mettre à jour last_login
        $stmt = $pdo->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);

        $stmt = $pdo->prepare("UPDATE active_clients SET last_login = NOW() WHERE client_code = ?");
        $stmt->execute([$admin['client_code']]);

        // Initialiser les données du client s'il n'en a pas
        require_once __DIR__ . '/init_client_data.php';
        initialize_client_data($admin['client_code'], $pdo);

        // Rediriger vers le dashboard (chemin absolu)
        header("Location: /pagesweb_cn/dashboard.php");
        exit;

    } catch (PDOException $e) {
        header("Location: ".PARSE_CONNECT."?role=admin&err=99");
        exit;
    }
}


/* ============================================================
   AUTH VENDEUR (AGENT SANS MOT DE PASSE)
   ============================================================ */
if($role === 'seller') {

    $vendor_code = trim($_POST['vendor_number'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM agents WHERE seller_code = ? LIMIT 1");
    $stmt->execute([$vendor_code]);
    $agent = $stmt->fetch();

    if(!$agent){
        header("Location: ".PARSE_CONNECT."?role=seller&err=1");
        exit;
    }

    // Vérifier statut
    if($agent['status'] !== 'active'){
        header("Location: ".PARSE_CONNECT."?role=seller&err=2");
        exit;
    }

    // récupérer maison
    $h = $pdo->prepare("SELECT name FROM houses WHERE id = ?");
    $h->execute([$agent['house_id']]);
    $house_name = $h->fetchColumn() ?: '';

    // créer session agent
    $_SESSION['user_role']  = 'agent';
    $_SESSION['user_id']    = $agent['id'];
    $_SESSION['house_id']   = $agent['house_id'];
    $_SESSION['house_name'] = $house_name;

    //header("Location: seller_dashboard.php");
    header("Location: /pagesweb_cn/seller_dashboard.php");

    exit;
}


/* ============================================================
   ROLE INCONNU
   ============================================================ */
http_response_code(400);
echo "Role manquant";
exit;
?>

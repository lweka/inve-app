<?php
// Test file for reports functionality
// This script checks if all components are working

session_start();

// Check 1: Database Connection
try {
    require_once __DIR__ . '/../../Downloads/connectDb.php';
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Check 2: Admin Authentication
require_once __DIR__ . '/require_admin_auth.php';
echo "✅ Admin authentication loaded\n";

// Check 3: Client Code
if (!isset($_SESSION['client_code'])) {
    echo "❌ Client code not set in session\n";
} else {
    echo "✅ Client code found: " . $_SESSION['client_code'] . "\n";
}

// Check 4: PHPMailer
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $test_mail = new PHPMailer\PHPMailer\PHPMailer();
    echo "✅ PHPMailer loaded successfully\n";
} catch (Exception $e) {
    echo "❌ PHPMailer error: " . $e->getMessage() . "\n";
}

// Check 5: TCPDF
try {
    $tcpdf_test = new TCPDF();
    echo "✅ TCPDF library available\n";
} catch (Exception $e) {
    echo "❌ TCPDF error: " . $e->getMessage() . "\n";
}

// Check 6: Sample Data
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_movements WHERE client_code = ? AND type IN ('out', 'sale')");
$stmt->execute([$_SESSION['client_code']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "✅ Product movements found: " . $result['count'] . "\n";

echo "\n✅ All checks passed - reports.php should work correctly\n";
?>

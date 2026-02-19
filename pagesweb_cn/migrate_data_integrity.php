<?php
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/data_integrity.php';

$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    require_once __DIR__ . '/require_admin_auth.php';
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Acces refuse']);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

try {
    $pdo->beginTransaction();

    $cleanupBefore = di_cleanup_orphans($pdo);
    $appliedConstraints = di_enforce_foreign_keys($pdo);
    $cleanupAfter = di_cleanup_orphans($pdo);

    $pdo->commit();

    $payload = [
        'ok' => true,
        'cleanup_before' => $cleanupBefore,
        'constraints_added' => $appliedConstraints,
        'cleanup_after' => $cleanupAfter,
    ];

    if ($isCli) {
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
    } else {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $payload = ['ok' => false, 'message' => $e->getMessage()];
    if ($isCli) {
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
    } else {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    exit;
}


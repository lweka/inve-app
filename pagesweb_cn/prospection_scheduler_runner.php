<?php
define('PROSPECTION_RUNNER_MODE', true);

function runnerDeny(int $status, string $message): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'ok' => false,
        'runner' => true,
        'error' => $message,
        'status' => $status,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (PHP_SAPI !== 'cli') {
    $configuredToken = trim((string)getenv('PROSPECTION_RUNNER_TOKEN'));
    if ($configuredToken === '') {
        runnerDeny(503, 'Runner token non configure. Definissez PROSPECTION_RUNNER_TOKEN cote serveur.');
    }
    $providedToken = trim((string)($_GET['token'] ?? ($_SERVER['HTTP_X_PROSPECTION_TOKEN'] ?? '')));
    if ($providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
        runnerDeny(401, 'Acces non autorise.');
    }
}

if (PHP_SAPI === 'cli') {
    if (!isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] === '') {
        $_SERVER['SERVER_NAME'] = 'localhost';
    }
    if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    }
}
require_once __DIR__ . '/prospection_manager.php';

<?php
$normalizedDir = str_replace('\\', '/', __DIR__);
$isLocalHost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true);
$isLocalPath = stripos($normalizedDir, '/wamp64/www/') !== false;
$isLocalCli = PHP_SAPI === 'cli';
$debugMode = $isLocalHost || $isLocalPath || $isLocalCli;

ini_set('display_errors', $debugMode ? '1' : '0');
ini_set('display_startup_errors', $debugMode ? '1' : '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Kinshasa');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$connectionCandidates = [];

$envHost = getenv('DB_HOST') ?: '';
$envDb = getenv('DB_NAME') ?: '';
$envUser = getenv('DB_USER') ?: '';
$envPass = getenv('DB_PASS');

if ($envHost !== '' && $envDb !== '' && $envUser !== '' && $envPass !== false) {
    $connectionCandidates[] = [
        'label' => 'env',
        'host' => $envHost,
        'db' => $envDb,
        'user' => $envUser,
        'pass' => $envPass,
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ];
}

if ($isLocalHost || $isLocalPath || $isLocalCli) {
    $connectionCandidates[] = [
        'label' => 'wamp-local',
        'host' => '127.0.0.1',
        'db' => 'inventeur_produits-app',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ];
}

$connectionCandidates[] = [
    'label' => 'hostinger',
    'host' => 'srv996.hstgr.io',
    'db' => 'u424760992_inventeur_prod',
    'user' => 'u424760992_inventeur_p_us',
    'pass' => '0814926220@Kin243',
    'charset' => 'utf8mb4',
];

$connectionErrors = [];
$pdo = null;

foreach ($connectionCandidates as $candidate) {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $candidate['host'],
        $candidate['db'],
        $candidate['charset']
    );

    try {
        $pdo = new PDO($dsn, $candidate['user'], $candidate['pass'], $options);
        break;
    } catch (PDOException $e) {
        $connectionErrors[] = $candidate['label'] . ': ' . $e->getMessage();
    }
}

if (!$pdo instanceof PDO) {
    error_log('Erreur de connexion a la BDD : ' . implode(' | ', $connectionErrors));
    die('Une erreur technique est survenue. Veuillez reessayer plus tard.');
}

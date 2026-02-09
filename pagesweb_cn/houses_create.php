<?php
// houses_create.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

function clean($v) { return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')); }

function redirectWithErrors(array $errors): void {
    $err = urlencode(json_encode($errors));
    $target = (defined('HOUSES_MANAGE') ? HOUSES_MANAGE : HOUSES_MANAGE);
    header('Location: ' . $target . '?err=' . $err);
    exit;
}

function ensureHouseLogoColumns(PDO $pdo): array {
    $result = [
        'logo_path' => false,
        'logo_updated_at' => false
    ];

    try {
        $columns = $pdo->query('SHOW COLUMNS FROM houses')->fetchAll(PDO::FETCH_COLUMN, 0);
        $result['logo_path'] = in_array('logo_path', $columns, true);
        $result['logo_updated_at'] = in_array('logo_updated_at', $columns, true);

        if (!$result['logo_path']) {
            $pdo->exec('ALTER TABLE houses ADD COLUMN logo_path VARCHAR(255) NULL AFTER address');
        }
        if (!$result['logo_updated_at']) {
            $pdo->exec('ALTER TABLE houses ADD COLUMN logo_updated_at DATETIME NULL AFTER logo_path');
        }

        $columns = $pdo->query('SHOW COLUMNS FROM houses')->fetchAll(PDO::FETCH_COLUMN, 0);
        $result['logo_path'] = in_array('logo_path', $columns, true);
        $result['logo_updated_at'] = in_array('logo_updated_at', $columns, true);
    } catch (Throwable $e) {
        error_log('House logo schema error: ' . $e->getMessage());
    }

    return $result;
}

$name = clean($_POST['name'] ?? '');
$code = clean($_POST['code'] ?? '');
$type = clean($_POST['type'] ?? '');
$address = clean($_POST['address'] ?? '');

$errors = [];

if ($name === '') $errors[] = 'Le nom de la maison est obligatoire.';
if ($code === '') $errors[] = 'Le code maison est obligatoire.';
if ($address === '') $errors[] = 'L\'adresse de la maison est obligatoire.';

if (strlen($name) < 3) $errors[] = 'Le nom doit contenir au moins 3 caracteres.';
if (strlen($code) < 2) $errors[] = 'Le code doit contenir au moins 2 caracteres.';
if (strlen($address) < 5) $errors[] = 'L\'adresse doit contenir au moins 5 caracteres.';

if (strlen($name) > 150) $errors[] = 'Le nom est trop long.';
if (strlen($code) > 50) $errors[] = 'Le code est trop long.';
if (strlen($type) > 100) $errors[] = 'Le type est trop long.';
if (strlen($address) > 255) $errors[] = 'L\'adresse est trop longue.';

if (!preg_match('/^[A-Za-z0-9_\-]+$/', $code)) {
    $errors[] = 'Le code maison ne doit contenir que des lettres, chiffres, tirets (-) ou underscores (_).';
}

$uploadedLogo = $_FILES['house_logo'] ?? null;
$logoTmpPath = null;
$logoExtension = null;
$logoRelativePath = null;

if ($uploadedLogo && ($uploadedLogo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($uploadedLogo['error'] ?? UPLOAD_ERR_CANT_WRITE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Le logo n\'a pas pu etre telecharge.';
    } else {
        $maxBytes = 2 * 1024 * 1024;
        if ((int)$uploadedLogo['size'] > $maxBytes) {
            $errors[] = 'Le logo depasse la taille maximale autorisee (2 Mo).';
        } else {
            $mime = '';
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string)$finfo->file($uploadedLogo['tmp_name']);
            } elseif (function_exists('mime_content_type')) {
                $mime = (string)mime_content_type($uploadedLogo['tmp_name']);
            }
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];
            if (!isset($allowedMimes[$mime])) {
                $errors[] = 'Format logo invalide. Utilisez JPG, PNG ou WEBP.';
            } else {
                $logoTmpPath = $uploadedLogo['tmp_name'];
                $logoExtension = $allowedMimes[$mime];
            }
        }
    }
}

/* Unicite (par client) */
$stmt = $pdo->prepare('SELECT id FROM houses WHERE code = ? AND client_code = ?');
$stmt->execute([$code, $client_code]);
if ($stmt->fetch()) $errors[] = 'Ce code maison existe deja.';

if (!empty($errors)) {
    redirectWithErrors($errors);
}

$logoSchema = ensureHouseLogoColumns($pdo);

if ($logoTmpPath !== null) {
    if (!($logoSchema['logo_path'] ?? false)) {
        $errors[] = 'Le champ de reference logo n\'est pas disponible dans la base.';
    } else {
        $logoDir = __DIR__ . '/../images/logo_client';
        if (!is_dir($logoDir) && !mkdir($logoDir, 0755, true)) {
            $errors[] = 'Impossible de creer le dossier logo_client.';
        } else {
            $safeClient = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$client_code);
            if ($safeClient === '') $safeClient = 'client';

            try {
                $rand = bin2hex(random_bytes(4));
            } catch (Throwable $e) {
                $rand = substr(sha1(uniqid('', true)), 0, 8);
            }

            $fileName = 'house_' . $safeClient . '_' . date('Ymd_His') . '_' . $rand . '.' . $logoExtension;
            $targetPath = $logoDir . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($logoTmpPath, $targetPath)) {
                $errors[] = 'Echec de l\'enregistrement du logo sur le serveur.';
            } else {
                $logoRelativePath = 'logo_client/' . $fileName;
            }
        }
    }
}

if (!empty($errors)) {
    if ($logoRelativePath) {
        @unlink(__DIR__ . '/../images/' . str_replace('/', DIRECTORY_SEPARATOR, $logoRelativePath));
    }
    redirectWithErrors($errors);
}

/* Insert */
try {
    if (($logoSchema['logo_path'] ?? false) && ($logoSchema['logo_updated_at'] ?? false)) {
        $logoUpdatedAt = $logoRelativePath ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare('
            INSERT INTO houses (client_code, name, code, type, address, logo_path, logo_updated_at, created_at)
            VALUES (?,?,?,?,?,?,?, NOW())
        ');
        $stmt->execute([$client_code, $name, $code, $type, $address, $logoRelativePath, $logoUpdatedAt]);
    } elseif ($logoSchema['logo_path'] ?? false) {
        $stmt = $pdo->prepare('
            INSERT INTO houses (client_code, name, code, type, address, logo_path, created_at)
            VALUES (?,?,?,?,?,?, NOW())
        ');
        $stmt->execute([$client_code, $name, $code, $type, $address, $logoRelativePath]);
    } else {
        if ($logoRelativePath) {
            @unlink(__DIR__ . '/../images/' . str_replace('/', DIRECTORY_SEPARATOR, $logoRelativePath));
            $logoRelativePath = null;
        }
        $stmt = $pdo->prepare('
            INSERT INTO houses (client_code, name, code, type, address, created_at)
            VALUES (?,?,?,?,?, NOW())
        ');
        $stmt->execute([$client_code, $name, $code, $type, $address]);
    }
} catch (Throwable $e) {
    if ($logoRelativePath) {
        @unlink(__DIR__ . '/../images/' . str_replace('/', DIRECTORY_SEPARATOR, $logoRelativePath));
    }
    error_log('House creation error: ' . $e->getMessage());
    redirectWithErrors(['Une erreur est survenue pendant la creation de la maison.']);
}

$target = (defined('HOUSES_MANAGE') ? HOUSES_MANAGE : HOUSES_MANAGE);
header('Location: ' . $target . '?msg=created');
exit;

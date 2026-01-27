<?php
// Détecter si on est en local
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

// Si on est en local → le projet est dans /cartelpluscn/
// Si en ligne → il est directement à la racine "/"
if (!defined('PROJECT_ROOT_URL')) {
    define('PROJECT_ROOT_URL', $isLocal ? '/cartelpluscn/' : '/');
}

// URL de base
define('BASE_URL', rtrim(PROJECT_ROOT_URL, '/') . '/');

// Dossiers publics (assets)
define('CSS_DIR', BASE_URL . 'css/');
define('JS_DIR', BASE_URL . 'js/');
define('IMG_DIR', BASE_URL . 'images/');
define('FONTS_DIR', BASE_URL . 'fonts/');

// Dossiers côté serveur
define('ROOT_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . PROJECT_ROOT_URL);
define('PAGES_DIR', ROOT_DIR . 'pagesweb_cn/');

// Helper pour générer une URL
function url(string $path): string {
    return BASE_URL . ltrim($path, '/');
}

// Exemple de page
define('URL_FORMATIONS', BASE_URL . 'pagesweb_cn/formations.php');

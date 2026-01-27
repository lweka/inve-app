<?php
// Si ton projet est dans un sous-dossier local (ex: localhost/cartelpluscn/) mets '/cartelpluscn/'
// Sinon laisse '/'
if (!defined('PROJECT_ROOT_URL')) {
    // Si tu es en local dans un dossier "cartelpluscn" sous www ou htdocs
    // define('PROJECT_ROOT_URL', '/cartelpluscn/'); 
    // Si ton site est à la racine du domaine (ex: cartelplus.tech)
    define('PROJECT_ROOT_URL', '/'); 
}

// BASE_URL doit commencer et se terminer par un slash pour être cohérent.
// Si PROJECT_ROOT_URL est '/cartelpluscn/', alors BASE_URL sera '/cartelpluscn/'
// Si PROJECT_ROOT_URL est '/', alors BASE_URL sera '/'
define('BASE_URL', rtrim(PROJECT_ROOT_URL, '/') . '/'); 

// ASSETS_DIR pour les ressources publiques
// Si tes assets (css, js, images) sont dans ROOT_DIR/pagesweb_cn/
define('ASSETS_DIR', BASE_URL . 'pagesweb_cn/'); 

// Ces définitions de chemins de fichiers côté serveur sont bonnes
define('ROOT_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . PROJECT_ROOT_URL);
define('PAGES_DIR', ROOT_DIR . 'pagesweb_cn/'); 

// La fonction url() est un excellent helper pour générer des URLs relatives à BASE_URL
function url(string $path): string {
    return BASE_URL . ltrim($path, '/');
}

// Les URL_ constantes pour des liens spécifiques, tu peux les utiliser aussi.
/** Par exemple: <a href="<?php echo URL_FORMATIONS; ?>">Formations</a>*/ 
define('URL_FORMATIONS', BASE_URL . 'pagesweb_cn/formations.php');
// define('URL_PAGE1', BASE_URL . 'pagesweb_cn/page1.php'); // Cet exemple semble redondant, utilise plutôt url('pagesweb_cn/page1.php')
?>


*****


<?php
// Si ton site est à la racine du domaine, BASE_URL = '/'
define('BASE_URL', '/'); // ou '/ton-sous-dossier/' si ton site est dans un sous-dossier

// Dossier où se trouvent les fichiers publics (images, css, js) — utilisé dans les templates
define('ASSETS_DIR', BASE_URL . 'pagesweb_cn/'); // si tes images sont dans pagesweb_cn/images
 $formationsPath = BASE_URL . 'pagesweb_cn/formations.php'; 

// -----------------
// Si ton projet est à la racine du domaine => '/'
if (!defined('PROJECT_ROOT_URL')) {
    // Si ton projet est dans un sous-dossier local (ex: localhost/cartelpluscn/) mets '/cartelpluscn/'
    // Sinon laisse '/'
    define('PROJECT_ROOT_URL', '/cartelpluscn/'); // <--- adapte ici si nécessaire
}

// Base URL (toujours termine par '/')
//define('BASE_URL', rtrim(PROJECT_ROOT_URL, '/') . '/');


// URLs publiques (pour les href)
define('URL_FORMATIONS', BASE_URL . 'cartelpluscn/pagesweb_cn/formations.php');
define('URL_PAGE1', BASE_URL . 'pagesweb_cn/page1.php');

// Dossiers pour require/include (chemin fichier côté serveur)
define('ROOT_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . PROJECT_ROOT_URL);
define('PAGES_DIR', ROOT_DIR . 'pagesweb_cn/'); // ex: C:/wamp64/www/cartelpluscn/pagesweb_cn/

// Helper (optionnel) pour construire des URLs
function url(string $path): string {
    return BASE_URL . ltrim($path, '/');
}




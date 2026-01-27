<?php
// Racine du projet (en local '/cartelpluscn/', en prod '/')
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);


if (!defined('PROJECT_ROOT_URL')) {
    define('PROJECT_ROOT_URL', '/inve-app/');
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

// Exemple de page à supprimer ou modifier selon les besoins
define('URL_FORMATIONS', BASE_URL . 'pagesweb_cn/formations/');

define('URL_DETAILFORMATIONS', BASE_URL . 'pagesweb_cn/readdetail/');

define('URL_SERVICEHEBERGEMENTWEB', BASE_URL . 'pagesweb_cn/service-hebergement/');

define('URL_SERVICEDEVSITEWEB', BASE_URL . 'pagesweb_cn/votresiteweb/');

define('URL_INSCRIPTIONOKFORMATIONS', BASE_URL . 'pagesweb_cn/inscritformaok/');
define('URL_INSCRIPTIONOKSITEWEB', BASE_URL . 'pagesweb_cn/inscrit-siteweb/');
define('URL_INSCRIPTIONOKHEBERGEMENTWEB', BASE_URL . 'pagesweb_cn/inscrit-hebergementweb/');


define('URL_404', BASE_URL . 'pagesweb_cn/404/');

define('URL_ACCUEIL', BASE_URL . 'index/'); // Page d'accueil 

define('URL_CONCEPTION_DEV_INFORMATIQUE', BASE_URL . 'pagesweb_cn/conception/');
define('URL_VOTRE_SITE_WEB', BASE_URL . 'pagesweb_cn/votre-site-web/');
define('URL_SERVICE_HEBERGEMENT_WEB', BASE_URL . 'pagesweb_cn/hebergement/');
define('URL_MAINTENANCE_INSTALLATION_INFORMATIQUE', BASE_URL . 'pagesweb_cn/maintenance/');
define('URL_DECRIVEZ_VOS_BESOIN', BASE_URL . 'pagesweb_cn/besoin/');


define('URL_ACCOMPAGNEMENT_BINANCE', BASE_URL . 'pagesweb_cn/binance-accompagnement/');
define('URL_MINAGE_BTC', BASE_URL . 'pagesweb_cn/minage-BTC/');
define('URL_VOTRE_BUSINESS', BASE_URL . 'pagesweb_cn/votre-business/');
define('URL_BOOST_VISIBILITE', BASE_URL . 'pagesweb_cn/boost-visibilite/');
define('URL_FORMATION_MAKETING', BASE_URL . 'pagesweb_cn/formation-marketing/');
define('URL_BLOG', BASE_URL . 'pagesweb_cn/blog/');


// Exemple de page pour inveteur produits
define('PARSE_CONNECT', BASE_URL . 'pagesweb_cn/connect-parse/'); // Page de connexion
define('AUTHENTIFICATION', BASE_URL . 'pagesweb_cn/auth/'); // parse authentification
define('DASHBOARD_ADMIN', BASE_URL . 'pagesweb_cn/dashboard/'); // Tableau de bord admin
define('DASHBOARD_SELLER', BASE_URL . 'pagesweb_cn/dashboard-seller'); // Tableau de bord vendeur
define('HOUSES_MANAGE', BASE_URL . 'pagesweb_cn/houses'); // Gestion maisons

define('AGENTS_MANAGE', BASE_URL . 'pagesweb_cn/agents'); // Gestion vendeurs
define('AGENTS_CREATE', BASE_URL . 'pagesweb_cn/agent_create'); // Gestion vendeurs
define('AGENTS_UPDATE_STATUS', BASE_URL . 'pagesweb_cn/agent_update_status'); // Gestion vendeurs
define('AGENTS_DELETE', BASE_URL . 'pagesweb_cn/agent_delete'); // Suppression vendeur




define('PRODUCTS_MANAGE', BASE_URL . 'pagesweb_cn/products'); // Gestion produits
define('REPORTS_INVENTORY', BASE_URL . 'pagesweb_cn/reports'); // Rapports / Inventaire (PDF)
define('HOUSES_CREATE', BASE_URL . 'pagesweb_cn/houses_create'); // Créer une maison
define('CHECK_HOUSECODE', BASE_URL. 'pagesweb_cn/check_house_code');
define('ADMIN_CHECK_HOUSE', BASE_URL. 'pagesweb_cn/admin_check_password');
define('SEND_HOUSE_DELETE_CODE', BASE_URL. 'pagesweb_cn/send_house_delete_code');
define('DELETE_HOUSE_WAIT', BASE_URL. 'pagesweb_cn/delete_house_wait');
define('VERIFY_HOUSE_DELETE_CODE', BASE_URL. 'pagesweb_cn/verify_house_delete_code');
define('EXCHANGE_RATE_MANAGER', BASE_URL. 'pagesweb_cn/exchange_rate_manage');
define('PRODUCTS_ALL_STORY', BASE_URL. 'pagesweb_cn/products_history_global');
define('PRODUCTS_LOW_STOCK', BASE_URL. 'pagesweb_cn/products_low_stock');
define('MARGE_PAR_MAISON', BASE_URL. 'pagesweb_cn/house_marge');









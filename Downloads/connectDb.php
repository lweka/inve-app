<?php
// Configuration de la base de données
$host = 'srv996.hstgr.io';
$db   = 'u424760992_cartelplustech';
$user = 'u424760992_cartelplus_bdd';
$pass = '0814926220@Kin243';
$charset = 'utf8mb4'; // Recommandé pour une compatibilité complète

// Options de PDO pour une connexion robuste
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // Création de l'instance PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En cas d'échec de la connexion, on arrête tout et on affiche une erreur générique
    // Il est déconseillé d'afficher $e->getMessage() en production pour des raisons de sécurité.
    error_log("Erreur de connexion à la BDD : " . $e->getMessage()); // Log l'erreur pour le développeur
    die("Une erreur technique est survenue. Veuillez réessayer plus tard.");
}
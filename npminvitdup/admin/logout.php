<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>
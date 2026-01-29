<?php
// Test de réécriture d'URL
header('Content-Type: text/plain');
echo "Test de réécriture d'URL\n";
echo "REQUEST_URI: ".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n";
echo "SCRIPT_NAME: ".(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '')."\n";
echo "PATH_INFO: ".(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '')."\n";
echo "FICHIER PHYSIQUE: ".__FILE__."\n";
?>

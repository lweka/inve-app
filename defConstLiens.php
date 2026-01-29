<?php

    $footerPath = __DIR__ . '/pagesweb_cn/footerPageCn.php'; // chemin correct
        if (!file_exists($footerPath)) {
        // message d'erreur lisible pour debug
        die("Erreur : footer introuvable : $footerPath");}

    $headerPath = __DIR__ . '/pagesweb_cn/headerPageCn.php'; // chemin correct
        if (!file_exists($headerPath)) {die("Erreur : footer introuvable : $headerPath");}

    $prestationServicePath = __DIR__ . '/pagesweb_cn/componosprestations.php'; // chemin correct
        if (!file_exists($prestationServicePath)) {die("Erreur : footer introuvable : $prestationServicePath");}

    $formationsCompoPath = __DIR__ . '/pagesweb_cn/componosformations.php'; // chemin correct
        if (!file_exists($formationsCompoPath)) {die("Erreur : footer introuvable : $formationsCompoPath");}

    $citationsEntreprisePath = __DIR__ . '/pagesweb_cn/compocitation.php'; // chemin correct
        if (!file_exists($citationsEntreprisePath)) {die("Erreur : footer introuvable : $citationsEntreprisePath");}

    $invitationQRPath = __DIR__ . '/pagesweb_cn/compoinvitqrcn.php'; // chemin correct
        if (!file_exists($invitationQRPath)) {die("Erreur : footer introuvable : $invitationQRPath");}

    $dataDbConnect = __DIR__ . '/pagesweb_cn/connectDb.php'; // chemin correct
        if (!file_exists($dataDbConnect)) {die("Erreur : footer introuvable : $dataDbConnect");}

    $readDetailsNosFormations = __DIR__ . '/pagesweb_cn/readdetail.php'; // chemin correct
    if (!file_exists($readDetailsNosFormations)) {die("Erreur : footer introuvable : $readDetailsNosFormations");}

    $inscritFormaok = __DIR__ . '/pagesweb_cn/readdetail.php'; // chemin correct
    if (!file_exists($inscritFormaok)) {die("Erreur : footer introuvable : $inscritFormaok");}

    $page404 = __DIR__ . '/pagesweb_cn/readdetail.php'; // chemin correct
    if (!file_exists($page404)) {die("Erreur : footer introuvable : $page404");}

// Constantes pour l'authentification admin/seller
define('AUTHENTIFICATION', '/inve-app/pagesweb_cn/auth.php');
define('PARSE_CONNECT', '/inve-app/pagesweb_cn/connect-parse.php');
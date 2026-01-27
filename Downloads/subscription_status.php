<?php
// On récupère le statut depuis l'URL (ex: ?status=success)
$status = isset($_GET['status']) ? $_GET['status'] : '';

$message = '';
$message_class = '';

// On définit le message et le style en fonction du statut
switch ($status) {
    case 'success':
        $message = "Merci ! Votre inscription à notre newsletter a bien été prise en compte.";
        $message_class = "success";
        break;
    case 'exists':
        $message = "Cette adresse e-mail est déjà inscrite à notre newsletter.";
        $message_class = "info";
        break;
    case 'invalid_email':
        $message = "L'adresse e-mail que vous avez fournie n'est pas valide. Veuillez réessayer.";
        $message_class = "error";
        break;
    case 'error':
        $message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        $message_class = "error";
        break;
    default:
        $message = "Aucune action spécifiée.";
        $message_class = "info";
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut de l'inscription</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { text-align: center; padding: 40px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .message { padding: 20px; border-radius: 5px; margin-bottom: 20px; font-size: 1.2em; }
        .success { background-color: #e6ffed; border: 1px solid #b7e4c7; color: #1d3522; }
        .error { background-color: #ffebee; border: 1px solid #ef9a9a; color: #c62828; }
        .info { background-color: #e3f2fd; border: 1px solid #90caf9; color: #1976d2; }
        a.button { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Statut de votre inscription</h1>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_class); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <a href="index.php" class="button">Retour à l'accueil</a>
    </div>
</body>
</html>
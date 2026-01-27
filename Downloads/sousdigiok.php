<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Meta refresh pour la redirection après 30 secondes -->
    <meta http-equiv="refresh" content="30;url=index.php">
    <title>Souscription Réussie</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; max-width: 600px; margin: auto; border: 1px solid #ddd; margin-top: 50px; text-align: center; background-color: #e9f7ef; }
        .container { background-color: #fff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #28a745; } /* Vert succès */
        p { font-size: 1.1em; color: #333; }
        .countdown { margin-top: 20px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Souscription Réussie !</h1>
        <p>Merci pour votre intérêt pour notre atelier "Marketing Digital pour tous".</p>
        <p>Votre demande de souscription a bien été enregistrée.</p>
        <p>Notre équipe vous contactera très prochainement pour la facturation et vous fournir les détails liés à la formation.</p>

        <div class="countdown">
            <p>Vous allez être redirigé automatiquement vers la page d'accueil dans <span id="countdown-timer">30</span> secondes...</p>
            <p>Si la redirection ne fonctionne pas, <a href="index.php">cliquez ici</a>.</p>
        </div>
    </div>

    <script>
        // Script simple pour afficher le compte à rebours
        let seconds = 30;
        const countdownElement = document.getElementById('countdown-timer');

        const interval = setInterval(() => {
            seconds--;
            if (countdownElement) {
                countdownElement.textContent = seconds;
            }
            if (seconds <= 0) {
                clearInterval(interval);
                // La redirection se fait via la balise meta refresh,
                // mais on pourrait aussi la déclencher ici si besoin :
                // window.location.href = 'index.php';
            }
        }, 1000); // Met à jour toutes les secondes (1000 ms)
    </script>

</body>
</html>
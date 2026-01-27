<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Charger PHPMailer
require __DIR__ . '/vendor/autoload.php';

// Charger la connexion à la base
require_once __DIR__ . '/pagesweb_cn/connectDb.php';

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formation_id = $_POST['formation_id'] ?? null;
    $nom_complet  = $_POST['nom_complet'] ?? '';
    $email        = $_POST['email'] ?? '';
    $ville        = $_POST['ville'] ?? '';
    $telephone    = $_POST['telephone'] ?? '';

    if (!$formation_id || !$nom_complet || !$email) {
        die("❌ Erreur : informations incomplètes.");
    }

    try {
        // Insérer dans la base
        $stmt = $pdo->prepare("
            INSERT INTO formations_cmdf_env_cn (formation_id, nom_complet, email, ville, telephone, date_inscription)
            VALUES (:formation_id, :nom_complet, :email, :ville, :telephone, NOW())
        ");
        $stmt->execute([
            'formation_id' => $formation_id,
            'nom_complet'  => $nom_complet,
            'email'        => $email,
            'ville'        => $ville,
            'telephone'    => $telephone,
        ]);

        // Récupérer les infos de la formation
        $fStmt = $pdo->prepare("SELECT titre FROM formations WHERE id = ?");
        $fStmt->execute([$formation_id]);
        $formation = $fStmt->fetch();

        // Préparer le mail
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.titan.email';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jlweka@cartelplus.tech';
        $mail->Password   = 'TON_MOT_DE_PASSE'; // ⚠️ À remplacer
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('jlweka@cartelplus.tech', 'Cartel Plus - Inscriptions');
        $mail->addAddress('jlweka@cartelplus.tech', 'Jonathan Lweka');

        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle inscription à une formation';

        $mail->Body = "
            <h2>Nouvelle inscription</h2>
            <p><strong>Formation :</strong> {$formation['titre']}</p>
            <p><strong>Nom complet :</strong> {$nom_complet}</p>
            <p><strong>Email :</strong> {$email}</p>
            <p><strong>Ville :</strong> {$ville}</p>
            <p><strong>Téléphone :</strong> {$telephone}</p>
            <hr>
            <small>Notification envoyée automatiquement depuis le site Cartel Plus CN.</small>
        ";

        $mail->send();

        // Redirection après succès
        header('Location: pagesweb_cn/inscritformaok.php');
        exit;

    } catch (Exception $e) {
        error_log("Erreur PHPMailer : {$mail->ErrorInfo}");
        die("❌ Une erreur est survenue : " . $e->getMessage());
    }
}
?>

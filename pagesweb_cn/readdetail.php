<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php'; // connexion PDO
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer

// Récupérer le slug de la formation depuis l'URL
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header("Location: pagesweb_cn/404.php"); // redirection si aucun slug
    exit;
}

// Récupérer les infos de la formation
try {
    $stmt = $pdo->prepare("SELECT id, titre, image_path, description_importance FROM formations WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    $formation = $stmt->fetch();
} catch (\PDOException $e) {
    error_log("Erreur BDD: " . $e->getMessage());
    $formation = false;
}

if (!$formation) {
    header("Location: " . URL_404); // redirection si formation introuvable
    exit;
}

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $ville       = trim($_POST['ville'] ?? '');
    $telephone   = trim($_POST['telephone'] ?? '');
    $formation_id = $formation['id'];

    if ($nom_complet && $email && $ville && $telephone) {
        try {
            // Insertion en base
            $stmt = $pdo->prepare("INSERT INTO formations_cmdf_env_cn (formation_id, nom_complet, email, ville, telephone, date_inscription)
                                   VALUES (:formation_id, :nom, :email, :ville, :telephone, NOW())");
            $stmt->execute([
                'formation_id' => $formation_id,
                'nom' => $nom_complet,
                'email' => $email,
                'ville' => $ville,
                'telephone' => $telephone
            ]);

            // Envoi du mail
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.titan.email';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cartelplus-congo@cartelplus.tech';
            $mail->Password   = 'Jo@Kin243'; // ⚠️ Remplacer
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo - Inscriptions');
            $mail->addAddress('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo Entreprise');

            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle inscription à une formation';
            $mail->Body = "
                <h2>Nouvelle inscription à la formation</h2>
                <p><strong>Formation :</strong> {$formation['titre']}</p>
                <p><strong>Nom complet :</strong> {$nom_complet}</p>
                <p><strong>Email :</strong> {$email}</p>
                <p><strong>Ville :</strong> {$ville}</p>
                <p><strong>Téléphone :</strong> {$telephone}</p>
                <p><strong>Date :</strong> " . date('d/m/Y H:i') . "</p>
            ";

            $mail->send();

            // ✅ Redirection après succès
           header("Location: " . URL_INSCRIPTIONOKFORMATIONS);
           exit;



        } catch (Exception $e) {
            error_log("Erreur PHPMailer ou BDD : " . $e->getMessage());
            // Ici, on peut rediriger vers une page d'erreur si on veut
            header("Location: pagesweb_cn/inscritfail.php"); 
            exit;
        }
    } else {
        header("Location: pagesweb_cn/inscritfail.php");
        exit;
    }
}

// Définir le titre de la page
$pageTitle = "Inscription : " . htmlspecialchars($formation['titre']);
?>

<?php require_once $headerPath; ?>

<header class="site-header">
    <div class="section-overlay"></div>

    <div class="container">
        <div class="row">
            
            <div class="col-lg-12 col-12 text-center">
                <h1 class="text-white"><?= htmlspecialchars($formation['titre']) ?></h1>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="<?=URL_ACCUEIL ?>">Accueil</a></li>

                        <li class="breadcrumb-item active" aria-current="page">Formulaire d'Inscription</li>
                    </ol>
                </nav>
            </div>

        </div>
    </div>
</header><br>

<section class="about-section">
    <div class="container">
        <div class="row justify-content-center align-items-center">

            <div class="col-lg-5 col-12">
                <div class="about-info-text">
                    <h2 class="mb-0"><?= htmlspecialchars($formation['titre']) ?></h2>
                    <h4 class="mb-2">Pourquoi cette formation est essentielle pour vous ?</h4>
                    <p><?= nl2br(htmlspecialchars($formation['description_importance'])) ?></p>
                </div>
            </div>

            <div class="col-lg-5 col-12 mt-5 mt-lg-0">
                <div class="about-image-wrap">
                    <img src="<?= BASE_URL . htmlspecialchars($formation['image_path']) ?>" class="about-image about-image-border-radius img-fluid" alt="<?= htmlspecialchars($formation['titre']) ?>">
                    <div class="about-info d-flex">
                        <h4 class="text-white mb-0 me-2"><?= htmlspecialchars($formation['titre']) ?></h4>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="contact-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12 mx-auto">
                <form class="custom-form contact-form" action="" method="post" role="form">
                    <h2 class="text-center mb-4">Formulaire d'Inscription</h2>
                    <input type="hidden" name="formation_id" value="<?= $formation['id'] ?>">

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-12">
                            <label>Nom complet<span>*</span></label>
                            <input type="text" name="nom_complet" class="form-control" placeholder="Jonathan Lweka" required>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <label>Email<span>*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="Jlweka@gmail.com" required>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <label>Ville de résidence<span>*</span></label>
                            <input type="text" name="ville" class="form-control" placeholder="Ex: Kinshasa" required>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <label>Téléphone<span>*</span></label>
                            <input type="text" name="telephone" class="form-control" placeholder="Ex: +243 89 64 34 898" required>
                        </div>
                        <div class="col-lg-4 col-md-4 col-6 mx-auto mt-3">
                            <button type="submit" class="form-control">Envoyer ma demande</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once $footerPath; ?>

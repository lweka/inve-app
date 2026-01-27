<?php 
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;


    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
    require_once __DIR__ . '/connectDb.php'; // connexion PDO
    require __DIR__ . '/../vendor/autoload.php'; // PHPMailer


    // Récupérer les infos de la formation
   
    // --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organization = trim($_POST['organization'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $email  = trim($_POST['email'] ?? '');



    if ($organization && $domain && $phone && $email) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO hebergement_web_formulaire_cn
                (organization, domain, phone, email, date_inscription)
                VALUES (:organization, :domain, :phone, :email, NOW())
            ");
            $stmt->execute([
                'organization' => $organization,
                'domain' => $domain,
                'phone' => $phone,
                'email' => $email
            ]);

            // Envoi d'email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.titan.email';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cartelplus-congo@cartelplus.tech';
            $mail->Password   = 'Jo@Kin243';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo - Besoin accompagnement hébergement web');
            $mail->addAddress('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo Entreprise');

            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle demande pour l\'hebergement web';
            $mail->Body = "
                <h2>Nouvelle demande reçue</h2>
                <p><strong>Nom :</strong> {$organization}</p>
                <p><strong>Email :</strong> {$domain}</p>
                <p><strong>Téléphone :</strong> {$phone}</p>
                <p><strong>Type de site :</strong> {$email}</p>
                <p><strong>Date :</strong> " . date('d/m/Y H:i') . "</p>
            ";

            $mail->send();

            header("Location: " . URL_INSCRIPTIONOKHEBERGEMENTWEB);
            exit;

        } catch (Exception $e) {
            error_log("Erreur : " . $e->getMessage());
            header("Location: pagesweb_cn/inscritfail.php"); 
            exit;
        }
    } else {
        header("Location: pagesweb_cn/inscritfail.php");
        exit;
    }
}


?>

<?php require_once $headerPath; ?>

<main>

    <header class="site-header">
        <div class="section-overlay"></div>

        <div class="container">
            <div class="row">
                
                <div class="col-lg-12 col-12 text-center">
                    <h1 class="text-white">Notre service d'Hébergement</h1>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a href="index.html">Accueil</a></li>

                            <li class="breadcrumb-item active" aria-current="page">Service d'hébergement</li>
                        </ol>
                    </nav>
                </div>

            </div>
        </div>
    </header> <br>


    <section class="about-section">
        <div class="container">
            <div class="row justify-content-center align-items-center">

                <div class="col-lg-5 col-12">
                    <div class="about-info-text">
                        <h2 class="mb-0">Présentation de notre service d’hébergement web</h2>

                        <h4 class="mb-2">Offrez à votre site une présence en ligne stable, rapide et sécurisée.</h4>

                        <p>Chez CartelPlus, nous mettons à votre disposition un service d’hébergement web professionnel reposant sur les infrastructures performantes de notre partenaire Hostinger. Cette collaboration nous permet de garantir à nos clients une haute disponibilité, une vitesse de chargement optimisée et une sécurité renforcée pour leurs projets en ligne.</p>
                        <p>Que vous soyez une entreprise, un indépendant ou une organisation, notre équipe s’occupe de toute la configuration technique : nom de domaine, hébergement, certificat SSL, base de données, messagerie professionnelle et sécurité du serveur. Grâce à notre suivi et nos services de mise à jour, votre site reste performant, protégé et toujours à jour dans le temps.</p>
                        <p>Nous croyons que chaque projet mérite une base solide. Avec CartelPlus, votre présence en ligne démarre sur les meilleures fondations.</p>

                        <div class="d-flex align-items-center mt-4">
                            <a href="#services-section" class="custom-btn custom-border-btn btn me-4">Explorer nos solutions </a>

                            <a href="contact.html" class="custom-link smoothscroll">Démarrer mon projet web</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 col-12 mt-5 mt-lg-0">
                    <div class="about-image-wrap">
                        <img src="<?= IMG_DIR ?>horizontal-shot-happy-mixed-race-females.jpg" class="about-image about-image-border-radius img-fluid" alt="">

                        <div class="about-info d-flex">
                            <h4 class="text-white mb-0 me-2">8</h4>

                            <p class="text-white mb-0">années d'expérience</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <section class="services-section section-padding" id="services-section">
        <div class="container">
            <div class="row">

                <div class="col-lg-12 col-12 text-center">
                    <h2 class="mb-5">Nous offrons des solutions d’hébergement fiables, rapides et sécurisées pour vos projets web.</h2>
                </div>

                <div class="services-block-wrap col-lg-4 col-md-6 col-12">
                    <div class="services-block">
                        <div class="services-block-title-wrap">
                            <i class="services-block-icon bi-window"></i>
                        
                            <h4 class="services-block-title">Hébergement et nom de domaine</h4>
                        </div>

                        <div class="services-block-body">
                            <p>Nous achetons et configurons l’hébergement adapté à votre activité. Nous associons un nom de domaine professionnel qui reflète votre marque et facilite la visibilité de votre entreprise en ligne.</p>
                        </div>
                    </div>
                </div>

                <div class="services-block-wrap col-lg-4 col-md-6 col-12 my-4 my-lg-0 my-md-0">
                    <div class="services-block">
                        <div class="services-block-title-wrap">
                            <i class="services-block-icon bi-twitch"></i>
                        
                            <h4 class="services-block-title">Configuration et mise en ligne</h4>
                        </div>

                        <div class="services-block-body">
                            <p>Nous procédons à la mise en ligne complète de vos fichiers, à l’installation du certificat SSL, à la configuration de la base de données et à la sécurisation du serveur selon vos besoins spécifiques.</p>
                        </div>
                    </div>
                </div>

                <div class="services-block-wrap col-lg-4 col-md-6 col-12">
                    <div class="services-block">
                        <div class="services-block-title-wrap">
                            <i class="services-block-icon bi-play-circle-fill"></i>
                        
                            <h4 class="services-block-title">Maintenance et mise à jour</h4>
                        </div>

                        <div class="services-block-body">
                            <p>Notre équipe assure un suivi technique permanent et des mises à jour régulières, afin de maintenir votre site fonctionnel, rapide et compatible avec les dernières technologies web.</p>
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
                    <form class="custom-form contact-form" action="" method="POST" role="form">
                        <h2 class="text-center mb-4">Vous avez un projet en tête ? Parlons-en !</h2>

                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="organization">Nom de l’organisation</label>
                                <input type="text" name="organization" id="organization" class="form-control" placeholder="Ex. CartelPlus" required>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="domain">Nom de domaine souhaité</label>
                                <input type="text" name="domain" id="domain" class="form-control" placeholder="ex. monsite.com" required>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="phone">Téléphone</label>
                                <input type="tel" name="phone" id="phone" class="form-control" placeholder="+243 000 000 000" required>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12">
                                <label for="email">Adresse e-mail</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="exemple@mail.com" required>
                            </div>

                            <div class="col-lg-4 col-md-4 col-6 mx-auto">
                                <button type="submit" class="form-control">Envoyer la demande</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>


    


    
   <!-- Composant invitation QR page cn debut -->
        <?php require_once $invitationQRPath;  ?>
    <!-- Composant invitation QR page cn fin  -->
</main>
<?php require_once $footerPath; ?>
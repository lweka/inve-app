<?php 
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;


    require_once __DIR__ . '/../configUrlcn.php';
    require_once __DIR__ . '/../defConstLiens.php';
    require_once __DIR__ . '/connectDb.php'; // connexion PDO
    require __DIR__ . '/../vendor/autoload.php'; // PHPMailer


    // Récupérer les infos de la formation
    try {
        $stmt = $pdo->prepare("SELECT *FROM type_siteweb_cn");
        $stmt->execute();
        $type_siteweb_cn = $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Erreur BDD: " . $e->getMessage());
        $type_siteweb_cn = false;
    }

    if (!$type_siteweb_cn) {
        header("Location: " . URL_404); // redirection si formation introuvable
        exit;
    } 



    // --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom        = trim($_POST['nom'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $telephone  = trim($_POST['telephone'] ?? '');
    $type_site  = trim($_POST['type_site'] ?? '');
    $autre_type = trim($_POST['autre_type_site'] ?? '');
    $budget     = trim($_POST['budget'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // ✅ Si "autre", on remplace la valeur "autre" par le texte saisi
    if ($type_site === 'autre' && !empty($autre_type)) {
        $type_site = $autre_type;
    }

    if ($nom && $email && $type_site && $description) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO site_web_formulaire_cn 
                (nom, email, telephone, type_site, budget, description, date_inscription)
                VALUES (:nom, :email, :telephone, :type_site, :budget, :description, NOW())
            ");
            $stmt->execute([
                'nom' => $nom,
                'email' => $email,
                'telephone' => $telephone,
                'type_site' => $type_site,
                'budget' => $budget,
                'description' => $description
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

            $mail->setFrom('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo - Besoin site web');
            $mail->addAddress('cartelplus-congo@cartelplus.tech', 'CartelPlus Congo Entreprise');

            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle demande de création de site web';
            $mail->Body = "
                <h2>Nouvelle demande reçue</h2>
                <p><strong>Nom :</strong> {$nom}</p>
                <p><strong>Email :</strong> {$email}</p>
                <p><strong>Téléphone :</strong> {$telephone}</p>
                <p><strong>Type de site :</strong> {$type_site}</p>
                <p><strong>Budget :</strong> {$budget}</p>
                <p><strong>Description :</strong><br>{$description}</p>
                <p><strong>Date :</strong> " . date('d/m/Y H:i') . "</p>
            ";

            $mail->send();

            header("Location: " . URL_INSCRIPTIONOKSITEWEB);
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
                    <h1 class="text-white">Démarrer mon projet web</h1>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Votre site web</li>
                        </ol>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <section class="section-padding pb-0 d-flex justify-content-center align-items-center">
        <div class="container">
            <div class="row">

                <div class="col-lg-12 col-12">
                    <form class="custom-form hero-form" action="" method="POST" role="form">
                        <h3 class="text-white mb-3">Parlez-nous de votre projet</h3>
                        <p class="text-light mb-4">Remplissez ce formulaire afin que notre équipe CartelPlus Congo puisse vous recontacter rapidement.</p>

                        <div class="row">
                            <!-- Nom complet -->
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-person custom-icon"></i></span>
                                    <input type="text" name="nom" id="nom" class="form-control" placeholder="Nom complet *" required>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-envelope custom-icon"></i></span>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Adresse e-mail *" required>
                                </div>
                            </div>

                            <!-- Téléphone -->
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-telephone custom-icon"></i></span>
                                    <input type="tel" name="telephone" id="telephone" class="form-control" placeholder="Numéro de téléphone (WhatsApp)">
                                </div>
                            </div>

                            <!-- Type de site -->
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-laptop custom-icon"></i></span>
                                    <select class="form-select form-control" name="type_site" id="type_site" required>
                                        <option value="">Type de site souhaité *</option>
                                        <option value="Site vitrine">Site vitrine</option>
                                        <option value="Site e-commerce">Site e-commerce</option>
                                        <option value="Site institutionnel">Site institutionnel</option>
                                        <option value="Portfolio professionnel">Portfolio professionnel</option>
                                        <option value="Blog">Blog</option>
                                        <option value="autre">Autre projet</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Champ caché qui apparaît si "Autre projet" -->
                            <div class="col-lg-12 col-12" id="autre_type_container" style="display: none;">
                                <div class="input-group mt-3">
                                    <span class="input-group-text"><i class="bi-pencil custom-icon"></i></span>
                                    <input type="text" name="autre_type_site" id="autre_type_site" class="form-control" placeholder="Précisez le type de site souhaité">
                                </div>
                            </div>

                            <!-- Budget estimé -->
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-cash custom-icon"></i></span>
                                    <select class="form-select form-control" name="budget" id="budget">
                                        <option value="">Budget estimé (optionnel)</option>
                                        <option value="100-500">$100 - $500</option>
                                        <option value="500-1000">$500 - $1 000</option>
                                        <option value="1000-2000">$1 000 - $2 000</option>
                                        <option value="plus2000">Plus de $2 000</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Description du projet -->
                            <div class="col-lg-12 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi-chat-square-text custom-icon"></i></span>
                                    <textarea name="description" id="description" class="form-control" rows="5" placeholder="Décrivez brièvement votre projet, vos objectifs et vos attentes *" required></textarea>
                                </div>
                            </div>

                            <!-- Bouton d'envoi -->
                            <div class="col-lg-12 col-12">
                                <button type="submit" class="form-control btn btn-primary mt-3">
                                    Envoyer ma demande
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                        const typeSelect = document.getElementById('type_site');
                        const autreContainer = document.getElementById('autre_type_container');
                        const autreInput = document.getElementById('autre_type_site');

                        typeSelect.addEventListener('change', function() {
                            if (this.value === 'autre') {
                                autreContainer.style.display = 'block';
                                autreInput.required = true;
                            } else {
                                autreContainer.style.display = 'none';
                                autreInput.required = false;
                            }
                        });
                    </script>

                </div>

            </div>
        </div>
    </section>

    <!-- Composant invitation QR page cn debut -->
    <?php require_once $invitationQRPath;  ?>
    <!-- Composant invitation QR page cn fin  -->
</main>
<?php require_once $footerPath; ?>
<?php
    // ----- DÉBUT DE LA LOGIQUE PHP -----
    require 'modeleHeader.php'; // Votre header
    require 'connectDb.php';   // Votre fichier de connexion

    // 1. Récupérer le slug de la formation depuis l'URL
    $slug = $_GET['slug'] ?? null;
    if (!$slug) {
        echo "<div class='container section'><p>Aucune formation spécifiée.</p></div>";
        require 'footerPn.php';
        exit;
    }

    // 2. Récupérer les informations de la formation sélectionnée
    $formation = null;
    try {
        $stmt = $pdo->prepare("SELECT id, titre, image_path, description_importance FROM formations WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $formation = $stmt->fetch();
    } catch (\PDOException $e) {
        // Gérer l'erreur de manière plus discrète en production
        error_log("Erreur de BDD: " . $e->getMessage());
        $formation = false;
    }

    // 3. Gérer le cas où la formation n'existe pas
    if (!$formation) {
        echo "<div class='container section'><p>Désolé, la formation demandée n'a pas été trouvée.</p></div>";
        require 'footerPn.php';
        exit;
    }

    // Définir les variables pour le header (si votre header est dynamique)
    $pageTitle = "Inscription : " . htmlspecialchars($formation['titre']);
    // ----- FIN DE LA LOGIQUE PHP -----
?>

<!-- Start Section d'inscription -->
<section class="blog-single section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-12">
                <div class="blog-single-main">
                    <div class="row">
                        <div class="col-12">
                            <div class="image">
                                <img src="<?php echo htmlspecialchars($formation['image_path']); ?>" alt="<?php echo htmlspecialchars($formation['titre']); ?>">
                            </div>
                            <div class="blog-detail">
                                <h2 class="blog-title">Inscription à la formation : <?php echo htmlspecialchars($formation['titre']); ?></h2>
                                <div class="content">
                                    <!-- Texte sur l'importance de la formation -->
                                    <p><strong>Pourquoi cette formation est essentielle pour vous ?</strong></p>
                                    <blockquote>
                                        <i class="fa fa-quote-left"></i>
                                        <?php echo nl2br(htmlspecialchars($formation['description_importance'])); // nl2br pour respecter les sauts de ligne ?>
                                    </blockquote>
                                    
                                    <hr>

                                    <!-- Formulaire d'inscription -->
                                    <h3>Je m'inscris !</h3>
                                    <p>Remplissez ce formulaire pour pré-réserver votre place. Notre équipe vous contactera dans les plus brefs délais pour finaliser les détails.</p>
                                    
                                    <form class="form" method="POST" action="traitement_inscription.php">
                                        <!-- Champ caché pour savoir à quelle formation l'utilisateur s'inscrit -->
                                        <input type="hidden" name="formation_id" value="<?php echo $formation['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Votre Nom complet<span>*</span></label>
                                                    <input type="text" name="nom_complet" placeholder="Ex: Jean Dupont" required="required">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-12">
                                                <div class="form-group">
                                                    <label>Votre Email<span>*</span></label>
                                                    <input type="email" name="email" placeholder="Ex: jean.dupont@email.com" required="required">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-12">
                                                <div class="form-group">
                                                    <label>Votre Numéro de téléphone<span>*</span></label>
                                                    <input type="text" name="telephone" placeholder="Ex: +33 6 12 34 56 78" required="required">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group button">
                                                    <button type="submit" class="btn">Envoyer ma demande d'inscription</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <!--/ End Form -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="main-sidebar">
                    <!-- Widget pour afficher les autres formations -->
                    <div class="single-widget recent-post">
                        <h3 class="title">Découvrir nos autres formations</h3>
                        <?php
                        try {
                            // Exclure la formation actuelle et prendre 4 autres formations
                            $stmt_others = $pdo->prepare("SELECT titre, slug, image_path FROM formations WHERE id != :current_id ORDER BY RAND() LIMIT 4");
                            $stmt_others->execute(['current_id' => $formation['id']]);
                            $other_formations = $stmt_others->fetchAll();
                        } catch (\PDOException $e) {
                            $other_formations = [];
                        }

                        foreach ($other_formations as $other):
                        ?>
                        <!-- Single Post -->
                        <div class="single-post">
                            <div class="image">
                                <a href="readdetailsformation.php?slug=<?php echo htmlspecialchars($other['slug']); ?>">
                                    <img src="<?php echo htmlspecialchars($other['image_path']); ?>" alt="<?php echo htmlspecialchars($other['titre']); ?>">
                                </a>
                            </div>
                            <div class="content">
                                <h5><a href="readdetailsformation.php?slug=<?php echo htmlspecialchars($other['slug']); ?>"><?php echo htmlspecialchars($other['titre']); ?></a></h5>
                            </div>
                        </div>
                        <!-- End Single Post -->
                        <?php endforeach; ?>
                    </div>
                    <!--/ End Widget -->
                </div>
            </div>
        </div>
    </div>
</section>
<!--/ End Section d'inscription -->

<?php require 'footerPn.php'; ?>
<?php
    require 'headPn.php';
    require 'headerindex.php';  
    require 'connectDb.php'; // Notre fichier de connexion PDO
    
    try {
        // Récupérer les articles de blog, les plus récents en premier
        $stmt = $pdo->query("SELECT id, title, slug, excerpt, image_path, publication_date FROM newblog ORDER BY publication_date DESC LIMIT 3");
        $articles = $stmt->fetchAll();
    } catch (\PDOException $e) {
        // Gérer l'erreur, par exemple afficher un message
        echo "Erreur lors de la récupération des articles : " . $e->getMessage();
        $articles = []; // Initialiser comme tableau vide pour éviter les erreurs plus loin
    }
?>


    <!-- Start Small Banner  -->
    
    <section class="small-banner section">
        <div class="container-fluid">
            <div class="row">
                <!-- Single Banner  -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="single-banner">
                        <img src="images/images_plan/ret32.jpg" alt="Découverte et Prise en Main de Binance avec cartelplus">
                        <div class="content">
                            <p style="color:white">Découverte et Prise en Main de Binance</p>
                            <h3 style="color:white">Présentation de Binance, ses atouts. Aide pas à pas pour l'inscription, KYC et éviter les erreurs courantes.</h3>
                            <a href="cryptosupport.php" style="color:white">Découvrez maintenant</a>
                        </div>
                    </div>
                </div>
                <!-- /End Single Banner  -->
                <!-- Single Banner  -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="single-banner">
                        <img src="images/conception/concept2.jpg" alt="#">
                        <div class="content">
                            <p>Pour vos site web professionnel</p>
                            <h3>Notre équipe d'experts <br>prend soin d'étudier <br>votre projet web en détail.</h3>
                            <a href="concept-dev.php">Démarrez ici</a>
                        </div>
                    </div>
                </div>
                <!-- /End Single Banner  -->
                <!-- Single Banner  -->
                <div class="col-lg-4 col-12">
                    <div class="single-banner tab-height">
                        <img src="images/images_plan//decriv.jpg" alt="image de la section conception">
                        <div class="content">
                            <p style="color:white">Décrivez votre besoin en développement</p>
                            <h3 style="color:white">Veuillez remplir ce formulaire pour nous expliquer en détail votre projet ou le besoin spécifique que vous avez. </h3>
                            <a href="decrive-dev.php" style="color:white">Découvrez maintenant</a>
                        </div>
                    </div>
                </div>
                <!-- /End Single Banner  -->
            </div>
        </div>
    </section>
    <!-- End Small Banner -->
    
    <!-- Nos realisations en images et vidéos -->
<div class="product-area most-popular section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h2>Nos actions en images et vidéos, semaine après semaine</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="owl-carousel popular-slider">
                    <?php
                    $directory = 'images/realisations/';
                    $files = scandir($directory);
                    $files = array_diff($files, array('.', '..'));

                    foreach ($files as $file) {
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            // Affichage d'une image
                            echo '
                            <div class="single-product">
                                <div class="product-img">
                                    <a href="#">
                                        <img class="default-img" src="' . $directory . $file . '" alt="#">
                                        <img class="hover-img" src="' . $directory . $file . '" alt="#">
                                    </a>
                                </div>
                            </div>';
                        } elseif ($extension === 'mp4') {
                            // Affichage d'une vidéo
                            echo '
                            <div class="single-product">
                                <div class="product-img">
                                    <video width="100%" height="auto" controls>
                                        <source src="' . $directory . $file . '" type="video/mp4">
                                        Votre navigateur ne supporte pas la lecture des vidéos.
                                    </video>
                                </div>
                            </div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- fin de nos realisation en images et vidéos -->

    
    <!-- Espace presentation video et images libre de droit  -->
    <section class="midium-banner">
        <div class="container">
            <div class="row">
                <!-- Single Banner  -->
                <div class="col-lg-6 col-md-6 col-12">
                    <div class="single-banner">
                        <video autoplay loop muted class="">
                            <source src="images/libreDroit/videos/v01.mp4" type="video/mp4">
                        </video>
                        <div class="content">
                            <p>Notre collection des vidéos libre de droit</p>
                            <h3 class="" style="color:#eee;">notre banque <br>Des vidéos<span> 100%</span>Gratuite</h3>
                            <a href="videofree.php">Consultez-ici</a>
                        </div>
                    </div>
                </div>
                <!-- /End Single Banner  -->
                <!-- Single Banner  -->
                <div class="col-lg-6 col-md-6 col-12">
                    <div class="single-banner">
                        <img src="images/libreDroit/images/be1.jpg" alt="#">
                        <div class="content">
                            <p>Notre collection des images libre de droit</p>
                            <h3 class="" style="color:#eee;">Vous téléchargez <br> Des images <span>100%</span><br>Gratuite</h3>
                            <a href="imagefree.php" class="btn">Cliquez ici</a>
                        </div>
                    </div>
                </div>
                <!-- /End Single Banner  -->
            </div>
        </div>
    </section>  <br>
    <!-- fin de l'espace video et images libre de droit -->

 

    




    

    <!-- Presentation de nos formations  -->
<section class="shop-home-list section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h2>Nos Formations</h2>
                </div>
            </div>
        </div>
        <div class="row">
            
            <?php
            try {
                // On récupère toutes les formations de la base de données
                $stmt = $pdo->query("SELECT titre, image_path, slug FROM formations ORDER BY id");
                $formations = $stmt->fetchAll();

                // On boucle sur chaque formation pour l'afficher
                foreach ($formations as $formation) {
            ?>
                    <div class="col-lg-4 col-md-6 col-12">
                        <!-- Start Single List  -->
                        <div class="single-list">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="list-image overlay">
                                        <img src="<?php echo htmlspecialchars($formation['image_path']); ?>" alt="<?php echo htmlspecialchars($formation['titre']); ?>">
                                        
                                        <!-- Le lien pointe maintenant vers la page de détails avec le slug de la formation -->
                                        <a href="readdetailsformation.php?slug=<?php echo htmlspecialchars($formation['slug']); ?>" class="buy"><i class="fa fa-shopping-bag"></i></a>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12 no-padding">
                                    <div class="content">
                                        <h5 class="title"><a href="readdetailsformation.php?slug=<?php echo htmlspecialchars($formation['slug']); ?>"><?php echo htmlspecialchars($formation['titre']); ?></a></h5>
                                        
                                        <!-- Le lien "S'inscrire" pointe aussi vers la page de détails -->
                                        <p class="price with-discount">
                                            <a href="readdetailsformation.php?slug=<?php echo htmlspecialchars($formation['slug']); ?>" style="color: #f7f7f7; font-weight: bold;">S'Inscrire</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Single List  -->
                    </div>
            <?php
                } // Fin de la boucle
            } catch (\PDOException $e) {
                echo "Erreur: " . $e->getMessage();
            }
            ?>
        </div>
    </div>
</section>
<!-- Fin de la liste de nos formations  -->
    
    
    

    <!-- Start Cowndown Area -->
    <section class="cown-down">
        <div class="section-inner ">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6 col-12 padding-right">
                        <div class="image">
                            <img src="images/hebergement/offre12.jpg" alt="image d'un hébergeur web">
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 padding-left">
                        <div class="content">
                            <div class="heading-block">
                                <p class="small-title">Offre exceptionnelle : hébergement web à portée de main</p>
                                <h3 class="title">Faites développer votre site web sur mesure par notre équipe de professionnels.</h3>
                                <p class="text">Bénéficiez d'un hébergement web pendant un an, d'un nom de domaine offert, ainsi que de la configuration d'adresses e-mail professionnelles.</p>
                              
                                <h1 class="price">Contactez-nous dès maintenant : +243 814 926 220</h1>
                                <!-- <div class="coming-time">
                                    <div class="clearfix" data-countdown="2021/02/30"></div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /End Cowndown Area -->

    <!-- Start Shop Blog  -->
<section class="shop-blog section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h2>De notre blog</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $article): ?>
                    <div class="col-lg-4 col-md-6 col-12">
                        <!-- Start Single Blog  -->
                        <div class="shop-single-blog">
                            <img src="<?= htmlspecialchars($article['image_path']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                            <div class="content">
                                <p class="date">
                                    <?php
                                        // Formatage de la date.
                                        // Assurez-vous que $article['publication_date'] est dans un format que DateTime peut comprendre (ex: YYYY-MM-DD HH:MM:SS)
                                        $date = new DateTime($article['publication_date']);
                                        
                                        // Option 1: Utilisation de IntlDateFormatter (recommandé si disponible)
                                        // Décommentez la création de $formatter dans la partie PHP ci-dessus
                                        // echo $formatter->format($date);

                                        // Option 2: Formatage manuel simple (si IntlDateFormatter n'est pas disponible ou pour un format spécifique)
                                        // Vous devrez peut-être ajuster cela pour obtenir exactement "juin" au lieu de "June", etc.
                                        // Pour cela, setlocale et strftime, ou un tableau de traduction des mois/jours est nécessaire.
                                        // Exemple simple avec les noms de mois/jours en anglais:
                                        // echo $date->format('d F, Y. l');

                                        // Exemple pour obtenir un format similaire à "24 juin , 2023. Samedi" en français (nécessite que la locale soit bien configurée sur le serveur)
                                        // setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra'); // À mettre au début de votre script PHP
                                        // echo strftime('%d %B, %Y. %A', $date->getTimestamp());
                                        
                                        // Solution la plus simple pour le format donné, si Intl n'est pas utilisable et setlocale est capricieux :
                                        $mois_fr = ["", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"];
                                        $jours_fr = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                                        echo $date->format('d') . ' ' . $mois_fr[(int)$date->format('n')] . ', ' . $date->format('Y') . '. ' . $jours_fr[(int)$date->format('w')];
                                    ?>
                                </p>
                                <a href="readblog.php?slug=<?= htmlspecialchars($article['slug']) ?>" class="title"><?= htmlspecialchars($article['title']) ?></a>
                                <a href="readblog.php?slug=<?= htmlspecialchars($article['slug']) ?>" class="more-btn">Continuer la lecture</a>
                            </div>
                        </div>
                        <!-- End Single Blog  -->
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p>Aucun article de blog à afficher pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- End Shop Blog  -->

    <!-- Start Shop Services Area -->
    <section class="shop-services section home">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <!-- Start Single Service -->
                    <div class="single-service">
                        <i class="ti-rocket"></i>
                        <h4>Salutions adaptées</h4>
                        <p>Nous comprenons les réalités locales et développons des outils et services adaptés aux besoins des entreprises et particuliers</p>
                    </div>
                    <!-- End Single Service -->
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <!-- Start Single Service -->
                    <div class="single-service">
                        <i class="ti-reload"></i>
                        <h4>Accompagnement personnalisé </h4>
                        <p>Nous travaillons main dans la main avec nos clients, en proposant un accompagnement sur mesure pour chaque projet.</p>
                    </div>
                    <!-- End Single Service -->
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <!-- Start Single Service -->
                    <div class="single-service">
                        <i class="ti-lock"></i>
                        <h4>Expertise en formation</h4>
                        <p>Nous formons la nouvelle génération de talents africains avec des méthodes modernes et des outils adaptés aux évolutions</p>
                    </div>
                    <!-- End Single Service -->
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <!-- Start Single Service -->
                    <div class="single-service">
                        <i class="ti-tag"></i>
                        <h4>Accessibilité pour tous</h4>
                        <p>Nos solutions et accessibles à tous, quels que soient les niveaux de compétence.</p>
                    </div>
                    <!-- End Single Service -->
                </div>
            </div>
        </div>
    </section>
    <!-- End Shop Services Area -->

    <!-- Start Shop Newsletter  -->
    <section class="shop-newsletter section">
        <div class="container">
            <div class="inner-top">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-12">
                        <!-- Start Newsletter Inner -->
                        <div class="inner">
                            <h4>BULLETIN</h4>
                            <p> Abonnez-vous à notre newsletter et bénéficiez de <span>10%</span> de réduction sur votre premier achat</p>
                            <form action="subscribe.php" method="post" class="newsletter-inner">
                                <input name="EMAIL" placeholder="Votre adresse e-mail" required="" type="email">
                                <button class="btn" type="submit">S'abonner</button>
                            </form>
                        </div>
                        <!-- End Newsletter Inner -->
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Shop Newsletter -->

















    <!-- Boite Modal produits -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="ti-close" aria-hidden="true"></span></button>
                </div>
                <div class="modal-body">
                    <div class="row no-gutters">
                        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                            <!-- Product Slider -->
                            <div class="product-gallery">
                                <div class="quickview-slider-active">
                                    <div class="single-slider">
                                        <img src="images/boutique/chemises/c1.jpg" alt="modèle chemise 4 poches ">
                                    </div>
                                    <div class="single-slider">
                                        <img src="images/boutique/chemises/c2.jpg" alt="image modèle du chemise 4 poches">
                                    </div>
                                    <div class="single-slider">
                                        <img src="images/boutique/chemises/c3.jpg" alt="image modèle du chemise 4 poches">
                                    </div>
                                    <div class="single-slider">
                                        <img src="images/boutique/chemises/c4.jpg" alt="image modèle du chemise 4 poches">
                                    </div>
                                </div>
                            </div>
                            <!-- End Product slider -->
                        </div>
                        <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                            <div class="quickview-content">
                                <h2>Chemise 4 poches dresser</h2>
                                <div class="quickview-ratting-review">
                                    <div class="quickview-ratting-wrap">
                                        <div class="quickview-ratting">
                                            <i class="yellow fa fa-star"></i>
                                            <i class="yellow fa fa-star"></i>
                                            <i class="yellow fa fa-star"></i>
                                            <i class="yellow fa fa-star"></i>
                                            <i class="fa fa-star"></i>
                                        </div>
                                        <a href="#"> De notes chez nos clients</a>
                                    </div>
                                    <div class="quickview-stock">
                                        <span><i class="fa fa-check-circle-o"></i> En stock</span>
                                    </div>
                                </div>
                                <h3>25$</h3>
                                <div class="quickview-peragraph">
                                    <p>Chemise 4 poches. De bonne qualité dresser pour vous ! <br> Passez maintenant votre commande pour être livré sans frais de livraison ! </p>
                                </div>
                                <div class="size">
                                    <div class="row">
                                        <div class="col-lg-6 col-12">
                                            <h5 class="title">Size</h5>
                                            <select>
													<option selected="selected">s</option>
													<option>m</option>
													<option>l</option>
													<option>xl</option>
												</select>
                                        </div>
                                        <div class="col-lg-6 col-12">
                                            <h5 class="title">Color</h5>
                                            <select>
													<option selected="selected">orange</option>
													<option>purple</option>
													<option>black</option>
													<option>pink</option>
												</select>
                                        </div>
                                    </div>
                                </div>
                                <div class="quantity">
                                    <!-- Input Order -->
                                    <div class="input-group">
                                        <div class="button minus">
                                            <button type="button" class="btn btn-primary btn-number" disabled="disabled" data-type="minus" data-field="quant[1]">
													<i class="ti-minus"></i>
												</button>
                                        </div>
                                        <input type="text" name="quant[1]" class="input-number" data-min="1" data-max="1000" value="1">
                                        <div class="button plus">
                                            <button type="button" class="btn btn-primary btn-number" data-type="plus" data-field="quant[1]">
													<i class="ti-plus"></i>
												</button>
                                        </div>
                                    </div>
                                    <!--/ End Input Order -->
                                </div>
                                <div class="add-to-cart">
                                    <a href="#" class="btn">Payer maintenant ! </a>
                                    <a href="#" class="btn min"><i class="ti-heart"></i></a>
                                    <a href="#" class="btn min"><i class="fa fa-compress"></i></a>
                                </div>
                                <div class="default-social">
                                    <h4 class="share-now">Partagez</h4>
                                    <ul>
                                        <li><a class="facebook" href="#"><i class="fa fa-facebook"></i></a></li>
                                        <li><a class="twitter" href="#"><i class="fa fa-twitter"></i></a></li>
                                        <li><a class="youtube" href="#"><i class="fa fa-pinterest-p"></i></a></li>
                                        <li><a class="dribbble" href="#"><i class="fa fa-google-plus"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal end -->
<?php require 'footerPn.php';  ?>
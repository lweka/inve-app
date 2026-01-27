<?php
    require_once __DIR__ . '/configUrlcn.php'; // __DIR__ = dossier racine
    require_once __DIR__ . '/defConstLiens.php'; // __DIR__ = dossier racine

    require_once $dataDbConnect;
 
?>

<!-- Composant header  page cn debut -->
    <?php require_once $headerPath;  ?>
<!-- Composant header page cn fin  -->
        <main>
            <section class="hero-section d-flex justify-content-center align-items-center">
                <div class="section-overlay"></div>

                <div class="container">
                    <div class="row">

                        <div class="col-lg-6 col-12 mb-5 mb-lg-0">
                            <div class="hero-section-text mt-5">
                                <h3 class="hero-title text-white mt-4 mb-4">Unir technologie, savoir-faire et impact social  <br> pour construire un avenir numérique durable et inclusif.</h3>
                                <a href="#categories-section" class="custom-btn custom-border-btn btn">Explorer nos solutions</a>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <form class="custom-form hero-form" action="#" method="get" role="form">
                                <h3 class="text-white mb-3">Trouvez la solution numérique adaptée à vos besoins</h3>

                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="input-group">
                                            <span class="input-group-text" id="basic-addon1"><i class="bi-person custom-icon"></i></span>

                                            <input type="text" name="job-title" id="job-title" class="form-control" placeholder="Type de projet" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="input-group">
                                            <span class="input-group-text" id="basic-addon2"><i class="bi-geo-alt custom-icon"></i></span>

                                            <input type="text" name="job-location" id="job-location" class="form-control" placeholder="Domaine" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-12">
                                        <button type="submit" class="form-control">
                                            Lancer la recherche
                                        </button>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex flex-wrap align-items-center mt-4 mt-lg-0">
                                            <span class="text-white mb-lg-0 mb-md-0 me-2">Mots-clés populaires:</span>
                                            <div>
                                                <a href="job-listings.html" class="badge">Banque d'images et vidéos</a>
                                                <a href="job-listings.html" class="badge">Nos prestations</a>
                                                <a href="job-listings.html" class="badge">Formation professionnelle</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </section>


            <section class="categories-section section-padding" id="categories-section">
                <div class="container">
                    <div class="row justify-content-center align-items-center">

                        <div class="col-lg-12 col-12 text-center">
                            <h4 class="mb-5">Trouvez la solution numérique <span>adaptée à vos besoins</span></h4>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-window"></i>
                                
                                    <small class="categories-block-title">Web design</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">320</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-twitch"></i>
                                
                                    <small class="categories-block-title">Marketing Digital</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">180</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-play-circle-fill"></i>
                                
                                    <small class="categories-block-title">CartelPlus TV</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">340</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-globe"></i>
                                
                                    <small class="categories-block-title">Websites</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">140</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="categories-block">
                                <a href="#" class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <i class="categories-icon bi-people"></i>
                                
                                    <small class="categories-block-title">Customer Support</small>

                                    <div class="categories-block-number d-flex flex-column justify-content-center align-items-center">
                                        <span class="categories-block-number-text">84</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section class="about-section">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-3 col-12">
                            <div class="about-image-wrap custom-border-radius-start">
                                <img src="<?= IMG_DIR ?>/professional-asian-businesswoman-gray-blazer.png" class="about-image custom-border-radius-start img-fluid" alt="">

                                <div class="about-info">
                                    <h4 class="text-white mb-0 me-2">Jonathan lweka</h4>
                                    <p class="text-white mb-0">CEO</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="custom-text-block">
                                <h2 class="text-white mb-2">Cartelplus Congo</h2>

                                <p class="text-white">CartelPlus Congo, nous sommes une entreprise numérique spécialisée dans <strong>La prestation de services numériques,</strong> La <strong>formation professionnelle</strong>, Le <strong>développement informatique</strong>, L<strong>'Accompagnement dans le domaine de la crypto-monnaie</strong> </p>

                                <div class="custom-border-btn-wrap d-flex align-items-center mt-5">
                                    <a href="about.html" class="custom-btn custom-border-btn btn me-4">Explorer nos solutions</a>

                                    <a href="#job-section" class="custom-link smoothscroll">Notre catalogue de formation</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-12">
                            <div class="instagram-block">
                                <img src="<?= IMG_DIR ?>/horizontal-shot-happy-mixed-race-females.jpg" class="about-image custom-border-radius-end img-fluid" alt="">

                                <div class="instagram-block-text">
                                    <a href="https://instagram.com/" class="custom-btn btn">
                                        <i class="bi-instagram"></i>
                                        @Cartelplus
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Nos prestation debut  -->
             <?php require_once $prestationServicePath;  ?>
            <!-- Nos prestattion end  -->

            <!-- nos formations compo début -->
            <?php require_once $formationsCompoPath;  ?>
            <!-- Nos formations compo fin  -->
            
            <!-- Nos citations en entreprise -->
             <?php require_once $citationsEntreprisePath;  ?>
            <!-- Nos citations en entreprise -->


            

        <!-- Composant footer page cn debut -->
         <?php require_once $footerPath;  ?>
        <!-- Composant footer page cn fin  -->


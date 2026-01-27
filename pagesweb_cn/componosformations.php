<?php
// pagesweb_cn/page1.php
    require_once __DIR__ . '/../configUrlcn.php'; // remonte d'un niveau vers cartelpluscn/
    require_once __DIR__ . '/../defConstLiens.php'; // __DIR__ = dossier racine


    // Requête : récupérer les 6 dernières formations
    $sql = "SELECT id, titre, image_path, slug, description_importance 
            FROM formations 
            ORDER BY id ASC 
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $formations = $stmt->fetchAll();
   
?>

<section class="job-section recent-jobs-section section-padding">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-lg-6 col-12 mb-4">
                <h2>Opportunités récentes</h2>

                <p><strong>Plus de 10 000 postes ouverts dans le secteur du numérique et des technologies.</strong> Notre mission est de préparer les talents d'aujourd'hui et de demain grâce à des formations pratiques et adaptées aux besoins du marché</p>
            </div>
            <div class="clearfix"></div>


           <?php foreach ($formations as $formation): ?>
            
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="job-thumb job-thumb-box">
                        <div class="job-image-box-wrap">
                            <a href="<?php echo URL_DETAILFORMATIONS; ?>?slug=<?php echo htmlspecialchars($formation['slug']); ?>">
                                <img src="<?= BASE_URL . htmlspecialchars($formation['image_path']) ?>" class="job-image img-fluid" alt="<?= htmlspecialchars($formation['titre']) ?>">
                            </a>
                            <div class="job-image-box-wrap-info d-flex align-items-center">
                                <p class="mb-0">
                                    <a href="<?php echo URL_DETAILFORMATIONS; ?>?slug=<?php echo htmlspecialchars($formation['slug']); ?>" 
                                    class="badge badge-level">
                                    <?= htmlspecialchars($formation['titre']) ?>
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="job-body">
                            <h4 class="job-title">
                                <a href="<?php echo URL_DETAILFORMATIONS; ?>?slug=<?php echo htmlspecialchars($formation['slug']); ?>" 
                                class="job-title-link">
                                <?= htmlspecialchars($formation['titre']) ?>
                                </a>
                            </h4>
                            <p class="mb-3">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($formation['description_importance'], 0, 100, "..."))) ?>
                            </p>
                            <div class="d-flex align-items-center border-top pt-3">
                                <a href="<?php echo URL_DETAILFORMATIONS; ?>?slug=<?php echo htmlspecialchars($formation['slug']); ?>" 
                                class="custom-btn btn ms-auto">
                                Suivre la formation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>
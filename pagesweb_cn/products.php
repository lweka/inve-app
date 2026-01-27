<?php
// pagesweb_cn/products.php

require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';

//verifier si admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){
    header("Location: ".PARSE_CONNECT."?role=admin");
    exit;
}

$house_id = (int)($_GET['house_id'] ?? 0);
if($house_id <= 0){
    header("Location: houses.php");
    exit;
}


// 🔁 COMPATIBILITÉ ANCIENS LIENS ?err=...
if (isset($_GET['err']) && empty($_SESSION['flash_error'])) {
    try {
        $_SESSION['flash_error'] = json_decode(
            urldecode($_GET['err']),
            true
        );
    } catch (Exception $e) {
        $_SESSION['flash_error'] = ["Une erreur est survenue."];
    }
}


// verification si la maison à un produit configuré
$stmt = $pdo->prepare("
  SELECT COUNT(*) 
  FROM exchange_rate 
  WHERE house_id = ?
");
$stmt->execute([$house_id]);
$hasRate = (int)$stmt->fetchColumn();

// récupérer house_id
$house_id = intval($_GET['house_id'] ?? 0);
if($house_id <= 0){
    header("Location: houses.php");
    exit;
}

/* ===== Maison ===== */

// fetch house
$stmt = $pdo->prepare("SELECT * FROM houses WHERE id = ?");
$stmt->execute([$house_id]);
$house = $stmt->fetch();
if(!$house) { header("Location: houses.php"); exit; }





// fetch products + stock
$stmt = $pdo->prepare("
SELECT 
    p.id,
    p.name,
    p.description,
    p.buy_price,
    p.sell_price,
    p.sell_currency,
    p.usd_rate_at_creation,
    p.is_active,
    IFNULL(SUM(hs.qty),0) AS stock_qty
FROM products p
LEFT JOIN house_stock hs 
    ON hs.product_id = p.id AND hs.house_id = ?
WHERE p.house_id = ?
GROUP BY p.id
ORDER BY p.name ASC
");
$stmt->execute([$house_id, $house_id]);
$products = $stmt->fetchAll();

// include header if exists
if(isset($headerPath) && is_file($headerPath)) require_once $headerPath;

// charger taux USD

$usd_rate = $pdo->query("SELECT usd_rate FROM exchange_rate WHERE id = 1")->fetchColumn();
if(!$usd_rate || $usd_rate <= 0){
    $usd_rate = 1; // sécurité anti division par zéro
}

?>




<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Produits — <?=htmlspecialchars($house['name'])?></h4>
      <div class="small text-muted">Gestion des produits et du stock</div>
    </div>
    <div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fa fa-plus me-1"></i> Ajouter produit
      </button>
      <a href="<?= EXCHANGE_RATE_MANAGER ?>?house_id=<?= (int)$house_id ?>" class="btn btn-secondary">TAUX USD</a>
      <a href="<?=PRODUCTS_ALL_STORY?>" class="btn btn-primary">PRODUIT HISTORIQUE COMPLET</a>
      <a href="<?=PRODUCTS_LOW_STOCK?>" class="btn btn-primary">PRODUIT EN ALERTE</a>
      <a href="<?=HOUSES_MANAGE?>" class="btn btn-secondary"> ← Retour aux maisons</a>
    </div>
  </div>

  <div class="mb-3">
    <input id="searchInput" class="form-control" placeholder="Rechercher un produit (nom) ...">
  </div>

  <div id="productsGrid" class="row">
    <?php foreach ($products as $p):

        $rate = max(1, (float)$p['usd_rate_at_creation']);
        $stockLow = ((int)$p['stock_qty'] < 5);

        if ($p['sell_currency'] === 'USD') {
            $buyLabel  = number_format($p['buy_price'], 2) . ' USD';
            $sellLabel = number_format($p['sell_price'], 2) . ' USD';
            $convert   = number_format($p['sell_price'] * $rate, 0) . ' CDF';
        } else {
            $buyLabel  = number_format($p['buy_price'], 0) . ' CDF';
            $sellLabel = number_format($p['sell_price'], 0) . ' CDF';
            $convert   = number_format($p['sell_price'] / $rate, 2) . ' USD';
        }
    ?>
      <div class="col-md-4 mb-3 prod-row" data-name="<?=htmlspecialchars(strtolower($p['name']))?>">
        <div class="card-prod">
          <div class="d-flex justify-content-between">
            <strong><?=htmlspecialchars($p['name'])?></strong>
            <div>
              <button class="btn btn-sm btn-outline-info"
                onclick="location.href='./product_history.php?product_id=<?= $p['id'] ?>&house_id=<?= $house_id ?>'">
                Historique
              </button>
              <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal(<?= $p['id'] ?>)">
                Modifier
              </button>
            </div>
          </div>

          <?php
        $mainPrice = number_format((float)$p['sell_price'],2).' '.$p['sell_currency'];

        $rate = (float)$p['usd_rate_at_creation'];

          if ($rate <= 0) {
              $rate = 1; // sécurité absolue
          }

          if ($p['sell_currency'] === 'USD') {
              $mainPrice = number_format($p['sell_price'],2).' USD';
              $secondaryPrice = number_format($p['sell_price'] * $rate,0).' CDF';
          } else {
              $mainPrice = number_format($p['sell_price'],0).' CDF';
              $secondaryPrice = number_format($p['sell_price'] / $rate,2).' USD';
          }

          ?>

          
       <div class="small-muted mt-2">
          Achat : <strong><?= $buyLabel ?></strong><br>
          Vente : <strong><?= $sellLabel ?></strong><br>
          <small class="text-muted">
              ≈ <?= $convert ?> (taux: <?= number_format($rate, 2) ?>)
          </small>
      </div>
         
          <div class="small-muted">Stock maison: <strong id="stock_<?=$p['id']?>"><?=intval($p['stock_qty'])?></strong></div>
          <!-- card- qui doit s'afficher si le stock est bas et disparait automatiquement si on met à jour le stock -->
          <div class="card-prod <?= $stockLow ? 'border border-danger' : '' ?>">
            <div class="d-flex justify-content-between">
              <!-- <strong><?=htmlspecialchars($p['name'])?></strong> -->
              <?php if($stockLow): ?>
                <span class="badge bg-danger">Stock bas</span>
              <?php endif; ?>
            </div>
          </div>

          
          <div class="mt-2 small-muted"><?=nl2br(htmlspecialchars($p['description']))?></div>

          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-outline-success btn-sm w-50" onclick="openStockModal(<?= $p['id'] ?>,'in')">
              <i class="fa fa-arrow-down"></i> Entrée
            </button>
            <button class="btn btn-outline-warning btn-sm w-50" onclick="openStockModal(<?= $p['id'] ?>,'transfer')">
              <i class="fa fa-arrow-up"></i> Sortie
            </button>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-outline-secondary btn-sm w-50" onclick="toggleProduct(<?= $p['id'] ?>)">
              <?= $p['is_active'] ? 'Désactiver' : 'Activer' ?>
            </button>
            <button class="btn btn-outline-danger btn-sm w-50"
              onclick="deleteProduct(<?= $p['id'] ?>,'<?= addslashes(htmlspecialchars($p['name'])) ?>')">
              Supprimer
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>


</div>

<!-- Modal: add product -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="product_add.php">

        <div class="modal-header">
          <h5 class="modal-title">Ajouter un produit</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="house_id" value="<?= $house_id ?>">

          <div class="mb-3">
            <label class="form-label">Nom du produit</label>
            <input name="name" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-4">
              <label class="form-label">Devise de vente</label>
              <select name="sell_currency" class="form-select" required>
                <option value="CDF">CDF</option>
                <option value="USD">USD</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Prix d’achat</label>
              <input name="buy_price" type="number" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Prix de vente</label>
              <input name="sell_price" type="number" step="0.01" class="form-control" required>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>

          <div class="alert alert-info mt-3 small">
            💡 Les prix sont stockés en CDF.  
            Si vous choisissez USD, la conversion est faite automatiquement avec le taux de la maison.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary">Créer le produit</button>
        </div>

      </form>
    </div>
  </div>
</div>


<!-- Modal: edit product -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <form id="editProductForm">

        <div class="modal-header">
          <h5 class="modal-title" id="editModalTitle">Modifier produit</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="product_id" id="edit_product_id">

          <div class="mb-3">
            <label class="form-label">Nom du produit</label>
            <input name="name" id="edit_name" class="form-control" required>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="form-label" id="label_buy_price">Prix d’achat</label>
              <input name="buy_price" id="edit_buy_price" type="number" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label" id="label_sell_price">Prix de vente</label>
              <input name="sell_price" id="edit_sell_price" type="number" step="0.01" class="form-control" required>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary">Enregistrer</button>
        </div>

      </form>

    </div>
  </div>
</div>


<!-- Modal: stock update -->
<div class="modal fade" id="stockModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="stockForm">
        <div class="modal-header">
          <h5 class="modal-title" id="stockModalTitle">Mise à jour stock</h5>
          <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="product_id" id="stock_product_id">
          <input type="hidden" name="house_id" value="<?= $house_id ?>">
          <input type="hidden" name="type" id="stock_type">

          <div class="mb-3">
            <label>Quantité</label>
            <input name="qty" id="stock_qty" type="number" class="form-control" min="1">
          </div>




          <div class="mb-3">
            <label class="form-label">Vendeur</label>
            <select name="agent_id" id="stock_agent_id" class="form-select">
              <option value="">— Sélectionner un vendeur —</option>
              <?php
              $agents = $pdo->query("
                SELECT id, fullname 
                FROM agents 
                WHERE house_id = $house_id AND status='active'
              ")->fetchAll();
              foreach($agents as $a):
              ?>
                <option value="<?= $a['id'] ?>">
                  <?= htmlspecialchars($a['fullname']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          





          <div class="mb-3">
            <label>Note (optionnel)</label>
            <input name="note" id="stock_note" class="form-control">
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-light" type="button" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary" type="submit">Valider sortie</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- MODAL ERREUR si le taux ne pas definit -->
<div class="modal fade" id="errorModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle"></i> Action impossible
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="errorModalBody"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <a href="<?= EXCHANGE_RATE_MANAGER ?>?house_id=<?= (int)$house_id ?>"
           class="btn btn-primary">
          Configurer le taux USD
        </a>
      </div>
    </div>
  </div>
</div>


    <?php if(!empty($_SESSION['flash_error'])): ?>
      <script>
      document.addEventListener('DOMContentLoaded', function(){

        const errors = <?= json_encode($_SESSION['flash_error']) ?>;

        document.getElementById('errorModalBody').innerHTML =
          '<ul class="mb-0">' +
          errors.map(e => `<li>${e}</li>`).join('') +
          '</ul>';

        new bootstrap.Modal(
          document.getElementById('errorModal')
        ).show();

      });
      </script>
      <?php unset($_SESSION['flash_error']); endif; ?>




<!-- MODAL PREMIÈRE CONFIGURATION -->
<div class="modal fade" id="firstConfigModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-gear"></i> Configuration requise
        </h5>
      </div>
      <div class="modal-body">
        <p class="mb-2">
          Cette maison n’a pas encore de <strong>taux USD</strong> configuré.
        </p>
        <p class="text-muted">
          Le taux USD est indispensable pour :
        </p>
        <ul>
          <li>Créer des produits</li>
          <li>Gérer les conversions USD ↔ CDF</li>
          <li>Calculer correctement les marges</li>
        </ul>
      </div>
      <div class="modal-footer">
        <a href="<?= EXCHANGE_RATE_MANAGER ?>?house_id=<?= (int)$house_id ?>"
           class="btn btn-success">
          <i class="bi bi-currency-exchange"></i>
          Configurer maintenant
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Affichage une seule fois -->
<?php if($hasRate === 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const key = 'first_rate_notice_house_<?= $house_id ?>';
  if(!localStorage.getItem(key)){
    new bootstrap.Modal(
      document.getElementById('firstConfigModal')
    ).show();
    localStorage.setItem(key, 'shown');
  }
});
</script>
<?php endif; ?>





<script>
  // pour la modification produit
  function openEditModal(productId){

    fetch('product_edit.php?get=1&product_id=' + productId)
      .then(r => r.json())
      .then(j => {

        if(!j.ok){
          alert(j.message || 'Erreur');
          return;
        }

        const p = j.product;

        document.getElementById('edit_product_id').value = p.id;
        document.getElementById('edit_name').value = p.name;
        document.getElementById('edit_buy_price').value = p.buy_price;
        document.getElementById('edit_sell_price').value = p.sell_price;
        document.getElementById('edit_description').value = p.description || '';

        // labels dynamiques
        document.getElementById('editModalTitle').textContent =
          'Modifier produit ' + p.sell_currency;

        document.getElementById('label_buy_price').textContent =
          'Prix d’achat (' + p.sell_currency + ')';

        document.getElementById('label_sell_price').textContent =
          'Prix de vente (' + p.sell_currency + ')';

        new bootstrap.Modal(
          document.getElementById('editProductModal')
        ).show();
      })
      .catch(() => alert('Erreur réseau'));
  }



  document.getElementById('editProductForm').addEventListener('submit', function(e){
  e.preventDefault(); // 🔥 STOP soumission HTML

  const form = this;
  const data = new FormData(form);

  fetch('product_edit.php', {
    method: 'POST',
    body: data
  })
  .then(r => {
    if(!r.ok) throw new Error('Erreur serveur');
    return r.text();
  })
  .then(() => {
    // fermer la modale
    bootstrap.Modal.getInstance(
      document.getElementById('editProductModal')
    ).hide();

    // refresh page (simple et sûr)
    location.reload();
  })
  .catch(err => {
    alert('Erreur lors de la mise à jour');
    console.error(err);
  });
});
</script>








<script>
// === Recherche produit ===
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.trim().toLowerCase();
  document.querySelectorAll('.prod-row').forEach(el => {
    const name = el.getAttribute('data-name') || '';
    el.style.display = q === '' || name.indexOf(q) !== -1 ? '' : 'none';
  });
});



// === Activer / Désactiver produit ===
function toggleProduct(id){
  fetch('product_toggle_status.php', {
    method:'POST',
    body: new URLSearchParams({ id: id })
  }).then(r => r.json()).then(j=>{
    if(j.ok) location.reload();
    else alert(j.message || 'Erreur');
  }).catch(()=> alert('Erreur réseau'));
}

// === Supprimer produit ===
function deleteProduct(id, name){
  if(!confirm('Supprimer le produit: ' + name + ' ?')) return;
  fetch('product_delete.php', {
    method:'POST',
    body: new URLSearchParams({ id: id })
  }).then(r=>r.json()).then(j=>{
    if(j.ok) location.reload();
    else alert(j.message || 'Erreur');
  }).catch(()=> alert('Erreur réseau'));
}

// === Modale stock ===
function openStockModal(productId, type){
  document.getElementById('stock_product_id').value = productId;
  document.getElementById('stock_type').value = type;
  document.getElementById('stock_qty').value = "";
  document.getElementById('stock_note').value = "";

  const agentBlock = document.getElementById('stock_agent_id').closest('.mb-3');
  if(type === 'transfer'){
    agentBlock.style.display = 'block';
  } else {
    agentBlock.style.display = 'none';
    document.getElementById('stock_agent_id').value = '';
  }

  document.getElementById('stockModalTitle').textContent =
    (type === 'in')
      ? "Ajouter au stock"
      : "Transférer vers vendeur";

  new bootstrap.Modal(document.getElementById('stockModal')).show();
}

// === Soumission stock (UNE seule version) ===
let isSubmittingStock = false;

document.getElementById('stockForm').addEventListener('submit', function(e){
  e.preventDefault();

  if(isSubmittingStock) return;
  isSubmittingStock = true;

  const data = new FormData(this);
  const qty = parseInt(data.get('qty'));

  if(isNaN(qty) || qty <= 0){
    alert("Veuillez indiquer une quantité valide");
    isSubmittingStock = false;
    return;
  }

  fetch('product_stock_update.php', {
    method:'POST',
    body: data
  })
  .then(r => r.json())
  .then(j => {
    isSubmittingStock = false;

    if(j.ok){
      const pid = data.get('product_id');
      document.getElementById('stock_' + pid).textContent = j.new_qty;

      bootstrap.Modal.getInstance(document.getElementById('stockModal')).hide();
      alert('Stock mis à jour.');
    } else {
      alert(j.message || 'Erreur');
    }
  })
  .catch(() => {
    isSubmittingStock = false;
    alert('Erreur réseau');
  });
});
</script>




<?php if(isset($_GET['err'])): ?>
<script>
  // 🔥 Nettoyage forcé de l’URL (sécurité anti anciens liens)
  const cleanUrl =
    window.location.origin +
    window.location.pathname +
    '?house_id=<?= (int)$house_id ?>';

  window.history.replaceState({}, document.title, cleanUrl);
</script>
<?php endif; ?>





<?php 
if(isset($footerPath) && is_file($footerPath)) require_once $footerPath;
?>



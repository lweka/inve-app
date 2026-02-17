<?php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    header("Location: connect-parse.php?role=seller");
    exit;
}

$stmt = $pdo->prepare("SELECT id, status, fullname FROM agents WHERE id=? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();

if(!$agent || $agent['status'] !== 'active'){
    header("Location: account_disabled.php");
    exit;
}

$_SESSION['agent_name'] = $agent['fullname'];

$house_id = (int)$_SESSION['house_id'];
if($house_id <= 0){
    die("Maison non assignée");
}

// Récupérer le taux de change USD de cette maison
$stmt = $pdo->prepare("SELECT usd_rate FROM exchange_rate WHERE house_id = ? LIMIT 1");
$stmt->execute([$house_id]);
$usd_rate = $stmt->fetchColumn();
if (!$usd_rate || $usd_rate <= 0) {
    $usd_rate = 2500; // taux par défaut si non configuré
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Point de vente | Cartelplus Congo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
/* ================================================
   PAYPAL/HOSTINGER THEME - SELLER DASHBOARD
================================================ */
:root {
  --pp-blue: #0070e0;
  --pp-blue-dark: #003087;
  --pp-cyan: #00a8ff;
  --pp-bg: #f5f7fb;
  --pp-white: #ffffff;
  --pp-text: #0b1f3a;
  --pp-border: #e1e8f0;
  --pp-success: #1f8f6a;
  --pp-danger: #dc2626;
  --pp-warning: #f59e0b;
  --pp-orange: #ff6b35;
  --pp-shadow: rgba(0, 48, 135, 0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  min-height: 100vh;
  background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  color: var(--pp-text);
  padding: 20px;
  overflow-x: hidden;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pulseGlow {
  0%, 100% { box-shadow: 0 4px 20px var(--pp-shadow); }
  50% { box-shadow: 0 8px 30px rgba(0, 112, 224, 0.15); }
}

/* ===== HEADER ===== */
.seller-header {
  background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
  padding: 24px 28px;
  border-radius: 16px;
  margin-bottom: 24px;
  box-shadow: 0 10px 30px var(--pp-shadow);
  animation: fadeSlide 0.7s ease both;
}

.seller-header h1 {
  color: var(--pp-white);
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.seller-header h1 i {
  color: var(--pp-cyan);
  font-size: 28px;
}

.seller-header .agent-info {
  color: rgba(255, 255, 255, 0.85);
  font-size: 14px;
  margin-top: 6px;
}

.seller-actions {
  display: flex;
  gap: 10px;
}

/* ===== BOUTONS PAYPAL ===== */
.btn-pp {
  padding: 10px 20px;
  border-radius: 24px;
  font-weight: 600;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  text-decoration: none;
  font-size: 14px;
}

.btn-pp-primary {
  background: var(--pp-blue);
  color: var(--pp-white);
}

.btn-pp-primary:hover {
  background: var(--pp-blue-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 112, 224, 0.3);
}

.btn-pp-success {
  background: var(--pp-success);
  color: var(--pp-white);
}

.btn-pp-success:hover {
  background: #197a5a;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(31, 143, 106, 0.3);
}

.btn-pp-danger {
  background: var(--pp-danger);
  color: var(--pp-white);
}

.btn-pp-danger:hover {
  background: #b91c1c;
  transform: translateY(-2px);
}

.btn-pp-secondary {
  background: var(--pp-white);
  color: var(--pp-blue);
  border: 2px solid var(--pp-border);
}

.btn-pp-secondary:hover {
  background: var(--pp-bg);
  border-color: var(--pp-blue);
}

.btn-pp-warning {
  background: var(--pp-warning);
  color: var(--pp-white);
}

.btn-pp-warning:hover {
  background: #d97706;
  transform: translateY(-2px);
}

/* ===== CARTES ===== */
.card-pp {
  background: var(--pp-white);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 20px var(--pp-shadow);
  border: 1px solid var(--pp-border);
  animation: fadeUp 0.6s ease both;
  height: 100%;
}

.card-pp h5 {
  font-size: 18px;
  font-weight: 700;
  color: var(--pp-text);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.card-pp h5 i {
  color: var(--pp-blue);
  font-size: 22px;
}

/* ===== PRODUITS LIST ===== */
#productsList {
  max-height: 520px;
  overflow-y: auto;
  padding-right: 8px;
}

#productsList::-webkit-scrollbar {
  width: 6px;
}

#productsList::-webkit-scrollbar-track {
  background: var(--pp-bg);
  border-radius: 10px;
}

#productsList::-webkit-scrollbar-thumb {
  background: var(--pp-blue);
  border-radius: 10px;
}

.product-item {
  background: var(--pp-bg);
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 10px;
  border: 1px solid var(--pp-border);
  transition: all 0.3s ease;
  cursor: pointer;
}

.product-item:hover {
  background: var(--pp-white);
  border-color: var(--pp-blue);
  transform: translateX(4px);
  box-shadow: 0 4px 12px var(--pp-shadow);
}

.product-name {
  font-weight: 600;
  color: var(--pp-text);
  font-size: 15px;
}

.product-details {
  font-size: 13px;
  color: #6b7280;
  margin-top: 4px;
}

.product-stock {
  display: inline-block;
  padding: 3px 10px;
  background: var(--pp-success);
  color: var(--pp-white);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.product-stock.low {
  background: var(--pp-warning);
}

.product-stock.out {
  background: var(--pp-danger);
}

/* ===== PANIER ===== */
.cart-item {
  background: var(--pp-bg);
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 12px;
  border: 1px solid var(--pp-border);
  animation: fadeUp 0.4s ease;
}

.cart-item-name {
  font-weight: 600;
  color: var(--pp-text);
  font-size: 15px;
}

.cart-item-price {
  color: var(--pp-blue-dark);
  font-weight: 600;
  font-size: 14px;
}

.qty-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: var(--pp-blue);
  color: var(--pp-white);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.qty-btn:hover {
  background: var(--pp-blue-dark);
  transform: scale(1.1);
}

.qty-display {
  font-weight: 700;
  color: var(--pp-text);
  min-width: 30px;
  text-align: center;
}

/* ===== TOTAL ===== */
.total-section {
  background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
  padding: 20px;
  border-radius: 12px;
  margin-top: 20px;
  animation: pulseGlow 3s infinite;
}

.total-label {
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  font-weight: 500;
}

.total-amount {
  color: var(--pp-white);
  font-size: 28px;
  font-weight: 700;
  margin-top: 4px;
}

/* ===== FORM CONTROLS ===== */
.form-control,
.form-select {
  border: 1px solid var(--pp-border);
  border-radius: 10px;
  padding: 10px 14px;
  transition: all 0.3s ease;
  font-size: 14px;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--pp-blue);
  box-shadow: 0 0 0 3px rgba(0, 112, 224, 0.1);
}

.form-label {
  font-weight: 600;
  color: var(--pp-text);
  font-size: 14px;
  margin-bottom: 8px;
}

/* ===== SEARCH BOX ===== */
.search-box {
  position: relative;
  margin-bottom: 16px;
}

.search-box input {
  padding-left: 40px;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 16px;
}

/* ===== MODALES ===== */
.modal-content {
  border-radius: 16px;
  border: none;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
  color: var(--pp-white);
  border-radius: 16px 16px 0 0;
  padding: 20px 24px;
}

.modal-title {
  font-weight: 700;
  font-size: 18px;
}

.modal-body {
  padding: 24px;
  color: var(--pp-text);
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--pp-border);
}

/* Message modal - priorité élevée pour s'afficher au-dessus des autres modales */
#msgModal {
  z-index: 1060;
}

#msgModal .modal-backdrop {
  z-index: 1059;
}

/* ===== KIT PRODUCTS ===== */
#kitProducts {
  max-height: 300px;
  overflow-y: auto;
}

#kitProducts::-webkit-scrollbar {
  width: 6px;
}

#kitProducts::-webkit-scrollbar-thumb {
  background: var(--pp-blue);
  border-radius: 10px;
}

.kit-product-item {
  background: var(--pp-bg);
  padding: 12px;
  border-radius: 10px;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--pp-border);
  transition: all 0.2s ease;
}

.kit-product-item:hover {
  background: var(--pp-white);
  border-color: var(--pp-blue);
}

.kit-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

/* ===== ALERTS ===== */
#posMsg {
  font-size: 13px;
  padding: 10px 14px;
  border-radius: 8px;
  animation: fadeUp 0.5s ease;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .seller-header h1 {
    font-size: 20px;
  }
  
  .card-pp {
    margin-bottom: 20px;
  }
  
  #productsList {
    max-height: 400px;
  }
}

@media (max-width: 576px) {
  .seller-header .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 14px;
  }

  .seller-actions {
    width: 100%;
    flex-direction: column;
  }

  .seller-actions .btn-pp {
    width: 100%;
    justify-content: center;
  }
}

/* ===== STAGGER ANIMATIONS ===== */
.card-pp:nth-child(1) { animation-delay: 0s; }
.card-pp:nth-child(2) { animation-delay: 0.15s; }
</style>
</head>

<body>


<div class="container-fluid" style="max-width: 1400px;">

<!-- HEADER -->
<div class="seller-header">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h1>
        <i class="fa-solid fa-shop"></i>
        <?= htmlspecialchars($_SESSION['house_name']) ?>
      </h1>
      <div class="agent-info">
        <i class="fa-solid fa-user"></i> Vendeur : <?= htmlspecialchars($_SESSION['agent_name']) ?>
      </div>
    </div>
    <div class="seller-actions">
      <a href="seller_sales_history.php" class="btn-pp btn-pp-secondary">
        <i class="fa-solid fa-clock-rotate-left"></i> Historique
      </a>
      <a href="<?= BASE_URL ?>pagesweb_cn/logout.php" class="btn-pp btn-pp-danger">
        <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
      </a>
    </div>
  </div>
</div>

<div class="row g-4">

<!-- PRODUITS -->
<div class="col-lg-7">
  <div class="card-pp">
    <h5>
      <i class="fa-solid fa-box-open"></i> Produits disponibles
    </h5>
    
    <button class="btn-pp btn-pp-warning btn-sm mb-3 w-100" onclick="openKitModal()">
      <i class="fa-solid fa-boxes-stacked"></i> Composer un kit produit
    </button>
    
    <div class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input id="searchProd" class="form-control" placeholder="Rechercher un produit...">
    </div>
    
    <div id="productsList"></div>
  </div>
</div>

<!-- PANIER -->
<div class="col-lg-5">
  <div class="card-pp">
    <h5>
      <i class="fa-solid fa-cart-shopping"></i> Panier
    </h5>

    <div id="cartItems" style="min-height: 200px;"></div>

    <div class="mb-3">
      <label class="form-label">
        <i class="fa-solid fa-tag"></i> Remise (CDF)
      </label>
      <input id="discount" type="number" class="form-control" value="0" min="0" placeholder="0">
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="fa-solid fa-credit-card"></i> Mode de paiement
      </label>
      <select id="payment_method" class="form-select">
        <option value="cash">💵 Espèces</option>
        <option value="mobile">📱 Mobile Money</option>
        <option value="credit">💳 Crédit</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="fa-solid fa-user-tag"></i> Nom du client (optionnel)
      </label>
      <input id="customer_name" class="form-control" placeholder="Entrez le nom...">
    </div>

    <div class="total-section">
      <div class="total-label">TOTAL À PAYER</div>
      <div class="total-amount" id="totalAmount">0 CDF</div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button class="btn-pp btn-pp-secondary flex-fill" onclick="clearCart()">
        <i class="fa-solid fa-trash-can"></i> Vider
      </button>
      <button class="btn-pp btn-pp-success flex-fill" id="checkoutBtn">
        <i class="fa-solid fa-check-circle"></i> Valider la vente
      </button>
    </div>

    <div id="posMsg" class="mt-3"></div>
  </div>
</div>

</div>
</div>



<!-- MODAL MESSAGE -->
<div class="modal fade" id="msgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="msgTitle">
          <i class="fa-solid fa-circle-info"></i> Information
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="msgBody"></div>
      <div class="modal-footer">
        <button class="btn-pp btn-pp-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CONFIRMATION -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-check"></i> Confirmer la vente
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Voulez-vous vraiment valider cette vente ?</p>
      </div>
      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
        <button class="btn-pp btn-pp-success" id="confirmSaleBtn">
          <i class="fa-solid fa-check-circle"></i> Confirmer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL KIT PRODUIT -->
<div class="modal fade" id="kitModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-boxes-stacked"></i> Composer un kit
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <strong>Produits disponibles</strong>
        </div>
        <div id="kitProducts"></div>

        <hr>

        <strong>Contenu du kit</strong>
        <div id="kitPreview" class="mt-2"></div>
      </div>

      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal" onclick="resetKitModal()">Annuler</button>
        <button class="btn-pp btn-pp-primary" onclick="addKitToCart()">
          <i class="fa-solid fa-cart-plus"></i> Ajouter le kit au panier
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL IMPRESSION -->
<div class="modal fade" id="printModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-receipt"></i> Aperçu du ticket
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" style="height:80vh">
        <iframe id="printFrame" src="" style="width:100%;height:100%;border:none"></iframe>
      </div>

      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Fermer</button>
        <button class="btn-pp btn-pp-secondary" type="button" onclick="downloadTicketPdf()">
          <i class="fa-solid fa-file-pdf"></i> PDF
        </button>
        <button class="btn-pp btn-pp-success" onclick="printTicket()">
          <i class="fa-solid fa-print"></i> Imprimer
        </button>
      </div>
    </div>
  </div>
</div>




<script src="../js/offline-db.js"></script>
<script src="../js/sync-manager.js"></script>
<script src="../js/offline-status.js"></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/inve-app/js/service-worker.js', { scope: '/inve-app/' })
      .then(function () {
        console.log('[PWA] Service Worker enregistre');
      })
      .catch(function (err) {
        console.error('[PWA] Echec Service Worker:', err);
      });
  });
}
</script>

<script>
  let products=[], cart=[];
  let offlineProductsNoticeShown = false;
  const USD_RATE = <?= (float)$usd_rate ?>; // Taux de change USD -> CDF
  const HOUSE_ID = <?= (int)$house_id ?>;
  const AGENT_ID = <?= (int)$_SESSION['user_id'] ?>;
  const PRODUCTS_CACHE_KEY = `seller_products_cache_house_${HOUSE_ID}`;

  function hasUsdCurrency(totals){
    return !!(totals && Object.prototype.hasOwnProperty.call(totals, 'USD'));
  }

  function buildTotalsLabel(totals){
    if(!totals) return '';
    const parts = [];
    for(const cur in totals){
      const amount = Number(totals[cur] || 0);
      const formatted = amount.toFixed(2);
      parts.push(`${formatted} ${cur}`);
    }
    return parts.join(' + ');
  }

  function convertTotalsToCdf(totals){
    if(!totals) return 0;
    let totalCdf = 0;
    for(const cur in totals){
      const amount = Number(totals[cur] || 0);
      if(cur === 'USD'){
        totalCdf += amount * USD_RATE;
      } else {
        totalCdf += amount;
      }
    }
    return totalCdf;
  }

  function formatCdf(amount){
    return `${Number(amount || 0).toFixed(0)} CDF`;
  }

  function getCurrentSearchQuery(){
    const searchInput = document.getElementById('searchProd');
    if(!searchInput) return '';
    return (searchInput.value || '').toLowerCase().trim();
  }

  function renderProductsByCurrentFilter(){
    const query = getCurrentSearchQuery();
    if(!query){
      renderProducts(products);
      return;
    }
    renderProducts(products.filter(p => String(p.name || '').toLowerCase().includes(query)));
  }

  function cacheProductsSnapshot(list){
    try {
      localStorage.setItem(PRODUCTS_CACHE_KEY, JSON.stringify({
        saved_at: Date.now(),
        products: Array.isArray(list) ? list : []
      }));
    } catch (e) {
      console.error('Erreur cache produits local:', e);
    }
  }

  function readCachedProductsSnapshot(){
    try {
      const raw = localStorage.getItem(PRODUCTS_CACHE_KEY);
      if(!raw) return [];
      const parsed = JSON.parse(raw);
      if(!parsed || !Array.isArray(parsed.products)) return [];
      return parsed.products;
    } catch (e) {
      console.error('Erreur lecture cache produits local:', e);
      return [];
    }
  }

  async function ensureOfflineDbReady(){
    if(typeof offlineDB === 'undefined'){
      return false;
    }
    if(offlineDB.db){
      return true;
    }
    try {
      await offlineDB.init();
      return true;
    } catch (e) {
      console.error('OfflineDB indisponible:', e);
      return false;
    }
  }

  function generateOfflineSaleId(){
    const agentPart = Number(AGENT_ID || 0).toString(36).toUpperCase();
    const housePart = Number(HOUSE_ID || 0).toString(36).toUpperCase();
    const timePart = Date.now().toString(36).toUpperCase();
    const randomPart = Math.random().toString(36).slice(2, 7).toUpperCase();
    return `OF-${agentPart}-${housePart}-${timePart}-${randomPart}`.slice(0, 36);
  }

  function buildSalePayload(cartSnapshot, offlineId = ''){
    return {
      house_id: String(HOUSE_ID),
      items: JSON.stringify(cartSnapshot),
      discount: String(document.getElementById('discount').value || 0),
      payment_method: String(document.getElementById('payment_method').value || 'cash'),
      customer_name: String(document.getElementById('customer_name').value || ''),
      offline_id: String(offlineId || '')
    };
  }

  function payloadToFormData(payloadObj){
    const payload = new URLSearchParams();
    Object.keys(payloadObj).forEach((key) => {
      payload.append(key, payloadObj[key] == null ? '' : String(payloadObj[key]));
    });
    return payload;
  }

  async function queueOfflineSale(payloadObj){
    const ready = await ensureOfflineDbReady();
    if(!ready){
      return false;
    }

    await offlineDB.addToSyncQueue('pos_sale', {
      endpoint: 'create_sale.php',
      payload: payloadObj,
      queued_at: Date.now(),
      source: 'seller_dashboard'
    });

    if(typeof syncManager !== 'undefined' && typeof syncManager.updateUI === 'function'){
      syncManager.updateUI('offline-request');
    }

    if(typeof syncManager !== 'undefined' && typeof syncManager.updatePendingCounter === 'function'){
      try {
        await syncManager.updatePendingCounter();
      } catch (e) {
        console.error('Erreur mise a jour compteur sync:', e);
      }
    }

    return true;
  }

  function applyOfflineStockChanges(cartSnapshot){
    const decreaseStock = function(productId, qty){
      const idx = products.findIndex(p => Number(p.id) === Number(productId));
      if(idx === -1) return;
      const current = Number(products[idx].stock || 0);
      products[idx].stock = Math.max(0, current - Number(qty || 0));
    };

    (cartSnapshot || []).forEach((item) => {
      if(item && item.is_kit && Array.isArray(item.items)){
        item.items.forEach((kitItem) => decreaseStock(kitItem.product_id, kitItem.qty));
      } else if(item && item.product_id){
        decreaseStock(item.product_id, item.qty);
      }
    });

    cacheProductsSnapshot(products);
    renderProductsByCurrentFilter();
  }

  /* ===== PRODUITS ===== */
  function loadProducts(){
    fetch('seller_products.php?house_id=<?= $house_id ?>')
      .then(r => r.json())
      .then(j => {
        if(!j.ok){
          throw new Error(j.message || 'Chargement produits impossible');
        }
        products = Array.isArray(j.products) ? j.products : [];
        cacheProductsSnapshot(products);
        offlineProductsNoticeShown = false;
        renderProductsByCurrentFilter();
      })
      .catch(err => {
        console.error('Chargement produits online erreur:', err);
        const cachedProducts = readCachedProductsSnapshot();
        if(cachedProducts.length > 0){
          products = cachedProducts;
          renderProductsByCurrentFilter();
          if(!offlineProductsNoticeShown && !navigator.onLine){
            showPosMsg(
              'Mode hors ligne : produits charges depuis le cache local.',
              'error',
              8000
            );
            offlineProductsNoticeShown = true;
          }
        }
      });
  }

  function renderProducts(list){
    const el=document.getElementById('productsList');
    el.innerHTML='';
    if(list.length === 0){
      el.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;"><i class="fa-solid fa-box-open" style="font-size:48px;margin-bottom:12px;"></i><div>Aucun produit disponible</div></div>';
      return;
    }
    list.forEach(p=>{
      const stockClass = p.stock <= 0 ? 'out' : p.stock < 5 ? 'low' : '';
      const stockText = p.stock <= 0 ? 'Rupture' : `Stock: ${p.stock}`;
      
      el.innerHTML+=`
      <div class="product-item" onclick="addToCart(${p.id})">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1">
            <div class="product-name">${p.name}</div>
            <div class="product-details">
              <strong style="color: var(--pp-blue-dark);">${p.sell_price} ${p.sell_currency}</strong>
              <span style="margin-left:12px;" class="product-stock ${stockClass}">${stockText}</span>
            </div>
          </div>
          <button class="btn-pp btn-pp-primary btn-sm" onclick="event.stopPropagation(); addToCart(${p.id})">
            <i class="fa-solid fa-plus"></i>
          </button>
        </div>
      </div>
      `;
    });
  }

function showMsg(title, message){
  document.getElementById('msgTitle').textContent = title;
  document.getElementById('msgBody').innerHTML = message;
  
  const msgModalEl = document.getElementById('msgModal');
  const msgModal = new bootstrap.Modal(msgModalEl);
  
  // Écouter l'événement d'affichage pour ajuster le z-index
  msgModalEl.addEventListener('shown.bs.modal', function () {
    // Trouver le backdrop et le modal
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const modals = document.querySelectorAll('.modal.show');
    
    // S'assurer que le msgModal est au-dessus
    if(backdrops.length > 1) {
      backdrops[backdrops.length - 1].style.zIndex = '1059';
    }
    if(modals.length > 0) {
      msgModalEl.style.zIndex = '1060';
    }
  }, { once: true });
  
  msgModal.show();
}

let posMsgTimer = null;
let ticketPrintPending = false;

function clearPosMsg(){
  const posMsg = document.getElementById('posMsg');
  if(!posMsg) return;
  if(posMsgTimer){
    clearTimeout(posMsgTimer);
    posMsgTimer = null;
  }
  posMsg.className = 'mt-3';
  posMsg.innerHTML = '';
}

function showPosMsg(message, type = 'success', autoHideMs = 0){
  const posMsg = document.getElementById('posMsg');
  if(!posMsg) return;

  if(posMsgTimer){
    clearTimeout(posMsgTimer);
    posMsgTimer = null;
  }

  const alertClass = (type === 'error') ? 'alert alert-danger' : 'alert alert-success';
  posMsg.className = `mt-3 ${alertClass}`;
  posMsg.innerHTML = message;

  if(autoHideMs > 0){
    posMsgTimer = setTimeout(() => {
      clearPosMsg();
    }, autoHideMs);
  }
}

function finalizeSaleUiAfterPrint(){
  ticketPrintPending = false;
  clearPosMsg();

  const printModalEl = document.getElementById('printModal');
  const printModal = bootstrap.Modal.getInstance(printModalEl);
  if(printModal){
    printModal.hide();
  }

  const frame = document.getElementById('printFrame');
  if(frame){
    frame.src = '';
    delete frame.dataset.ticketPreviewUrl;
    delete frame.dataset.ticketPrintUrl;
    delete frame.dataset.ticketPdfUrl;
  }
}

/* ===== PANIER ===== */
  function addToCart(pid){
    const p = products.find(x => x.id == pid);
    if(!p) return;

    let line = cart.find(c => c.product_id == pid);

    if(line){
      if(line.qty >= p.stock){
        showMsg("Stock insuffisant",
          "Vous avez atteint le stock maximum disponible pour ce produit.");
        return;
      }
      line.qty++;
    }else{
      if(p.stock <= 0){
        showMsg("Stock vide", "Ce produit n'est plus disponible.");
        return;
      }
      cart.push({...p, product_id:p.id, qty:1});
    }
    renderCart();
  }

  function changeQty(i, delta){
    const item = cart[i];
    const prod = products.find(p => p.id == item.product_id);

    if(delta > 0){
      // Calculer la quantité déjà dans le panier pour ce produit
      let qtyInCart = 0;
      cart.forEach((cartItem, idx) => {
        if(cartItem.is_kit && cartItem.items){
          // Si c'est un kit, vérifier les produits dans le kit
          cartItem.items.forEach(kitProd => {
            if(kitProd.product_id == item.product_id){
              qtyInCart += kitProd.qty;
            }
          });
        } else if(cartItem.product_id == item.product_id){
          // Si c'est un produit simple
          qtyInCart += cartItem.qty;
        }
      });

      // Vérifier si on peut augmenter la quantité
      if(qtyInCart >= prod.stock){
        showMsg("Stock insuffisant",
          `Stock disponible: <strong>${prod.stock}</strong><br>` +
          `Déjà dans le panier: <strong>${qtyInCart}</strong>`);
        return;
      }
    }

    item.qty += delta;

    if(item.qty <= 0){
      cart.splice(i,1);
    }
    renderCart();
  }

  function removeItem(i){
    cart.splice(i,1);
    renderCart();
  }

  function clearCart(){
    cart = [];
    renderCart();
  }




// function renderCart
  function renderCart(){
  const el = document.getElementById('cartItems');
  el.innerHTML = '';
  
  if(cart.length === 0){
    el.innerHTML = '<div style="text-align:center;padding:40px;color:#9ca3af;"><i class="fa-solid fa-cart-shopping" style="font-size:48px;margin-bottom:12px;"></i><div>Panier vide</div></div>';
    document.getElementById('totalAmount').textContent = '0 CDF';
    return;
  }
  
  let totals = {};

  cart.forEach((c,i)=>{

    /* ================= KIT ================= */
    if(c.is_kit){

      // Gérer les totaux par devise
      if(c.total_by_currency) {
        for(const cur in c.total_by_currency) {
          totals[cur] = (totals[cur] || 0) + c.total_by_currency[cur];
        }
      } else if(c.sell_currency) {
        // Fallback pour ancien format
        totals[c.sell_currency] = (totals[c.sell_currency] || 0) + (c.total_price || 0);
      }

      const displayTotals = c.display_total || buildTotalsLabel(c.total_by_currency || {});
      const convertedCdf = (typeof c.converted_total_cdf === 'number')
        ? c.converted_total_cdf
        : convertTotalsToCdf(c.total_by_currency || {});
      const showConvertedTotal = (typeof c.show_converted_total === 'boolean')
        ? c.show_converted_total
        : hasUsdCurrency(c.total_by_currency || {});

      el.innerHTML += `
        <div class="cart-item">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <div class="cart-item-name">
                <i class="fa-solid fa-boxes-stacked"></i> ${c.label}
              </div>
              <div class="small" style="color:#6b7280;margin-top:4px;">
                ${c.items.length} produit(s)
              </div>
              <ul class="small mt-2 mb-2" style="color:#6b7280;">
                ${c.items.map(it => `<li>${it.name} × ${it.qty} = ${(it.sell_price * it.qty).toFixed(2)} ${it.sell_currency}</li>`).join('')}
              </ul>
              <div class="cart-item-price" style="border-top:1px solid #e5e7eb;padding-top:8px;margin-top:8px;">
                <strong>Total: ${displayTotals}</strong>
              </div>
              ${showConvertedTotal ? `<div class="small" style="margin-top:4px;color:var(--pp-blue-dark);font-weight:600;">Total converti: ${formatCdf(convertedCdf)} (1 USD = ${formatCdf(USD_RATE)})</div>` : ''}
            </div>
            <button class="btn-pp btn-pp-danger btn-sm" onclick="cart.splice(${i},1); renderCart();">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
      `;
      return;
    }

    /* ================= PRODUIT SIMPLE ================= */
    totals[c.sell_currency] =
      (totals[c.sell_currency] || 0) + (c.sell_price * c.qty);

    el.innerHTML += `
      <div class="cart-item">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="cart-item-name">${c.name}</div>
            <div class="cart-item-price">${c.sell_price} ${c.sell_currency}</div>
          </div>
          <button class="btn-pp btn-pp-danger btn-sm" onclick="removeItem(${i})">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </div>
        <div class="qty-controls">
          <button class="qty-btn" onclick="changeQty(${i},-1)">−</button>
          <span class="qty-display">${c.qty}</span>
          <button class="qty-btn" onclick="changeQty(${i},1)">+</button>
          <div class="flex-grow-1 text-end" style="font-weight:600;color:var(--pp-blue-dark);">
            ${(c.sell_price * c.qty).toFixed(2)} ${c.sell_currency}
          </div>
        </div>
      </div>
    `;
  });

  let txt = '';
  for(const cur in totals){
    txt += `${totals[cur].toFixed(2)} ${cur} `;
  }
  document.getElementById('totalAmount').textContent = txt || '0 CDF';
}






 





  /* ===== KIT PRODUIT ===== */



  let currentKit = [];

  function addToKit(pid, qtyStr){
    const p = products.find(x => x.id == pid);
    if(!p) return;

    const qty = parseInt(qtyStr);
    if(isNaN(qty) || qty <= 0){
      showMsg("Erreur", "Quantité invalide");
      return;
    }

    // Vérifier si le produit existe déjà dans le kit
    const existingItem = currentKit.find(k => k.product_id == pid);
    
    if(existingItem){
      // Vérifier que la nouvelle quantité totale ne dépasse pas le stock
      const newQty = existingItem.qty + qty;
      if(newQty > p.stock){
        showMsg("Stock insuffisant", 
          `Stock disponible: <strong>${p.stock}</strong><br>` +
          `Déjà dans le kit: <strong>${existingItem.qty}</strong><br>` +
          `Vous ne pouvez ajouter que <strong>${p.stock - existingItem.qty}</strong> de plus`);
        return;
      }
      existingItem.qty = newQty;
    } else {
      // Nouveau produit dans le kit
      if(qty > p.stock){
        showMsg("Stock insuffisant", `Stock disponible: <strong>${p.stock}</strong>`);
        return;
      }
      
      currentKit.push({
        product_id: p.id,
        name: p.name,
        qty: qty,
        sell_price: p.sell_price,
        sell_currency: p.sell_currency
      });
    }

    renderKitPreview();
  }



// function add to kit
function addKitToCart(){

  if(currentKit.length === 0){
    showMsg("Kit vide","Ajoutez des produits au kit");
    return;
  }

  // Vérifier le stock disponible pour chaque produit du kit
  for(let k of currentKit){
    const p = products.find(x => x.id == k.product_id);
    if(!p){
      showMsg("Erreur", `Produit ${k.name} introuvable`);
      return;
    }

    // Calculer la quantité déjà dans le panier pour ce produit
    let qtyInCart = 0;
    cart.forEach(cartItem => {
      if(cartItem.is_kit && cartItem.items){
        // Si c'est un kit, vérifier les produits dans le kit
        cartItem.items.forEach(kitProd => {
          if(kitProd.product_id == k.product_id){
            qtyInCart += kitProd.qty;
          }
        });
      } else if(cartItem.product_id == k.product_id){
        // Si c'est un produit simple
        qtyInCart += cartItem.qty;
      }
    });

    // Vérifier si le stock est suffisant
    if((qtyInCart + k.qty) > p.stock){
      showMsg("Stock insuffisant", 
        `Le produit <strong>${k.name}</strong> n'a que <strong>${p.stock}</strong> en stock.<br>` +
        `Déjà dans le panier: <strong>${qtyInCart}</strong><br>` +
        `Demandé dans le kit: <strong>${k.qty}</strong>`
      );
      return;
    }
  }

  // Calculer le total du kit (chaque produit avec sa devise)
  let totals = {};

  currentKit.forEach(k => {
    const cur = k.sell_currency;
    totals[cur] = (totals[cur] || 0) + (k.sell_price * k.qty);
  });

  // Créer un label affichant toutes les devises
  const totalDisplay = buildTotalsLabel(totals);
  const convertedTotalCdf = convertTotalsToCdf(totals);
  const showConvertedTotal = hasUsdCurrency(totals);

  // Ajouter le kit au panier (en gardant tous les composants)
  cart.push({
    is_kit: true,
    label: "KIT PRODUITS",
    total_by_currency: totals,
    display_total: totalDisplay,
    converted_total_cdf: convertedTotalCdf,
    show_converted_total: showConvertedTotal,
    sell_currency: Object.keys(totals).join('/'), // ex: "CDF/USD"
    items: JSON.parse(JSON.stringify(currentKit)) // copie propre
  });

  // reset
  currentKit = [];
  renderCart();

  // fermer + reset modal
  bootstrap.Modal.getInstance(
    document.getElementById('kitModal')
  ).hide();
  resetKitModal();
}

//reset kit 
function resetKitModal(){
  currentKit = [];
  document.getElementById('kitPreview').innerHTML = '';
}



/**function resetKit(){
  currentKit = [];
  document.getElementById('kitPreview').innerHTML = '';
  document.getElementById('kitAlert').innerHTML = '';
}***/


//JS — ouverture du modal KIT
function openKitModal(){
  renderKitProducts();
  new bootstrap.Modal(
    document.getElementById('kitModal')
  ).show();
}

//JS — afficher produits dans le kit
function renderKitProducts(){
  const el = document.getElementById('kitProducts');
  el.innerHTML = '';

  products.forEach(p=>{
    el.innerHTML += `
      <div class="kit-product-item">
        <div class="flex-grow-1">
          <div style="font-weight:600;color:var(--pp-text);">${p.name}</div>
          <div class="small" style="color:#6b7280;margin-top:2px;">
            ${p.sell_price} ${p.sell_currency} • Stock ${p.stock}
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <input type="number" min="1" max="${p.stock}" value="1"
            id="kit_qty_${p.id}" class="form-control form-control-sm" style="width:70px;border-radius:8px;">
          <button class="btn-pp btn-pp-primary btn-sm"
            onclick="addToKit(${p.id}, document.getElementById('kit_qty_${p.id}').value)">
            <i class="fa-solid fa-plus"></i> Ajouter
          </button>
        </div>
      </div>
    `;
  });
}


//JS — aperçu du kit
function renderKitPreview(){
  const el = document.getElementById('kitPreview');
  if(currentKit.length === 0){
    el.innerHTML = '<div style="text-align:center;padding:20px;color:#9ca3af;">Aucun produit dans le kit</div>';
    return;
  }
  
  el.innerHTML = '';
  
  // Grouper par devise pour affichage
  const kitsByUnitCurrency = {};
  const currencyTotals = {};
  
  currentKit.forEach((k, idx) => {
    const cur = k.sell_currency;
    if(!kitsByUnitCurrency[cur]) {
      kitsByUnitCurrency[cur] = [];
    }
    kitsByUnitCurrency[cur].push({...k, idx: idx});
    currencyTotals[cur] = (currencyTotals[cur] || 0) + (k.sell_price * k.qty);
  });

  // Afficher chaque groupe avec sa devise
  for(const currency in kitsByUnitCurrency) {
    const subtotalByCurrency = currencyTotals[currency] || 0;
    
    el.innerHTML += `<div style="margin-bottom:12px;padding:8px;background:rgba(0,112,224,0.05);border-left:3px solid var(--pp-blue);border-radius:6px;">`;
    
    kitsByUnitCurrency[currency].forEach(k => {
      const subtotal = (k.sell_price * k.qty).toFixed(2);
      el.innerHTML += `
        <div class="d-flex justify-content-between align-items-center" style="padding:6px 0;font-size:13px;">
          <span style="color:var(--pp-text);">${k.name} × ${k.qty}</span>
          <div class="d-flex align-items-center gap-2">
            <span style="color:var(--pp-blue);font-weight:600;">${subtotal} ${currency}</span>
            <button class="btn-pp btn-pp-danger btn-sm" style="padding:2px 8px;font-size:11px;" 
              onclick="removeFromKit(${k.idx})" title="Retirer du kit">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
      `;
    });
    
    // Sous-total par devise
    el.innerHTML += `
      <div style="border-top:1px solid rgba(0,112,224,0.2);padding-top:6px;margin-top:6px;">
        <div class="d-flex justify-content-between" style="font-weight:700;color:var(--pp-blue-dark);">
          <span>Sous-total ${currency}</span>
          <span>${subtotalByCurrency.toFixed(2)} ${currency}</span>
        </div>
      </div>
    `;
    
    el.innerHTML += '</div>';
  }

  const kitTotalLabel = buildTotalsLabel(currencyTotals);
  el.innerHTML += `
    <div style="margin-top:10px;padding:10px;border:1px dashed var(--pp-border);border-radius:10px;background:#fff;">
      <div style="display:flex;justify-content:space-between;font-weight:700;color:var(--pp-text);">
        <span>Total kit</span>
        <span>${kitTotalLabel}</span>
      </div>
      ${hasUsdCurrency(currencyTotals) ? `<div style="margin-top:6px;font-size:13px;color:var(--pp-blue-dark);font-weight:600;">Total converti: ${formatCdf(convertTotalsToCdf(currencyTotals))} (1 USD = ${formatCdf(USD_RATE)})</div>` : ''}
    </div>
  `;
}

// Fonction pour retirer un produit du kit
function removeFromKit(idx){
  currentKit.splice(idx, 1);
  renderKitPreview();
}





  /* ===== VENTE ===== */
document.getElementById('checkoutBtn').onclick = ()=>{
  if(cart.length === 0){
    showMsg("Panier vide", "Ajoutez au moins un produit avant de valider.");
    return;
  }

  new bootstrap.Modal(
    document.getElementById('confirmModal')
  ).show();
};



function resetSaleForm(){
  document.getElementById('customer_name').value = '';
  document.getElementById('discount').value = 0;
  document.getElementById('payment_method').value = 'cash';
}



document.getElementById('confirmSaleBtn').onclick = async ()=>{
  const cartSnapshot = JSON.parse(JSON.stringify(cart));

  if(!Array.isArray(cartSnapshot) || cartSnapshot.length === 0){
    showMsg("Panier vide", "Ajoutez au moins un produit avant de valider.");
    return;
  }

  const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
  if(confirmModal){
    confirmModal.hide();
  }

  const submitOnlineSale = async (payloadObj) => {
    const response = await fetch('create_sale.php', {
      method: 'POST',
      body: payloadToFormData(payloadObj)
    });
    const result = await response.json();

    if(!result.ok){
      showMsg("Erreur", result.message || "Echec enregistrement vente");
      return false;
    }

    clearCart();
    resetSaleForm();
    loadProducts();

    if(result.sale_id){
      showPosMsg(
        "Vente enregistree avec succes. Le ticket est en cours d'ouverture pour impression."
      );
      openTicket(result.sale_id);
    }

    return true;
  };

  if(!navigator.onLine){
    const offlineId = generateOfflineSaleId();
    const offlinePayload = buildSalePayload(cartSnapshot, offlineId);
    const queued = await queueOfflineSale(offlinePayload);

    if(!queued){
      showMsg("Erreur", "Impossible de sauvegarder cette vente hors ligne.");
      return;
    }

    clearCart();
    resetSaleForm();
    applyOfflineStockChanges(cartSnapshot);
    showPosMsg(
      "Vente sauvegardee hors ligne. Synchronisation automatique des que la connexion revient.",
      'success',
      10000
    );
    return;
  }

  const onlinePayload = buildSalePayload(cartSnapshot, '');

  try {
    await submitOnlineSale(onlinePayload);
  } catch (err) {
    console.error('Erreur creation vente online:', err);

    if(navigator.onLine){
      showMsg(
        "Erreur",
        "Le serveur a retourne une erreur. La vente n'a pas ete envoyee en mode hors ligne."
      );
      return;
    }

    const offlineId = generateOfflineSaleId();
    const offlinePayload = buildSalePayload(cartSnapshot, offlineId);
    const queued = await queueOfflineSale(offlinePayload);

    if(!queued){
      showMsg("Erreur", "Connexion perdue et sauvegarde hors ligne indisponible.");
      return;
    }

    clearCart();
    resetSaleForm();
    applyOfflineStockChanges(cartSnapshot);
    showPosMsg(
      "Connexion perdue. Vente sauvegardee hors ligne et en attente de synchronisation.",
      'error',
      12000
    );
  }
};

  searchProd.oninput=e=>{
    renderProductsByCurrentFilter();
  };

  loadProducts();
  setInterval(() => {
    if(navigator.onLine){
      loadProducts();
    }
  }, 3000);
</script>

<script>

function openTicket(saleId){
  const frame = document.getElementById('printFrame');
  if(!frame){
    alert('Erreur: element d\'impression introuvable.');
    return;
  }

  const ts = Date.now();
  const safeSaleId = encodeURIComponent(saleId);
  const previewUrl = 'seller_ticket_print.php?sale_id=' + safeSaleId + '&preview=1&_ts=' + ts;
  const pdfUrl = 'seller_ticket_pdf.php?sale_id=' + safeSaleId + '&_ts=' + ts;

  frame.dataset.ticketPreviewUrl = previewUrl;
  frame.dataset.ticketPrintUrl = previewUrl;
  frame.dataset.ticketPdfUrl = pdfUrl;
  ticketPrintPending = true;

  frame.onload = function () {
    frame.onload = null;
    setTimeout(() => {
      printTicket();
    }, 280);
  };
  frame.src = previewUrl;

  const printModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('printModal'));
  printModal.show();
}

function printTicket(){
  const frame = document.getElementById('printFrame');
  if(!frame) return;

  try {
    if(frame.contentWindow){
      frame.contentWindow.focus();
      frame.contentWindow.print();
      return;
    }
  } catch(e) {
    console.error('Erreur impression iframe:', e);
  }

  const fallbackUrl = frame.dataset.ticketPreviewUrl || frame.src;
  if(fallbackUrl){
    window.open(fallbackUrl, '_blank', 'width=900,height=760');
  }
}

function downloadTicketPdf(){
  const frame = document.getElementById('printFrame');
  if(!frame) return;
  const pdfUrl = frame.dataset.ticketPdfUrl;
  if(pdfUrl){
    window.open(pdfUrl, '_blank');
  }
}

window.addEventListener('message', (event) => {
  if(event.origin !== window.location.origin) return;
  if(!event.data || event.data.type !== 'ticket_print_done') return;
  finalizeSaleUiAfterPrint();
});

window.addEventListener('afterprint', () => {
  if(ticketPrintPending){
    finalizeSaleUiAfterPrint();
  }
});

document.getElementById('printModal')?.addEventListener('hidden.bs.modal', () => {
  if(!ticketPrintPending){
    clearPosMsg();
  }
});
  /* //Script impression 
  let currentSaleId = null;

  function openPrintModal(saleId){
    currentSaleId = saleId;

    const iframe = document.getElementById('printFrame');
    iframe.src = 'seller_ticket_pdf.php?sale_id=' + saleId;

    new bootstrap.Modal(
      document.getElementById('printModal')
    ).show();

    // impression auto après chargement
    iframe.onload = () => {
      setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      }, 600);
    };
  }

  function printTicket(){
    const iframe = document.getElementById('printFrame');
    if(iframe && iframe.contentWindow){
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    }
  } */
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- SYSTÈME DE VÉRIFICATION DU STATUT DU VENDEUR EN TEMPS RÉEL -->
<script>
// Vérifier le statut du vendeur toutes les 5 secondes
let accountDisabledShown = false;

function checkSellerStatus() {
  fetch('api_check_seller_status.php')
    .then(response => response.json())
    .then(data => {
      // Si le vendeur a été désactivé
      if(data.is_active === false && !accountDisabledShown) {
        accountDisabledShown = true;
        showAccountDisabledModal(data.name);
      }
    })
    .catch(err => console.error('Erreur vérification statut:', err));
}

function showAccountDisabledModal(agentName) {
  // Créer la modale dynamiquement
  const modalHTML = `
    <div class="modal fade" id="accountDisabledModal" tabindex="-1" backdrop="static" keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 48, 135, 0.15);">
          <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border: none; border-radius: 20px 20px 0 0; padding: 24px;">
            <div style="text-align: center; width: 100%; color: white;">
              <div style="font-size: 48px; margin-bottom: 12px;">
                <i class="fa-solid fa-lock" style="animation: pulse 1s ease-in-out infinite;"></i>
              </div>
              <h5 class="modal-title" style="color: white; font-weight: 700; font-size: 20px;">Compte Désactivé</h5>
            </div>
          </div>
          <div class="modal-body" style="padding: 32px; text-align: center;">
            <p style="font-size: 16px; color: #0b1f3a; margin-bottom: 16px; font-weight: 500;">
              Votre compte a été désactivé par l'administrateur.
            </p>
            <p style="font-size: 15px; color: #6b7280; margin-bottom: 24px; line-height: 1.6;">
              L'accès à votre espace de vente n'est plus disponible.
            </p>
            <div style="background: #fff3cd; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; text-align: left; margin-bottom: 24px;">
              <strong style="color: #856404; display: block; margin-bottom: 6px;">
                <i class="fa-solid fa-exclamation-circle"></i> Action requise
              </strong>
              <span style="color: #704214; font-size: 14px;">
                Veuillez contacter l'administrateur pour rétablir l'accès à votre compte.
              </span>
            </div>
            <p style="font-size: 13px; color: #9ca3af; margin: 0;">
              <i class="fa-solid fa-user-circle"></i> ${agentName}
            </p>
          </div>
          <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 16px 24px;">
            <button type="button" class="btn" style="background: #0070e0; color: white; border: none; border-radius: 24px; padding: 10px 24px; font-weight: 600;" onclick="redirectToDisabled()">
              <i class="fa-solid fa-sign-in"></i> Retour à la connexion
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Ajouter la modale au DOM
  document.body.insertAdjacentHTML('beforeend', modalHTML);

  // Afficher la modale
  const modal = new bootstrap.Modal(document.getElementById('accountDisabledModal'), {
    backdrop: 'static',
    keyboard: false
  });
  modal.show();
}

function redirectToDisabled() {
  // Rediriger vers la page de compte désactivé
  window.location.href = 'account_disabled.php';
}

// Lancer la vérification toutes les 5 secondes
setInterval(checkSellerStatus, 5000);

// Vérifier aussi au chargement
checkSellerStatus();

// Animation CSS pour le pulse
const style = document.createElement('style');
style.textContent = \`
  @keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
  }
\`;
document.head.appendChild(style);
</script>

</body>
</html>


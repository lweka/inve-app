<?php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent'){
    header("Location: connect-parse.php?role=seller");
    exit;
}

$stmt = $pdo->prepare("SELECT id, status, fullname FROM agents WHERE id=? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();

if(!$agent || $agent['status'] !== 'active'){
    die("Compte vendeur désactivé");
}

$_SESSION['agent_name'] = $agent['fullname'];

$house_id = (int)$_SESSION['house_id'];
if($house_id <= 0){
    die("Maison non assignée");
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

/* ===== STAGGER ANIMATIONS ===== */
.card-pp:nth-child(1) { animation-delay: 0s; }
.card-pp:nth-child(2) { animation-delay: 0.15s; }
</style>
</head>

<body>

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
      <a href="auth.php?logout=1" class="btn-pp btn-pp-danger">
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
        <button class="btn-pp btn-pp-success" onclick="printTicket()">
          <i class="fa-solid fa-print"></i> Imprimer
        </button>
      </div>
    </div>
  </div>
</div>




<script>
  let products=[], cart=[];

  /* ===== PRODUITS ===== */
  function loadProducts(){
    fetch('seller_products.php?house_id=<?= $house_id ?>')
      .then(r=>r.json())
      .then(j=>{
        if(!j.ok){ return; }
        products=j.products;
        renderProducts(products);
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
  new bootstrap.Modal(document.getElementById('msgModal')).show();
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

    if(delta > 0 && item.qty >= prod.stock){
      showMsg("Stock insuffisant",
        "Vous ne pouvez pas dépasser le stock disponible.");
      return;
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

      totals[c.sell_currency] =
        (totals[c.sell_currency] || 0) + c.total_price;

      el.innerHTML += `
        <div class="cart-item">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <div class="cart-item-name">
                <i class="fa-solid fa-boxes-stacked"></i> ${c.label}
              </div>
              <div class="small" style="color:#6b7280;margin-top:4px;">
                ${c.items.length} produits dans le kit
              </div>
              <ul class="small mt-2 mb-2" style="color:#6b7280;">
                ${c.items.map(it => `<li>${it.name} × ${it.qty}</li>`).join('')}
              </ul>
              <div class="cart-item-price">
                ${c.total_price.toFixed(2)} ${c.sell_currency}
              </div>
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

  function addToKit(pid, qty){
    const p = products.find(x => x.id == pid);
    if(!p) return;

    if(qty <= 0 || qty > p.stock){
      showMsg("Erreur", "Quantité invalide pour le kit");
      return;
    }

    currentKit.push({
      product_id: p.id,
      name: p.name,
      qty: qty,
      sell_price: p.sell_price,
      sell_currency: p.sell_currency
    });

    renderKitPreview();
  }



// function add to kit
function addKitToCart(){

  if(currentKit.length === 0){
    showMsg("Kit vide","Ajoutez des produits au kit");
    return;
  }

  let total = 0;
  let currency = currentKit[0].sell_currency;

  currentKit.forEach(k=>{
    total += k.sell_price * k.qty;
  });

  cart.push({
    is_kit: true,
    label: "KIT PRODUITS",
    total_price: total,
    sell_currency: currency,
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
  currentKit.forEach(k=>{
    el.innerHTML += `
      <div class="d-flex justify-content-between align-items-center" style="padding:8px 12px;background:var(--pp-bg);border-radius:8px;margin-bottom:6px;">
        <span style="font-weight:600;color:var(--pp-text);">${k.name}</span>
        <span style="color:var(--pp-blue);">× ${k.qty}</span>
      </div>
    `;
  });
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



document.getElementById('confirmSaleBtn').onclick = ()=>{
  const payload = new URLSearchParams();
  payload.append('house_id', <?= $house_id ?>);
  payload.append('items', JSON.stringify(cart));
  payload.append('discount', document.getElementById('discount').value);
  payload.append('payment_method', document.getElementById('payment_method').value);
  payload.append('customer_name', document.getElementById('customer_name').value);

  fetch('create_sale.php',{method:'POST',body:payload})
    .then(r=>r.json())
    .then(j=>{
      bootstrap.Modal.getInstance(
        document.getElementById('confirmModal')
      ).hide();

      if(!j.ok){
        showMsg("Erreur", j.message);
        return;
      }

      clearCart();
      resetSaleForm();
      loadProducts();

      // 🔥 OUVERTURE IMPRESSION AUTOMATIQUE
      if(j.ok && j.sale_id){
        setTimeout(() => {
          openTicket(j.sale_id);
          showMsg(
            "Vente réussie",
            "✅ La vente a été enregistrée avec succès.\n\nLe ticket s'ouvre pour impression..."
          );
        }, 300);
      }

    });
};

  searchProd.oninput=e=>{
    const q=e.target.value.toLowerCase();
    renderProducts(products.filter(p=>p.name.toLowerCase().includes(q)));
  };

  loadProducts();
  setInterval(loadProducts,3000);
</script>

<script>

function openTicket(saleId){
  console.log('🎫 Ouverture du ticket - Sale ID:', saleId);
  
  const frame = document.getElementById('printFrame');
  if(!frame){
    console.error('❌ Élément printFrame non trouvé');
    alert('Erreur: élément d\'impression non trouvé');
    return;
  }
  
  frame.src = 'seller_ticket_pdf.php?sale_id=' + saleId;
  
  const printModal = new bootstrap.Modal(document.getElementById('printModal'));
  
  frame.onload = function(){
    console.log('✅ PDF chargé, lancement de l\'impression');
    setTimeout(() => {
      try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
      } catch(e) {
        console.error('Erreur impression:', e);
      }
    }, 500);
  };
  
  frame.onerror = function(){
    console.error('❌ Erreur chargement PDF');
  };
  
  printModal.show();
}

function printTicket(){
  const frame = document.getElementById('printFrame');
  if(frame && frame.contentWindow){
    frame.contentWindow.focus();
    frame.contentWindow.print();
  }
}



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

</body>
</html>

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
<title>Point de vente</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#f5f7fa}
.card{border-radius:14px}
.list-group-item{border-radius:10px;margin-bottom:6px}
.qty-btn{width:30px;height:30px}
</style>
</head>
<body class="p-3">

<div class="container-fluid" style="max-width:1200px">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-shop"></i>
      <?= htmlspecialchars($_SESSION['house_name']) ?>
    </h4>
    <small class="text-muted">
      Vendeur : <?= htmlspecialchars($_SESSION['agent_name']) ?>
    </small>
  </div>
  <div>
    <a href="seller_sales_history.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-clock-history"></i> Historique
    </a>
    <a href="auth.php?logout=1" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<div class="row g-3">

<!-- PRODUITS -->
<div class="col-lg-6">
  <div class="card p-3 shadow-sm">
    <input id="searchProd" class="form-control mb-2" placeholder="🔍 Rechercher un produit">
    <div id="productsList" style="max-height:520px;overflow:auto"></div>
  </div>
</div>

<!-- PANIER -->
<div class="col-lg-6">
  <div class="card p-3 shadow-sm">
    <h5 class="mb-3"><i class="bi bi-cart"></i> Panier</h5>

    <div id="cartItems"></div>

    <div class="mt-3">
      <label class="form-label">Remise</label>
      <input id="discount" type="number" class="form-control" value="0" min="0">
    </div>

    <div class="mt-3">
      <label class="form-label">Paiement</label>
      <select id="payment_method" class="form-select">
        <option value="cash">Espèces</option>
        <option value="mobile">Mobile Money</option>
        <option value="credit">Crédit</option>
      </select>
    </div>

    <div class="mt-3">
      <label class="form-label">Client</label>
      <input id="customer_name" class="form-control">
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
      <strong id="totalAmount">0</strong>
      <div>
        <button class="btn btn-light btn-sm" onclick="clearCart()">
          <i class="bi bi-trash"></i>
        </button>
        <button class="btn btn-success btn-sm" id="checkoutBtn">
          <i class="bi bi-check-circle"></i> Valider
        </button>
      </div>
    </div>

    <div id="posMsg" class="mt-2"></div>
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
  list.forEach(p=>{
    el.innerHTML+=`
    <div class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <strong>${p.name}</strong>
        <div class="small text-muted">
          ${p.sell_price} ${p.sell_currency} • Stock ${p.stock}
        </div>
      </div>
      <button class="btn btn-sm btn-primary" onclick="addToCart(${p.id})">
        <i class="bi bi-plus"></i>
      </button>
    </div>`;
  });
}

/* ===== PANIER ===== */
function addToCart(pid){
  const p=products.find(x=>x.id==pid);
  if(!p) return;
  let line=cart.find(c=>c.product_id==pid);
  if(line){
    if(line.qty<p.stock) line.qty++;
  }else{
    cart.push({...p,product_id:p.id,qty:1});
  }
  renderCart();
}

function renderCart(){
  const el=document.getElementById('cartItems');
  el.innerHTML='';
  let totals={};

  cart.forEach((c,i)=>{
    totals[c.sell_currency]=(totals[c.sell_currency]||0)+(c.sell_price*c.qty);
    el.innerHTML+=`
    <div class="d-flex justify-content-between border-bottom py-2">
      <div>
        <strong>${c.name}</strong><br>
        <small>${c.sell_price} ${c.sell_currency}</small>
      </div>
      <div class="d-flex align-items-center gap-1">
        <button class="btn btn-light btn-sm qty-btn" onclick="changeQty(${i},-1)">−</button>
        <span>${c.qty}</span>
        <button class="btn btn-light btn-sm qty-btn" onclick="changeQty(${i},1)">+</button>
        <button class="btn btn-danger btn-sm" onclick="removeItem(${i})">×</button>
      </div>
    </div>`;
  });

  let txt='';
  for(const cur in totals){
    txt+=`${totals[cur].toFixed(2)} ${cur} `;
  }
  document.getElementById('totalAmount').textContent=txt||'0';
}

function changeQty(i,d){
  cart[i].qty+=d;
  if(cart[i].qty<=0) cart.splice(i,1);
  renderCart();
}
function removeItem(i){ cart.splice(i,1); renderCart(); }
function clearCart(){ cart=[]; renderCart(); }

/* ===== VENTE ===== */
document.getElementById('checkoutBtn').onclick=()=>{
  if(!cart.length) return alert('Panier vide');
  if(!confirm('Confirmer la vente ?')) return;

  const p=new URLSearchParams();
  p.append('house_id',<?= $house_id ?>);
  p.append('items',JSON.stringify(cart));
  p.append('discount',discount.value);
  p.append('payment_method',payment_method.value);
  p.append('customer_name',customer_name.value);

  fetch('create_sale.php',{method:'POST',body:p})
    .then(r=>r.json())
    .then(j=>{
      posMsg.innerHTML=j.ok
        ?'<div class="alert alert-success">Vente enregistrée</div>'
        :'<div class="alert alert-danger">'+j.message+'</div>';
      if(j.ok){ clearCart(); loadProducts(); }
    });
};

searchProd.oninput=e=>{
  const q=e.target.value.toLowerCase();
  renderProducts(products.filter(p=>p.name.toLowerCase().includes(q)));
};

loadProducts();
setInterval(loadProducts,3000);
</script>

</body>
</html>


**************************************VERSION DEUX AVEC NODALE MAIS PAS FONCTIONNELLE*******************************
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
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Point de vente | Cartelplus Congo</title>

<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ===============================
   CHARTE CARTELPLUS CONGO
================================ */
:root{
  --cp-blue:#0A6FB6;
  --cp-orange:#F45B2A;
  --cp-black:#0B0B0B;
  --cp-white:#F8FAFC;
}

/* ===== BACKGROUND GLOBAL ===== */
body{
  min-height:100vh;
  background:
    radial-gradient(circle at top, rgba(10,111,182,.35), transparent 60%),
    linear-gradient(160deg, #050505, #0b0b0b 60%);
  color:var(--cp-white);
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
}

/* ===== CONTAINER ===== */
.container-fluid{
  max-width:1200px;
}

/* ===== HEADER ===== */
h4{
  color:var(--cp-white);
}
h4 i{
  color:var(--cp-orange);
}
small.text-muted{
  color:#aab4c0 !important;
}

/* ===== CARTES ===== */
.card{
  background:var(--cp-white);
  color:#111;
  border-radius:18px;
  border:none;
  box-shadow:0 20px 40px rgba(0,0,0,.45);
}

/* ===== INPUTS ===== */
.form-control,
.form-select{
  border-radius:12px;
  border:1px solid #dde3ea;
}

/* ===== LISTE PRODUITS ===== */
.list-group-item{
  border-radius:12px;
  margin-bottom:8px;
  border:1px solid #e4e9f0;
  transition:all .15s ease;
}
.list-group-item:hover{
  background:#eef5fb;
  transform:translateX(2px);
}

/* ===== BOUTONS ===== */
.btn-primary{
  background:var(--cp-blue);
  border:none;
}
.btn-primary:hover{
  background:#095f9b;
}

.btn-success{
  background:var(--cp-orange);
  border:none;
}
.btn-success:hover{
  background:#e24e1f;
}

.btn-outline-primary{
  color:var(--cp-blue);
  border-color:var(--cp-blue);
}
.btn-outline-primary:hover{
  background:var(--cp-blue);
  color:#fff;
}

.btn-outline-danger{
  border-color:var(--cp-orange);
  color:var(--cp-orange);
}
.btn-outline-danger:hover{
  background:var(--cp-orange);
  color:#fff;
}

/* ===== PANIER ===== */
.qty-btn{
  width:32px;
  height:32px;
  padding:0;
  font-weight:bold;
}

/* ===== TOTAL ===== */
#totalAmount{
  font-size:1.2rem;
  color:var(--cp-orange);
  font-weight:700;
}

/* ===== SCROLL ===== */
#productsList{
  max-height:520px;
  overflow:auto;
  scrollbar-width:thin;
}

/* ===== ANIMATIONS PREMIUM ===== */
.card{
  animation:fadeUp .35s ease both;
}
@keyframes fadeUp{
  from{opacity:0;transform:translateY(10px)}
  to{opacity:1;transform:none}
}

.btn{
  transition:all .15s ease;
}
.btn:active{
  transform:scale(.96);
}

/* ===== POS MOBILE ===== */
@media(max-width:991px){
  body{
    padding:.75rem !important;
  }
  .card{
    border-radius:14px;
  }
  #productsList{
    max-height:360px;
  }
  .qty-btn{
    width:38px;
    height:38px;
    font-size:1.1rem;
  }
  .btn-sm{
    padding:.5rem .75rem;
    font-size:.9rem;
  }
}








</style>
</head>

<body class="p-3">

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-shop"></i>
      <?= htmlspecialchars($_SESSION['house_name']) ?>
    </h4>
    <small class="text-muted">
      Vendeur : <?= htmlspecialchars($_SESSION['agent_name']) ?>
    </small>
  </div>
  <div>
    <a href="seller_sales_history.php" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-clock-history"></i> Historique
    </a>
    <a href="auth.php?logout=1" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<div class="row g-3">

<!-- PRODUITS -->
<div class="col-lg-6">
  <div class="card p-3">
    <input id="searchProd" class="form-control mb-2" placeholder="🔍 Rechercher un produit">
    <div id="productsList"></div>
  </div>
</div>

<!-- PANIER -->
<div class="col-lg-6">
  <div class="card p-3">
    <h5 class="mb-3">
      <i class="bi bi-cart"></i> Panier
    </h5>

    <div id="cartItems"></div>

    <div class="mt-3">
      <label class="form-label">Remise</label>
      <input id="discount" type="number" class="form-control" value="0" min="0">
    </div>

    <div class="mt-3">
      <label class="form-label">Paiement</label>
      <select id="payment_method" class="form-select">
        <option value="cash">Espèces</option>
        <option value="mobile">Mobile Money</option>
        <option value="credit">Crédit</option>
      </select>
    </div>

    <div class="mt-3">
      <label class="form-label">Client</label>
      <input id="customer_name" class="form-control">
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
      <strong id="totalAmount">0</strong>
      <div>
        <button class="btn btn-light btn-sm" onclick="clearCart()">
          <i class="bi bi-trash"></i>
        </button>
        <button class="btn btn-success btn-sm" id="checkoutBtn">
          <i class="bi bi-check-circle"></i> Valider
        </button>
      </div>
    </div>

    <div id="posMsg" class="mt-2"></div>
  </div>
</div>

</div>
</div>


<!-- MODAL MESSAGE -->
<div class="modal fade" id="msgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="msgTitle"></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="msgBody"></div>
      <div class="modal-footer">
        <button class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CONFIRMATION -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Confirmer la vente ?
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-success" id="confirmYes">Confirmer</button>
      </div>
    </div>
  </div>
</div>


<script>
const HOUSE_ID = <?= (int)$house_id ?>;
</script>

<script>
let products=[], cart=[];

// pour le nodale message 
const msgModal = new bootstrap.Modal('#msgModal');
const confirmModal = new bootstrap.Modal('#confirmModal');

function showMsg(title, body){
  msgTitle.textContent = title;
  msgBody.textContent = body;
  msgModal.show();
}



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
  list.forEach(p=>{
    el.innerHTML+=`
    <div class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <strong>${p.name}</strong>
        <div class="small text-muted">
          ${p.sell_price} ${p.sell_currency} • Stock ${p.stock}
        </div>
      </div>
      <button class="btn btn-sm btn-primary" onclick="addToCart(${p.id})">
        <i class="bi bi-plus"></i>
      </button>
    </div>`;
  });
}

/* ===== PANIER ===== */
function addToCart(pid){
  const p=products.find(x=>x.id==pid);
  if(!p) return;
  let line=cart.find(c=>c.product_id==pid);
  if(line){
    if(line.qty<p.stock) line.qty++;
  }else{
    cart.push({...p,product_id:p.id,qty:1});
  }
  renderCart();
}

function renderCart(){
  const el=document.getElementById('cartItems');
  el.innerHTML='';
  let totals={};

  cart.forEach((c,i)=>{
    totals[c.sell_currency]=(totals[c.sell_currency]||0)+(c.sell_price*c.qty);
    el.innerHTML+=`
    <div class="d-flex justify-content-between border-bottom py-2">
      <div>
        <strong>${c.name}</strong><br>
        <small>${c.sell_price} ${c.sell_currency}</small>
      </div>
      <div class="d-flex align-items-center gap-1">
        <button class="btn btn-light btn-sm qty-btn" onclick="changeQty(${i},-1)">−</button>
        <span>${c.qty}</span>
        <button class="btn btn-light btn-sm qty-btn" onclick="changeQty(${i},1)">+</button>
        <button class="btn btn-danger btn-sm" onclick="removeItem(${i})">×</button>
      </div>
    </div>`;
  });

  let txt='';
  for(const cur in totals){
    txt+=`${totals[cur].toFixed(2)} ${cur} `;
  }
  document.getElementById('totalAmount').textContent=txt||'0';
}

function changeQty(i,d){
  cart[i].qty+=d;
  if(cart[i].qty<=0) cart.splice(i,1);
  renderCart();
}
function removeItem(i){ cart.splice(i,1); renderCart(); }
function clearCart(){ cart=[]; renderCart(); }

/* ===== VENTE ===== */
document.getElementById('checkoutBtn').onclick=()=>{
      if(!cart.length){
          showMsg(
            'Panier vide',
            'Ajoutez au moins un produit avant de valider.'
          );
          return;
        }

        confirmModal.show();

        /* IMPORTANT : remove old handlers */
        confirmYes.onclick = null;

        confirmYes.onclick = function(){

          confirmModal.hide();

          const p = new URLSearchParams();
          p.append('house_id', HOUSE_ID);
          p.append('items', JSON.stringify(cart));
          p.append('discount', discount.value);
          p.append('payment_method', payment_method.value);
          p.append('customer_name', customer_name.value);

          fetch('create_sale.php',{
            method:'POST',
            body:p
          })
          .then(r=>r.json())
          .then(j=>{
            showMsg(
              j.ok ? 'Succès' : 'Erreur',
              j.ok
                ? 'Vente enregistrée avec succès.'
                : j.message
            );

            if(j.ok){
              clearCart();
              loadProducts();
            }
          })
          .catch(()=>{
            showMsg(
              'Erreur réseau',
              'Impossible de contacter le serveur.'
            );
          });
        };
};

searchProd.oninput=e=>{
  const q=e.target.value.toLowerCase();
  renderProducts(products.filter(p=>p.name.toLowerCase().includes(q)));
};

loadProducts();
setInterval(loadProducts,3000);


//éviter double clic rapide sur Valider :
  confirmYes.disabled = true;
  setTimeout(()=>confirmYes.disabled=false,800);
</script>

</body>
</html>

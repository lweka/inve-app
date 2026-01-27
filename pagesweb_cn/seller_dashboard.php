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



/* ===== FIX MODALES ===== */
.modal-content{
  color:#111 !important;
}

.modal-header{
  background:#f5f7fa;
  border-bottom:1px solid #e0e6ed;
}

.modal-title{
  font-weight:600;
}

.modal-body{
  font-size:0.95rem;
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
     <button class="btn btn-outline-primary btn-sm mb-2" onclick="openKitModal()"><i class="bi bi-boxes"></i> Kit produit</button>
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
        <h5 class="modal-title" id="msgTitle">Information</h5>
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
        <h5 class="modal-title">Confirmer la vente</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Voulez-vous vraiment valider cette vente ?
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-success" id="confirmSaleBtn">
          <i class="bi bi-check-circle"></i> Confirmer
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
          <i class="bi bi-boxes"></i> Composer un kit
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="mb-3">
          <strong>Produits disponibles</strong>
        </div>

        <div id="kitProducts" style="max-height:300px; overflow:auto"></div>

        <hr>

        <strong>Contenu du kit</strong>
        <div id="kitPreview" class="mt-2"></div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal" onclick="resetKitModal()">Annuler</button>
        <button class="btn btn-primary" onclick="addKitToCart()">
          <i class="bi bi-cart-plus"></i> Ajouter le kit au panier
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
          🧾 Aperçu du ticket
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" style="height:80vh">
        <iframe id="printFrame"
                src=""
                style="width:100%;height:100%;border:none">
        </iframe>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          Fermer
        </button>
        <button class="btn btn-success" onclick="printTicket()">
          🖨️ Imprimer
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
  let totals = {};

  cart.forEach((c,i)=>{

    /* ================= KIT ================= */
    if(c.is_kit){

      totals[c.sell_currency] =
        (totals[c.sell_currency] || 0) + c.total_price;

      el.innerHTML += `
        <div class="border-bottom py-2">
          <strong>${c.label}</strong>
          <div class="small text-muted">
            ${c.items.length} produits dans le kit
          </div>

          <ul class="small mt-1 mb-1">
            ${c.items.map(it =>
              `<li>${it.name} × ${it.qty}</li>`
            ).join('')}
          </ul>

          <div class="fw-bold text-end text-success">
            ${c.total_price.toFixed(2)} ${c.sell_currency}
          </div>

          <div class="text-end">
            <button class="btn btn-sm btn-danger"
              onclick="cart.splice(${i},1); renderCart();">
              ×
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
      <div class="d-flex justify-content-between border-bottom py-2">
        <div>
          <strong>${c.name}</strong><br>
          <small>${c.sell_price} ${c.sell_currency}</small>
        </div>
        <div class="d-flex align-items-center gap-1">
          <button class="btn btn-light btn-sm qty-btn"
            onclick="changeQty(${i},-1)">−</button>
          <span>${c.qty}</span>
          <button class="btn btn-light btn-sm qty-btn"
            onclick="changeQty(${i},1)">+</button>
          <button class="btn btn-danger btn-sm"
            onclick="removeItem(${i})">×</button>
        </div>
      </div>
    `;
  });

  let txt = '';
  for(const cur in totals){
    txt += `${totals[cur].toFixed(2)} ${cur} `;
  }
  document.getElementById('totalAmount').textContent = txt || '0';
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
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <div>
          <strong>${p.name}</strong><br>
          <small>${p.sell_price} ${p.sell_currency} • Stock ${p.stock}</small>
        </div>
        <div class="d-flex gap-1">
          <input type="number" min="1" max="${p.stock}" value="1"
            id="kit_qty_${p.id}" class="form-control form-control-sm" style="width:70px">
          <button class="btn btn-sm btn-outline-primary"
            onclick="addToKit(${p.id}, document.getElementById('kit_qty_${p.id}').value)">
            Ajouter
          </button>
        </div>
      </div>
    `;
  });
}


//JS — aperçu du kit
function renderKitPreview(){
  const el = document.getElementById('kitPreview');
  el.innerHTML = '';

  currentKit.forEach(k=>{
    el.innerHTML += `
      <div class="d-flex justify-content-between">
        <span>${k.name}</span>
        <span>x ${k.qty}</span>
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

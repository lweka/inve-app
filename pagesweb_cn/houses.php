<?php
// houses.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php'; // fournit $pdo
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

// récupération des maisons du client connecté
$stmt = $pdo->prepare("SELECT * FROM houses WHERE client_code = ? ORDER BY id DESC");
$stmt->execute([$client_code]);
$houses = $stmt->fetchAll();

// optional header include
if(isset($headerPath) && is_file($headerPath)){
    require_once $headerPath;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des maisons – Cartelplus Congo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
:root {
    --pp-blue: #0070e0;
    --pp-blue-dark: #003087;
    --pp-cyan: #00a8ff;
    --pp-bg: #f5f7fb;
    --pp-text: #0b1f3a;
    --pp-muted: #6b7a90;
    --pp-card: #ffffff;
    --pp-border: #e5e9f2;
    --pp-shadow: 0 12px 30px rgba(0, 48, 135, 0.08);
}

body {
    background: radial-gradient(1200px 600px at 10% -10%, rgba(0,112,224,0.12), transparent 60%),
                radial-gradient(1200px 600px at 110% 10%, rgba(0,48,135,0.10), transparent 60%),
                var(--pp-bg);
    color: var(--pp-text);
    min-height: 100vh;
    font-family: "Segoe UI", system-ui, sans-serif;
}

.page-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 16px 60px;
}

.page-hero {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 26px;
    position: relative;
    overflow: hidden;
    animation: fadeSlide 0.7s ease both;
}

  .page-hero > * {
    position: relative;
    z-index: 1;
  }

.page-hero::after {
    content: "";
    position: absolute;
    inset: -60% -20% auto auto;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 60%);
    animation: pulseGlow 3.2s ease-in-out infinite;
  pointer-events: none;
}

.page-hero h3 {
    font-size: 26px;
    font-weight: 700;
    margin: 0;
    color: #fff;
}

.btn-pp {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 18px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.btn-pp-primary {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    box-shadow: 0 10px 24px rgba(0, 112, 224, 0.25);
}

.btn-pp-secondary {
    background: #fff;
    color: var(--pp-blue-dark);
    border-color: var(--pp-border);
}

.btn-pp-danger {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.25);
}

.btn-pp:hover {
    transform: translateY(-1px);
    opacity: 0.95;
}

.alert {
    border-radius: 12px;
    padding: 14px 18px;
    animation: fadeSlide 0.5s ease both;
}

.houses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 18px;
}

.card-house {
    background: var(--pp-card);
    border: 1px solid var(--pp-border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--pp-shadow);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    animation: fadeUp 0.6s ease both;
}

.card-house:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.14);
}

.card-house h5 {
    color: var(--pp-blue-dark);
    font-weight: 700;
    margin-bottom: 10px;
}

.code-mask {
    background: rgba(0,112,224,0.08);
    color: var(--pp-blue-dark);
    padding: 6px 12px;
    border-radius: 8px;
    letter-spacing: 2px;
    font-family: monospace;
}

.pointer {
    cursor: pointer;
}

.modal-content {
    border-radius: 16px;
    border: 1px solid var(--pp-border);
}

.modal-header {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 16px 16px 0 0;
}

.modal-header.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
}

.form-control {
    border-radius: 8px;
    border: 1px solid var(--pp-border);
}

.form-control:focus {
    border-color: var(--pp-blue);
    box-shadow: 0 0 0 3px rgba(0,112,224,0.1);
}

@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.15); opacity: 1; }
}
</style>
</head>

<body>

<div class="page-wrap">
  <div class="page-hero">
    <h3>Gestion des maisons</h3>
    <button class="btn-pp btn-pp-primary" data-bs-toggle="modal" data-bs-target="#createHouseModal">+ Créer une maison</button>
  </div>
  <a href="<?=DASHBOARD_ADMIN?>" class="btn-pp btn-pp-secondary mb-3" style="display:inline-flex;">← Retour</a>

  <!-- Alerts -->
  <div id="alerts-container" class="mb-3">
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
      <div class="alert alert-success alert-dismissible fade show" id="app-alert">
        <strong>Succès :</strong> La maison a été créée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if(isset($_GET['err'])):
        $errors = json_decode(urldecode($_GET['err']), true);
        if(!$errors) $errors = [htmlspecialchars($_GET['err'])];
    ?>
      <div class="alert alert-danger alert-dismissible fade show" id="app-alert">
        <strong>Erreur :</strong>
        <ul class="mb-0">
          <?php foreach($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
  </div>

  <div class="houses-grid">
    <?php foreach($houses as $h): ?>
        <div class="card-house" style="animation-delay: 0.<?= array_search($h, $houses) * 5 ?>s;">


          <h5><?= htmlspecialchars($h['name']) ?></h5>
          <div class="mb-2 d-flex align-items-center">
            <span class="text-muted small me-1">Code :</span>

            <span class="code-mask fw-bold" id="code_display_<?= $h['id'] ?>">●●●●●●</span>

            <i class="fa-solid fa-lock ms-2 text-secondary pointer"
              id="code_icon_<?= $h['id'] ?>"
              onclick="requestShowCode(<?= $h['id'] ?>)"
              style="font-size: 16px;"></i>
          </div>

        
          <div class="text-muted small">Type : <?= htmlspecialchars($h['type']) ?></div>
          <div class="text-muted small mb-3">Adresse : <?= htmlspecialchars($h['address']) ?></div>
          <a href="<?=AGENTS_MANAGE?>?house_id=<?= $h['id'] ?>" class="btn-pp btn-pp-secondary btn-sm mb-2 w-100" style="justify-content: center;"><i class="fa-solid fa-users"></i> Gérer les vendeurs</a>
          <a href="<?=PRODUCTS_MANAGE?>?house_id=<?= $h['id'] ?>" class="btn-pp btn-pp-primary btn-sm mb-2 w-100" style="justify-content: center;"><i class="fa-solid fa-box"></i> Gérer les produits</a>
          <button class="btn-pp btn-pp-danger btn-sm w-100" style="justify-content: center;" onclick="openDeleteModal(<?= $h['id'] ?>, '<?= addslashes(htmlspecialchars($h['name'])) ?>')"><i class="fa-solid fa-trash"></i> Supprimer</button>
        </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal creation -->
<div class="modal fade" id="createHouseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Créer une maison</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="createHouseForm" method="POST" action="<?= (defined('HOUSES_CREATE') ? HOUSES_CREATE : 'houses_create.php'); ?>" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input id="name" name="name" type="text" class="form-control" required minlength="3" maxlength="150">
            <div class="invalid-feedback" id="nameFeedback"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Code maison (unique) <span class="text-danger">*</span></label>
            <input id="code" name="code" type="text" class="form-control" required minlength="2" maxlength="50"
                   pattern="^[A-Za-z0-9_\-]+$">
            <div class="invalid-feedback" id="codeFeedback"></div>
            <div class="form-text">Lettres, chiffres, tirets (-) ou underscores (_).</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Type</label>
            <input id="type" name="type" type="text" class="form-control" maxlength="100">
            <div class="invalid-feedback" id="typeFeedback"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Adresse <span class="text-danger">*</span></label>
            <textarea id="address" name="address" class="form-control" required minlength="5" maxlength="255"></textarea>
            <div class="invalid-feedback" id="addressFeedback"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
          <button id="openConfirmBtn" type="button" class="btn-pp btn-pp-primary">Valider</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Confirmer l'action</h5>
      </div>
      <div class="modal-body">
        <p class="fw-bold text-center">Voulez-vous vraiment créer cette maison ?<br><span class="text-danger">(Action irrévocable)</span></p>
        <div id="confirmSummary" class="small text-center text-muted"></div>
      </div>
      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
        <button id="submitFinalBtn" class="btn-pp btn-pp-danger">Valider définitivement</button>
      </div>
    </div>
  </div>
</div>





<!-- Modal: confirmer pour afficher le code -->
<div class="modal fade" id="pwModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Authentification Administrateur</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="pw_alert" class="alert d-none"></div>

        <div class="mb-3">
          <label>Nom d'utilisateur (optionnel)</label>
          <input id="pw_user" class="form-control">
        </div>

        <div class="mb-3">
          <label>Mot de passe</label>
          <input id="pw_pass" class="form-control" type="password">
        </div>

        <input type="hidden" id="pw_house_id">
      </div>

      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
        <button id="pw_submit" class="btn-pp btn-pp-primary">Valider</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: demander email pour suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Supprimer cette maison</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="text-danger fw-bold">
          ⚠️ Attention : Cette action est irréversible.
        </p>

        <div id="delete_alert" class="alert d-none"></div>

        <label>Email où envoyer le code de suppression :</label>
        <input id="del_email" type="email" class="form-control">
        <input type="hidden" id="del_house_id">
      </div>

      <div class="modal-footer">
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
        <button id="del_request_btn" class="btn-pp btn-pp-danger">Continuer</button>
      </div>

    </div>
  </div>
</div>





<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* DOM */
const nameInput = document.getElementById('name');
const codeInput = document.getElementById('code');
const typeInput = document.getElementById('type');
const addressInput = document.getElementById('address');

const nameFeedback = document.getElementById('nameFeedback');
const codeFeedback = document.getElementById('codeFeedback');
const typeFeedback = document.getElementById('typeFeedback');
const addressFeedback = document.getElementById('addressFeedback');

const createForm = document.getElementById('createHouseForm');
const openConfirmBtn = document.getElementById('openConfirmBtn');
const submitFinalBtn = document.getElementById('submitFinalBtn');

let codeUnique = false;
let checkingCode = false;
let debounceTimer = null;

/* helper affichage */
function showInvalid(input, box, msg){
  input.classList.add('is-invalid'); input.classList.remove('is-valid');
  box.textContent = msg;
}
function showValid(input, box){
  input.classList.remove('is-invalid'); input.classList.add('is-valid');
  box.textContent = '';
}

/* live validation */
nameInput.addEventListener('input', () => {
  const v = nameInput.value.trim();
  if(v.length < 3) showInvalid(nameInput, nameFeedback, 'Le nom doit contenir au moins 3 caractères.');
  else showValid(nameInput, nameFeedback);
});
addressInput.addEventListener('input', () => {
  const v = addressInput.value.trim();
  if(v.length < 5) showInvalid(addressInput, addressFeedback, "L'adresse doit contenir au moins 5 caractères.");
  else showValid(addressInput, addressFeedback);
});
typeInput.addEventListener('input', () => {
  if(typeInput.value.length > 100) showInvalid(typeInput, typeFeedback, 'Le type est trop long.');
  else showValid(typeInput, typeFeedback);
});

/* API URL from PHP constant or fallback relative */
const checkHouseCodeUrl = "<?php echo (defined('CHECK_HOUSECODE') ? CHECK_HOUSECODE : '../api/check_house_code.php'); ?>";

/* code input : debounce + AJAX */
codeInput.addEventListener('input', () => {
  codeUnique = false;
  const v = codeInput.value.trim();
  if(v.length < 2){ showInvalid(codeInput, codeFeedback, 'Le code doit contenir au moins 2 caractères.'); return; }
  if(v.length > 50){ showInvalid(codeInput, codeFeedback, 'Le code est trop long.'); return; }
  if(!/^[A-Za-z0-9_\-]+$/.test(v)){ showInvalid(codeInput, codeFeedback, 'Caractères invalides. Lettres, chiffres, - ou _ seulement.'); return; }

  clearTimeout(debounceTimer);
  checkingCode = true;
  codeFeedback.textContent = 'Vérification du code…';
  debounceTimer = setTimeout(()=> checkCodeUniqueness(v), 400);
});

function checkCodeUniqueness(code){
  fetch(checkHouseCodeUrl + '?code=' + encodeURIComponent(code), { credentials: 'same-origin' })
    .then(async (r) => {
      const text = await r.text();
      try {
        const j = JSON.parse(text);
        checkingCode = false;
        if(j.ok === false && j.message){
          showInvalid(codeInput, codeFeedback, j.message || 'Erreur vérif.');
          codeUnique = false;
          return;
        }
        if(j.exists){
          codeUnique = false;
          showInvalid(codeInput, codeFeedback, 'Ce code est déjà utilisé.');
        } else {
          codeUnique = true;
          showValid(codeInput, codeFeedback);
        }
      } catch (err) {
        checkingCode = false;
        console.error('Erreur vérif code: Non JSON reçu:', text);
        showInvalid(codeInput, codeFeedback, 'Erreur lors de la vérification (réponse invalide).');
      }
    })
    .catch((err) => {
      checkingCode = false;
      console.error('Erreur vérif code (fetch):', err);
      showInvalid(codeInput, codeFeedback, 'Impossible de vérifier le code (erreur réseau). Réessayez.');
    });
}

/* open confirm and submit handlers (same as before) */
openConfirmBtn.addEventListener('click', () => {
  const nameV = nameInput.value.trim();
  const codeV = codeInput.value.trim();
  const addrV = addressInput.value.trim();

  if(nameV.length < 3){ showInvalid(nameInput, nameFeedback, 'Le nom doit contenir au moins 3 caractères.'); return; }
  if(addrV.length < 5){ showInvalid(addressInput, addressFeedback, "L'adresse doit contenir au moins 5 caractères."); return; }
  if(!/^[A-Za-z0-9_\-]+$/.test(codeV)){ showInvalid(codeInput, codeFeedback, 'Code invalide.'); return; }

  if(checkingCode){
    alert('La vérification du code est en cours. Patientez un instant.');
    return;
  }
  if(!codeUnique){
    showInvalid(codeInput, codeFeedback, 'Veuillez fournir un code unique.');
    return;
  }

  document.getElementById('confirmSummary').innerHTML =
    `<strong>${escapeHtml(nameV)}</strong><br>Code: <code>${escapeHtml(codeV)}</code><br>Type: ${escapeHtml(typeInput.value)}<br>${escapeHtml(addrV)}`;

  new bootstrap.Modal(document.getElementById('confirmModal')).show();
});

submitFinalBtn.addEventListener('click', () => {
  submitFinalBtn.disabled = true;
  submitFinalBtn.textContent = 'Enregistrement…';
  createForm.submit();
});

function escapeHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* auto clear alert */
window.addEventListener('load', () => {
  const alertEl = document.getElementById('app-alert');
  if(!alertEl) return;
  setTimeout(()=> {
    try{ alertEl.classList.remove('show'); }catch(e){}
    if(window.history && window.history.replaceState){
      const cleanUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
      window.history.replaceState({}, document.title, cleanUrl);
    }
  }, 5000);
});



</script>

<!-- JS pour modals afficher code et suppression -->
<script>
// ouvrir modal pour afficher code
function requestShowCode(houseId){
  document.getElementById('pw_house_id').value = houseId;
  document.getElementById('pw_user').value = "";
  document.getElementById('pw_pass').value = "";
  document.getElementById('pw_alert').classList.add('d-none');
  new bootstrap.Modal(document.getElementById('pwModal')).show();
}

document.getElementById('pw_submit').addEventListener('click', function(){
  let user = document.getElementById('pw_user').value;
  let pass = document.getElementById('pw_pass').value;
  let hid = document.getElementById('pw_house_id').value;

  if(pass.trim() === ""){
    let a = document.getElementById('pw_alert');
    a.classList.remove('d-none'); a.classList.add('alert-danger');
    a.innerHTML = "Mot de passe requis.";
    return;
  }

  this.disabled = true;
  this.textContent = "Vérification…";

  fetch("<?= ADMIN_CHECK_HOUSE;?>", {
    method: "POST",
    body: new URLSearchParams({
      username: user,
      password: pass,
      house_id: hid
    })
  })
  .then(r => r.json())
  .then(j => {
    this.disabled = false;
    this.textContent = "Valider";


    if(j.ok){
    document.getElementById("code_display_"+hid).textContent = j.code;

    // changement icône lock → unlock
    let icon = document.getElementById("code_icon_"+hid);
    icon.classList.remove("fa-lock");
    icon.classList.add("fa-lock-open");
    icon.style.color = "#0d6efd";

    bootstrap.Modal.getInstance(document.getElementById('pwModal')).hide();
  } else {
      let a = document.getElementById('pw_alert');
      a.classList.remove('d-none'); a.classList.add('alert-danger');
      a.innerHTML = j.message;
    }










  })
  .catch(() => {
    this.disabled = false;
    this.textContent = "Valider";
  });
});


// --- suppression maison ---
function openDeleteModal(houseId){
  document.getElementById('del_house_id').value = houseId;
  document.getElementById('delete_alert').classList.add('d-none');
  document.getElementById('del_email').value = "";
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('del_request_btn').addEventListener('click', function(){
  let email = document.getElementById('del_email').value;
  let houseId = document.getElementById('del_house_id').value;

  if(!email.includes("@")){
    let a = document.getElementById('delete_alert');
    a.classList.remove('d-none'); a.classList.add('alert-danger');
    a.innerHTML = "Adresse email invalide.";
    return;
  }

  this.disabled = true;
  this.textContent = "Envoi…";

  fetch("<?=SEND_HOUSE_DELETE_CODE;?>", {
    method: "POST",
    body: new URLSearchParams({
      email: email,
      house_id: houseId
    })
  })
  .then(r => r.json())
  .then(j => {
    this.disabled = false;
    this.textContent = "Continuer";

    if(j.ok){
    window.location.href = "<?= (defined('DELETE_HOUSE_WAIT') ? DELETE_HOUSE_WAIT : 'delete_house_wait.php') ?>?request_id=" + j.request_id;
} else {
      let a = document.getElementById('delete_alert');
      a.classList.remove('d-none'); a.classList.add('alert-danger');
      a.innerHTML = j.message;
    }
  })
  .catch(() => {
    this.disabled = false;
    this.textContent = "Continuer";
  });
});
</script>

</body>
</html>

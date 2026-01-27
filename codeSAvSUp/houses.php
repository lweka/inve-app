<?php
// houses.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php'; // fournit $pdo

if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!=='admin'){
    header("Location: ".PARSE_CONNECT."?role=admin");
    exit;
}

// récupération des maisons
$houses = $pdo->query("SELECT * FROM houses ORDER BY id DESC")->fetchAll();

// optional header include
if(isset($headerPath) && is_file($headerPath)){
    require_once $headerPath;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Gestion des maisons</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card-house { border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,.07); transition:.2s; }
    .card-house:hover{ transform: translateY(-3px); }
    .invalid-feedback{ display:block; }
  </style>
</head>
<body>

<div class="container py-4">
  <div class="d-flex justify-content-between mb-4">
    <h3 class="fw-bold">Gestion des maisons</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createHouseModal">+ Créer une maison</button>
  </div>

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

  <div class="row">
    <?php foreach($houses as $h): ?>
      <div class="col-md-4 mb-3">
        <div class="card card-house p-3">
          <h5><?= htmlspecialchars($h['name']) ?></h5>
          <div class="text-muted small">Code : <?= htmlspecialchars($h['code']) ?></div>

          <span class="text-muted small">Code : </span>
          <span class="code-mask" id="code_display_<?= $h['id'] ?>">●●●●●●</span>
          <button class="btn btn-sm btn-outline-secondary ms-2"onclick="requestShowCode(<?= $h['id'] ?>)">Afficher</button>

          
          <div class="text-muted small">Type : <?= htmlspecialchars($h['type']) ?></div>
          <div class="text-muted small mb-3">Adresse : <?= htmlspecialchars($h['address']) ?></div>
          <a href="agents.php?house_id=<?= $h['id'] ?>" class="btn btn-outline-secondary btn-sm mb-1 w-100">Gérer les vendeurs</a>
          <a href="products.php?house_id=<?= $h['id'] ?>" class="btn btn-outline-success btn-sm mb-1 w-100">Gérer les produits</a>
          <button class="btn btn-outline-danger btn-sm w-100" onclick="openDeleteModal(<?= $h['id'] ?>, '<?= addslashes(htmlspecialchars($h['name'])) ?>')">Supprimer cette maison</button>
        </div>
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
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          <button id="openConfirmBtn" type="button" class="btn btn-primary">Valider</button>
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
        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button id="submitFinalBtn" class="btn btn-danger">Valider définitivement</button>
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
</body>
</html>

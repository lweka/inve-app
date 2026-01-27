<?php
// pagesweb_cn/delete_house_wait.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';







// expected GET: request_id
$request_id = intval($_GET['request_id'] ?? 0);
if($request_id <= 0){
    header("Location: houses.php");
    exit;
}

// get request
$stmt = $pdo->prepare("SELECT * FROM house_delete_requests WHERE id = ?");
$stmt->execute([$request_id]);
$req = $stmt->fetch();
if(!$req){
    echo "Demande introuvable."; exit;
}

// load house
$stmt = $pdo->prepare("SELECT * FROM houses WHERE id = ?");
$stmt->execute([$req['house_id']]);
$house = $stmt->fetch();
if(!$house){
    echo "Maison introuvable."; exit;
}

$expires_at = intval($req['expires_at']);
$remaining = $expires_at - time();
if($remaining < 0) $remaining = 0;
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Suppression maison</title>
<link href="../css/bootstrap.min.css" rel="stylesheet"></head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .success-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #d4edda;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #28a745;
    animation: popIn .4s ease-out;
    }
    .success-circle i {
    font-size: 40px;
    color: #28a745;
    }

    @keyframes popIn {
    0% { transform: scale(0.4); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
    }
</style>

<body class="p-4">
<div class="container" style="max-width:720px">
  <h4>Suppression : <?=htmlspecialchars($house['name'])?></h4>
  <p><strong>Adresse :</strong> <?=htmlspecialchars($house['address'])?></p>
  <p><strong>Code maison (masqué):</strong> *******</p>

  <div class="alert alert-warning">
    Un code a été envoyé à <strong><?=htmlspecialchars($req['email'])?></strong>.
    Entrez-le ci-dessous dans les <?php echo ceil($remaining); ?> secondes.
  </div>

  <div id="expired" class="alert alert-danger d-none">Délai expiré. Vous allez être redirigé.</div>

  <form id="verifyForm">
    <div class="mb-3"><label>Code de suppression</label><input name="code" id="code" class="form-control" autocomplete="off"></div>
    <input type="hidden" name="request_id" value="<?= $request_id ?>">
    <button class="btn btn-danger">Vérifier et supprimer</button>
    <a href="houses.php" class="btn btn-secondary">Annuler</a>
  </form>

  <div class="mt-3">Temps restant: <span id="timer"><?= $remaining ?></span>s</div>
</div>

<script>
let remaining = <?= $remaining ?>;
const timerEl = document.getElementById('timer');
const expiredEl = document.getElementById('expired');

const interval = setInterval(()=>{
  remaining--;
  timerEl.textContent = remaining;
  if(remaining <= 0){
    clearInterval(interval);
    expiredEl.classList.remove('d-none');
    setTimeout(()=> { window.location.href = 'houses.php'; }, 2000);
  }
}, 1000);

document.getElementById('verifyForm').addEventListener('submit', (e)=>{
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);
  
  fetch("<?=VERIFY_HOUSE_DELETE_CODE?>", { method:'POST', body: data })
    .then(r=>r.json()).then(j=>{
      if(j.ok){
        alert('Maison supprimée.');
        window.location.href = 'houses.php';
      }else {
        alert('Erreur: ' + (j.message || 'Code invalide'));
      }
    }).catch(()=> alert('Erreur réseau'));
});
</script>




<!-- Modal succès -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">

      <div class="text-center mb-3">
        <div class="success-circle mx-auto">
          <i class="fa-solid fa-check"></i>
        </div>
      </div>

      <h4 class="mb-2">Suppression réussie</h4>
      <p class="text-muted">La maison a été supprimée avec succès.</p>

      <p class="small text-secondary">Redirection dans <span id="successTimer">3</span>…</p>

    </div>
  </div>
</div>







</body>
</html>

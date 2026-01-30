<?php
// agents.php
require_once __DIR__ . '/../configUrlcn.php';
require_once __DIR__ . '/../defConstLiens.php';
require_once __DIR__ . '/connectDb.php';
require_once __DIR__ . '/require_admin_auth.php'; // charge $client_code

$house_id = intval($_GET['house_id'] ?? 0);
if($house_id <= 0){ header("Location: houses.php"); exit; }

// fetch house (sécurisé par client_code)
$stmt = $pdo->prepare("SELECT * FROM houses WHERE id = ? AND client_code = ?");
$stmt->execute([$house_id, $client_code]);
$house = $stmt->fetch();

if(!$house){
    header("Location: houses.php");
    exit;
}

// fetch agents (client connecté uniquement)
$stmt = $pdo->prepare("SELECT * FROM agents WHERE house_id = ? AND client_code = ? ORDER BY id DESC");
$stmt->execute([$house_id, $client_code]);
$agents = $stmt->fetchAll();

// Header
if(isset($headerPath) && is_file($headerPath)){
    require_once $headerPath;
}
?>

<?php $AGENT_DELETE_URL = 'agent_delete.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des maisons – Cartelplus Congo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
/* ==============================
   CHARTE CARTELPLUS CONGO
============================== */
:root{
  --cp-blue:#0b6fbf;
  --cp-blue-dark:#0a2540;
  --cp-orange:#f25c2a;
  --cp-black:#050505;
  --cp-card:#0f2f4f;
}

/* ===== BACKGROUND GLOBAL ===== */
body{
  background: radial-gradient(circle at top, #0a2540 0%, #050505 70%);
  color:#e9f1f8;
  min-height:100vh;
  font-family: 'Segoe UI', system-ui, sans-serif;
}

</style>
</head>

<body>


<div class="container py-4">

  <h3 class="fw-bold mb-1">Gestion des vendeurs</h3>
  <div class="text-muted mb-4">
    Maison : <strong><?=htmlspecialchars($house['name'])?></strong>
  </div>
    <a href="<?=HOUSES_MANAGE?>" class="btn btn-secondary mb-3">
        ← Retour aux maisons
    </a>

  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createAgentModal">
    + Ajouter un vendeur
  </button>

  <div class="row">
    <?php foreach($agents as $a): ?>
      <div class="col-md-4 mb-3">
        <div class="card-agent">

          <h5><?= htmlspecialchars($a['fullname']) ?></h5>

          <div class="small text-muted">Téléphone : <?= htmlspecialchars($a['phone']) ?></div>
          <div class="small text-muted">Adresse : <?= htmlspecialchars($a['address']) ?></div>
          <div class="small text-muted mb-1">
            Code vendeur : <code><?= htmlspecialchars($a['seller_code']) ?></code>
          </div>

          <div class="mb-2">
            Statut :
            <span class="agent-status <?= $a['status'] ?>">
                <?= $a['status'] === 'active' ? 'Actif' : 'Inactif' ?>
            </span>
          </div>

          <button class="btn btn-outline-secondary btn-sm w-100 mb-1"
            onclick="toggleStatus(<?= $a['id'] ?>)">
            <?= $a['status'] === 'active' ? 'Désactiver' : 'Activer' ?>
          </button>

          <button class="btn btn-outline-danger btn-sm w-100"
            onclick="deleteAgent(<?= $a['id'] ?>, '<?= addslashes(htmlspecialchars($a['fullname'])) ?>')">
            Supprimer
          </button>

        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- MODAL CREATE -->
<div class="modal fade" id="createAgentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Ajouter un vendeur</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="<?=AGENTS_CREATE; ?>">
      <div class="modal-body">

        <input type="hidden" name="house_id" value="<?= $house_id ?>">

        <div class="mb-3">
          <label class="form-label">Nom complet</label>
          <input class="form-control" name="fullname" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Téléphone</label>
          <input class="form-control" name="phone" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Adresse du vendeur</label>
          <input class="form-control" name="address" required>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button class="btn btn-primary">Créer vendeur</button>
      </div>

      </form>
    </div>
  </div>
</div>


<script src="../js/bootstrap.min.js"></script>
<script src="../js/jquery.min.js"></script>

<script>
// changer statut vendeur
function toggleStatus(id){
  $.post("agent_update_status.php?", { id:id }, function(res){
      if(res.ok){
        location.reload();
      } else {
        alert(res.message);
      }
  }, "json");
}

// supprimer vendeur
async function deleteAgent(id, name){
  if(!confirm("Supprimer le vendeur : " + name + " ?")) return;

  try {
    const form = new URLSearchParams({ id: id });
    const resp = await fetch('agent_delete.php?go=1', {   // <-- FIX 100%
      method: 'POST',
      body: form,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });

    if(!resp.ok){
      alert('Erreur réseau: ' + resp.status);
      return;
    }
    const j = await resp.json();
    if(j.ok){
      location.reload();
    } else {
      alert(j.message || 'Erreur serveur');
    }
  } catch(err){
    console.error(err);
    alert('Erreur réseau (check console)');
  }
}


</script>

</body>
</html>

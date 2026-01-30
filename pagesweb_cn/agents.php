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
    --pp-success: #1f8f6a;
    --pp-danger: #dc2626;
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
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 16px 60px;
}

.page-hero {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.2);
    margin-bottom: 26px;
    animation: fadeSlide 0.7s ease both;
}

.page-hero h3 {
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.page-hero .subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 16px;
}

.btn-pp {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
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
    background: linear-gradient(135deg, var(--pp-danger), #991b1b);
    color: #fff;
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.25);
}

.btn-pp:hover {
    transform: translateY(-1px);
    opacity: 0.95;
}

.btn-sm {
    padding: 7px 14px;
    font-size: 13px;
}

.agents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 18px;
}

.card-agent {
    background: var(--pp-card);
    border: 1px solid var(--pp-border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--pp-shadow);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    animation: fadeUp 0.6s ease both;
}

.card-agent:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 36px rgba(0, 48, 135, 0.14);
}

.card-agent h5 {
    color: var(--pp-blue-dark);
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 12px;
}

.agent-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.agent-status.active {
    background: rgba(31, 143, 106, 0.15);
    color: var(--pp-success);
}

.agent-status.inactive {
    background: rgba(107, 122, 144, 0.15);
    color: var(--pp-muted);
}

.modal-content {
    border-radius: 16px;
    border: 1px solid var(--pp-border);
}

.modal-header {
    background: linear-gradient(135deg, var(--pp-blue), var(--pp-blue-dark));
    color: #fff;
    border-radius: 16px 16px 0 0;
    padding: 20px 24px;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--pp-border);
}

.form-control {
    border-radius: 8px;
    border: 1px solid var(--pp-border);
    padding: 10px 14px;
}

.form-control:focus {
    border-color: var(--pp-blue);
    box-shadow: 0 0 0 3px rgba(0,112,224,0.1);
}

.form-label {
    font-weight: 600;
    color: var(--pp-text);
    margin-bottom: 6px;
}

@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>
</head>

<body>


<div class="page-wrap">

  <div class="page-hero">
    <div>
      <h3>👥 Gestion des vendeurs</h3>
      <div class="subtitle">Maison : <strong><?=htmlspecialchars($house['name'])?></strong></div>
    </div>
    <div style="display: flex; gap: 10px;">
      <button class="btn-pp btn-pp-primary" data-bs-toggle="modal" data-bs-target="#createAgentModal">
        <i class="fa-solid fa-plus"></i> Ajouter un vendeur
      </button>
      <a href="<?=HOUSES_MANAGE?>" class="btn-pp btn-pp-secondary">
        ← Retour
      </a>
    </div>
  </div>

  <div class="agents-grid">
    <?php foreach($agents as $idx => $a): ?>
      <div style="animation-delay: 0.<?= $idx * 3 ?>s;">
        <div class="card-agent">

          <h5><?= htmlspecialchars($a['fullname']) ?></h5>

          <div class="small" style="color: var(--pp-muted); line-height: 1.6;">
            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($a['phone']) ?><br>
            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($a['address']) ?><br>
            <i class="fa-solid fa-barcode"></i> Code : <code style="background: rgba(0,112,224,0.1); padding: 2px 8px; border-radius: 6px; color: var(--pp-blue-dark);"><?= htmlspecialchars($a['seller_code']) ?></code>
          </div>

          <div class="mt-3 mb-3">
            Statut :
            <span class="agent-status <?= $a['status'] ?>">
                <?= $a['status'] === 'active' ? 'Actif' : 'Inactif' ?>
            </span>
          </div>

          <button class="btn-pp btn-pp-secondary btn-sm w-100 mb-2"
            onclick="toggleStatus(<?= $a['id'] ?>)" style="justify-content: center;">
            <i class="fa-solid fa-power-off"></i> <?= $a['status'] === 'active' ? 'Désactiver' : 'Activer' ?>
          </button>

          <button class="btn-pp btn-pp-danger btn-sm w-100"
            onclick="deleteAgent(<?= $a['id'] ?>, '<?= addslashes(htmlspecialchars($a['fullname'])) ?>')" style="justify-content: center;">
            <i class="fa-solid fa-trash"></i> Supprimer
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
        <button class="btn-pp btn-pp-secondary" data-bs-dismiss="modal">Annuler</button>
        <button class="btn-pp btn-pp-primary">Créer vendeur</button>
      </div>

      </form>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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

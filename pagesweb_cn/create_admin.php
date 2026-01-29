
<?php
// pagesweb_cn/create_admin.php
require_once __DIR__ . '/connectDb.php';


// Si au moins un admin existe on redirige vers login/admin_manage
$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
if($stmt->fetchColumn() > 0){
    header("Location: admin_manage.php");
    exit;
}

$errors = [];
$success = false;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if($name === '') $errors[] = "Nom requis.";
    if($username === '') $errors[] = "Nom d'utilisateur requis.";
    if($password === '' || strlen($password) < 6) $errors[] = "Mot de passe requis (>=6).";
    if($password !== $password2) $errors[] = "Les mots de passe ne correspondent pas.";

    if(empty($errors)){
        // vérifier unicité username
        $s = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $s->execute([$username]);
        if($s->fetch()) $errors[] = "Nom d'utilisateur déjà pris.";
        else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO admins (name, username, password_hash) VALUES (?,?,?)");
            $ins->execute([$name, $username, $hash]);
            $success = true;
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Créer un administrateur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="../css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f7fafd; }
    .admin-form-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,112,186,0.08);
      padding: 2.5rem 2rem 2rem 2rem;
      margin: 40px auto;
      max-width: 480px;
      border: 1px solid #e5e7eb;
    }
    .form-label { font-weight: 600; color: #003087; }
    .form-control:focus { border-color: #0070ba; box-shadow: 0 0 0 0.2rem #0070ba22; }
    .btn-primary {
      background: linear-gradient(90deg,#0070ba 0%,#003087 100%);
      border: none;
      font-weight: 700;
      border-radius: 8px;
      transition: background 0.2s;
    }
    .btn-primary:hover { background: #ffc439; color: #003087; }
    .form-floating>label { color: #888; }
    .fade-in { animation: fadeIn 0.7s; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(20px);} to { opacity:1; transform:none; } }
  </style>
</head>
<body>
<div class="admin-form-card fade-in">
  <h3 class="mb-4 text-center" style="color:#003087;font-weight:700;">Créer l'administrateur</h3>
  <?php if($success): ?>
    <!-- Modal de succès -->
    <div class="modal show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Succès</h5>
          </div>
          <div class="modal-body">
            <p>L'administrateur a été créé avec succès.</p>
            <a href="admin_manage.php" class="btn btn-success">Gérer les admins</a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <?php if($errors): ?>
    <div class="alert alert-danger fade-in"><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
  <?php endif; ?>
  <form method="post" class="needs-validation" novalidate autocomplete="off">
    <div class="form-floating mb-3">
      <input name="name" class="form-control" id="name" placeholder="Nom" required>
      <label for="name">Nom</label>
      <div class="invalid-feedback">Veuillez saisir votre nom.</div>
    </div>
    <div class="form-floating mb-3">
      <input name="username" class="form-control" id="username" placeholder="Nom d'utilisateur" required>
      <label for="username">Nom d'utilisateur</label>
      <div class="invalid-feedback">Veuillez choisir un nom d'utilisateur.</div>
    </div>
    <div class="form-floating mb-3">
      <input name="password" type="password" class="form-control" id="password" placeholder="Mot de passe" required minlength="6">
      <label for="password">Mot de passe</label>
      <div class="invalid-feedback">Mot de passe requis (6 caractères minimum).</div>
    </div>
    <div class="form-floating mb-4">
      <input name="password2" type="password" class="form-control" id="password2" placeholder="Confirmer mot de passe" required minlength="6">
      <label for="password2">Confirmer mot de passe</label>
      <div class="invalid-feedback">Les mots de passe doivent correspondre.</div>
    </div>
    <button class="btn btn-primary w-100 py-2" type="submit">Créer</button>
  </form>
</div>
<script src="../js/bootstrap.min.js"></script>
<script>
// Validation Bootstrap
(() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity() || document.getElementById('password').value !== document.getElementById('password2').value) {
        event.preventDefault();
        event.stopPropagation();
        if(document.getElementById('password').value !== document.getElementById('password2').value) {
          document.getElementById('password2').setCustomValidity('Les mots de passe doivent correspondre.');
        } else {
          document.getElementById('password2').setCustomValidity('');
        }
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
</body>
</html>

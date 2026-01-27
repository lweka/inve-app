
<?php
// pagesweb_cn/create_admin.php
require_once __DIR__ . '/connectDb.php';
session_start();

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
<head><meta charset="utf-8"><title>Créer admin</title>
<link href="../css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container" style="max-width:680px">
  <h3>Créer l'administrateur</h3>
  <?php if($success): ?>
    <div class="alert alert-success">Administrateur créé. <a href="admin_manage.php">Gérer les admins</a></div>
  <?php endif; ?>
  <?php if($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3"><label>Nom</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label>Nom d'utilisateur</label><input name="username" class="form-control" required></div>
    <div class="mb-3"><label>Mot de passe</label><input name="password" type="password" class="form-control" required></div>
    <div class="mb-3"><label>Confirmer mot de passe</label><input name="password2" type="password" class="form-control" required></div>
    <button class="btn btn-primary">Créer</button>
  </form>
</div>
</body>
</html>

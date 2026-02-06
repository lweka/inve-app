<?php
// pagesweb_cn/admin_manage.php
require_once __DIR__ . '/connectDb.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'){
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// changement de mot de passe
$messages = [];
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pw'])){
    $old = $_POST['old_pw'] ?? '';
    $new = $_POST['new_pw'] ?? '';
    $new2 = $_POST['new_pw2'] ?? '';

    // vérifier mot de passe actuel
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $row = $stmt->fetch();

    if(!$row || !password_verify($old, $row['password_hash'])) {
        $messages[] = "Mot de passe actuel incorrect.";
    } elseif($new === '' || $new !== $new2) {
        $messages[] = "Nouveau mot de passe invalide ou confirmation différente.";
    } else {
        $h = password_hash($new, PASSWORD_BCRYPT);
        $u = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $u->execute([$h, $admin_id]);
        $messages[] = "Mot de passe mis à jour.";
    }
}

// liste admins — correction : ajout password_hash
$admins = $pdo->query("SELECT id, name, username, password_hash, created_at FROM admins ORDER BY id ASC")->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Admin Manage</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">

  <h3>Administration</h3>
  <p>Connecté en tant que <strong><?=htmlspecialchars($_SESSION['admin_username'] ?? '')?></strong> — 
    <a href="<?= BASE_URL ?>pagesweb_cn/logout.php">Déconnexion</a>
  </p>

  <?php foreach($messages as $m): ?>
      <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
  <?php endforeach; ?>

  <h5>Admins</h5>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Username</th>
        <th>Password Hash</th>
        <th>Créé</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($admins as $a): ?>
      <tr>
        <td><?= $a['id'] ?></td>
        <td><?= htmlspecialchars($a['name']) ?></td>
        <td><?= htmlspecialchars($a['username']) ?></td>
        <td><small><?= htmlspecialchars($a['password_hash']) ?></small></td>
        <td><?= $a['created_at'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <hr>

  <h5>Changer mot de passe</h5>
  <form method="post">
    <input type="hidden" name="change_pw" value="1">

    <div class="mb-3">
      <label>Mot de passe actuel</label>
      <input name="old_pw" type="password" class="form-control">
    </div>

    <div class="mb-3">
      <label>Nouveau mot de passe</label>
      <input name="new_pw" type="password" class="form-control">
    </div>

    <div class="mb-3">
      <label>Confirmer</label>
      <input name="new_pw2" type="password" class="form-control">
    </div>

    <button class="btn btn-primary">Modifier</button>
  </form>

</div>
</body>
</html>

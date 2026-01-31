<?php
/**
 * ============================================================
 *  LOGOUT PAGE - Admin & Seller Unified
 *  ============================================================
 */
session_start();

// Récupérer le rôle avant destruction de session
$role = $_SESSION['user_role'] ?? 'unknown';
$user_name = '';

if($role === 'admin') {
    $user_name = $_SESSION['admin_full_name'] ?? 'Admin';
} elseif($role === 'agent') {
    $user_name = $_SESSION['agent_name'] ?? 'Vendeur';
}

// Détruire la session
$_SESSION = [];
if (session_id() != '') {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

// Déterminer la redirection
$redirect_url = ($role === 'admin') ? 'admin_login_form.php' : 'connect-parse.php?role=seller';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Déconnexion | Cartelplus Congo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
:root {
  --pp-blue: #0070e0;
  --pp-blue-dark: #003087;
  --pp-cyan: #00a8ff;
  --pp-bg: #f5f7fb;
  --pp-white: #ffffff;
  --pp-text: #0b1f3a;
  --pp-success: #1f8f6a;
  --pp-shadow: rgba(0, 48, 135, 0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  min-height: 100vh;
  background: linear-gradient(135deg, var(--pp-bg) 0%, #e8f0f8 100%);
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  color: var(--pp-text);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes bounce {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

@keyframes checkmark {
  0% { 
    stroke-dashoffset: 50;
    transform: scale(0);
  }
  50% {
    transform: scale(1.1);
  }
  100% { 
    stroke-dashoffset: 0;
    transform: scale(1);
  }
}

/* ===== LOGOUT CARD ===== */
.logout-container {
  background: var(--pp-white);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 60px var(--pp-shadow);
  text-align: center;
  max-width: 500px;
  animation: slideUp 0.6s ease both;
}

.logout-icon {
  width: 100px;
  height: 100px;
  margin: 0 auto 24px;
  background: linear-gradient(135deg, var(--pp-success), #1a8f6e);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 50px;
  color: var(--pp-white);
  animation: bounce 0.8s ease both;
  box-shadow: 0 10px 30px rgba(31, 143, 106, 0.3);
}

.logout-icon i {
  animation: checkmark 0.8s ease-out 0.3s both;
  transform-origin: center;
}

.logout-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--pp-text);
  margin-bottom: 12px;
  animation: fadeIn 0.6s ease 0.2s both;
}

.logout-message {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 12px;
  animation: fadeIn 0.6s ease 0.3s both;
}

.logout-user {
  font-size: 14px;
  color: var(--pp-blue);
  font-weight: 600;
  margin-bottom: 32px;
  animation: fadeIn 0.6s ease 0.4s both;
}

.logout-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  animation: fadeIn 0.6s ease 0.5s both;
}

.btn-logout {
  padding: 12px 24px;
  border-radius: 24px;
  font-weight: 600;
  border: none;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
}

.btn-logout-primary {
  background: var(--pp-blue);
  color: var(--pp-white);
}

.btn-logout-primary:hover {
  background: var(--pp-blue-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 112, 224, 0.3);
}

.btn-logout-secondary {
  background: var(--pp-bg);
  color: var(--pp-blue);
  border: 2px solid var(--pp-blue);
}

.btn-logout-secondary:hover {
  background: var(--pp-blue);
  color: var(--pp-white);
  transform: translateY(-2px);
}

.logout-footer {
  margin-top: 28px;
  padding-top: 28px;
  border-top: 1px solid #e1e8f0;
  font-size: 12px;
  color: #9ca3af;
  animation: fadeIn 0.6s ease 0.6s both;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 576px) {
  .logout-container {
    padding: 32px 20px;
  }
  
  .logout-title {
    font-size: 24px;
  }
  
  .logout-message {
    font-size: 14px;
  }
}
</style>
</head>

<body>

<div class="logout-container">
  <div class="logout-icon">
    <i class="fa-solid fa-check"></i>
  </div>
  
  <h1 class="logout-title">Déconnexion réussie</h1>
  
  <p class="logout-message">Vous avez été déconnecté avec succès</p>
  
  <?php if($user_name): ?>
    <div class="logout-user">
      <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($user_name) ?>
    </div>
  <?php endif; ?>
  
  <div class="logout-actions">
    <a href="<?= $redirect_url ?>" class="btn-logout btn-logout-primary">
      <i class="fa-solid fa-sign-in"></i> Se reconnecter
    </a>
    <a href="../index.php" class="btn-logout btn-logout-secondary">
      <i class="fa-solid fa-home"></i> Accueil
    </a>
  </div>
  
  <div class="logout-footer">
    © 2026 Cartelplus Congo. Tous droits réservés.
  </div>
</div>

<!-- Auto-redirect après 5 secondes (optionnel) -->
<script>
  setTimeout(() => {
    // Optionnel : décommenter pour rediriger automatiquement
    // window.location.href = '<?= $redirect_url ?>';
  }, 5000);
</script>

</body>
</html>

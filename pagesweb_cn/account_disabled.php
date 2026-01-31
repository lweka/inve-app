<?php
/**
 * ============================================================
 *  ACCOUNT DISABLED PAGE
 *  Compte désactivé pour vendeur ou admin
 * ============================================================
 */
session_start();

// Récupérer les infos avant destruction
$user_role = $_SESSION['user_role'] ?? 'unknown';
$user_name = '';
$reason_type = 'disabled'; // Par défaut désactivation

if($user_role === 'agent') {
    $user_name = $_SESSION['agent_name'] ?? 'Vendeur';
} elseif($user_role === 'admin') {
    $user_name = $_SESSION['admin_full_name'] ?? 'Admin';
}

// Détruire la session
$_SESSION = [];
if (session_id() != '') {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

// Déterminer la redirection selon le rôle
$login_url = ($user_role === 'admin') ? 'connect-parse.php?role=admin' : 'connect-parse.php?role=seller';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Compte Inaccessible | Cartelplus Congo</title>

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
  --pp-danger: #dc2626;
  --pp-warning: #f59e0b;
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

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

@keyframes shake {
  0%, 100% { transform: rotateZ(0deg); }
  25% { transform: rotateZ(-5deg); }
  75% { transform: rotateZ(5deg); }
}

/* ===== DISABLED CONTAINER ===== */
.disabled-container {
  background: var(--pp-white);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 60px var(--pp-shadow);
  text-align: center;
  max-width: 500px;
  animation: slideUp 0.6s ease both;
}

.disabled-icon {
  width: 100px;
  height: 100px;
  margin: 0 auto 24px;
  background: linear-gradient(135deg, var(--pp-danger), #b91c1c);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 50px;
  color: var(--pp-white);
  animation: pulse 1s ease-in-out infinite;
  box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
}

.disabled-icon i {
  animation: shake 0.5s ease-in-out;
}

.disabled-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--pp-danger);
  margin-bottom: 12px;
  animation: fadeIn 0.6s ease 0.2s both;
}

.disabled-message {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 12px;
  line-height: 1.6;
  animation: fadeIn 0.6s ease 0.3s both;
}

.disabled-user {
  font-size: 14px;
  color: var(--pp-blue);
  font-weight: 600;
  margin-bottom: 32px;
  animation: fadeIn 0.6s ease 0.4s both;
}

.disabled-contact {
  background: #fff3cd;
  border-left: 4px solid var(--pp-warning);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 28px;
  font-size: 14px;
  color: #856404;
  animation: fadeIn 0.6s ease 0.5s both;
}

.disabled-contact strong {
  display: block;
  margin-bottom: 6px;
  color: #704214;
}

.disabled-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  animation: fadeIn 0.6s ease 0.6s both;
}

.btn-disabled {
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

.btn-disabled-primary {
  background: var(--pp-blue);
  color: var(--pp-white);
}

.btn-disabled-primary:hover {
  background: var(--pp-blue-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 112, 224, 0.3);
}

.btn-disabled-secondary {
  background: var(--pp-bg);
  color: var(--pp-blue);
  border: 2px solid var(--pp-blue);
}

.btn-disabled-secondary:hover {
  background: var(--pp-blue);
  color: var(--pp-white);
  transform: translateY(-2px);
}

.disabled-footer {
  margin-top: 28px;
  padding-top: 28px;
  border-top: 1px solid #e1e8f0;
  font-size: 12px;
  color: #9ca3af;
  animation: fadeIn 0.6s ease 0.7s both;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 576px) {
  .disabled-container {
    padding: 32px 20px;
  }
  
  .disabled-title {
    font-size: 24px;
  }
  
  .disabled-message {
    font-size: 14px;
  }
}
</style>
</head>

<body>

<div class="disabled-container">
  <div class="disabled-icon">
    <i class="fa-solid fa-lock"></i>
  </div>
  
  <h1 class="disabled-title">Compte inaccessible</h1>
  
  <p class="disabled-message">
    Votre compte a été désactivé par l'administrateur et n'est plus accessible.
  </p>
  
  <?php if($user_name): ?>
    <div class="disabled-user">
      <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($user_name) ?>
    </div>
  <?php endif; ?>
  
  <div class="disabled-contact">
    <strong><i class="fa-solid fa-exclamation-circle"></i> Action requise</strong>
    Veuillez contacter l'administrateur pour plus d'informations ou pour rétablir l'accès à votre compte.
  </div>
  
  <div class="disabled-actions">
    <a href="<?= $login_url ?>" class="btn-disabled btn-disabled-primary">
      <i class="fa-solid fa-sign-in"></i> Retour à la connexion
    </a>
    <a href="../index.php" class="btn-disabled btn-disabled-secondary">
      <i class="fa-solid fa-home"></i> Accueil
    </a>
  </div>
  
  <div class="disabled-footer">
    © 2026 Cartelplus Congo. Tous droits réservés.
  </div>
</div>

</body>
</html>

<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
requireAdminAuth();

$database = new Database();
$db = $database->getConnection();

// Récupérer les statistiques
$stats = [];
$query = "SELECT statut, COUNT(*) as count FROM evenements GROUP BY statut";
$stmt = $db->query($query);
$statusCounts = $stmt->fetchAll();

foreach ($statusCounts as $row) {
    $stats[$row['statut']] = $row['count'];
}

// Récupérer toutes les demandes
$query = "SELECT e.*, c.nom as client_nom, c.email as client_email 
          FROM evenements e 
          JOIN clients c ON e.client_id = c.id 
          ORDER BY e.created_at DESC";
$stmt = $db->query($query);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Administration</h2>
            <ul>
                <li class="active"><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="dashboard-header">
                <h1>Tableau de bord</h1>
                <p>Connecté en tant que: <?php echo $_SESSION['admin_username']; ?></p>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total demandes</h3>
                    <p><?php echo array_sum($stats); ?></p>
                </div>
                <div class="stat-card">
                    <h3>En attente</h3>
                    <p><?php echo $stats['en_attente'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Payées</h3>
                    <p><?php echo $stats['paye'] ?? 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Traitées</h3>
                    <p><?php echo $stats['traite'] ?? 0; ?></p>
                </div>
            </div>
            
            <h2>Toutes les demandes</h2>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Événement</th>
                        <th>Date</th>
                        <th>Participants</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo escape($request['client_nom']); ?><br><?php echo escape($request['client_email']); ?></td>
                        <td><?php echo escape($request['type_evenement']); ?><br><?php echo escape($request['lieu']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($request['date_evenement'])); ?></td>
                        <td><?php echo $request['nombre_participants']; ?></td>
                        <td><?php echo $request['prix']; ?> $</td>
                        <td>
                            <span class="status-badge status-<?php echo $request['statut']; ?>">
                                <?php 
                                $statusLabels = [
                                    'en_attente' => 'En attente',
                                    'paye' => 'Payé',
                                    'annule' => 'Annulé',
                                    'traite' => 'Traité'
                                ];
                                echo $statusLabels[$request['statut']]; 
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($request['statut'] === 'en_attente' || $request['statut'] === 'paye'): ?>
                                <a href="actions.php?action=generate&id=<?php echo $request['id']; ?>" class="btn btn-primary">Générer invitations</a>
                                <a href="actions.php?action=cancel&id=<?php echo $request['id']; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette demande?')">Annuler</a>
                            <?php elseif ($request['statut'] === 'traite'): ?>
                                <a href="../guest/register.php?event_id=<?php echo $request['id']; ?>" class="btn btn-primary">Inscrire invité</a>
                                <a href="actions.php?action=export&id=<?php echo $request['id']; ?>" class="btn">Exporter</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
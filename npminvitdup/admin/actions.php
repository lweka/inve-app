<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
requireAdminAuth();

$database = new Database();
$db = $database->getConnection();

// Vérifier l'action demandée
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$action = $_GET['action'];
$event_id = (int)$_GET['id'];

// Récupérer les informations de l'événement
$query = "SELECT * FROM evenements WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: dashboard.php');
    exit;
}

// Traiter l'action demandée
switch ($action) {
    case 'generate':
        // Générer les invitations (créer les hashs uniques)
        try {
            $db->beginTransaction();
            
            // Vérifier si des invitations existent déjà
            $query = "SELECT COUNT(*) as count FROM invitations WHERE evenement_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$event_id]);
            $existingCount = $stmt->fetch()['count'];
            
            if ($existingCount === 0) {
                // Créer une invitation pour chaque participant
                for ($i = 0; $i < $event['nombre_participants']; $i++) {
                    $hash = generateUniqueHash();
                    $query = "INSERT INTO invitations (evenement_id, hash_unique) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$event_id, $hash]);
                }
                
                // Mettre à jour le statut de l'événement
                $query = "UPDATE evenements SET statut = 'traite' WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$event_id]);
            }
            
            $db->commit();
            $_SESSION['success_message'] = "Invitations générées avec succès.";
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur génération invitations: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors de la génération des invitations.";
        }
        break;
        
    case 'cancel':
        // Annuler la demande
        $query = "UPDATE evenements SET statut = 'annule' WHERE id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$event_id])) {
            $_SESSION['success_message'] = "Demande annulée avec succès.";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'annulation de la demande.";
        }
        break;
        
    case 'export':
        // Exporter les données de l'événement et des participants
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=evenement_' . $event_id . '_participants.csv');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($output, ['Nom', 'Prénom', 'Email', 'Téléphone', 'QR Code']);
        
        // Données des participants
        $query = "SELECT p.nom, p.prenom, p.email, p.telephone, p.qr_code_file 
                  FROM participants p 
                  JOIN invitations i ON p.invitation_id = i.id 
                  WHERE i.evenement_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$event_id]);
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    default:
        header('Location: dashboard.php');
        exit;
}

// Rediriger vers le tableau de bord
header('Location: dashboard.php');
exit;
?>
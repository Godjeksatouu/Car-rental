<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Veuillez vous connecter pour accéder à cette page', 'error');
}

// Check if reservation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirectWithMessage('profile.php', 'Identifiant de réservation invalide', 'error');
}

$reservationId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Get reservation details and verify ownership
$query = "SELECT r.*, v.marque, v.modele, l.ETAT_PAIEMENT 
          FROM RESERVATION r 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          WHERE r.id_reservation = ? AND r.id_client = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $reservationId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    redirectWithMessage('profile.php', 'Réservation non trouvée ou accès non autorisé', 'error');
}

$reservation = mysqli_fetch_assoc($result);

// Check if reservation can be deleted (not paid and not started)
$today = date('Y-m-d');
$canDelete = true;
$deleteMessage = '';

if ($reservation['ETAT_PAIEMENT'] == 1) {
    $canDelete = false;
    $deleteMessage = 'Cette réservation ne peut pas être supprimée car elle est déjà payée. Veuillez contacter l\'administration.';
} elseif ($reservation['date_debut'] <= $today) {
    $canDelete = false;
    $deleteMessage = 'Cette réservation ne peut pas être supprimée car elle a déjà commencé ou est terminée.';
}

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && $canDelete) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from LOCATION first (if exists)
        $delete_location = "DELETE FROM LOCATION WHERE id_reservation = ?";
        $stmt = mysqli_prepare($conn, $delete_location);
        mysqli_stmt_bind_param($stmt, "i", $reservationId);
        mysqli_stmt_execute($stmt);
        
        // Note: We don't update car status anymore
        // Cars remain 'disponible' as they're only unavailable for specific dates
        
        // Delete reservation
        $delete_reservation = "DELETE FROM RESERVATION WHERE id_reservation = ?";
        $stmt = mysqli_prepare($conn, $delete_reservation);
        mysqli_stmt_bind_param($stmt, "i", $reservationId);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        redirectWithMessage('profile.php', 'Votre réservation a été supprimée avec succès', 'success');
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Réservation - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="delete-section">
        <div class="container">
            <div class="delete-content">
                <div class="delete-card">
                    <div class="delete-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Supprimer la Réservation</h2>
                        <p>Êtes-vous sûr de vouloir supprimer cette réservation ?</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reservation Details -->
                    <div class="reservation-details">
                        <h3>Détails de la réservation</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="label">Réservation #</span>
                                <span class="value"><?php echo $reservation['id_reservation']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Véhicule</span>
                                <span class="value"><?php echo htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Date de début</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Date de fin</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Statut de paiement</span>
                                <span class="value">
                                    <?php if ($reservation['ETAT_PAIEMENT']): ?>
                                        <span class="status-badge paid">Payé</span>
                                    <?php else: ?>
                                        <span class="status-badge unpaid">Non payé</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canDelete): ?>
                        <!-- Warning Message -->
                        <div class="warning-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <h4>Attention !</h4>
                                <p>Cette action est irréversible. Une fois supprimée, cette réservation ne pourra pas être récupérée.</p>
                            </div>
                        </div>

                        <!-- Delete Form -->
                        <form method="POST" action="" class="delete-form">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-danger btn-large" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer cette réservation ?')">
                                    <i class="fas fa-trash"></i>
                                    Oui, supprimer la réservation
                                </button>
                                <a href="profile.php" class="btn btn-outline btn-large">
                                    <i class="fas fa-arrow-left"></i>
                                    Non, retour au profil
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Cannot Delete Message -->
                        <div class="cannot-delete">
                            <i class="fas fa-ban"></i>
                            <div>
                                <h4>Suppression impossible</h4>
                                <p><?php echo $deleteMessage; ?></p>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="profile.php" class="btn btn-primary btn-large">
                                <i class="fas fa-arrow-left"></i>
                                Retour au profil
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Check if reservation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirectWithMessage('reservations.php', 'Identifiant de réservation invalide', 'error');
}

$reservationId = (int)$_GET['id'];

// Get reservation details
$query = "SELECT r.*, v.marque, v.modele, v.immatriculation, c.nom, c.prénom, c.email, l.ETAT_PAIEMENT 
          FROM RESERVATION r 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          JOIN CLIENT c ON r.id_client = c.id_client 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          WHERE r.id_reservation = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $reservationId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    redirectWithMessage('reservations.php', 'Réservation non trouvée', 'error');
}

$reservation = mysqli_fetch_assoc($result);

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from LOCATION first (if exists)
        $delete_location = "DELETE FROM LOCATION WHERE id_reservation = ?";
        $stmt = mysqli_prepare($conn, $delete_location);
        mysqli_stmt_bind_param($stmt, "i", $reservationId);
        mysqli_stmt_execute($stmt);
        
        // Update car status back to available
        $update_car = "UPDATE VOITURE SET statut = 'disponible' WHERE id_voiture = ?";
        $stmt = mysqli_prepare($conn, $update_car);
        mysqli_stmt_bind_param($stmt, "i", $reservation['id_voiture']);
        mysqli_stmt_execute($stmt);
        
        // Delete reservation
        $delete_reservation = "DELETE FROM RESERVATION WHERE id_reservation = ?";
        $stmt = mysqli_prepare($conn, $delete_reservation);
        mysqli_stmt_bind_param($stmt, "i", $reservationId);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        redirectWithMessage('reservations.php', 'Réservation supprimée avec succès', 'success');
        
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
    <title>Supprimer Réservation - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-trash"></i> Supprimer la Réservation</h2>
                    <a href="reservations.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Warning Message -->
                    <div class="warning-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h4>Attention !</h4>
                            <p>Vous êtes sur le point de supprimer définitivement cette réservation. Cette action est irréversible.</p>
                        </div>
                    </div>

                    <!-- Reservation Details -->
                    <div class="reservation-summary">
                        <h3>Détails de la réservation à supprimer</h3>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <tbody>
                                    <tr>
                                        <td><strong>ID Réservation</strong></td>
                                        <td>#<?php echo $reservation['id_reservation']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Client</strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($reservation['prénom'] . ' ' . $reservation['nom']); ?>
                                            <br><small><?php echo htmlspecialchars($reservation['email']); ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Véhicule</strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']); ?>
                                            <br><small><?php echo htmlspecialchars($reservation['immatriculation']); ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Période</strong></td>
                                        <td>
                                            Du <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?>
                                            au <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date de réservation</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statut de paiement</strong></td>
                                        <td>
                                            <?php if ($reservation['ETAT_PAIEMENT']): ?>
                                                <span class="status-badge paid">Payé</span>
                                            <?php else: ?>
                                                <span class="status-badge unpaid">Non payé</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete Form -->
                    <form method="POST" action="" class="admin-form">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer cette réservation ? Cette action est irréversible.')">
                                <i class="fas fa-trash"></i>
                                Confirmer la suppression
                            </button>
                            <a href="reservations.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>

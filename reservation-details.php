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
$query = "SELECT r.*, v.marque, v.modele, v.prix_par_jour, l.ETAT_PAIEMENT 
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Réservation - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="edit-section">
        <div class="container">
            <div class="edit-content">
                <div class="edit-card">
                    <div class="edit-header">
                        <h2><i class="fas fa-car"></i> Détails de la Réservation</h2>
                        <p>Voici les informations concernant votre réservation</p>
                    </div>

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
                                <span class="label">Prix par jour</span>
                                <span class="value"><?php echo number_format($reservation['prix_par_jour'], 2); ?>€</span>
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
                            <div class="detail-item">
                                <span class="label">Date de début</span>
                                <span class="value"><?php echo $reservation['date_debut']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Date de fin</span>
                                <span class="value"><?php echo $reservation['date_fin']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Retour -->
                    <div class="form-actions">
                        <a href="profile.php" class="btn btn-primary btn-large">
                            <i class="fas fa-arrow-left"></i>
                            Retour au profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Veuillez vous connecter pour accéder aux paiements', 'error');
}

// Check if location ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirectWithMessage('profile.php', 'Identifiant de location invalide', 'error');
}

$locationId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Get location and reservation details
$query = "SELECT l.*, r.*, v.marque, v.modele, v.prix_par_jour, c.nom, c.prénom 
          FROM LOCATION l 
          JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          JOIN CLIENT c ON r.id_client = c.id_client 
          WHERE l.id_location = ? AND r.id_client = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $locationId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    redirectWithMessage('profile.php', 'Location non trouvée ou accès non autorisé', 'error');
}

$location = mysqli_fetch_assoc($result);

// Check if already paid
if ($location['ETAT_PAIEMENT'] == 1) {
    redirectWithMessage('profile.php', 'Cette réservation est déjà payée', 'info');
}

// Calculate total amount
$start_date = new DateTime($location['date_debut']);
$end_date = new DateTime($location['date_fin']);
$duration = $start_date->diff($end_date)->days + 1;
$total_amount = $duration * $location['prix_par_jour'];

// Handle payment confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update location payment status
        // First check if date_paiement column exists
        $column_check = mysqli_query($conn, "SHOW COLUMNS FROM LOCATION LIKE 'date_paiement'");

        if (mysqli_num_rows($column_check) > 0) {
            // Column exists, update both status and date
            $update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = 1, date_paiement = NOW() WHERE id_location = ?";
        } else {
            // Column doesn't exist, update only status
            $update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = 1 WHERE id_location = ?";
        }

        $stmt = mysqli_prepare($conn, $update_query);

        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $locationId);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Erreur lors de l'exécution de la requête: " . mysqli_stmt_error($stmt));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        redirectWithMessage('profile.php', 'Paiement confirmé avec succès ! Votre réservation est maintenant payée.', 'success');
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $error = "Erreur lors du traitement du paiement: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="payment-section">
        <div class="container">
            <div class="payment-content">
                <div class="payment-card">
                    <div class="payment-header">
                        <h2><i class="fas fa-credit-card"></i> Confirmation de Paiement</h2>
                        <p>Confirmez votre paiement pour finaliser votre réservation</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Reservation Summary -->
                    <div class="reservation-summary">
                        <h3>Détails de la réservation</h3>
                        <div class="summary-details">
                            <div class="detail-row">
                                <span class="label">Véhicule:</span>
                                <span class="value"><?php echo htmlspecialchars($location['marque'] . ' ' . $location['modele']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Client:</span>
                                <span class="value"><?php echo htmlspecialchars($location['prénom'] . ' ' . $location['nom']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Date de début:</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($location['date_debut'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Date de fin:</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($location['date_fin'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Durée:</span>
                                <span class="value"><?php echo $duration; ?> jour<?php echo $duration > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Prix par jour:</span>
                                <span class="value"><?php echo number_format($location['prix_par_jour'], 2); ?>€</span>
                            </div>
                            <div class="detail-row total">
                                <span class="label">Total à payer:</span>
                                <span class="value"><?php echo number_format($total_amount, 2); ?>€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="payment-form">
                        <h3>Simulation de Paiement</h3>
                        <p class="payment-note">
                            <i class="fas fa-info-circle"></i>
                            Ceci est une simulation de paiement. En cliquant sur "Confirmer le paiement", 
                            votre réservation sera automatiquement marquée comme payée.
                        </p>
                        
                        <form method="POST" action="">
                            <div class="payment-method">
                                <div class="method-option selected">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Paiement sécurisé</span>
                                    <div class="method-details">
                                        <small>Simulation de paiement en ligne</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <i class="fas fa-lock"></i>
                                    Confirmer le paiement - <?php echo number_format($total_amount, 2); ?>€
                                </button>
                                <a href="profile.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i>
                                    Retour à mon profil
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>

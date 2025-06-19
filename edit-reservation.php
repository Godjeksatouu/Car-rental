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

// Check if reservation can be edited (not paid and not started)
$today = date('Y-m-d');
$canEdit = true;
$editMessage = '';

if ($reservation['ETAT_PAIEMENT'] == 1) {
    $canEdit = false;
    $editMessage = 'Cette réservation ne peut pas être modifiée car elle est déjà payée. Veuillez contacter l\'administration.';
} elseif ($reservation['date_debut'] <= $today) {
    $canEdit = false;
    $editMessage = 'Cette réservation ne peut pas être modifiée car elle a déjà commencé ou est terminée.';
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $canEdit) {
    $new_date_debut = trim($_POST['date_debut']);
    $new_date_fin = trim($_POST['date_fin']);
    
    // Validation
    $errors = [];
    
    if (empty($new_date_debut)) {
        $errors[] = "La date de début est requise";
    }
    
    if (empty($new_date_fin)) {
        $errors[] = "La date de fin est requise";
    }
    
    if (!empty($new_date_debut) && !empty($new_date_fin)) {
        if ($new_date_debut >= $new_date_fin) {
            $errors[] = "La date de fin doit être postérieure à la date de début";
        }
        
        if ($new_date_debut < date('Y-m-d')) {
            $errors[] = "La date de début ne peut pas être dans le passé";
        }
        
        // Check car availability for new dates (excluding current reservation)
        $availability_query = "SELECT COUNT(*) as count FROM RESERVATION 
                              WHERE id_voiture = ? 
                              AND id_reservation != ? 
                              AND ((date_debut <= ? AND date_fin >= ?) 
                                   OR (date_debut <= ? AND date_fin >= ?) 
                                   OR (date_debut >= ? AND date_fin <= ?))";
        $stmt = mysqli_prepare($conn, $availability_query);
        mysqli_stmt_bind_param($stmt, "iissssss", 
            $reservation['id_voiture'], $reservationId,
            $new_date_debut, $new_date_debut,
            $new_date_fin, $new_date_fin,
            $new_date_debut, $new_date_fin
        );
        mysqli_stmt_execute($stmt);
        $availability_result = mysqli_stmt_get_result($stmt);
        $availability = mysqli_fetch_assoc($availability_result);
        
        if ($availability['count'] > 0) {
            $errors[] = "Le véhicule n'est pas disponible pour ces dates";
        }
    }
    
    if (empty($errors)) {
        // Update reservation
        $update_query = "UPDATE RESERVATION SET date_debut = ?, date_fin = ? WHERE id_reservation = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssi", $new_date_debut, $new_date_fin, $reservationId);
        
        if (mysqli_stmt_execute($stmt)) {
            redirectWithMessage('profile.php', 'Votre réservation a été modifiée avec succès', 'success');
        } else {
            $errors[] = "Erreur lors de la modification de la réservation";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Réservation - AutoDrive</title>
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
                        <h2><i class="fas fa-edit"></i> Modifier la Réservation</h2>
                        <p>Modifiez les dates de votre réservation</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
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
                        </div>
                    </div>

                    <?php if ($canEdit): ?>
                        <!-- Edit Form -->
                        <div class="edit-form">
                            <h3>Modifier les dates</h3>
                            <form method="POST" action="">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="date_debut">Date de début*</label>
                                        <input type="date" 
                                               id="date_debut" 
                                               name="date_debut" 
                                               value="<?php echo $reservation['date_debut']; ?>"
                                               min="<?php echo date('Y-m-d'); ?>"
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_fin">Date de fin*</label>
                                        <input type="date" 
                                               id="date_fin" 
                                               name="date_fin" 
                                               value="<?php echo $reservation['date_fin']; ?>"
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="price-preview" id="price-preview" style="display: none;">
                                    <div class="price-calculation">
                                        <span class="duration">Durée: <span id="duration-days">0</span> jour(s)</span>
                                        <span class="total-price">Total: <span id="total-amount">0</span>€</span>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-large">
                                        <i class="fas fa-save"></i>
                                        Sauvegarder les modifications
                                    </button>
                                    <a href="profile.php" class="btn btn-outline btn-large">
                                        <i class="fas fa-arrow-left"></i>
                                        Retour au profil
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Cannot Edit Message -->
                        <div class="cannot-edit">
                            <i class="fas fa-ban"></i>
                            <div>
                                <h4>Modification impossible</h4>
                                <p><?php echo $editMessage; ?></p>
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
    <script>
        // Calculate price when dates change
        function calculatePrice() {
            const startDate = document.getElementById('date_debut').value;
            const endDate = document.getElementById('date_fin').value;
            const pricePerDay = <?php echo $reservation['prix_par_jour']; ?>;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const timeDiff = end.getTime() - start.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                
                if (daysDiff > 0) {
                    const totalPrice = daysDiff * pricePerDay;
                    document.getElementById('duration-days').textContent = daysDiff;
                    document.getElementById('total-amount').textContent = totalPrice.toFixed(2);
                    document.getElementById('price-preview').style.display = 'block';
                } else {
                    document.getElementById('price-preview').style.display = 'none';
                }
            } else {
                document.getElementById('price-preview').style.display = 'none';
            }
        }
        
        // Add event listeners
        document.getElementById('date_debut').addEventListener('change', calculatePrice);
        document.getElementById('date_fin').addEventListener('change', calculatePrice);
        
        // Calculate initial price
        calculatePrice();
        
        // Update end date minimum when start date changes
        document.getElementById('date_debut').addEventListener('change', function() {
            const startDate = this.value;
            if (startDate) {
                const nextDay = new Date(startDate);
                nextDay.setDate(nextDay.getDate() + 1);
                document.getElementById('date_fin').min = nextDay.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>

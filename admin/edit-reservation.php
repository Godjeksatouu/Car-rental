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
$query = "SELECT r.*, v.marque, v.modele, v.immatriculation, v.prix_par_jour, 
                 c.nom, c.prénom, c.email, l.ETAT_PAIEMENT 
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

// Get all available cars for potential car change (exclude only maintenance)
$cars_query = "SELECT id_voiture, marque, modele, immatriculation, prix_par_jour FROM VOITURE WHERE statut != 'maintenance' OR id_voiture = ?";
$stmt = mysqli_prepare($conn, $cars_query);
mysqli_stmt_bind_param($stmt, "i", $reservation['id_voiture']);
mysqli_stmt_execute($stmt);
$cars_result = mysqli_stmt_get_result($stmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_date_debut = trim($_POST['date_debut']);
    $new_date_fin = trim($_POST['date_fin']);
    $new_car_id = (int)$_POST['id_voiture'];
    
    // Validation
    $errors = [];
    
    if (empty($new_date_debut)) {
        $errors[] = "La date de début est requise";
    }
    
    if (empty($new_date_fin)) {
        $errors[] = "La date de fin est requise";
    }
    
    if (!$new_car_id) {
        $errors[] = "Veuillez sélectionner un véhicule";
    }
    
    if (!empty($new_date_debut) && !empty($new_date_fin)) {
        if ($new_date_debut >= $new_date_fin) {
            $errors[] = "La date de fin doit être postérieure à la date de début";
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
            $new_car_id, $reservationId,
            $new_date_debut, $new_date_debut,
            $new_date_fin, $new_date_fin,
            $new_date_debut, $new_date_fin
        );
        mysqli_stmt_execute($stmt);
        $availability_result = mysqli_stmt_get_result($stmt);
        $availability = mysqli_fetch_assoc($availability_result);
        
        if ($availability['count'] > 0) {
            $errors[] = "Le véhicule sélectionné n'est pas disponible pour ces dates";
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Note: We don't update car status globally anymore
            // Cars are only unavailable for specific dates, not entirely
            // Both old and new cars remain 'disponible' for other dates
            
            // Update reservation
            $update_query = "UPDATE RESERVATION SET date_debut = ?, date_fin = ?, id_voiture = ? WHERE id_reservation = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ssii", $new_date_debut, $new_date_fin, $new_car_id, $reservationId);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            redirectWithMessage('reservations.php', 'Réservation modifiée avec succès', 'success');
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $errors[] = "Erreur lors de la modification: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Réservation - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-edit"></i> Modifier la Réservation #<?php echo $reservation['id_reservation']; ?></h2>
                    <a href="reservations.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Current Reservation Info -->
                    <div class="current-info">
                        <h3>Informations actuelles</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Client:</span>
                                <span class="value"><?php echo htmlspecialchars($reservation['prénom'] . ' ' . $reservation['nom']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Email:</span>
                                <span class="value"><?php echo htmlspecialchars($reservation['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Véhicule actuel:</span>
                                <span class="value"><?php echo htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Dates actuelles:</span>
                                <span class="value">
                                    Du <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?>
                                    au <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="label">Statut paiement:</span>
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

                    <!-- Edit Form -->
                    <div class="edit-form">
                        <h3>Modifier la réservation</h3>
                        <form method="POST" action="" class="admin-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_debut">Date de début*</label>
                                    <input type="date" 
                                           id="date_debut" 
                                           name="date_debut" 
                                           value="<?php echo $reservation['date_debut']; ?>"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label for="date_fin">Date de fin*</label>
                                    <input type="date" 
                                           id="date_fin" 
                                           name="date_fin" 
                                           value="<?php echo $reservation['date_fin']; ?>"
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="id_voiture">Véhicule*</label>
                                    <select id="id_voiture" name="id_voiture" required>
                                        <?php while ($car = mysqli_fetch_assoc($cars_result)): ?>
                                            <option value="<?php echo $car['id_voiture']; ?>" 
                                                    <?php echo ($car['id_voiture'] == $reservation['id_voiture']) ? 'selected' : ''; ?>
                                                    data-price="<?php echo $car['prix_par_jour']; ?>">
                                                <?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele'] . ' (' . $car['immatriculation'] . ') - ' . number_format($car['prix_par_jour'], 2) . '€/jour'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="price-preview" id="price-preview">
                                <div class="price-calculation">
                                    <span class="duration">Durée: <span id="duration-days">0</span> jour(s)</span>
                                    <span class="price-per-day">Prix/jour: <span id="price-per-day">0</span>€</span>
                                    <span class="total-price">Total: <span id="total-amount">0</span>€</span>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Sauvegarder les modifications
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
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script>
        // Calculate price when dates or car changes
        function calculatePrice() {
            const startDate = document.getElementById('date_debut').value;
            const endDate = document.getElementById('date_fin').value;
            const carSelect = document.getElementById('id_voiture');
            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const pricePerDay = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            
            if (startDate && endDate && pricePerDay > 0) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const timeDiff = end.getTime() - start.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                
                if (daysDiff > 0) {
                    const totalPrice = daysDiff * pricePerDay;
                    document.getElementById('duration-days').textContent = daysDiff;
                    document.getElementById('price-per-day').textContent = pricePerDay.toFixed(2);
                    document.getElementById('total-amount').textContent = totalPrice.toFixed(2);
                } else {
                    document.getElementById('duration-days').textContent = '0';
                    document.getElementById('total-amount').textContent = '0';
                }
            }
        }
        
        // Add event listeners
        document.getElementById('date_debut').addEventListener('change', calculatePrice);
        document.getElementById('date_fin').addEventListener('change', calculatePrice);
        document.getElementById('id_voiture').addEventListener('change', calculatePrice);
        
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

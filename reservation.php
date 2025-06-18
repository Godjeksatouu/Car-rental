<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php?redirect=' . urlencode('reservation.php?id=' . ($_GET['id'] ?? '')), 'Veuillez vous connecter pour réserver', 'error');
}

// Check if car ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirectWithMessage('cars.php', 'Identifiant de voiture invalide', 'error');
}

$carId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Get car details
$query = "SELECT * FROM VOITURE WHERE id_voiture = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $carId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    redirectWithMessage('cars.php', 'Voiture non trouvée', 'error');
}

$car = mysqli_fetch_assoc($result);

// Check if car is available
if ($car['statut'] !== 'disponible') {
    redirectWithMessage('car-details.php?id=' . $carId, 'Cette voiture n\'est pas disponible à la location', 'error');
}

// Get user details
$query = "SELECT * FROM CLIENT WHERE id_client = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Initialize user fields with default values if they don't exist
$userNom = isset($user['nom']) ? $user['nom'] : '';
$userEmail = isset($user['email']) ? $user['email'] : '';
$userTelephone = isset($user['téléphone']) ? $user['téléphone'] : '';

$errors = [];
$dateDebut = $dateFin = "";
$totalPrice = 0;

// Process reservation form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dateDebut = sanitize($_POST["date_debut"]);
    $dateFin = sanitize($_POST["date_fin"]);
    
    // Validate dates
    if (empty($dateDebut)) {
        $errors[] = "Date de début est requise";
    }
    
    if (empty($dateFin)) {
        $errors[] = "Date de fin est requise";
    }
    
    if (!empty($dateDebut) && !empty($dateFin)) {
        $today = date('Y-m-d');
        $start = new DateTime($dateDebut);
        $end = new DateTime($dateFin);
        $interval = $start->diff($end);
        
        if ($dateDebut < $today) {
            $errors[] = "La date de début ne peut pas être dans le passé";
        }
        
        if ($dateFin < $dateDebut) {
            $errors[] = "La date de fin doit être après la date de début";
        }
        
        // Check if car is available for the selected dates
        if (!isCarAvailable($carId, $dateDebut, $dateFin, $conn)) {
            $errors[] = "La voiture n'est pas disponible pour les dates sélectionnées";
        }
    }
    
    // If no errors, create reservation
    if (empty($errors)) {
        // Calculate total price
        $totalPrice = calculateRentalPrice($carId, $dateDebut, $dateFin, $conn);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create reservation
            $query = "INSERT INTO RESERVATION (id_client, date_debut, date_fin, id_voiture) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "issi", $userId, $dateDebut, $dateFin, $carId);
            mysqli_stmt_execute($stmt);
            
            $reservationId = mysqli_insert_id($conn);
            
            // Create location
            $query = "INSERT INTO LOCATION (id_reservation, ETAT_PAIEMENT) VALUES (?, 0)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $reservationId);
            mysqli_stmt_execute($stmt);
            
            // Update car status
            $query = "UPDATE VOITURE SET statut = 'réservé' WHERE id_voiture = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $carId);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            redirectWithMessage('profile.php', 'Votre réservation a été enregistrée avec succès', 'success');
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $errors[] = "Erreur lors de la réservation: " . $e->getMessage();
        }
    }
}

// Calculate price estimate if dates are provided
if (!empty($dateDebut) && !empty($dateFin)) {
    $totalPrice = calculateRentalPrice($carId, $dateDebut, $dateFin, $conn);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="reservation-section">
        <div class="container">
            <div class="reservation-content">
                <div class="reservation-car">
                    <h2>Détails du véhicule</h2>
                    <div class="car-preview">
                        <?php
                        $image = $car['image'] ? $car['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                        ?>
                        <img src="<?php echo $image; ?>" alt="<?php echo $car['marque'] . ' ' . $car['modele']; ?>">
                        <div class="car-details">
                            <h3><?php echo $car['marque'] . ' ' . $car['modele']; ?></h3>
                            <div class="car-specs">
                                <span><i class="fas fa-gas-pump"></i> <?php echo ucfirst($car['type']); ?></span>
                                <span><i class="fas fa-users"></i> <?php echo $car['nb_places']; ?> places</span>
                                <span><i class="fas fa-tachometer-alt"></i> <?php echo ucfirst($car['type']); ?></span>
                            </div>
                            <div class="car-price">
                                <span class="price"><?php echo $car['prix_par_jour']; ?> €</span>
                                <span class="period">/ jour</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="reservation-form">
                    <h2>Réserver votre voiture</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $carId; ?>" method="POST" id="reservationForm">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($userNom); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($userTelephone); ?>" readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_debut">Date de début*</label>
                                <input type="date" id="date_debut" name="date_debut" value="<?php echo $dateDebut; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_fin">Date de fin*</label>
                                <input type="date" id="date_fin" name="date_fin" value="<?php echo $dateFin; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="price-estimate" id="priceEstimate" style="<?php echo $totalPrice > 0 ? 'display: block;' : 'display: none;'; ?>">
                            <h3>Estimation du prix</h3>
                            <div class="price-details">
                                <div class="price-row">
                                    <span>Prix par jour:</span>
                                    <span><?php echo $car['prix_par_jour']; ?> €</span>
                                </div>
                                <div class="price-row" id="daysRow">
                                    <span>Nombre de jours:</span>
                                    <span id="daysCount">
                                        <?php
                                        if (!empty($dateDebut) && !empty($dateFin)) {
                                            $start = new DateTime($dateDebut);
                                            $end = new DateTime($dateFin);
                                            $interval = $start->diff($end);
                                            echo $interval->days + 1;
                                        } else {
                                            echo "0";
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="price-row total">
                                    <span>Prix total:</span>
                                    <span id="totalPrice"><?php echo $totalPrice; ?> €</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block">Confirmer la réservation</button>
                            <a href="cars.php" class="btn btn-outline btn-block">Retour aux voitures</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateDebutInput = document.getElementById('date_debut');
            const dateFinInput = document.getElementById('date_fin');
            const priceEstimate = document.getElementById('priceEstimate');
            const daysCount = document.getElementById('daysCount');
            const totalPrice = document.getElementById('totalPrice');
            const pricePerDay = <?php echo $car['prix_par_jour']; ?>;
            
            function updatePriceEstimate() {
                const dateDebut = dateDebutInput.value;
                const dateFin = dateFinInput.value;
                
                if (dateDebut && dateFin) {
                    const start = new Date(dateDebut);
                    const end = new Date(dateFin);
                    
                    if (end >= start) {
                        const diffTime = Math.abs(end - start);
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                        
                        daysCount.textContent = diffDays;
                        totalPrice.textContent = (pricePerDay * diffDays).toFixed(2) + ' €';
                        priceEstimate.style.display = 'block';
                    }
                }
            }
            
            dateDebutInput.addEventListener('change', updatePriceEstimate);
            dateFinInput.addEventListener('change', updatePriceEstimate);
        });
    </script>
</body>
</html>
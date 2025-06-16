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
            
            redirectWithMessage('reservations.php', 'Votre réservation a été enregistrée avec succès', 'success');
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
    <style>
        .reservation-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        
        .reservation-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .reservation-car, .reservation-form {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .car-preview {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }
        
        .car-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .car-details h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .car-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .car-specs span {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .car-price {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #3563e9;
        }
        
        .period {
            font-size: 0.9rem;
            color: #666;
            margin-left: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #3563e9;
            box-shadow: 0 0 0 3px rgba(53, 99, 233, 0.1);
            outline: none;
        }
        
        .form-group input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .price-estimate {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .price-estimate h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .price-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: #666;
        }
        
        .price-row.total {
            font-weight: 700;
            color: #333;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 5px;
        }
        
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: #3563e9;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2a4fba;
        }
        
        .btn-outline {
            background-color: transparent;
            color: #3563e9;
            border: 1px solid #3563e9;
        }
        
        .btn-outline:hover {
            background-color: #f0f5ff;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        @media (max-width: 768px) {
            .reservation-content {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
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
                            <a href="car-details.php?id=<?php echo $carId; ?>" class="btn btn-outline btn-block">Retour aux détails</a>
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
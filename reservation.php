<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

// Check if car is available (only block maintenance, not reserved status)
if ($car['statut'] === 'maintenance') {
    redirectWithMessage('car-details.php?id=' . $carId, 'Cette voiture est en maintenance et n\'est pas disponible à la location', 'error');
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
            
            // Note: We don't update car status to 'réservé' globally anymore
            // Cars are only unavailable for specific dates, not entirely
            // The car remains 'disponible' for other dates
            
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

// Get all reserved dates for this car to block them in the calendar
function getReservedDatesForCar($carId, $conn) {
    $reservedDates = [];

    // Get all reservations for this car that are not cancelled
    $query = "SELECT date_debut, date_fin FROM RESERVATION
              WHERE id_voiture = ?
              AND date_fin >= CURDATE()
              ORDER BY date_debut ASC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $carId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $startDate = new DateTime($row['date_debut']);
        $endDate = new DateTime($row['date_fin']);

        // Generate all dates in the range
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $reservedDates[] = $currentDate->format('Y-m-d');
            $currentDate->add(new DateInterval('P1D'));
        }
    }

    return array_unique($reservedDates);
}

// Get reserved dates for this specific car
$reservedDates = getReservedDatesForCar($carId, $conn);

// Also get reservation periods for better display
function getReservationPeriods($carId, $conn) {
    $periods = [];

    $query = "SELECT r.date_debut, r.date_fin, c.nom, c.prénom
              FROM RESERVATION r
              JOIN CLIENT c ON r.id_client = c.id_client
              WHERE r.id_voiture = ?
              AND r.date_fin >= CURDATE()
              ORDER BY r.date_debut ASC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $carId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $periods[] = [
            'start' => $row['date_debut'],
            'end' => $row['date_fin'],
            'client' => $row['prénom'] . ' ' . substr($row['nom'], 0, 1) . '.'
        ];
    }

    return $periods;
}

$reservationPeriods = getReservationPeriods($carId, $conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Litepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">

    <style>
        /* Custom Litepicker Styles */
        .date-picker-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .date-picker-container input {
            flex: 1;
            padding: 12px 45px 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--white);
            cursor: pointer !important;
            transition: all 0.3s ease;
        }

        .date-picker-container input[readonly] {
            cursor: pointer !important;
            background: var(--white) !important;
        }

        .date-picker-container input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .date-picker-container input.date-selected {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .date-picker-icon {
            position: absolute;
            right: 16px;
            color: var(--gray-500);
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .date-picker-container:hover .date-picker-icon {
            color: var(--primary-color);
        }

        .date-feedback {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #28a745;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-top: 4px;
            animation: slideDown 0.3s ease;
            z-index: 10;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .price-estimate.price-updated {
            animation: priceUpdate 0.5s ease;
        }

        @keyframes priceUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* Litepicker Theme Customization */
        .litepicker {
            font-family: inherit;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--gray-200);
        }

        .litepicker .container__main {
            border-radius: 12px;
        }

        .litepicker .container__months .month-item-header {
            background: var(--primary-color);
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 12px;
            font-weight: 600;
        }

        .litepicker .container__days .day-item {
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer !important;
        }

        .litepicker .container__days .day-item:not(.is-locked) {
            cursor: pointer !important;
        }

        .litepicker .container__days .day-item.is-locked {
            cursor: not-allowed !important;
        }

        .litepicker .container__days .day-item:hover:not(.is-locked) {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
            cursor: pointer !important;
        }

        .litepicker .container__days .day-item.is-start-date,
        .litepicker .container__days .day-item.is-end-date {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        .litepicker .container__days .day-item.is-in-range {
            background: rgba(66, 153, 225, 0.2);
            color: var(--primary-color);
        }

        /* Blocked/Reserved dates styling */
        .litepicker .container__days .day-item.is-locked {
            background: #f8d7da !important;
            color: #721c24 !important;
            cursor: not-allowed !important;
            position: relative;
            text-decoration: line-through;
        }

        .litepicker .container__days .day-item.is-locked:hover {
            background: #f5c6cb !important;
            color: #721c24 !important;
            transform: none !important;
        }

        .litepicker .container__days .day-item.is-locked::after {
            content: '🚫';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 8px;
            opacity: 0.7;
        }

        .litepicker .container__footer {
            padding: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .litepicker .container__footer .button-apply {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .litepicker .container__footer .button-apply:hover {
            background: var(--primary-dark);
        }

        .litepicker .container__footer .button-cancel {
            background: transparent;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
            padding: 10px 20px;
            border-radius: 6px;
            margin-right: 8px;
            transition: all 0.3s ease;
        }

        .litepicker .container__footer .button-cancel:hover {
            background: var(--gray-100);
            border-color: var(--gray-400);
        }

        /* Additional cursor fixes */
        .litepicker * {
            cursor: default !important;
        }

        .litepicker .container__days .day-item:not(.is-locked):not(.is-disabled) {
            cursor: pointer !important;
        }

        .litepicker .container__footer button {
            cursor: pointer !important;
        }

        .litepicker .container__months .month-item-header .button-prev-month,
        .litepicker .container__months .month-item-header .button-next-month {
            cursor: pointer !important;
        }

        /* Date status indicator */
        .date-status {
            margin-top: 10px;
            padding: 8px 12px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            color: #155724;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Manual date inputs */
        .manual-dates {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        .manual-date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .manual-date-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #495057;
        }

        .manual-date-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .toggle-manual-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background: transparent;
            border: 1px solid #6c757d;
            border-radius: 4px;
            color: #6c757d;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-manual-btn:hover {
            background: #6c757d;
            color: white;
        }

        /* Reserved dates information styles */
        .reserved-dates-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .reserved-dates-info h4 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reserved-periods {
            margin-bottom: 15px;
        }

        .reserved-period {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #ffeaa7;
        }

        .reserved-period:last-child {
            border-bottom: none;
        }

        .reserved-period i {
            color: #dc3545;
            font-size: 0.9rem;
        }

        .period-dates {
            font-weight: 600;
            color: #856404;
        }

        .period-client {
            color: #6c757d;
            font-size: 0.9rem;
            font-style: italic;
        }

        .reserved-note {
            margin: 0;
            font-size: 0.9rem;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Validation error styles */
        .validation-error {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Blocked date error styles */
        .blocked-date-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 15px;
            animation: slideInError 0.3s ease;
        }

        .blocked-error-content {
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .blocked-error-content > i {
            color: #721c24;
            font-size: 1.2rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .blocked-error-text {
            flex: 1;
        }

        .blocked-error-text h4 {
            margin: 0 0 8px 0;
            color: #721c24;
            font-size: 1rem;
        }

        .blocked-error-text p {
            margin: 0 0 8px 0;
            color: #721c24;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .blocked-error-text p:last-child {
            margin-bottom: 0;
        }

        .blocking-period {
            background: rgba(114, 28, 36, 0.1);
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #721c24;
        }

        .suggestion {
            font-weight: 600;
            color: #721c24 !important;
        }

        .close-error {
            background: none;
            border: none;
            color: #721c24;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.3s ease;
            flex-shrink: 0;
        }

        .close-error:hover {
            background: rgba(114, 28, 36, 0.1);
        }

        @keyframes slideInError {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .litepicker .container__months {
                flex-direction: column;
            }

            .litepicker .container__months .month-item {
                width: 100%;
            }

            .reserved-dates-info {
                padding: 15px;
            }

            .reserved-period {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .blocked-error-content {
                flex-direction: column;
                gap: 10px;
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

                        <!-- Car-specific blocking indicator -->
                        <div class="car-specific-info" style="background: #e3f2fd; border: 1px solid #90caf9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0 0 8px 0; color: #1565c0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-info-circle"></i>
                                Calendrier spécifique à ce véhicule
                            </h4>
                            <p style="margin: 0; color: #1565c0; font-size: 0.9rem;">
                                <strong>Véhicule:</strong> <?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele']); ?> (ID: <?php echo $carId; ?>)<br>
                                Les dates bloquées ci-dessous sont uniquement pour ce véhicule. D'autres véhicules peuvent être disponibles pour ces mêmes dates.
                            </p>
                        </div>



                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_range">Sélectionnez vos dates*</label>
                                <div class="date-picker-container">
                                    <input type="text" id="date_range" placeholder="Cliquez pour sélectionner les dates" readonly>
                                    <i class="fas fa-calendar-alt date-picker-icon"></i>
                                </div>

                                <!-- Date status indicator -->
                                <div id="date-status" class="date-status" style="display: none;">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="date-status-text">Dates sélectionnées</span>
                                </div>

                                <!-- Manual date inputs (fallback) -->
                                <div id="manual-dates" class="manual-dates" style="display: none;">
                                    <p><small>Ou saisissez les dates manuellement :</small></p>
                                    <div class="manual-date-inputs">
                                        <div class="manual-date-group">
                                            <label for="manual_start">Date de début:</label>
                                            <input type="date" id="manual_start" min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="manual-date-group">
                                            <label for="manual_end">Date de fin:</label>
                                            <input type="date" id="manual_end" min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Toggle manual input -->
                                <button type="button" id="toggle-manual" class="toggle-manual-btn">
                                    <i class="fas fa-keyboard"></i> Saisie manuelle
                                </button>

                                <!-- Hidden inputs for form submission -->
                                <input type="hidden" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($dateDebut); ?>" required>
                                <input type="hidden" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($dateFin); ?>" required>
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
    <!-- Litepicker JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceEstimate = document.getElementById('priceEstimate');
            const daysCount = document.getElementById('daysCount');
            const totalPrice = document.getElementById('totalPrice');
            const pricePerDay = <?php echo $car['prix_par_jour']; ?>;

            // Global variables for better state management
            let selectedStartDate = null;
            let selectedEndDate = null;
            let pickerInstance = null;

            // Reserved dates from PHP (convert to JavaScript array)
            const reservedDates = <?php echo json_encode($reservedDates); ?>;
            const reservationPeriods = <?php echo json_encode($reservationPeriods); ?>;
            const currentCarId = <?php echo $carId; ?>;

            console.log('=== CAR-SPECIFIC BLOCKING DEBUG ===');
            console.log('Current Car ID:', currentCarId);
            console.log('Reserved dates for this car only:', reservedDates);
            console.log('Reservation periods for this car:', reservationPeriods);
            console.log('Total blocked dates count:', reservedDates.length);

            if (reservedDates.length > 0) {
                console.log('First blocked date:', reservedDates[0]);
                console.log('Last blocked date:', reservedDates[reservedDates.length - 1]);
            } else {
                console.log('✅ No blocked dates for this car - calendar should be fully available');
            }

            // Debug function
            window.debugLitepicker = function() {
                console.log('=== Litepicker Debug Info ===');
                console.log('Date debut value:', document.getElementById('date_debut').value);
                console.log('Date fin value:', document.getElementById('date_fin').value);
                console.log('Date range input value:', document.getElementById('date_range').value);
                console.log('Selected start date:', selectedStartDate);
                console.log('Selected end date:', selectedEndDate);
                console.log('Picker object:', pickerInstance);
                if (pickerInstance && pickerInstance.getStartDate()) {
                    console.log('Picker start date:', pickerInstance.getStartDate().format('YYYY-MM-DD'));
                }
                if (pickerInstance && pickerInstance.getEndDate()) {
                    console.log('Picker end date:', pickerInstance.getEndDate().format('YYYY-MM-DD'));
                }
                console.log('=== End Debug Info ===');
            };

            // Enhanced debug form data function
            window.debugFormData = function() {
                console.log('=== COMPREHENSIVE FORM DEBUG ===');

                const form = document.getElementById('reservationForm');
                const formData = new FormData(form);

                console.log('📋 All Form Data:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: "${value}"`);
                }

                // Get all date-related values
                const startDateElement = document.getElementById('date_debut');
                const endDateElement = document.getElementById('date_fin');
                const dateRangeElement = document.getElementById('date_range');

                const startDate = startDateElement ? startDateElement.value : 'ELEMENT NOT FOUND';
                const endDate = endDateElement ? endDateElement.value : 'ELEMENT NOT FOUND';
                const dateRange = dateRangeElement ? dateRangeElement.value : 'ELEMENT NOT FOUND';

                const debugInfo = {
                    '🔍 Hidden start date (date_debut)': startDate,
                    '🔍 Hidden end date (date_fin)': endDate,
                    '📅 Date range display': dateRange,
                    '🌐 Global start date': selectedStartDate || 'NOT SET',
                    '🌐 Global end date': selectedEndDate || 'NOT SET',
                    '✅ Form validity': form.checkValidity(),
                    '🎯 Picker has dates': pickerInstance && pickerInstance.getStartDate() ? 'YES' : 'NO'
                };

                console.table(debugInfo);

                // Check if elements exist
                console.log('🔍 Element existence check:');
                console.log('  date_debut element:', !!startDateElement);
                console.log('  date_fin element:', !!endDateElement);
                console.log('  date_range element:', !!dateRangeElement);

                // Show detailed alert
                let alertMessage = '🐛 FORM DEBUG RESULTS:\n\n';

                if (startDate && endDate && startDate !== 'ELEMENT NOT FOUND' && endDate !== 'ELEMENT NOT FOUND') {
                    alertMessage += '✅ SUCCESS: Both dates are populated!\n';
                    alertMessage += `📅 Start: ${startDate}\n`;
                    alertMessage += `📅 End: ${endDate}\n\n`;
                    alertMessage += '🚀 Form should submit successfully!';
                } else {
                    alertMessage += '❌ PROBLEM: Dates are missing!\n\n';
                    alertMessage += 'Debug details:\n';
                    for (let [key, value] of Object.entries(debugInfo)) {
                        alertMessage += `${key}: ${value}\n`;
                    }
                    alertMessage += '\n💡 Try selecting dates again or use manual input.';
                }

                alert(alertMessage);

                // Additional troubleshooting
                if (pickerInstance) {
                    console.log('🎯 Litepicker state:');
                    console.log('  Start date:', pickerInstance.getStartDate());
                    console.log('  End date:', pickerInstance.getEndDate());

                    if (pickerInstance.getStartDate() && pickerInstance.getEndDate()) {
                        console.log('🔧 Attempting to re-sync dates...');
                        updateSelectedDates(pickerInstance.getStartDate(), pickerInstance.getEndDate());
                    }
                }
            };

            // Initialize Litepicker with improved configuration and date blocking
            pickerInstance = new Litepicker({
                element: document.getElementById('date_range'),
                singleMode: false,
                numberOfColumns: window.innerWidth > 768 ? 2 : 1,
                numberOfMonths: window.innerWidth > 768 ? 2 : 1,
                minDate: new Date(),
                maxDate: new Date(new Date().getFullYear() + 2, 11, 31),
                format: 'DD/MM/YYYY',
                delimiter: ' - ',
                autoApply: false,
                showWeekNumbers: false,
                showTooltip: true,
                tooltipText: {
                    one: 'jour',
                    other: 'jours'
                },
                tooltipNumber: (totalDays) => {
                    return totalDays - 1;
                },
                lang: 'fr-FR',
                buttonText: {
                    apply: 'Confirmer',
                    cancel: 'Annuler'
                },
                // Block reserved dates
                lockDaysFilter: (date) => {
                    const dateStr = date.format('YYYY-MM-DD');
                    const isReserved = reservedDates.includes(dateStr);

                    if (isReserved) {
                        console.log('Blocking reserved date:', dateStr);
                    }

                    return isReserved;
                },
                // Disable selection of locked days in range
                disallowLockDaysInRange: true,
                setup: (picker) => {
                    console.log('Litepicker setup called');
                    // Set initial dates if they exist
                    <?php if (!empty($dateDebut) && !empty($dateFin)): ?>
                    try {
                        const initialStart = '<?php echo date('d/m/Y', strtotime($dateDebut)); ?>';
                        const initialEnd = '<?php echo date('d/m/Y', strtotime($dateFin)); ?>';
                        console.log('Setting initial dates:', initialStart, initialEnd);
                        picker.setDateRange(initialStart, initialEnd);

                        // Also update our global variables
                        selectedStartDate = '<?php echo $dateDebut; ?>';
                        selectedEndDate = '<?php echo $dateFin; ?>';
                    } catch (e) {
                        console.log('Error setting initial dates:', e);
                    }
                    <?php endif; ?>
                },
                onSelect: (start, end) => {
                    console.log('=== LITEPICKER onSelect TRIGGERED ===');
                    console.log('Start date object:', start);
                    console.log('End date object:', end);

                    if (start && end) {
                        console.log('Both dates provided, calling updateSelectedDates...');
                        const success = updateSelectedDates(start, end);
                        console.log('updateSelectedDates result:', success);

                        // Force immediate update of debug display
                        setTimeout(() => {
                            updateDebugDisplay();
                            console.log('Debug display updated');
                        }, 100);
                    } else {
                        console.log('Missing start or end date');
                    }
                },
                onShow: () => {
                    console.log('Picker shown');
                    setTimeout(() => {
                        const litepicker = document.querySelector('.litepicker');
                        if (litepicker) {
                            litepicker.style.zIndex = '9999';
                            litepicker.style.cursor = 'default';

                            // Add tooltips to blocked dates
                            addBlockedDateTooltips();
                        }
                    }, 100);
                },
                onHide: () => {
                    console.log('Picker hidden');
                    // Final validation when picker closes
                    validateSelectedDates();

                    // Force update from picker state when closing
                    setTimeout(() => {
                        if (pickerInstance && pickerInstance.getStartDate() && pickerInstance.getEndDate()) {
                            console.log('Forcing update on picker close...');
                            updateSelectedDates(pickerInstance.getStartDate(), pickerInstance.getEndDate());
                        }
                    }, 100);
                },
                onError: (error) => {
                    console.log('Litepicker error:', error);

                    // Handle specific error cases
                    if (error && error.message) {
                        if (error.message.includes('locked') || error.message.includes('disabled')) {
                            showBlockedDateError();
                        }
                    }
                },
                onSelectStart: (date) => {
                    console.log('Selection started:', date);
                    // Clear any previous error messages
                    clearBlockedDateError();
                },
                onSelectEnd: (date) => {
                    console.log('Selection ended:', date);
                    // Validate the selected range doesn't include blocked dates
                    if (pickerInstance.getStartDate() && pickerInstance.getEndDate()) {
                        const hasBlockedDates = checkRangeForBlockedDates(
                            pickerInstance.getStartDate(),
                            pickerInstance.getEndDate()
                        );

                        if (hasBlockedDates) {
                            showBlockedDateError();
                            // Clear the selection
                            setTimeout(() => {
                                pickerInstance.clearSelection();
                            }, 100);
                        }
                    }
                }
            });

            // Function to update selected dates - ENHANCED VERSION
            function updateSelectedDates(start, end) {
                console.log('=== updateSelectedDates called ===');
                console.log('Start date object:', start);
                console.log('End date object:', end);

                if (!start || !end) {
                    console.error('Invalid dates provided to updateSelectedDates');
                    return false;
                }

                try {
                    // Format dates for form submission (MySQL format)
                    const startFormatted = start.format('YYYY-MM-DD');
                    const endFormatted = end.format('YYYY-MM-DD');

                    console.log('Dates formatted for MySQL:', startFormatted, endFormatted);

                    // Update global variables
                    selectedStartDate = startFormatted;
                    selectedEndDate = endFormatted;

                    // Get hidden form inputs
                    const startInput = document.getElementById('date_debut');
                    const endInput = document.getElementById('date_fin');

                    console.log('Hidden input elements found:', !!startInput, !!endInput);

                    if (startInput && endInput) {
                        // Clear any existing values first
                        startInput.value = '';
                        endInput.value = '';

                        // Set new values
                        startInput.value = startFormatted;
                        endInput.value = endFormatted;

                        // Force the browser to recognize the change
                        startInput.setAttribute('value', startFormatted);
                        endInput.setAttribute('value', endFormatted);

                        console.log('✅ Hidden inputs updated:');
                        console.log('  - date_debut:', startInput.value);
                        console.log('  - date_fin:', endInput.value);

                        // Verify the values are actually set
                        setTimeout(() => {
                            console.log('Verification after timeout:');
                            console.log('  - date_debut value:', document.getElementById('date_debut').value);
                            console.log('  - date_fin value:', document.getElementById('date_fin').value);
                        }, 100);

                        // Update the display input with formatted dates
                        const displayFormatted = start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY');
                        document.getElementById('date_range').value = displayFormatted;

                        // Update price estimate
                        updatePriceEstimate(start, end);

                        // Show success feedback
                        showDateSelectionFeedback(start, end);

                        // Update real-time debug display
                        updateDebugDisplay();

                        // Trigger change events for any listeners
                        const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                        startInput.dispatchEvent(changeEvent);
                        endInput.dispatchEvent(changeEvent);

                        // Remove any existing validation errors
                        const existingError = document.querySelector('.validation-error');
                        if (existingError) {
                            existingError.remove();
                        }

                        return true;
                    } else {
                        console.error('❌ Could not find hidden input elements!');
                        console.log('Available elements with date IDs:');
                        console.log('date_debut:', document.getElementById('date_debut'));
                        console.log('date_fin:', document.getElementById('date_fin'));
                        return false;
                    }
                } catch (error) {
                    console.error('❌ Error in updateSelectedDates:', error);
                    return false;
                }
            }

            // Function to validate selected dates
            function validateSelectedDates() {
                const startValue = document.getElementById('date_debut').value;
                const endValue = document.getElementById('date_fin').value;

                console.log('Validating dates:', startValue, endValue);

                if (startValue && endValue) {
                    console.log('Dates are valid');
                    return true;
                } else {
                    console.log('Dates are missing');
                    return false;
                }
            }

            function updatePriceEstimate(startDate, endDate) {
                if (startDate && endDate) {
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                    daysCount.textContent = diffDays;
                    totalPrice.textContent = (pricePerDay * diffDays).toFixed(2) + ' €';
                    priceEstimate.style.display = 'block';

                    // Add animation to price estimate
                    priceEstimate.classList.add('price-updated');
                    setTimeout(() => {
                        priceEstimate.classList.remove('price-updated');
                    }, 500);
                }
            }

            function showDateSelectionFeedback(start, end) {
                const dateRangeInput = document.getElementById('date_range');
                dateRangeInput.classList.add('date-selected');

                // Show the new status indicator
                showDateStatus('Dates sélectionnées avec succès');

                setTimeout(() => {
                    dateRangeInput.classList.remove('date-selected');
                }, 2000);
            }

            // Enhanced form validation
            document.getElementById('reservationForm').addEventListener('submit', function(e) {
                console.log('=== FORM SUBMISSION STARTED ===');

                // Get all possible date values
                const startDateInput = document.getElementById('date_debut');
                const endDateInput = document.getElementById('date_fin');
                const dateRangeInput = document.getElementById('date_range');

                const startDate = startDateInput ? startDateInput.value : '';
                const endDate = endDateInput ? endDateInput.value : '';
                const dateRangeValue = dateRangeInput ? dateRangeInput.value : '';

                console.log('Form validation - Current values:', {
                    startDate: startDate,
                    endDate: endDate,
                    dateRangeValue: dateRangeValue,
                    selectedStartDate: selectedStartDate,
                    selectedEndDate: selectedEndDate
                });

                // First check: Are the hidden inputs populated?
                if (!startDate || !endDate || startDate.trim() === '' || endDate.trim() === '') {
                    console.log('Hidden inputs are empty, trying fallback methods...');

                    // Try to use global variables as fallback
                    if (selectedStartDate && selectedEndDate) {
                        console.log('Using global variables as fallback');
                        startDateInput.value = selectedStartDate;
                        endDateInput.value = selectedEndDate;
                    }
                    // Try to parse from display value as last resort
                    else if (dateRangeValue && dateRangeValue.includes(' - ')) {
                        console.log('Attempting to parse from display value:', dateRangeValue);
                        const parseSuccess = parseDisplayDates();
                        if (!parseSuccess) {
                            console.log('Parse failed - showing error');
                            e.preventDefault();
                            showValidationError('Erreur: Les dates ne peuvent pas être traitées. Veuillez les sélectionner à nouveau.');
                            return false;
                        }
                    }
                    else {
                        console.log('No dates found anywhere - showing error');
                        e.preventDefault();
                        showValidationError('Veuillez sélectionner vos dates de réservation.');

                        // Try to open the picker to help user
                        if (pickerInstance) {
                            pickerInstance.show();
                        }
                        return false;
                    }
                }

                // Get final values after potential fallback
                const finalStartDate = startDateInput.value;
                const finalEndDate = endDateInput.value;

                console.log('Final dates for validation:', finalStartDate, finalEndDate);

                // Validate date format and values
                const start = new Date(finalStartDate);
                const end = new Date(finalEndDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                    console.log('Invalid date objects');
                    e.preventDefault();
                    showValidationError('Les dates sélectionnées ne sont pas valides. Veuillez les sélectionner à nouveau.');
                    return false;
                }

                if (start < today) {
                    console.log('Start date is in the past');
                    e.preventDefault();
                    showValidationError('La date de début ne peut pas être dans le passé.');
                    return false;
                }

                if (end <= start) {
                    console.log('End date is not after start date');
                    e.preventDefault();
                    showValidationError('La date de fin doit être après la date de début.');
                    return false;
                }

                console.log('=== FORM VALIDATION PASSED ===');
                console.log('Submitting with dates:', finalStartDate, 'to', finalEndDate);
                return true;
            });

            // Manual date input functionality
            document.getElementById('toggle-manual').addEventListener('click', function() {
                const manualDates = document.getElementById('manual-dates');
                const isVisible = manualDates.style.display !== 'none';

                if (isVisible) {
                    manualDates.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-keyboard"></i> Saisie manuelle';
                } else {
                    manualDates.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times"></i> Masquer saisie manuelle';
                }
            });

            // Manual date input handlers
            document.getElementById('manual_start').addEventListener('change', function() {
                const startDate = this.value;
                const endInput = document.getElementById('manual_end');

                if (startDate) {
                    // Set minimum end date to start date + 1 day
                    const nextDay = new Date(startDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    endInput.min = nextDay.toISOString().split('T')[0];

                    updateManualDates();
                }
            });

            document.getElementById('manual_end').addEventListener('change', function() {
                updateManualDates();
            });

            function updateManualDates() {
                const startDate = document.getElementById('manual_start').value;
                const endDate = document.getElementById('manual_end').value;

                if (startDate && endDate) {
                    console.log('Manual dates selected:', startDate, endDate);

                    // Update hidden inputs
                    document.getElementById('date_debut').value = startDate;
                    document.getElementById('date_fin').value = endDate;

                    // Update global variables
                    selectedStartDate = startDate;
                    selectedEndDate = endDate;

                    // Update the main date picker display
                    const startFormatted = new Date(startDate).toLocaleDateString('fr-FR');
                    const endFormatted = new Date(endDate).toLocaleDateString('fr-FR');
                    document.getElementById('date_range').value = startFormatted + ' - ' + endFormatted;

                    // Update price estimate
                    const start = new Date(startDate);
                    const end = new Date(endDate);
                    updatePriceEstimate(start, end);

                    // Show status
                    showDateStatus('Dates saisies manuellement');
                }
            }

            function showDateStatus(message) {
                const statusDiv = document.getElementById('date-status');
                const statusText = document.getElementById('date-status-text');

                statusText.textContent = message;
                statusDiv.style.display = 'flex';

                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }

            // Real-time debug display update
            function updateDebugDisplay() {
                const startValue = document.getElementById('date_debut')?.value || 'Non défini';
                const endValue = document.getElementById('date_fin')?.value || 'Non défini';
                const displayValue = document.getElementById('date_range')?.value || 'Non défini';

                const debugStart = document.getElementById('debug-start');
                const debugEnd = document.getElementById('debug-end');
                const debugDisplay = document.getElementById('debug-display');
                const debugStatus = document.getElementById('debug-status');

                if (debugStart) debugStart.textContent = startValue;
                if (debugEnd) debugEnd.textContent = endValue;
                if (debugDisplay) debugDisplay.textContent = displayValue;

                if (debugStatus) {
                    if (startValue !== 'Non défini' && endValue !== 'Non défini') {
                        debugStatus.textContent = '✅ Dates définies - Prêt pour soumission';
                        debugStatus.style.color = '#28a745';
                    } else {
                        debugStatus.textContent = '❌ Dates manquantes';
                        debugStatus.style.color = '#dc3545';
                    }
                }
            }

            // Update debug display every second
            setInterval(updateDebugDisplay, 1000);

            // Function to check if a date range contains blocked dates
            function checkRangeForBlockedDates(startDate, endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const current = new Date(start);

                while (current <= end) {
                    const dateStr = current.toISOString().split('T')[0];
                    if (reservedDates.includes(dateStr)) {
                        console.log('Found blocked date in range:', dateStr);
                        return true;
                    }
                    current.setDate(current.getDate() + 1);
                }

                return false;
            }

            // Function to show blocked date error
            function showBlockedDateError() {
                // Remove existing error
                clearBlockedDateError();

                // Find which period is blocking
                let blockingPeriod = null;
                if (pickerInstance.getStartDate() && pickerInstance.getEndDate()) {
                    const start = pickerInstance.getStartDate().format('YYYY-MM-DD');
                    const end = pickerInstance.getEndDate().format('YYYY-MM-DD');

                    for (let period of reservationPeriods) {
                        if ((start >= period.start && start <= period.end) ||
                            (end >= period.start && end <= period.end) ||
                            (start <= period.start && end >= period.end)) {
                            blockingPeriod = period;
                            break;
                        }
                    }
                }

                // Create error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'blocked-date-error';
                errorDiv.innerHTML = `
                    <div class="blocked-error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="blocked-error-text">
                            <h4>Dates non disponibles</h4>
                            <p>Les dates sélectionnées incluent des périodes déjà réservées.</p>
                            ${blockingPeriod ? `
                                <p class="blocking-period">
                                    <strong>Période bloquée:</strong>
                                    Du ${new Date(blockingPeriod.start).toLocaleDateString('fr-FR')}
                                    au ${new Date(blockingPeriod.end).toLocaleDateString('fr-FR')}
                                    (${blockingPeriod.client})
                                </p>
                            ` : ''}
                            <p class="suggestion">Veuillez choisir d'autres dates disponibles.</p>
                        </div>
                        <button type="button" onclick="clearBlockedDateError()" class="close-error">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                // Insert before the date picker
                const datePickerContainer = document.querySelector('.date-picker-container');
                datePickerContainer.parentNode.insertBefore(errorDiv, datePickerContainer);

                // Auto-remove after 8 seconds
                setTimeout(() => {
                    clearBlockedDateError();
                }, 8000);
            }

            // Function to clear blocked date error
            function clearBlockedDateError() {
                const existingError = document.querySelector('.blocked-date-error');
                if (existingError) {
                    existingError.remove();
                }
            }

            // Make clearBlockedDateError globally available
            window.clearBlockedDateError = clearBlockedDateError;

            // Function to add tooltips to blocked dates
            function addBlockedDateTooltips() {
                const lockedDays = document.querySelectorAll('.litepicker .day-item.is-locked');

                lockedDays.forEach(day => {
                    const dateStr = day.getAttribute('data-time');
                    if (dateStr) {
                        const date = new Date(parseInt(dateStr));
                        const dateFormatted = date.toISOString().split('T')[0];

                        // Find which reservation period this date belongs to
                        let period = null;
                        for (let p of reservationPeriods) {
                            if (dateFormatted >= p.start && dateFormatted <= p.end) {
                                period = p;
                                break;
                            }
                        }

                        if (period) {
                            const tooltip = `Réservé par ${period.client}\nDu ${new Date(period.start).toLocaleDateString('fr-FR')} au ${new Date(period.end).toLocaleDateString('fr-FR')}`;
                            day.setAttribute('title', tooltip);
                            day.style.position = 'relative';
                        }
                    }
                });
            }

            // Force update function for manual troubleshooting
            window.forceUpdateDates = function() {
                console.log('=== FORCE UPDATE TRIGGERED ===');

                if (pickerInstance) {
                    const startDate = pickerInstance.getStartDate();
                    const endDate = pickerInstance.getEndDate();

                    console.log('Picker start date:', startDate);
                    console.log('Picker end date:', endDate);

                    if (startDate && endDate) {
                        console.log('Forcing date update...');
                        const success = updateSelectedDates(startDate, endDate);

                        if (success) {
                            alert('✅ Dates mises à jour avec succès!\n\nDébut: ' + startDate.format('DD/MM/YYYY') + '\nFin: ' + endDate.format('DD/MM/YYYY'));
                        } else {
                            alert('❌ Erreur lors de la mise à jour des dates');
                        }

                        updateDebugDisplay();
                    } else {
                        alert('❌ Aucune date sélectionnée dans le picker.\n\nVeuillez d\'abord sélectionner des dates dans le calendrier.');
                    }
                } else {
                    alert('❌ Picker non initialisé');
                }
            };

            // Alternative manual date parsing from display
            window.parseDisplayDates = function() {
                const displayValue = document.getElementById('date_range').value;
                console.log('Parsing display value:', displayValue);

                if (displayValue && displayValue.includes(' - ')) {
                    const [startStr, endStr] = displayValue.split(' - ');
                    console.log('Parsed strings:', startStr, endStr);

                    try {
                        // Parse DD/MM/YYYY format
                        const [startDay, startMonth, startYear] = startStr.split('/');
                        const [endDay, endMonth, endYear] = endStr.split('/');

                        const startFormatted = startYear + '-' + startMonth.padStart(2, '0') + '-' + startDay.padStart(2, '0');
                        const endFormatted = endYear + '-' + endMonth.padStart(2, '0') + '-' + endDay.padStart(2, '0');

                        console.log('Formatted for MySQL:', startFormatted, endFormatted);

                        // Update hidden inputs directly
                        document.getElementById('date_debut').value = startFormatted;
                        document.getElementById('date_fin').value = endFormatted;

                        // Update global variables
                        selectedStartDate = startFormatted;
                        selectedEndDate = endFormatted;

                        updateDebugDisplay();

                        alert('✅ Dates extraites de l\'affichage!\n\nDébut: ' + startFormatted + '\nFin: ' + endFormatted);

                        return true;
                    } catch (error) {
                        console.error('Error parsing dates:', error);
                        alert('❌ Erreur lors de l\'analyse des dates');
                        return false;
                    }
                } else {
                    alert('❌ Aucune date trouvée dans l\'affichage');
                    return false;
                }
            };

            function showValidationError(message) {
                // Remove existing error messages
                const existingError = document.querySelector('.validation-error');
                if (existingError) {
                    existingError.remove();
                }

                // Create error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error alert alert-error';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;

                // Insert before the form
                const form = document.getElementById('reservationForm');
                form.parentNode.insertBefore(errorDiv, form);

                // Scroll to error
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Remove after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 5000);

                // Also show browser alert as fallback
                alert(message);
            }
        });
    </script>
</body>
</html>
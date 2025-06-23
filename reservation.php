<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userQuery = "SELECT * FROM client WHERE id_client = $userId";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

$userNom = $user['nom'] ?? '';
$userEmail = $user['email'] ?? '';
$userTelephone = $user['téléphone'] ?? '';
$dateDebut = $dateDebut ?? '';
$dateFin = $dateFin ?? '';
$totalPrice = $totalPrice ?? 0;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cars.php");
    exit();
}
$carId = (int)$_GET['id'];
$carQuery = "SELECT * FROM voiture WHERE id_voiture = $carId";
$carResult = mysqli_query($conn, $carQuery);
if (mysqli_num_rows($carResult) === 0) {
    header("Location: cars.php");
    exit();
}
$car = mysqli_fetch_assoc($carResult);
if ($car['statut'] === 'maintenance') {
    header("Location: car-details.php?id=$carId");
    exit();
}

// Handle form
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dateDebut = $_POST["date_debut"];
    $dateFin = $_POST["date_fin"];
    if (empty($dateDebut)) $errors[] = "Date de début est requise";
    if (empty($dateFin)) $errors[] = "Date de fin est requise";
    if (empty($errors)) {
        $today = date('Y-m-d');
        if ($dateDebut < $today) $errors[] = "La date de début ne peut pas être dans le passé";
        if ($dateFin < $dateDebut) $errors[] = "La date de fin doit être après la date de début";
    }
    if (empty($errors)) {
        $start = new DateTime($dateDebut);
        $end = new DateTime($dateFin);
        $days = $start->diff($end)->days + 1;
        $totalPrice = $days * $car['prix_par_jour'];
        $insertQuery = "INSERT INTO reservation (id_client, date_debut, date_fin, id_voiture) VALUES ($userId, '$dateDebut', '$dateFin', $carId)";
        mysqli_query($conn, $insertQuery);
        header("Location: reservations.php");
        exit();
    }
}

// Reserved dates for calendar
$reservedDates = [];
$reservationPeriods = [];
$reservedQuery = "SELECT r.date_debut, r.date_fin, c.nom as client FROM reservation r
                  JOIN client c ON r.id_client = c.id_client
                  WHERE r.id_voiture = $carId AND r.date_fin >= CURDATE()";
$reservedResult = mysqli_query($conn, $reservedQuery);
while ($row = mysqli_fetch_assoc($reservedResult)) {
    $start = new DateTime($row['date_debut']);
    $end = new DateTime($row['date_fin']);
    $current = clone $start;
    $reservationPeriods[] = [
        'start' => $row['date_debut'],
        'end' => $row['date_fin'],
        'client' => $row['client']
    ];
    while ($current <= $end) {
        $reservedDates[] = $current->format('Y-m-d');
        $current->add(new DateInterval('P1D'));
    }
}
$reservedDates = array_unique($reservedDates);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <label for="date_range">Sélectionnez vos dates*</label>
                            <div class="date-picker-container">
                                <input type="text" id="date_range" placeholder="Cliquez pour sélectionner les dates" readonly>
                                <i class="fas fa-calendar-alt date-picker-icon"></i>
                            </div>
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
                            <button type="button" id="toggle-manual" class="toggle-manual-btn">
                                <i class="fas fa-keyboard"></i> Saisie manuelle
                            </button>
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
<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysCount = document.getElementById('daysCount');
    const totalPrice = document.getElementById('totalPrice');
    const priceEstimate = document.getElementById('priceEstimate');
    const pricePerDay = <?php echo $car['prix_par_jour']; ?>;
    const reservedDates = <?php echo json_encode($reservedDates); ?>;
    let selectedStartDate = null, selectedEndDate = null;

    // Litepicker
    const picker = new Litepicker({
        element: document.getElementById('date_range'),
        singleMode: false,
        minDate: new Date(),
        format: 'DD/MM/YYYY',
        delimiter: ' - ',
        lang: 'fr-FR',
        lockDaysFilter: date => reservedDates.includes(date.format('YYYY-MM-DD')),
        disallowLockDaysInRange: true,
        onSelect: (start, end) => {
            if (start && end) updateSelectedDates(start, end);
        }
    });

    function updateSelectedDates(start, end) {
        const startVal = start.format('YYYY-MM-DD');
        const endVal = end.format('YYYY-MM-DD');
        document.getElementById('date_debut').value = startVal;
        document.getElementById('date_fin').value = endVal;
        document.getElementById('date_range').value = start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY');
        selectedStartDate = startVal;
        selectedEndDate = endVal;
        updatePriceEstimate(new Date(startVal), new Date(endVal));
    }

    function updatePriceEstimate(start, end) {
        const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        daysCount.textContent = diffDays;
        totalPrice.textContent = (pricePerDay * diffDays).toFixed(2) + ' €';
        priceEstimate.style.display = 'block';
    }

    // Manual date input
    document.getElementById('toggle-manual').onclick = function() {
        const manual = document.getElementById('manual-dates');
        manual.style.display = manual.style.display === 'block' ? 'none' : 'block';
    };
    document.getElementById('manual_start').onchange = document.getElementById('manual_end').onchange = function() {
        const s = document.getElementById('manual_start').value;
        const e = document.getElementById('manual_end').value;
        if (s && e) {
            document.getElementById('date_debut').value = s;
            document.getElementById('date_fin').value = e;
            document.getElementById('date_range').value = new Date(s).toLocaleDateString('fr-FR') + ' - ' + new Date(e).toLocaleDateString('fr-FR');
            updatePriceEstimate(new Date(s), new Date(e));
        }
    };

    // Form validation
    document.getElementById('reservationForm').onsubmit = function(e) {
        const s = document.getElementById('date_debut').value;
        const e_ = document.getElementById('date_fin').value;
        // Fallback: try to set hidden fields from picker if empty
        if ((!s || !e_) && picker.getStartDate() && picker.getEndDate()) {
            updateSelectedDates(picker.getStartDate(), picker.getEndDate());
        }
        if (!document.getElementById('date_debut').value || !document.getElementById('date_fin').value) {
            alert('Veuillez sélectionner vos dates de réservation.');
            return false;
        }
        if (new Date(document.getElementById('date_debut').value) < new Date().setHours(0,0,0,0)) {
            alert('La date de début ne peut pas être dans le passé.');
            return false;
        }
        if (new Date(document.getElementById('date_fin').value) <= new Date(document.getElementById('date_debut').value)) {
            alert('La date de fin doit être après la date de début.');
            return false;
        }
        return true;
    };
});
</script>
</body>
</html>
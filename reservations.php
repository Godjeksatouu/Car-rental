<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'Veuillez vous connecter pour accéder à vos réservations', 'error');
}

$userId = $_SESSION['user_id'];

// Get user details
$query = "SELECT * FROM CLIENT WHERE id_client = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get user reservations
$query = "SELECT r.*, v.marque, v.modele, v.image, v.prix_par_jour, l.ETAT_PAIEMENT, l.id_location 
          FROM RESERVATION r 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation 
          WHERE r.id_client = ? 
          ORDER BY r.date_debut DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$reservations = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - AutoDrive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <div class="profile-content">
                <div class="sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-name">
                            <h3><?php echo $user['prénom'] ? $user['prénom'] . ' ' . $user['nom'] : $user['nom']; ?></h3>
                            <p><?php echo isset($user['email']) ? $user['email'] : ''; ?></p>
                        </div>
                    </div>
                    <ul class="sidebar-menu">
                        <li><a href="profile.php"><i class="fas fa-user"></i> Mon Profil</a></li>
                        <li class="active"><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Mes Réservations</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
                <div class="main-content">
                    <div class="section-header">
                        <h2>Mes Réservations</h2>
                        <p>Suivez toutes vos réservations de voitures</p>
                    </div>
                    
                    <div class="reservations-list">
                        <?php if (mysqli_num_rows($reservations) > 0): ?>
                            <?php while ($reservation = mysqli_fetch_assoc($reservations)): ?>
                                <div class="reservation-card">
                                    <div class="reservation-header">
                                        <h3><?php echo $reservation['marque'] . ' ' . $reservation['modele']; ?></h3>
                                        <?php
                                        $today = date('Y-m-d');
                                        $status = '';
                                        $statusClass = '';
                                        
                                        if ($reservation['date_fin'] < $today) {
                                            $status = 'Terminée';
                                            $statusClass = 'completed';
                                        } elseif ($reservation['date_debut'] > $today) {
                                            $status = 'À venir';
                                            $statusClass = 'upcoming';
                                        } else {
                                            $status = 'En cours';
                                            $statusClass = 'active';
                                        }
                                        ?>
                                        <div class="reservation-status <?php echo $statusClass; ?>"><?php echo $status; ?></div>
                                    </div>
                                    <div class="reservation-details">
                                        <div class="car-image">
                                            <?php
                                            $image = $reservation['image'] ? $reservation['image'] : 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1';
                                            ?>
                                            <img src="<?php echo $image; ?>" alt="<?php echo $reservation['marque'] . ' ' . $reservation['modele']; ?>">
                                        </div>
                                        <div class="reservation-info">
                                            <div class="info-row">
                                                <span class="label">Dates:</span>
                                                <span class="value"><?php echo formatDate($reservation['date_debut']); ?> - <?php echo formatDate($reservation['date_fin']); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Durée:</span>
                                                <?php
                                                $start = new DateTime($reservation['date_debut']);
                                                $end = new DateTime($reservation['date_fin']);
                                                $interval = $start->diff($end);
                                                $days = $interval->days + 1;
                                                ?>
                                                <span class="value"><?php echo $days; ?> jour(s)</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Prix total:</span>
                                                <span class="value"><?php echo ($reservation['prix_par_jour'] * $days); ?> €</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label">Statut paiement:</span>
                                                <span class="value payment-status <?php echo $reservation['ETAT_PAIEMENT'] ? 'paid' : 'unpaid'; ?>">
                                                    <?php echo $reservation['ETAT_PAIEMENT'] ? 'Payé' : 'Non payé'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="reservation-actions">
                                        <?php if (!$reservation['ETAT_PAIEMENT'] && $reservation['date_debut'] > $today): ?>
                                            <a href="payment.php?id=<?php echo $reservation['id_location']; ?>" class="btn btn-primary">Payer maintenant</a>
                                        <?php endif; ?>
                                        <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-outline">Voir détails</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-reservations">
                                <i class="fas fa-calendar-times"></i>
                                <h3>Aucune réservation</h3>
                                <p>Vous n'avez pas encore effectué de réservation.</p>
                                <a href="cars.php" class="btn btn-primary">Explorer les voitures</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: clients.php");
    exit();
}

$client_id = (int)$_GET['id'];

// Get client details
$query = "SELECT * FROM CLIENT WHERE id_client = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    header("Location: clients.php");
    exit();
}

// Get client reservations
$query = "SELECT r.*, v.marque, v.modele, v.immatriculation 
          FROM RESERVATION r 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          WHERE r.id_client = ? 
          ORDER BY r.date_reservation DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$reservations = $stmt->get_result();

// Get client payments
$query = "SELECT l.*, r.date_debut, r.date_fin, v.marque, v.modele 
          FROM LOCATION l 
          JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
          JOIN VOITURE v ON r.id_voiture = v.id_voiture 
          WHERE r.id_client = ? 
          ORDER BY l.date_paiement DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Client - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include 'includes/admin-header.php'; ?>

    <section class="admin-dashboard">
        <div class="container">
            <!-- Client Info Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-user"></i> Détails du Client</h2>
                    <a href="clients.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
                </div>
                <div class="admin-card-body">
                    <div class="client-details">
                        <div class="client-info">
                            <div class="client-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="client-data">
                                <h3><?php echo htmlspecialchars($client['nom'] . ' ' . $client['prénom']); ?></h3>
                                <p class="client-id">Client #<?php echo $client['id_client']; ?></p>
                                <div class="client-contact">
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['téléphone']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($client['adresse']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="client-stats">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="stat-content">
                                    <h4>Réservations</h4>
                                    <p class="stat-value"><?php echo $reservations->num_rows; ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="stat-content">
                                    <h4>Paiements</h4>
                                    <p class="stat-value"><?php echo $payments->num_rows; ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                                <div class="stat-content">
                                    <h4>Client depuis</h4>
                                    <p class="stat-value"><?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-calendar-alt"></i> Réservations</h2>
                </div>
                <div class="admin-card-body">
                    <?php if ($reservations->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Voiture</th>
                                        <th>Date de début</th>
                                        <th>Date de fin</th>
                                        <th>Statut</th>
                                        <th>Date de réservation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $reservation['id_reservation']; ?></td>
                                            <td><?php echo htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($reservation['statut']); ?>">
                                                    <?php echo htmlspecialchars($reservation['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></td>
                                            <td class="actions">
                                                <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>Aucune réservation</h3>
                            <p>Ce client n'a pas encore effectué de réservation.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payments Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2><i class="fas fa-money-check-alt"></i> Paiements</h2>
                </div>
                <div class="admin-card-body">
                    <?php if ($payments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Voiture</th>
                                        <th>Période</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Date de paiement</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $payment['id_location']; ?></td>
                                            <td><?php echo htmlspecialchars($payment['marque'] . ' ' . $payment['modele']); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($payment['date_debut'])) . ' - ' . date('d/m/Y', strtotime($payment['date_fin'])); ?>
                                            </td>
                                            <td><?php echo number_format($payment['montant'], 2, ',', ' '); ?> €</td>
                                            <td><?php echo htmlspecialchars($payment['methode_paiement']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['date_paiement'])); ?></td>
                                            <td>
                                                <span class="payment-status <?php echo $payment['ETAT_PAIEMENT'] ? 'paid' : 'unpaid'; ?>">
                                                    <?php echo $payment['ETAT_PAIEMENT'] ? 'Payé' : 'Non payé'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h3>Aucun paiement</h3>
                            <p>Ce client n'a pas encore effectué de paiement.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
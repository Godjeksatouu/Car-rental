<?php
/**
 * CLIENT DETAILS PAGE - CLEAN VERSION
 *
 * This page shows detailed information about a specific client including
 * their reservations and payment history.
 *
 * WHAT THIS PAGE DOES:
 * 1. Displays client personal information
 * 2. Shows all reservations made by the client
 * 3. Lists payment history and status
 * 4. Provides statistics about the client's activity
 *
 * BEGINNER EXPLANATION:
 * - Like a customer profile page in an online store
 * - Shows everything we know about this specific customer
 * - Helps admin staff understand customer history and activity
 */

// =============================================================================
// SETUP AND SECURITY CHECKS
// =============================================================================

// Start session to track admin login
session_start();

// Include our database connection and helper functions
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin (only admins can view client details)
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Check if a client ID was provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: clients.php");
    exit();
}

// Get the client ID from the URL and convert to integer for security
$client_id = (int)$_GET['id'];

// =============================================================================
// GET CLIENT INFORMATION FROM DATABASE
// =============================================================================

// Get basic client details
$client_query = "SELECT * FROM CLIENT WHERE id_client = ?";
$client_statement = mysqli_prepare($conn, $client_query);

// Check if the query preparation was successful
if (!$client_statement) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($client_statement, "i", $client_id);
mysqli_stmt_execute($client_statement);
$client_result = mysqli_stmt_get_result($client_statement);
$client_data = mysqli_fetch_assoc($client_result);

// If client doesn't exist, redirect back to clients list
if (!$client_data) {
    header("Location: clients.php");
    exit();
}

// =============================================================================
// GET CLIENT RESERVATIONS
// =============================================================================

// Get all reservations made by this client
// Note: We removed 'date_reservation' as it doesn't exist in the RESERVATION table
$reservations_query = "SELECT r.*, v.marque, v.modele, v.immatriculation
                      FROM RESERVATION r
                      JOIN VOITURE v ON r.id_voiture = v.id_voiture
                      WHERE r.id_client = ?
                      ORDER BY r.date_debut DESC";

$reservations_statement = mysqli_prepare($conn, $reservations_query);

// Check if the query preparation was successful
if (!$reservations_statement) {
    die("Database error in reservations query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($reservations_statement, "i", $client_id);
mysqli_stmt_execute($reservations_statement);
$reservations_result = mysqli_stmt_get_result($reservations_statement);

// =============================================================================
// GET CLIENT PAYMENTS
// =============================================================================

// Get all payments made by this client through their reservations
$payments_query = "SELECT l.*, r.date_debut, r.date_fin, v.marque, v.modele
                  FROM LOCATION l
                  JOIN RESERVATION r ON l.id_reservation = r.id_reservation
                  JOIN VOITURE v ON r.id_voiture = v.id_voiture
                  WHERE r.id_client = ?
                  ORDER BY l.id_location DESC";

$payments_statement = mysqli_prepare($conn, $payments_query);

// Check if the query preparation was successful
if (!$payments_statement) {
    die("Database error in payments query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($payments_statement, "i", $client_id);
mysqli_stmt_execute($payments_statement);
$payments_result = mysqli_stmt_get_result($payments_statement);
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
                                <h3><?php echo htmlspecialchars($client_data['nom'] . ' ' . $client_data['prénom']); ?></h3>
                                <p class="client-id">Client #<?php echo $client_data['id_client']; ?></p>
                                <div class="client-contact">
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client_data['email']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client_data['téléphone']); ?></p>
                                    <?php if (isset($client_data['adresse']) && !empty($client_data['adresse'])): ?>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($client_data['adresse']); ?></p>
                                    <?php else: ?>
                                        <p><i class="fas fa-map-marker-alt"></i> <em>Adresse non renseignée</em></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="client-stats">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="stat-content">
                                    <h4>Réservations</h4>
                                    <p class="stat-value"><?php echo mysqli_num_rows($reservations_result); ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="stat-content">
                                    <h4>Paiements</h4>
                                    <p class="stat-value"><?php echo mysqli_num_rows($payments_result); ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                                <div class="stat-content">
                                    <h4>Client depuis</h4>
                                    <p class="stat-value">
                                        <?php
                                        // Check if date_inscription exists, otherwise use a default
                                        if (isset($client_data['date_inscription']) && !empty($client_data['date_inscription']) && $client_data['date_inscription'] !== '0000-00-00') {
                                            echo date('d/m/Y', strtotime($client_data['date_inscription']));
                                        } else {
                                            echo "Non disponible";
                                        }
                                        ?>
                                    </p>
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
                    <?php if (mysqli_num_rows($reservations_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Voiture</th>
                                        <th>Date de début</th>
                                        <th>Date de fin</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation_data = mysqli_fetch_assoc($reservations_result)): ?>
                                        <tr>
                                            <td>#<?php echo $reservation_data['id_reservation']; ?></td>
                                            <td><?php echo htmlspecialchars($reservation_data['marque'] . ' ' . $reservation_data['modele']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation_data['date_debut'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation_data['date_fin'])); ?></td>
                                            <td class="actions">
                                                <a href="edit-reservation.php?id=<?php echo $reservation_data['id_reservation']; ?>" class="btn btn-sm btn-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete-reservation.php?id=<?php echo $reservation_data['id_reservation']; ?>"
                                                   class="btn btn-sm btn-danger"
                                                   title="Supprimer"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')">
                                                    <i class="fas fa-trash"></i>
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
                    <?php if (mysqli_num_rows($payments_result) > 0): ?>
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
                                    <?php while ($payment_data = mysqli_fetch_assoc($payments_result)): ?>
                                        <tr>
                                            <td>#<?php echo $payment_data['id_location']; ?></td>
                                            <td><?php echo htmlspecialchars($payment_data['marque'] . ' ' . $payment_data['modele']); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($payment_data['date_debut'])) . ' - ' . date('d/m/Y', strtotime($payment_data['date_fin'])); ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Check if montant field exists, otherwise calculate from car price and dates
                                                if (isset($payment_data['montant']) && !empty($payment_data['montant'])) {
                                                    echo number_format($payment_data['montant'], 2, ',', ' ') . ' €';
                                                } else {
                                                    echo 'Non calculé';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Check if payment method exists
                                                if (isset($payment_data['methode_paiement']) && !empty($payment_data['methode_paiement'])) {
                                                    echo htmlspecialchars($payment_data['methode_paiement']);
                                                } else {
                                                    echo 'Non spécifié';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Check if payment date exists
                                                if (isset($payment_data['date_paiement']) && !empty($payment_data['date_paiement'])) {
                                                    echo date('d/m/Y H:i', strtotime($payment_data['date_paiement']));
                                                } else {
                                                    echo 'Non payé';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="payment-status <?php echo $payment_data['ETAT_PAIEMENT'] ? 'paid' : 'unpaid'; ?>">
                                                    <?php echo $payment_data['ETAT_PAIEMENT'] ? 'Payé' : 'Non payé'; ?>
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
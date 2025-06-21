<?php
/**
 * CLIENT DETAILS PAGE - CLEAN VERSION
 * 
 * This admin page shows detailed information about a specific client including
 * their personal information, reservation history, and payment status.
 * 
 * WHAT THIS PAGE DOES:
 * 1. Displays client personal information (name, email, phone, address)
 * 2. Shows statistics about the client (number of reservations, payments, etc.)
 * 3. Lists all reservations made by the client
 * 4. Shows payment history and status for each reservation
 * 5. Provides action buttons to edit or delete reservations
 * 
 * BEGINNER EXPLANATION:
 * - Like a customer profile page in a CRM system
 * - Shows everything an admin needs to know about a specific customer
 * - Helps customer service staff understand client history and resolve issues
 * - Provides quick access to modify or cancel client reservations
 */

// =============================================================================
// SETUP AND SECURITY CHECKS
// =============================================================================

// Start session to track admin login status
session_start();

// Include our database connection and helper functions
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin (only admins can view client details)
if (!isAdmin()) {
    // If not admin, redirect to login page
    header("Location: ../login.php");
    exit();
}

// Check if a client ID was provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // If no valid ID provided, redirect back to clients list
    header("Location: clients.php");
    exit();
}

// Get the client ID from URL and convert to integer for security
$client_id = (int)$_GET['id'];

// =============================================================================
// GET CLIENT INFORMATION FROM DATABASE
// =============================================================================

/**
 * Get basic client details
 * 
 * WHAT IT DOES: Retrieves all information about the specific client
 * HOW IT WORKS: Uses prepared statement to safely query the CLIENT table
 */
$client_query = "SELECT * FROM CLIENT WHERE id_client = ?";
$client_statement = mysqli_prepare($conn, $client_query);

// Check if query preparation was successful
if (!$client_statement) {
    die("❌ Database error in client query: " . mysqli_error($conn));
}

// Execute the query with the client ID
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

/**
 * Get all reservations made by this client
 * 
 * WHAT IT DOES: Retrieves reservation history with car details
 * HOW IT WORKS: Joins RESERVATION and VOITURE tables to get complete information
 */
$reservations_query = "SELECT r.*, v.marque, v.modele, v.immatriculation 
                      FROM RESERVATION r 
                      JOIN VOITURE v ON r.id_voiture = v.id_voiture 
                      WHERE r.id_client = ? 
                      ORDER BY r.date_debut DESC";

$reservations_statement = mysqli_prepare($conn, $reservations_query);

// Check if query preparation was successful
if (!$reservations_statement) {
    die("❌ Database error in reservations query: " . mysqli_error($conn));
}

// Execute the query
mysqli_stmt_bind_param($reservations_statement, "i", $client_id);
mysqli_stmt_execute($reservations_statement);
$reservations_result = mysqli_stmt_get_result($reservations_statement);

// =============================================================================
// GET CLIENT PAYMENTS
// =============================================================================

/**
 * Get all payments made by this client
 * 
 * WHAT IT DOES: Retrieves payment history through reservations
 * HOW IT WORKS: Joins LOCATION, RESERVATION, and VOITURE tables
 */
$payments_query = "SELECT l.*, r.date_debut, r.date_fin, v.marque, v.modele 
                  FROM LOCATION l 
                  JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
                  JOIN VOITURE v ON r.id_voiture = v.id_voiture 
                  WHERE r.id_client = ? 
                  ORDER BY l.id_location DESC";

$payments_statement = mysqli_prepare($conn, $payments_query);

// Check if query preparation was successful
if (!$payments_statement) {
    die("❌ Database error in payments query: " . mysqli_error($conn));
}

// Execute the query
mysqli_stmt_bind_param($payments_statement, "i", $client_id);
mysqli_stmt_execute($payments_statement);
$payments_result = mysqli_stmt_get_result($payments_statement);

// =============================================================================
// CALCULATE STATISTICS
// =============================================================================

// Count total reservations
$total_reservations = mysqli_num_rows($reservations_result);

// Count total payments/locations
$total_payments = mysqli_num_rows($payments_result);

// Calculate total spent (if montant field exists)
$total_spent = 0;
mysqli_data_seek($payments_result, 0); // Reset result pointer
while ($payment_row = mysqli_fetch_assoc($payments_result)) {
    if (isset($payment_row['montant']) && is_numeric($payment_row['montant'])) {
        $total_spent += $payment_row['montant'];
    }
}
mysqli_data_seek($payments_result, 0); // Reset again for display

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Client - <?php echo htmlspecialchars($client_data['nom'] . ' ' . $client_data['prénom']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>

        <main class="admin-main">
            <!-- Page Header -->
            <div class="admin-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>
                            <i class="fas fa-user"></i> 
                            Détails du Client
                        </h1>
                        <p>Informations complètes sur le client sélectionné</p>
                    </div>
                    <div class="header-actions">
                        <a href="clients.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                        <a href="edit-client.php?id=<?php echo $client_id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    </div>
                </div>
            </div>

            <!-- Client Information Card -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-id-card"></i> Informations Personnelles</h2>
                </div>
                <div class="card-content">
                    <div class="client-profile">
                        <div class="client-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="client-info">
                            <h3><?php echo htmlspecialchars($client_data['nom'] . ' ' . $client_data['prénom']); ?></h3>
                            <p class="client-id">Client #<?php echo $client_data['id_client']; ?></p>
                            
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($client_data['email']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($client_data['téléphone']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($client_data['adresse']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon reservations">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Réservations</h3>
                        <p class="stat-number"><?php echo $total_reservations; ?></p>
                        <span class="stat-label">Total</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon payments">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Locations</h3>
                        <p class="stat-number"><?php echo $total_payments; ?></p>
                        <span class="stat-label">Effectuées</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Chiffre d'affaires</h3>
                        <p class="stat-number"><?php echo number_format($total_spent, 2, ',', ' '); ?> €</p>
                        <span class="stat-label">Total généré</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon member-since">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Client depuis</h3>
                        <p class="stat-number">
                            <?php 
                            // Display registration date if available
                            if (isset($client_data['date_inscription']) && !empty($client_data['date_inscription'])) {
                                echo date('d/m/Y', strtotime($client_data['date_inscription']));
                            } else {
                                echo "N/A";
                            }
                            ?>
                        </p>
                        <span class="stat-label">Date d'inscription</span>
                    </div>
                </div>
            </div>

            <!-- Reservations Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-alt"></i> Historique des Réservations</h2>
                    <span class="badge"><?php echo $total_reservations; ?> réservation(s)</span>
                </div>
                <div class="card-content">
                    <?php if ($total_reservations > 0): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Véhicule</th>
                                        <th>Période</th>
                                        <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Reset result pointer and display reservations
                                    mysqli_data_seek($reservations_result, 0);
                                    while ($reservation_data = mysqli_fetch_assoc($reservations_result)): 
                                        // Calculate rental duration
                                        $start_date = new DateTime($reservation_data['date_debut']);
                                        $end_date = new DateTime($reservation_data['date_fin']);
                                        $duration = $start_date->diff($end_date)->days + 1;
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="reservation-id">#<?php echo $reservation_data['id_reservation']; ?></span>
                                            </td>
                                            <td>
                                                <div class="vehicle-info">
                                                    <strong><?php echo htmlspecialchars($reservation_data['marque'] . ' ' . $reservation_data['modele']); ?></strong>
                                                    <small><?php echo htmlspecialchars($reservation_data['immatriculation']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-range">
                                                    <span class="start-date"><?php echo date('d/m/Y', strtotime($reservation_data['date_debut'])); ?></span>
                                                    <i class="fas fa-arrow-right"></i>
                                                    <span class="end-date"><?php echo date('d/m/Y', strtotime($reservation_data['date_fin'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="duration"><?php echo $duration; ?> jour(s)</span>
                                            </td>
                                            <td class="actions">
                                                <a href="edit-reservation.php?id=<?php echo $reservation_data['id_reservation']; ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Modifier la réservation">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete-reservation.php?id=<?php echo $reservation_data['id_reservation']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Supprimer la réservation"
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
                            <i class="fas fa-calendar-times"></i>
                            <h3>Aucune réservation</h3>
                            <p>Ce client n'a pas encore effectué de réservation.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payments Section -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-credit-card"></i> Historique des Locations</h2>
                    <span class="badge"><?php echo $total_payments; ?> location(s)</span>
                </div>
                <div class="card-content">
                    <?php if ($total_payments > 0): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID Location</th>
                                        <th>Véhicule</th>
                                        <th>Période</th>
                                        <th>Statut Paiement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Reset result pointer and display payments
                                    mysqli_data_seek($payments_result, 0);
                                    while ($payment_data = mysqli_fetch_assoc($payments_result)): 
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="location-id">#<?php echo $payment_data['id_location']; ?></span>
                                            </td>
                                            <td>
                                                <div class="vehicle-info">
                                                    <strong><?php echo htmlspecialchars($payment_data['marque'] . ' ' . $payment_data['modele']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-range">
                                                    <span><?php echo date('d/m/Y', strtotime($payment_data['date_debut'])); ?></span>
                                                    <i class="fas fa-arrow-right"></i>
                                                    <span><?php echo date('d/m/Y', strtotime($payment_data['date_fin'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="payment-status <?php echo $payment_data['ETAT_PAIEMENT'] ? 'paid' : 'pending'; ?>">
                                                    <i class="fas <?php echo $payment_data['ETAT_PAIEMENT'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                                    <?php echo $payment_data['ETAT_PAIEMENT'] ? 'Payé' : 'En attente'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h3>Aucune location</h3>
                            <p>Ce client n'a pas encore effectué de location.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

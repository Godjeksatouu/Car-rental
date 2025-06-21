<?php
/**
 * RESERVATION DETAILS PAGE - ADMIN DASHBOARD
 * 
 * This page shows complete details of a specific reservation and allows editing.
 * 
 * WHAT THIS PAGE DOES:
 * 1. Displays full reservation information (client, car, dates, payment status)
 * 2. Shows client details and contact information
 * 3. Displays car details and specifications
 * 4. Allows editing of reservation dates and status
 * 5. Shows payment history and allows payment updates
 * 6. Provides quick actions (cancel, modify, contact client)
 * 
 * BEGINNER EXPLANATION:
 * - Like a detailed order page in an e-commerce admin panel
 * - Shows everything about one specific car reservation
 * - Allows admin to modify or cancel the reservation
 * - Helps customer service resolve issues quickly
 */

// =============================================================================
// SETUP AND SECURITY CHECKS
// =============================================================================

// Start session to track admin login
session_start();

// Include database connection and helper functions
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin (only admins can view reservation details)
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Check if reservation ID was provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reservations.php");
    exit();
}

// Get reservation ID and convert to integer for security
$reservation_id = (int)$_GET['id'];

// =============================================================================
// PROCESS FORM SUBMISSIONS
// =============================================================================

$success_message = "";
$error_message = "";

// Handle reservation update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reservation'])) {
    
    $new_start_date = cleanUserInput($_POST['date_debut']);
    $new_end_date = cleanUserInput($_POST['date_fin']);
    
    // Validate dates
    if (empty($new_start_date) || empty($new_end_date)) {
        $error_message = "Les dates de début et fin sont requises.";
    } elseif (strtotime($new_start_date) >= strtotime($new_end_date)) {
        $error_message = "La date de fin doit être après la date de début.";
    } else {
        // Update reservation
        $update_query = "UPDATE RESERVATION SET date_debut = ?, date_fin = ? WHERE id_reservation = ?";
        $update_statement = mysqli_prepare($conn, $update_query);
        
        if ($update_statement) {
            mysqli_stmt_bind_param($update_statement, "ssi", $new_start_date, $new_end_date, $reservation_id);
            
            if (mysqli_stmt_execute($update_statement)) {
                $success_message = "Réservation mise à jour avec succès.";
            } else {
                $error_message = "Erreur lors de la mise à jour: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Erreur de préparation de la requête: " . mysqli_error($conn);
        }
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_status = (int)$_POST['payment_status'];
    
    // Update payment status in LOCATION table
    $payment_update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = ? WHERE id_reservation = ?";
    $payment_statement = mysqli_prepare($conn, $payment_update_query);
    
    if ($payment_statement) {
        mysqli_stmt_bind_param($payment_statement, "ii", $payment_status, $reservation_id);
        
        if (mysqli_stmt_execute($payment_statement)) {
            $success_message = "Statut de paiement mis à jour avec succès.";
        } else {
            $error_message = "Erreur lors de la mise à jour du paiement: " . mysqli_error($conn);
        }
    }
}

// =============================================================================
// GET RESERVATION DETAILS
// =============================================================================

// Get complete reservation information with client and car details
// Note: Based on your database structure, payment amount is in PAIEMENT table, not LOCATION
$reservation_query = "SELECT
    r.*,
    c.nom as client_nom,
    c.prénom as client_prenom,
    c.email as client_email,
    c.téléphone as client_telephone,
    v.marque,
    v.modele,
    v.immatriculation,
    v.type as fuel_type,
    v.nb_places,
    v.prix_par_jour,
    v.gear,
    l.id_location,
    l.ETAT_PAIEMENT,
    p.montant as payment_amount,
    p.date_paiement,
    p.mode_paiement
FROM RESERVATION r
LEFT JOIN CLIENT c ON r.id_client = c.id_client
LEFT JOIN VOITURE v ON r.id_voiture = v.id_voiture
LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation
LEFT JOIN PAIEMENT p ON l.id_location = p.id_location
WHERE r.id_reservation = ?";

$reservation_statement = mysqli_prepare($conn, $reservation_query);

if (!$reservation_statement) {
    die("Erreur de base de données: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($reservation_statement, "i", $reservation_id);
mysqli_stmt_execute($reservation_statement);
$reservation_result = mysqli_stmt_get_result($reservation_statement);
$reservation_data = mysqli_fetch_assoc($reservation_result);

// If reservation doesn't exist, redirect to reservations list
if (!$reservation_data) {
    header("Location: reservations.php");
    exit();
}

// =============================================================================
// CALCULATE RESERVATION DETAILS
// =============================================================================

// Calculate rental duration
$start_date = new DateTime($reservation_data['date_debut']);
$end_date = new DateTime($reservation_data['date_fin']);
$duration = $start_date->diff($end_date)->days + 1;

// Calculate total price
$daily_price = $reservation_data['prix_par_jour'];
$total_price = $daily_price * $duration;

// Determine reservation status
$today = new DateTime();
$reservation_status = "";
$status_class = "";

if ($start_date > $today) {
    $reservation_status = "À venir";
    $status_class = "upcoming";
} elseif ($end_date < $today) {
    $reservation_status = "Terminée";
    $status_class = "completed";
} else {
    $reservation_status = "En cours";
    $status_class = "active";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Réservation #<?php echo $reservation_id; ?> - AutoDrive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* =============================================================================
           RESERVATION DETAILS PAGE STYLES
           ============================================================================= */
        
        .reservation-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .reservation-header h1 {
            margin: 0;
            font-size: 2.5rem;
        }
        
        .reservation-header .breadcrumb {
            margin-top: 10px;
            opacity: 0.9;
        }
        
        .reservation-header .breadcrumb a {
            color: white;
            text-decoration: none;
        }
        
        .reservation-header .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .info-card h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.3rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.upcoming {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-badge.active {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-badge.completed {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .payment-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .payment-badge.paid {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .payment-badge.unpaid {
            background: #ffebee;
            color: #c62828;
        }
        
        .actions-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            height: fit-content;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            margin-bottom: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn.primary {
            background: #007bff;
            color: white;
        }
        
        .action-btn.primary:hover {
            background: #0056b3;
        }
        
        .action-btn.warning {
            background: #ffc107;
            color: #212529;
        }
        
        .action-btn.warning:hover {
            background: #e0a800;
        }
        
        .action-btn.danger {
            background: #dc3545;
            color: white;
        }
        
        .action-btn.danger:hover {
            background: #c82333;
        }
        
        .edit-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .reservation-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/admin-header.php'; ?>

    <!-- Reservation Header -->
    <section class="reservation-header">
        <div class="container">
            <h1><i class="fas fa-calendar-alt"></i> Réservation #<?php echo $reservation_id; ?></h1>
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a> > 
                <a href="reservations.php">Réservations</a> > 
                Détails #<?php echo $reservation_id; ?>
            </div>
        </div>
    </section>

    <section class="admin-content">
        <div class="container">
            
            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="details-grid">
                <!-- Main Details -->
                <div>
                    <!-- Reservation Information -->
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Informations de la Réservation</h3>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-hashtag"></i> ID Réservation
                            </span>
                            <span class="info-value">#<?php echo $reservation_data['id_reservation']; ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-calendar-check"></i> Date de début
                            </span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($reservation_data['date_debut'])); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-calendar-times"></i> Date de fin
                            </span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($reservation_data['date_fin'])); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-clock"></i> Durée
                            </span>
                            <span class="info-value"><?php echo $duration; ?> jour(s)</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-flag"></i> Statut
                            </span>
                            <span class="info-value">
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $reservation_status; ?>
                                </span>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-euro-sign"></i> Prix total
                            </span>
                            <span class="info-value"><?php echo number_format($total_price, 2, ',', ' '); ?> €</span>
                        </div>
                    </div>

                    <!-- Client Information -->
                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Informations Client</h3>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-id-card"></i> Nom complet
                            </span>
                            <span class="info-value">
                                <a href="client-details.php?id=<?php echo $reservation_data['id_client']; ?>">
                                    <?php echo htmlspecialchars($reservation_data['client_nom'] . ' ' . $reservation_data['client_prenom']); ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-envelope"></i> Email
                            </span>
                            <span class="info-value">
                                <a href="mailto:<?php echo $reservation_data['client_email']; ?>">
                                    <?php echo htmlspecialchars($reservation_data['client_email']); ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-phone"></i> Téléphone
                            </span>
                            <span class="info-value">
                                <a href="tel:<?php echo $reservation_data['client_telephone']; ?>">
                                    <?php echo htmlspecialchars($reservation_data['client_telephone']); ?>
                                </a>
                            </span>
                        </div>
                    </div>

                    <!-- Car Information -->
                    <div class="info-card">
                        <h3><i class="fas fa-car"></i> Informations Véhicule</h3>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-tag"></i> Véhicule
                            </span>
                            <span class="info-value">
                                <?php echo htmlspecialchars($reservation_data['marque'] . ' ' . $reservation_data['modele']); ?>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-id-badge"></i> Immatriculation
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($reservation_data['immatriculation']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-gas-pump"></i> Carburant
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($reservation_data['fuel_type']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-users"></i> Places
                            </span>
                            <span class="info-value"><?php echo $reservation_data['nb_places']; ?> places</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-cogs"></i> Transmission
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($reservation_data['gear']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-euro-sign"></i> Prix/jour
                            </span>
                            <span class="info-value"><?php echo number_format($daily_price, 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                </div>

                <!-- Actions Sidebar -->
                <div>
                    <!-- Quick Actions -->
                    <div class="actions-card">
                        <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
                        
                        <a href="edit-reservation.php?id=<?php echo $reservation_id; ?>" class="action-btn primary">
                            <i class="fas fa-edit"></i> Modifier la réservation
                        </a>
                        
                        <a href="mailto:<?php echo $reservation_data['client_email']; ?>" class="action-btn warning">
                            <i class="fas fa-envelope"></i> Contacter le client
                        </a>
                        
                        <a href="delete-reservation.php?id=<?php echo $reservation_id; ?>" 
                           class="action-btn danger"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                        
                        <a href="reservations.php" class="action-btn" style="background: #6c757d; color: white;">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>

                    <!-- Payment Status -->
                    <div class="actions-card">
                        <h3><i class="fas fa-credit-card"></i> Statut Paiement</h3>
                        
                        <div class="info-row">
                            <span class="info-label">Statut actuel</span>
                            <span class="info-value">
                                <?php
                                // Check if there's a location record and payment status
                                if (isset($reservation_data['ETAT_PAIEMENT'])) {
                                    $is_paid = $reservation_data['ETAT_PAIEMENT'];
                                    $status_class = $is_paid ? 'paid' : 'unpaid';
                                    $status_text = $is_paid ? 'Payé' : 'Non payé';
                                } else {
                                    // No location record yet
                                    $status_class = 'unpaid';
                                    $status_text = 'Pas encore de location';
                                }
                                ?>
                                <span class="payment-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </span>
                        </div>
                        
                        <?php if (isset($reservation_data['payment_amount']) && !empty($reservation_data['payment_amount'])): ?>
                        <div class="info-row">
                            <span class="info-label">Montant payé</span>
                            <span class="info-value"><?php echo number_format($reservation_data['payment_amount'], 2, ',', ' '); ?> €</span>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($reservation_data['date_paiement']) && !empty($reservation_data['date_paiement'])): ?>
                        <div class="info-row">
                            <span class="info-label">Date de paiement</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($reservation_data['date_paiement'])); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($reservation_data['mode_paiement']) && !empty($reservation_data['mode_paiement'])): ?>
                        <div class="info-row">
                            <span class="info-label">Mode de paiement</span>
                            <span class="info-value"><?php echo htmlspecialchars($reservation_data['mode_paiement']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Payment Update Form -->
                        <?php if (isset($reservation_data['id_location']) && !empty($reservation_data['id_location'])): ?>
                        <form method="post" class="edit-form">
                            <div class="form-group">
                                <label for="payment_status">Changer le statut:</label>
                                <select name="payment_status" id="payment_status">
                                    <option value="0" <?php echo !$reservation_data['ETAT_PAIEMENT'] ? 'selected' : ''; ?>>Non payé</option>
                                    <option value="1" <?php echo $reservation_data['ETAT_PAIEMENT'] ? 'selected' : ''; ?>>Payé</option>
                                </select>
                            </div>
                            <button type="submit" name="update_payment" class="action-btn primary">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="edit-form">
                            <p style="color: #666; font-style: italic;">
                                <i class="fas fa-info-circle"></i>
                                Aucune location créée pour cette réservation.
                            </p>
                            <small>Une location doit être créée avant de pouvoir gérer les paiements.</small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Edit Dates -->
                    <div class="actions-card">
                        <h3><i class="fas fa-calendar-edit"></i> Modifier les Dates</h3>
                        
                        <form method="post" class="edit-form">
                            <div class="form-group">
                                <label for="date_debut">Date de début:</label>
                                <input type="date" 
                                       name="date_debut" 
                                       id="date_debut" 
                                       value="<?php echo $reservation_data['date_debut']; ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_fin">Date de fin:</label>
                                <input type="date" 
                                       name="date_fin" 
                                       id="date_fin" 
                                       value="<?php echo $reservation_data['date_fin']; ?>" 
                                       required>
                            </div>
                            
                            <button type="submit" name="update_reservation" class="action-btn primary">
                                <i class="fas fa-save"></i> Sauvegarder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>

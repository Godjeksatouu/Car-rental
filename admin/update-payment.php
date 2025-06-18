<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Check if required parameters are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    redirectWithMessage('reservations.php', 'Paramètres manquants', 'error');
}

$reservationId = (int)$_GET['id'];
$action = $_GET['action'];

if ($action === 'mark_paid') {
    // Get location ID from reservation
    $query = "SELECT l.id_location FROM LOCATION l 
              JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
              WHERE r.id_reservation = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $reservationId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($location = mysqli_fetch_assoc($result)) {
        // Check if date_paiement column exists
        $column_check = mysqli_query($conn, "SHOW COLUMNS FROM LOCATION LIKE 'date_paiement'");

        if (mysqli_num_rows($column_check) > 0) {
            // Column exists, update both status and date
            $update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = 1, date_paiement = NOW() WHERE id_location = ?";
        } else {
            // Column doesn't exist, update only status
            $update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = 1 WHERE id_location = ?";
        }

        $stmt = mysqli_prepare($conn, $update_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $location['id_location']);

            if (mysqli_stmt_execute($stmt)) {
                redirectWithMessage('reservations.php', 'Paiement marqué comme effectué avec succès', 'success');
            } else {
                redirectWithMessage('reservations.php', 'Erreur lors de la mise à jour du paiement', 'error');
            }
        } else {
            redirectWithMessage('reservations.php', 'Erreur de préparation de la requête', 'error');
        }
    } else {
        redirectWithMessage('reservations.php', 'Réservation non trouvée', 'error');
    }
} else {
    redirectWithMessage('reservations.php', 'Action non reconnue', 'error');
}
?>

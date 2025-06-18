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
if (!isset($_GET['action']) || !isset($_GET['ids'])) {
    redirectWithMessage('reservations.php', 'Paramètres manquants', 'error');
}

$action = $_GET['action'];
$ids = explode(',', $_GET['ids']);
$ids = array_map('intval', $ids); // Convert to integers for security

if (empty($ids)) {
    redirectWithMessage('reservations.php', 'Aucune réservation sélectionnée', 'error');
}

$success_count = 0;
$error_count = 0;

if ($action === 'mark_paid') {
    // Mark multiple reservations as paid
    foreach ($ids as $reservationId) {
        // Get location ID from reservation
        $query = "SELECT l.id_location FROM LOCATION l 
                  JOIN RESERVATION r ON l.id_reservation = r.id_reservation 
                  WHERE r.id_reservation = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $reservationId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($location = mysqli_fetch_assoc($result)) {
            // Update payment status
            $update_query = "UPDATE LOCATION SET ETAT_PAIEMENT = 1, date_paiement = NOW() WHERE id_location = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $location['id_location']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "$success_count réservation(s) marquée(s) comme payée(s)";
        if ($error_count > 0) {
            $message .= " ($error_count erreur(s))";
        }
        redirectWithMessage('reservations.php', $message, 'success');
    } else {
        redirectWithMessage('reservations.php', 'Aucune réservation n\'a pu être mise à jour', 'error');
    }
    
} elseif ($action === 'delete') {
    // Delete multiple reservations
    foreach ($ids as $reservationId) {
        // Start transaction for each deletion
        mysqli_begin_transaction($conn);
        
        try {
            // Delete from LOCATION first (foreign key constraint)
            $delete_location = "DELETE FROM LOCATION WHERE id_reservation = ?";
            $stmt = mysqli_prepare($conn, $delete_location);
            mysqli_stmt_bind_param($stmt, "i", $reservationId);
            mysqli_stmt_execute($stmt);
            
            // Delete from RESERVATION
            $delete_reservation = "DELETE FROM RESERVATION WHERE id_reservation = ?";
            $stmt = mysqli_prepare($conn, $delete_reservation);
            mysqli_stmt_bind_param($stmt, "i", $reservationId);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            $success_count++;
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "$success_count réservation(s) supprimée(s)";
        if ($error_count > 0) {
            $message .= " ($error_count erreur(s))";
        }
        redirectWithMessage('reservations.php', $message, 'success');
    } else {
        redirectWithMessage('reservations.php', 'Aucune réservation n\'a pu être supprimée', 'error');
    }
    
} else {
    redirectWithMessage('reservations.php', 'Action non reconnue', 'error');
}
?>

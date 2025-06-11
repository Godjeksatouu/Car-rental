<?php
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Function to redirect with message
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit();
}

// Function to display messages
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $output = '<div class="alert alert-' . $type . '">';
        $output .= $_SESSION['message'];
        $output .= '</div>';
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return $output;
    }
    return '';
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to calculate rental price
function calculateRentalPrice($carId, $startDate, $endDate, $conn) {
    // Get car price per day
    $query = "SELECT prix_par_jour FROM VOITURE WHERE id_voiture = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $carId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $pricePerDay = $row['prix_par_jour'];
        
        // Calculate number of days
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days + 1; // Include both start and end day
        
        return $pricePerDay * $days;
    }
    
    return 0;
}

// Function to check if car is available for specific dates
function isCarAvailable($carId, $startDate, $endDate, $conn, $reservationId = null) {
    $query = "SELECT id_reservation 
              FROM RESERVATION 
              WHERE id_voiture = ? 
              AND ((date_debut BETWEEN ? AND ?) 
                  OR (date_fin BETWEEN ? AND ?) 
                  OR (date_debut <= ? AND date_fin >= ?))";
    
    if ($reservationId) {
        $query .= " AND id_reservation != ?";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($reservationId) {
        mysqli_stmt_bind_param($stmt, "issssssi", $carId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $reservationId);
    } else {
        mysqli_stmt_bind_param($stmt, "issssss", $carId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) === 0;
}

// Format date for display
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}
?>
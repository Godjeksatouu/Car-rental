<?php
// Check if user is logged in by checking if 'user_id' exists in the session
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin by checking if 'admin_id' exists in the session
function isUserAdmin() {
    return isset($_SESSION['admin_id']);
}

// Redirect user to a page and store a message in the session for display
function redirectUserWithMessage($page, $message, $type = 'success') {
    $_SESSION['message'] = $message;       // Save message text
    $_SESSION['message_type'] = $type;     // Save message type (success, error, etc.)
    header("Location: $page");              // Redirect to the specified page
    exit();                                // Stop further script execution
}

// Display any stored message from the session and then clear it
function displayStoredMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success'; // Default to 'success' if type missing
        $html = '<div class="alert alert-' . $type . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);          // Remove message from session
        unset($_SESSION['message_type']);     // Remove message type from session
        return $html;                         // Return HTML to display
    }
    return '';                              // Return empty if no message found
}

// Clean user input to prevent harmful data and formatting issues
function cleanUserInput($input) {
    $input = trim($input);                  // Remove whitespace from start and end
    $input = stripslashes($input);          // Remove backslashes
    $input = htmlspecialchars($input);      // Convert special chars to HTML-safe entities
    return $input;
}

// Calculate total rental price for a car between two dates
function calculateCarRentalPrice($car_id, $start_date, $end_date, $conn) {
    $query = "SELECT prix_par_jour FROM VOITURE WHERE id_voiture = ?";
    $stmt = mysqli_prepare($conn, $query);                     // Prepare query to avoid SQL injection
    mysqli_stmt_bind_param($stmt, "i", $car_id);               // Bind car ID as integer
    mysqli_stmt_execute($stmt);                                // Run the query
    $result = mysqli_stmt_get_result($stmt);
    
    if ($car = mysqli_fetch_assoc($result)) {
        $price_per_day = $car['prix_par_jour'];
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $start->diff($end)->days + 1;                  // Calculate number of days (+1 to include last day)
        return $price_per_day * $days;                         // Total price = daily price * number of days
    }
    return 0;                                                  // Return 0 if car not found
}

// Check if a car is available between two dates (optionally exclude a reservation by ID)
function checkCarAvailability($car_id, $start_date, $end_date, $conn, $exclude_reservation_id = null) {
    $query = "SELECT id_reservation FROM RESERVATION WHERE id_voiture = ? AND (
                (date_debut BETWEEN ? AND ?) OR
                (date_fin BETWEEN ? AND ?) OR
                (date_debut <= ? AND date_fin >= ?)
              )";
    if ($exclude_reservation_id) {
        $query .= " AND id_reservation != ?";
    }
    $stmt = mysqli_prepare($conn, $query);

    if ($exclude_reservation_id) {
        mysqli_stmt_bind_param($stmt, "issssssi",
            $car_id, $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date,
            $exclude_reservation_id
        );
    } else {
        mysqli_stmt_bind_param($stmt, "issssss",
            $car_id, $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date
        );
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($result);
    return ($count === 0); // True if no conflicting reservation found, else false
}

// Format date from database to 'day/month/year' for display
function formatDateForDisplay($date) {
    return date("d/m/Y", strtotime($date));
}

// Short helper functions to call the main ones with simpler names
function isLoggedIn() {
    return isUserLoggedIn();
}
function isAdmin() {
    return isUserAdmin();
}
function displayMessage() {
    return displayStoredMessage();
}
function redirectWithMessage($page, $msg, $type = 'success') {
    return redirectUserWithMessage($page, $msg, $type);
}
function sanitize($input) {
    return cleanUserInput($input);
}
function calculateRentalPrice($car_id, $start, $end, $conn) {
    return calculateCarRentalPrice($car_id, $start, $end, $conn);
}
function isCarAvailable($car_id, $start, $end, $conn, $exclude_id = null) {
    return checkCarAvailability($car_id, $start, $end, $conn, $exclude_id);
}
function formatDate($date) {
    return formatDateForDisplay($date);
}

// Ensure a LOCATION record exists for a reservation
function ensureLocationExists($reservation_id, $conn) {
    // Check if location already exists
    $check_query = "SELECT id_location FROM LOCATION WHERE id_reservation = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $reservation_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        // Location exists, return its ID
        $location = mysqli_fetch_assoc($result);
        return $location['id_location'];
    } else {
        // Create new location record
        $insert_query = "INSERT INTO LOCATION (id_reservation, ETAT_PAIEMENT) VALUES (?, 0)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "i", $reservation_id);

        if (mysqli_stmt_execute($insert_stmt)) {
            return mysqli_insert_id($conn);
        } else {
            return false;
        }
    }
}
?>

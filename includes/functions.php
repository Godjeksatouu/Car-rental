<?php
/**
 * HELPER FUNCTIONS FOR CAR RENTAL SYSTEM
 *
 * This file contains all the reusable functions that help our website work.
 * Think of functions like tools in a toolbox - each one does a specific job
 * and can be used over and over again throughout the website.
 *
 * BEGINNER EXPLANATION:
 * - Functions are like recipes - you write them once and use them many times
 * - They help avoid repeating the same code in multiple places
 * - Each function has a specific purpose and returns a result
 */

// =============================================================================
// USER AUTHENTICATION FUNCTIONS
// =============================================================================

/**
 * Check if a user is logged in
 *
 * WHAT IT DOES: Checks if someone is currently logged into the website
 * HOW IT WORKS: Looks for a 'user_id' in the session (like a temporary memory)
 * RETURNS: true if logged in, false if not logged in
 *
 * BEGINNER EXPLANATION:
 * - Sessions are like temporary sticky notes that remember who you are
 * - When you log in, we put your user ID on a sticky note
 * - This function checks if that sticky note exists
 */
function isUserLoggedIn() {
    // isset() checks if a variable exists and is not null
    // $_SESSION is PHP's way of remembering things about the current user
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an administrator
 *
 * WHAT IT DOES: Checks if the logged-in person has admin privileges
 * HOW IT WORKS: Looks for an 'admin_id' in the session
 * RETURNS: true if user is admin, false if regular user or not logged in
 */
function isUserAdmin() {
    // Check if there's an admin_id stored in the session
    return isset($_SESSION['admin_id']);
}

// =============================================================================
// MESSAGE AND REDIRECT FUNCTIONS
// =============================================================================

/**
 * Redirect user to another page with a message
 *
 * WHAT IT DOES: Sends the user to a different page and shows them a message
 * HOW IT WORKS: Stores the message in session, then redirects the browser
 *
 * PARAMETERS:
 * - $page_location: Where to send the user (like "cars.php")
 * - $message_text: What message to show them
 * - $message_type: Type of message ("success", "error", "warning")
 *
 * BEGINNER EXPLANATION:
 * - Like telling someone "Go to the kitchen and I'll leave you a note there"
 * - We put the message in session (temporary storage) and send them to the new page
 * - The new page will find and display the message
 */
function redirectUserWithMessage($page_location, $message_text, $message_type = 'success') {
    // Store the message in session so it survives the page change
    $_SESSION['message'] = $message_text;
    $_SESSION['message_type'] = $message_type;

    // Redirect the browser to the new page
    // header() sends instructions to the browser
    header("Location: $page_location");

    // exit() stops the current page from continuing to load
    exit();
}

/**
 * Display any stored messages to the user
 *
 * WHAT IT DOES: Shows messages that were stored during redirects
 * HOW IT WORKS: Checks session for messages, displays them, then clears them
 * RETURNS: HTML code for the message, or empty string if no message
 *
 * BEGINNER EXPLANATION:
 * - Like checking for notes that were left for you
 * - If there's a note (message), show it and then throw it away
 * - If no note, do nothing
 */
function displayStoredMessage() {
    // Check if there's a message waiting to be displayed
    if (isset($_SESSION['message'])) {
        // Get the message type (default to 'success' if not set)
        $message_type = $_SESSION['message_type'] ?? 'success';

        // Build the HTML for the message
        $message_html = '<div class="alert alert-' . $message_type . '">';
        $message_html .= $_SESSION['message'];
        $message_html .= '</div>';

        // Clear the message from session so it doesn't show again
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);

        // Return the HTML to be displayed
        return $message_html;
    }

    // No message found, return empty string
    return '';
}

// =============================================================================
// DATA SECURITY AND VALIDATION FUNCTIONS
// =============================================================================

/**
 * Clean and secure user input data
 *
 * WHAT IT DOES: Makes user input safe to use in our website
 * HOW IT WORKS: Removes dangerous characters and extra spaces
 *
 * PARAMETERS:
 * - $user_input: The data that came from a form or URL
 *
 * RETURNS: Clean, safe version of the input
 *
 * BEGINNER EXPLANATION:
 * - Like washing vegetables before cooking - removes dirt and harmful stuff
 * - Users might accidentally or intentionally send dangerous code
 * - This function cleans it up to keep our website safe
 */
function cleanUserInput($user_input) {
    // Remove extra spaces from beginning and end
    $cleaned_data = trim($user_input);

    // Remove backslashes that might be added automatically
    $cleaned_data = stripslashes($cleaned_data);

    // Convert special characters to safe HTML codes
    // This prevents malicious code from running on our website
    $cleaned_data = htmlspecialchars($cleaned_data);

    return $cleaned_data;
}

// =============================================================================
// PRICE CALCULATION FUNCTIONS
// =============================================================================

/**
 * Calculate the total price for renting a car
 *
 * WHAT IT DOES: Figures out how much a car rental will cost
 * HOW IT WORKS: Gets the daily price, counts the days, multiplies them
 *
 * PARAMETERS:
 * - $car_id: Which car we're calculating for
 * - $rental_start_date: When the rental begins (YYYY-MM-DD format)
 * - $rental_end_date: When the rental ends (YYYY-MM-DD format)
 * - $database_connection: Connection to our database
 *
 * RETURNS: Total price in euros, or 0 if car not found
 *
 * BEGINNER EXPLANATION:
 * - Like calculating hotel costs: daily rate × number of nights
 * - We look up the car's daily price in the database
 * - Count how many days between start and end dates
 * - Multiply daily price by number of days
 */
function calculateCarRentalPrice($car_id, $rental_start_date, $rental_end_date, $database_connection) {
    // Step 1: Get the daily price for this specific car from database
    $price_query = "SELECT prix_par_jour FROM VOITURE WHERE id_voiture = ?";

    // Prepare the query to prevent SQL injection attacks
    $prepared_statement = mysqli_prepare($database_connection, $price_query);

    // Bind the car ID to the query (i = integer)
    mysqli_stmt_bind_param($prepared_statement, "i", $car_id);

    // Execute the query
    mysqli_stmt_execute($prepared_statement);

    // Get the result
    $query_result = mysqli_stmt_get_result($prepared_statement);

    // Step 2: If we found the car, calculate the total price
    if ($car_data = mysqli_fetch_assoc($query_result)) {
        // Get the daily price from the database result
        $daily_price = $car_data['prix_par_jour'];

        // Step 3: Calculate the number of rental days
        // Create date objects to work with the dates
        $start_date_object = new DateTime($rental_start_date);
        $end_date_object = new DateTime($rental_end_date);

        // Calculate the difference between dates
        $date_difference = $start_date_object->diff($end_date_object);

        // Get number of days (add 1 to include both start and end day)
        $number_of_days = $date_difference->days + 1;

        // Step 4: Calculate total price
        $total_price = $daily_price * $number_of_days;

        return $total_price;
    }

    // If car not found, return 0
    return 0;
}

// =============================================================================
// CAR AVAILABILITY CHECKING FUNCTIONS
// =============================================================================

/**
 * Check if a car is available for specific rental dates
 *
 * WHAT IT DOES: Checks if a car is free to rent during certain dates
 * HOW IT WORKS: Looks in the database for any existing reservations that would conflict
 *
 * PARAMETERS:
 * - $car_id: Which car we're checking
 * - $desired_start_date: When customer wants to start renting (YYYY-MM-DD)
 * - $desired_end_date: When customer wants to end renting (YYYY-MM-DD)
 * - $database_connection: Connection to our database
 * - $exclude_reservation_id: (Optional) Ignore this reservation ID (used when editing)
 *
 * RETURNS: true if car is available, false if already booked
 *
 * BEGINNER EXPLANATION:
 * - Like checking if a hotel room is free for your vacation dates
 * - We look at all existing reservations for this car
 * - If any reservation overlaps with the desired dates, car is not available
 * - If no conflicts found, car is available
 */
function checkCarAvailability($car_id, $desired_start_date, $desired_end_date, $database_connection, $exclude_reservation_id = null) {

    // Step 1: Build a query to find conflicting reservations
    // We need to check for three types of overlaps:
    // 1. Existing reservation starts during our desired period
    // 2. Existing reservation ends during our desired period
    // 3. Existing reservation completely covers our desired period

    $availability_query = "SELECT id_reservation
                          FROM RESERVATION
                          WHERE id_voiture = ?
                          AND ((date_debut BETWEEN ? AND ?)
                              OR (date_fin BETWEEN ? AND ?)
                              OR (date_debut <= ? AND date_fin >= ?))";

    // Step 2: If we're editing an existing reservation, exclude it from the check
    if ($exclude_reservation_id) {
        $availability_query .= " AND id_reservation != ?";
    }

    // Step 3: Prepare and execute the query
    $prepared_statement = mysqli_prepare($database_connection, $availability_query);

    // Bind parameters based on whether we're excluding a reservation
    if ($exclude_reservation_id) {
        // Include the reservation ID to exclude (8 parameters total)
        mysqli_stmt_bind_param($prepared_statement, "issssssi",
            $car_id,
            $desired_start_date, $desired_end_date,    // For overlap check 1
            $desired_start_date, $desired_end_date,    // For overlap check 2
            $desired_start_date, $desired_end_date,    // For overlap check 3
            $exclude_reservation_id                     // Reservation to exclude
        );
    } else {
        // No reservation to exclude (7 parameters total)
        mysqli_stmt_bind_param($prepared_statement, "issssss",
            $car_id,
            $desired_start_date, $desired_end_date,    // For overlap check 1
            $desired_start_date, $desired_end_date,    // For overlap check 2
            $desired_start_date, $desired_end_date     // For overlap check 3
        );
    }

    // Execute the query
    mysqli_stmt_execute($prepared_statement);
    $query_result = mysqli_stmt_get_result($prepared_statement);

    // Step 4: Check the results
    // If we found 0 conflicting reservations, the car is available
    // If we found any conflicting reservations, the car is not available
    $number_of_conflicts = mysqli_num_rows($query_result);

    return ($number_of_conflicts === 0);
}

// =============================================================================
// DATE FORMATTING FUNCTIONS
// =============================================================================

/**
 * Format a date for display to users
 *
 * WHAT IT DOES: Converts database date format to user-friendly format
 * HOW IT WORKS: Changes YYYY-MM-DD to DD/MM/YYYY
 *
 * PARAMETERS:
 * - $database_date: Date in database format (YYYY-MM-DD)
 *
 * RETURNS: Date in French format (DD/MM/YYYY)
 *
 * BEGINNER EXPLANATION:
 * - Database stores dates like "2024-12-25" (computer-friendly)
 * - Users prefer dates like "25/12/2024" (human-friendly)
 * - This function converts between the two formats
 */
function formatDateForDisplay($database_date) {
    // Use PHP's date() function to reformat the date
    // "d/m/Y" means day/month/year with leading zeros
    // strtotime() converts the database date to a timestamp first
    return date("d/m/Y", strtotime($database_date));
}

// =============================================================================
// BACKWARD COMPATIBILITY FUNCTIONS
// =============================================================================

/**
 * Backward compatibility aliases for existing code
 *
 * WHAT THESE DO: Provide the old function names that existing code expects
 * WHY WE NEED THEM: So we don't break existing pages like header.php
 *
 * BEGINNER EXPLANATION:
 * - Sometimes when we improve code, we change function names
 * - But other parts of the website still use the old names
 * - These "alias" functions let both old and new names work
 * - It's like having a nickname - both your real name and nickname work
 */

/**
 * Alias for isUserLoggedIn() - for backward compatibility
 */
function isLoggedIn() {
    return isUserLoggedIn();
}

/**
 * Alias for isUserAdmin() - for backward compatibility
 */
function isAdmin() {
    return isUserAdmin();
}

/**
 * Alias for displayStoredMessage() - for backward compatibility
 */
function displayMessage() {
    return displayStoredMessage();
}

/**
 * Alias for redirectUserWithMessage() - for backward compatibility
 */
function redirectWithMessage($page_location, $message_text, $message_type = 'success') {
    return redirectUserWithMessage($page_location, $message_text, $message_type);
}

/**
 * Alias for cleanUserInput() - for backward compatibility
 */
function sanitize($user_input) {
    return cleanUserInput($user_input);
}

/**
 * Alias for calculateCarRentalPrice() - for backward compatibility
 */
function calculateRentalPrice($car_id, $rental_start_date, $rental_end_date, $database_connection) {
    return calculateCarRentalPrice($car_id, $rental_start_date, $rental_end_date, $database_connection);
}

/**
 * Alias for checkCarAvailability() - for backward compatibility
 */
function isCarAvailable($car_id, $desired_start_date, $desired_end_date, $database_connection, $exclude_reservation_id = null) {
    return checkCarAvailability($car_id, $desired_start_date, $desired_end_date, $database_connection, $exclude_reservation_id);
}

/**
 * Alias for formatDateForDisplay() - for backward compatibility
 */
function formatDate($database_date) {
    return formatDateForDisplay($database_date);
}

?>
?>
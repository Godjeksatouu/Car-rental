# 🔄 User Flow - Step-by-Step Journey Through the System

## 📖 Overview

This document traces the complete user journey through the AutoDrive system, showing exactly what happens at each step from both the user's perspective and the technical implementation.

## 👤 Customer Journey - Complete Booking Process

### **Step 1: Homepage Visit**
```
User Action: Types website URL or clicks link
↓
Technical Process:
1. Browser requests index.php
2. Server includes config.php (database connection)
3. Server includes functions.php (helper functions)
4. Server includes header.php (navigation)
5. Page displays featured cars and welcome message
6. Server includes footer.php (site footer)
```

**What the user sees:**
- Welcome message and hero section
- Featured cars with photos and prices
- Navigation menu with "Browse Cars" and "Login" options
- Call-to-action buttons

**Technical details:**
```php
// index.php key operations:
include 'includes/config.php';      // Database connection
include 'includes/functions.php';   // Helper functions
$featured_cars = getFeaturedCars(); // Get cars to display
include 'includes/header.php';      // Site navigation
// Display content
include 'includes/footer.php';      // Site footer
```

### **Step 2: Browse Available Cars**
```
User Action: Clicks "Browse Cars" or "View All Cars"
↓
Technical Process:
1. Browser navigates to cars.php
2. System checks for filter parameters ($_GET variables)
3. Database query built based on filters
4. Cars retrieved from VOITURE table
5. Availability status checked for each car
6. Results displayed in grid format
```

**What the user sees:**
- Grid of available cars with photos
- Filter options (brand, fuel type, transmission, price, seats)
- Car details (make, model, price per day, specifications)
- "Reserve" button for each available car

**Technical implementation:**
```php
// cars.php key operations:
$selected_brand = isset($_GET['marque']) ? cleanUserInput($_GET['marque']) : '';
$selected_fuel = isset($_GET['type']) ? cleanUserInput($_GET['type']) : '';

// Build dynamic query based on filters
$cars_query = "SELECT * FROM VOITURE WHERE statut = 'disponible'";
if (!empty($selected_brand)) {
    $cars_query .= " AND marque = ?";
}
// Execute query and display results
```

### **Step 3: Select Car and Dates**
```
User Action: Clicks "Reserve" button on desired car
↓
Technical Process:
1. System checks if user is logged in (isUserLoggedIn())
2. If not logged in: redirect to login.php with return URL
3. If logged in: navigate to reservation.php with car ID
4. Load car details from database
5. Initialize date picker with blocked dates
6. Display booking form
```

**Authentication check:**
```php
// reservation.php security check:
if (!isUserLoggedIn()) {
    redirectUserWithMessage('login.php?redirect=reservation.php&id=' . $car_id, 
                           'Please log in to make a reservation', 'error');
}
```

**What the user sees:**
- Car details and specifications
- Date picker calendar
- Blocked dates (when car is unavailable)
- Price calculation that updates as dates change
- Booking confirmation form

### **Step 4: Login/Registration (if needed)**
```
User Action: Enters email and password OR clicks "Register"
↓
Technical Process for Login:
1. Form submitted to login.php
2. Input cleaned with cleanUserInput()
3. Database query to find user by email
4. Password verified with password_verify()
5. Session created with user ID
6. Redirect to original destination (reservation page)
```

**Login process:**
```php
// login.php authentication:
$user_email = cleanUserInput($_POST["email"]);
$user_password = $_POST["password"];

$user_query = "SELECT * FROM CLIENT WHERE email = ?";
$user_statement = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_statement, "s", $user_email);
mysqli_stmt_execute($user_statement);
$result = mysqli_stmt_get_result($user_statement);

if ($user_data = mysqli_fetch_assoc($result)) {
    if (password_verify($user_password, $user_data['mot_de_passe'])) {
        $_SESSION['user_id'] = $user_data['id_client'];
        // Redirect to reservation page
    }
}
```

**Registration process:**
```php
// register.php account creation:
$hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
$insert_query = "INSERT INTO CLIENT (nom, prénom, email, téléphone, mot_de_passe) 
                VALUES (?, ?, ?, ?, ?)";
// Execute insert and create session
```

### **Step 5: Complete Reservation**
```
User Action: Selects dates and clicks "Confirm Booking"
↓
Technical Process:
1. Validate selected dates (end after start, not in past)
2. Check car availability for selected dates
3. Calculate total price
4. Insert reservation into RESERVATION table
5. Create location record in LOCATION table
6. Display confirmation message
```

**Availability checking:**
```php
// reservation.php booking process:
$is_available = checkCarAvailability($car_id, $start_date, $end_date, $conn);
if (!$is_available) {
    $error_message = "Car is not available for selected dates";
    return;
}

$total_price = calculateCarRentalPrice($car_id, $start_date, $end_date, $conn);

// Create reservation
$reservation_query = "INSERT INTO RESERVATION (id_client, id_voiture, date_debut, date_fin) 
                     VALUES (?, ?, ?, ?)";
```

**What the user sees:**
- Booking confirmation with reservation number
- Total price breakdown
- Rental details summary
- Next steps information

## 🛠️ Admin Journey - Managing the System

### **Step 1: Admin Login**
```
Admin Action: Navigates to admin/login.php
↓
Technical Process:
1. Admin enters credentials
2. System checks ADMIN table for username
3. Password verified (should be hashed)
4. Admin session created
5. Redirect to admin dashboard
```

**Admin authentication:**
```php
// admin/login.php process:
$admin_query = "SELECT * FROM ADMIN WHERE nom_utilisateur = ?";
// Verify credentials and create admin session
$_SESSION['admin_id'] = $admin_data['id_admin'];
```

### **Step 2: Dashboard Overview**
```
Admin Action: Views admin/dashboard.php
↓
Technical Process:
1. Check admin authentication (isUserAdmin())
2. Query database for statistics:
   - Total cars, reservations, customers
   - Recent activity
   - Revenue data
3. Display dashboard with charts and quick actions
```

**Dashboard data collection:**
```php
// admin/dashboard.php statistics:
$total_cars = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM VOITURE"));
$total_reservations = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM RESERVATION"));
$total_clients = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM CLIENT"));

// Recent reservations
$recent_query = "SELECT r.*, c.nom, c.prénom, v.marque, v.modele 
                FROM RESERVATION r 
                JOIN CLIENT c ON r.id_client = c.id_client 
                JOIN VOITURE v ON r.id_voiture = v.id_voiture 
                ORDER BY r.id_reservation DESC LIMIT 5";
```

### **Step 3: Manage Reservations**
```
Admin Action: Clicks "View" button on reservation
↓
Technical Process:
1. Navigate to reservation-details.php with reservation ID
2. Query database for complete reservation information:
   - Customer details
   - Car information
   - Payment status
   - Dates and pricing
3. Display comprehensive reservation view
4. Provide editing and management options
```

**Reservation details query:**
```php
// admin/reservation-details.php data retrieval:
$reservation_query = "SELECT 
    r.*,
    c.nom as client_nom, c.prénom as client_prenom, c.email, c.téléphone,
    v.marque, v.modele, v.immatriculation, v.prix_par_jour,
    l.ETAT_PAIEMENT,
    p.montant, p.date_paiement
FROM RESERVATION r
LEFT JOIN CLIENT c ON r.id_client = c.id_client
LEFT JOIN VOITURE v ON r.id_voiture = v.id_voiture
LEFT JOIN LOCATION l ON r.id_reservation = l.id_reservation
LEFT JOIN PAIEMENT p ON l.id_location = p.id_location
WHERE r.id_reservation = ?";
```

## 🔄 Data Flow Through the System

### **Complete Booking Data Flow:**
```
1. Customer Registration
   ↓
   CLIENT table ← New customer record

2. Car Selection
   ↓
   VOITURE table ← Query available cars

3. Date Selection
   ↓
   RESERVATION table ← Check existing bookings for conflicts

4. Booking Confirmation
   ↓
   RESERVATION table ← Insert new reservation
   ↓
   LOCATION table ← Create location record
   ↓
   PAIEMENT table ← (Future: payment processing)
```

### **Admin Management Data Flow:**
```
1. Admin Login
   ↓
   ADMIN table ← Verify credentials

2. View Dashboard
   ↓
   Multiple tables ← Aggregate statistics

3. Manage Reservation
   ↓
   RESERVATION + CLIENT + VOITURE ← Join queries for complete info

4. Update Status
   ↓
   LOCATION table ← Update payment status
```

## 🔒 Security Checkpoints Throughout User Journey

### **Customer Security Measures:**
1. **Input Sanitization:** All form inputs cleaned with `cleanUserInput()`
2. **Authentication:** Login required for reservations
3. **Session Management:** Secure session handling
4. **SQL Injection Prevention:** Prepared statements for all queries
5. **XSS Protection:** Output escaped with `htmlspecialchars()`

### **Admin Security Measures:**
1. **Admin Authentication:** Separate admin login system
2. **Access Control:** Admin functions only accessible to authenticated admins
3. **Audit Trail:** All admin actions can be tracked
4. **Data Validation:** All admin inputs validated and sanitized

## 📱 Responsive User Experience

### **Mobile User Journey:**
```
Mobile User → Responsive Design → Touch-Friendly Interface
    ↓
- Collapsible navigation menu
- Touch-optimized date picker
- Mobile-friendly car grid
- Simplified booking form
```

### **Desktop User Journey:**
```
Desktop User → Full Interface → Advanced Features
    ↓
- Full navigation menu
- Advanced filtering options
- Detailed car information
- Comprehensive admin dashboard
```

## 🎯 Error Handling Throughout Journey

### **User Error Scenarios:**
1. **Invalid Login:** Clear error message, form remains filled
2. **Car Unavailable:** Availability check prevents booking, suggests alternatives
3. **Invalid Dates:** Date validation with helpful error messages
4. **Session Timeout:** Graceful redirect to login with return URL

### **Technical Error Scenarios:**
1. **Database Connection Failure:** Graceful error page
2. **Query Errors:** Logged for admin review, user sees friendly message
3. **File Not Found:** Custom 404 page with navigation options

## 📊 Performance Optimization in User Flow

### **Database Optimization:**
- **Indexed Queries:** Fast lookups on frequently searched columns
- **Efficient Joins:** Minimal database calls per page
- **Prepared Statements:** Faster query execution

### **User Experience Optimization:**
- **Progressive Loading:** Critical content loads first
- **Caching:** Session-based caching for user data
- **Minimal Redirects:** Direct navigation where possible

---

## 🎓 Key User Flow Points for Presentation

### **Highlight These Journey Features:**

1. **Seamless Experience:** Smooth flow from browsing to booking
2. **Security Integration:** Security measures don't interrupt user experience
3. **Error Prevention:** System prevents common user errors
4. **Mobile Optimization:** Works perfectly on all devices
5. **Admin Efficiency:** Streamlined admin workflows

### **Technical Sophistication:**
- ✅ **State Management** - Proper session handling throughout journey
- ✅ **Data Integrity** - Availability checking prevents conflicts
- ✅ **User Experience** - Intuitive flow with clear feedback
- ✅ **Security Integration** - Security measures seamlessly integrated
- ✅ **Error Handling** - Graceful handling of all error scenarios

**Next:** [Key Features Demo](13-key-features-demo.md) - What to demonstrate in your presentation

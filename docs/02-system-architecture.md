# 🏗️ System Architecture - How Everything Works Together

## 📐 Overall Architecture

AutoDrive follows a **3-tier web application architecture**:

```
┌─────────────────────────────────────┐
│         PRESENTATION TIER           │
│     (HTML, CSS, JavaScript)         │
│   - User Interface                  │
│   - Forms and Interactions          │
│   - Responsive Design               │
└─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────┐
│         APPLICATION TIER            │
│            (PHP Logic)              │
│   - Business Logic                  │
│   - Authentication                  │
│   - Data Processing                 │
└─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────┐
│           DATA TIER                 │
│         (MySQL Database)            │
│   - Data Storage                    │
│   - Relationships                   │
│   - Data Integrity                  │
└─────────────────────────────────────┘
```

## 🔄 Request-Response Flow

### **1. User Makes a Request**
```
User clicks "View Cars" → Browser sends HTTP request → Web Server
```

### **2. Server Processes Request**
```
Web Server → PHP Engine → Execute PHP code → Query Database
```

### **3. Database Returns Data**
```
MySQL Database → Returns results → PHP processes data
```

### **4. Response Sent to User**
```
PHP generates HTML → Web Server → Browser displays page
```

## 📁 File Organization Architecture

### **Root Level Structure:**
```
autodrive/
├── index.php              # Homepage (entry point)
├── cars.php              # Car listing page
├── reservation.php       # Booking page
├── login.php             # User authentication
├── register.php          # User registration
├── includes/             # Shared components
├── admin/               # Admin dashboard
├── assets/              # Static resources
└── docs/                # Documentation
```

### **Includes Folder (Shared Components):**
```
includes/
├── config.php           # Database connection
├── functions.php        # Reusable functions
├── header.php          # Site header
└── footer.php          # Site footer
```

### **Admin Folder (Administrative Interface):**
```
admin/
├── dashboard.php        # Admin homepage
├── cars.php            # Vehicle management
├── reservations.php    # Booking management
├── clients.php         # Customer management
└── includes/           # Admin-specific components
```

### **Assets Folder (Static Resources):**
```
assets/
├── css/                # Stylesheets
├── js/                 # JavaScript files
└── images/             # Image resources
```

## 🔗 Component Relationships

### **Core Components and Their Connections:**

#### **1. Database Connection (config.php)**
```php
// Central database connection used by all components
$conn = mysqli_connect($server, $username, $password, $database);
```
- **Used by:** All PHP files that need database access
- **Purpose:** Single point of database configuration
- **Security:** Contains sensitive credentials

#### **2. Helper Functions (functions.php)**
```php
// Reusable functions used throughout the system
function cleanUserInput($input) { /* sanitize data */ }
function isUserLoggedIn() { /* check authentication */ }
function calculatePrice($car_id, $days) { /* pricing logic */ }
```
- **Used by:** All pages that need common functionality
- **Purpose:** Avoid code duplication, maintain consistency
- **Benefits:** Easier maintenance and updates

#### **3. Authentication System**
```
login.php ←→ functions.php ←→ session management
    ↓
All protected pages check authentication
```

#### **4. Data Flow Between Components**
```
User Input → Validation → Database Query → Results → Display
```

## 🎯 User Journey Architecture

### **Customer Journey:**
```
1. Homepage (index.php)
   ↓
2. Browse Cars (cars.php)
   ↓
3. Select Car → Login/Register (login.php/register.php)
   ↓
4. Make Reservation (reservation.php)
   ↓
5. Confirmation & Management
```

### **Admin Journey:**
```
1. Admin Login (admin/login.php)
   ↓
2. Dashboard (admin/dashboard.php)
   ↓
3. Manage Resources:
   - Cars (admin/cars.php)
   - Reservations (admin/reservations.php)
   - Clients (admin/clients.php)
```

## 🔐 Security Architecture

### **Authentication Flow:**
```
User Login → Password Verification → Session Creation → Access Control
```

### **Data Protection Layers:**
1. **Input Validation** - Clean all user input
2. **Prepared Statements** - Prevent SQL injection
3. **Output Escaping** - Prevent XSS attacks
4. **Session Security** - Secure session management

### **Security Implementation:**
```php
// Example of security layers
$clean_input = cleanUserInput($_POST['data']);           // Layer 1: Input cleaning
$stmt = mysqli_prepare($conn, "SELECT * FROM table WHERE id = ?"); // Layer 2: Prepared statement
echo htmlspecialchars($output);                         // Layer 3: Output escaping
```

## 📊 Database Architecture Integration

### **Database Connection Pattern:**
```php
// Every page that needs database access follows this pattern:
include 'includes/config.php';    // Get database connection
include 'includes/functions.php'; // Get helper functions
// Use $conn for database operations
```

### **Data Relationship Flow:**
```
CLIENT → RESERVATION → LOCATION → PAIEMENT
   ↓         ↓           ↓          ↓
Users    Bookings   Rentals   Payments
```

## 🎨 Frontend Architecture

### **CSS Architecture:**
```
assets/css/
├── style.css           # Main stylesheet
├── admin.css          # Admin-specific styles
└── responsive.css     # Mobile responsiveness
```

### **JavaScript Architecture:**
```
assets/js/
├── main.js            # Core functionality
├── admin.js           # Admin features
└── validation.js      # Form validation
```

### **Responsive Design Strategy:**
```css
/* Mobile-first approach */
.container { /* Mobile styles */ }

@media (min-width: 768px) {
    .container { /* Tablet styles */ }
}

@media (min-width: 1024px) {
    .container { /* Desktop styles */ }
}
```

## 🔄 Data Flow Architecture

### **Create Reservation Flow:**
```
1. User selects car and dates (cars.php)
   ↓
2. System checks availability (functions.php)
   ↓
3. User confirms booking (reservation.php)
   ↓
4. Data saved to database (RESERVATION table)
   ↓
5. Confirmation displayed to user
```

### **Admin Management Flow:**
```
1. Admin logs in (admin/login.php)
   ↓
2. Views dashboard (admin/dashboard.php)
   ↓
3. Manages resources (admin/cars.php, etc.)
   ↓
4. Updates database
   ↓
5. Changes reflected in customer interface
```

## 🛠️ Error Handling Architecture

### **Error Handling Layers:**
```
1. Client-side Validation (JavaScript)
   ↓
2. Server-side Validation (PHP)
   ↓
3. Database Error Handling (MySQL)
   ↓
4. User-friendly Error Messages
```

### **Error Flow Example:**
```php
// Multi-layer error handling
if (empty($user_input)) {
    $errors[] = "Field is required";           // Validation error
}

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Database error: " . mysqli_error($conn)); // Database error
}

// Display user-friendly messages
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<div class='alert alert-error'>$error</div>";
    }
}
```

## 📱 Responsive Architecture

### **Breakpoint Strategy:**
```
Mobile:    320px - 767px   (Single column)
Tablet:    768px - 1023px  (Two columns)
Desktop:   1024px+         (Multi-column)
```

### **Component Adaptation:**
```css
/* Navigation adapts to screen size */
.nav-desktop { display: none; }
.nav-mobile { display: block; }

@media (min-width: 768px) {
    .nav-desktop { display: block; }
    .nav-mobile { display: none; }
}
```

## 🔧 Configuration Architecture

### **Environment Configuration:**
```php
// config.php - Central configuration
$config = [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'car_rental'
    ],
    'app' => [
        'name' => 'AutoDrive',
        'version' => '1.2.0'
    ]
];
```

### **Feature Toggles:**
```php
// Easy feature management
$features = [
    'payment_gateway' => false,
    'email_notifications' => false,
    'advanced_search' => true
];
```

## 🎯 Performance Architecture

### **Optimization Strategies:**
1. **Database Optimization** - Efficient queries with proper indexes
2. **Code Optimization** - Minimal database calls per page
3. **Asset Optimization** - Compressed CSS/JS files
4. **Caching Strategy** - Session-based caching where appropriate

### **Loading Strategy:**
```
Critical CSS → HTML Content → JavaScript → Images
```

---

## 🎓 Key Architecture Points for Presentation

### **Highlight These Architectural Decisions:**

1. **Separation of Concerns** - Clear separation between presentation, logic, and data
2. **Reusable Components** - Functions and includes prevent code duplication
3. **Security by Design** - Multiple security layers throughout
4. **Scalable Structure** - Easy to add new features and pages
5. **Maintainable Code** - Clear organization and documentation

### **Technical Sophistication:**
- ✅ **MVC-like Pattern** - Separation of concerns
- ✅ **Database Abstraction** - Centralized database handling
- ✅ **Security Layers** - Multiple protection mechanisms
- ✅ **Responsive Design** - Mobile-first architecture
- ✅ **Error Handling** - Comprehensive error management

**Next:** [Database Design](03-database-design.md) - Deep dive into data structure

# 📁 File Structure - Every File Explained

## 🏗️ Complete Project Structure

```
autodrive/                          # Root directory of the project
├── 📄 index.php                   # Homepage - First page users see
├── 📄 cars.php                    # Car listing page - Browse available cars
├── 📄 cars-clean.php              # Clean version with detailed comments
├── 📄 reservation.php             # Booking page - Make reservations
├── 📄 reservation-clean.php       # Clean version with detailed comments
├── 📄 login.php                   # User login page
├── 📄 login-clean.php             # Clean version with detailed comments
├── 📄 register.php                # User registration page
├── 📄 logout.php                  # User logout functionality
├── 📄 profile.php                 # User profile and reservation history
├── 📄 README.md                   # Project documentation for GitHub
├── 📄 LICENSE                     # Open source license
├── 📄 CONTRIBUTING.md             # Guidelines for contributors
├── 📄 CHANGELOG.md                # Version history
├── 📄 .gitignore                  # Git ignore rules
│
├── 📁 includes/                   # Shared components used by multiple pages
│   ├── 📄 config.php             # Database connection configuration
│   ├── 📄 functions.php          # Reusable functions for the entire system
│   ├── 📄 header.php             # Website header (navigation, logo)
│   └── 📄 footer.php             # Website footer (copyright, links)
│
├── 📁 admin/                      # Administrative interface
│   ├── 📄 dashboard.php          # Admin homepage with statistics
│   ├── 📄 cars.php               # Vehicle management (add, edit, delete cars)
│   ├── 📄 add-cars.php           # Add new vehicles to inventory
│   ├── 📄 edit-car.php           # Edit existing vehicle details
│   ├── 📄 reservations.php       # Reservation management
│   ├── 📄 reservation-details.php # Detailed reservation view and editing
│   ├── 📄 edit-reservation.php   # Edit reservation details
│   ├── 📄 delete-reservation.php # Delete reservations
│   ├── 📄 clients.php            # Customer management
│   ├── 📄 client-details.php     # Detailed customer view
│   ├── 📄 client-details-clean.php # Clean version with comments
│   ├── 📄 login.php              # Admin login page
│   ├── 📄 logout.php             # Admin logout
│   ├── 📄 bulk-actions.php       # Bulk operations on data
│   ├── 📄 update-payment.php     # Payment status updates
│   └── 📁 includes/              # Admin-specific components
│       ├── 📄 admin-header.php   # Admin navigation header
│       └── 📄 admin-footer.php   # Admin footer
│
├── 📁 assets/                     # Static resources (CSS, JS, images)
│   ├── 📁 css/                   # Stylesheets
│   │   ├── 📄 style.css          # Main website styles
│   │   ├── 📄 admin.css          # Admin panel styles
│   │   └── 📄 responsive.css     # Mobile responsiveness
│   ├── 📁 js/                    # JavaScript files
│   │   ├── 📄 main.js            # Main website functionality
│   │   ├── 📄 admin.js           # Admin panel functionality
│   │   └── 📄 validation.js      # Form validation
│   └── 📁 images/                # Image resources
│       ├── 📄 logo.png           # Website logo
│       ├── 📄 hero-bg.jpg        # Homepage background
│       └── 📁 cars/              # Car photos
│
├── 📁 docs/                       # Project documentation
│   ├── 📄 README.md              # Documentation index
│   ├── 📄 01-project-overview.md # Project explanation
│   ├── 📄 02-system-architecture.md # How everything connects
│   ├── 📄 03-database-design.md  # Database structure
│   ├── 📄 04-file-structure.md   # This file
│   └── 📄 [more documentation files...]
│
└── 📁 database/                   # Database related files
    ├── 📄 car_rental.sql         # Database schema and sample data
    └── 📄 backup/                # Database backups (if any)
```

## 📄 Core Files Detailed Explanation

### **🏠 Homepage Files**

#### **index.php - Website Homepage**
```php
Purpose: First page visitors see, showcases the car rental service
Features:
- Hero section with call-to-action
- Featured cars display
- Service highlights
- Links to car browsing and registration
```

**What it does:**
- Welcomes visitors to AutoDrive
- Shows featured/popular cars
- Provides navigation to main features
- Encourages user registration and booking

#### **cars.php - Vehicle Listing**
```php
Purpose: Main car browsing page where customers find and select vehicles
Features:
- Display all available cars
- Advanced filtering (brand, fuel, transmission, price, seats)
- Real-time availability checking
- Links to reservation page
```

**What it does:**
- Retrieves cars from database
- Applies user-selected filters
- Shows car details (photos, specs, pricing)
- Provides "Reserve" buttons for each car

#### **reservation.php - Booking System**
```php
Purpose: Complete booking process for selected vehicles
Features:
- Date selection with calendar
- Price calculation
- Availability verification
- User authentication check
- Booking confirmation
```

**What it does:**
- Validates user is logged in
- Checks car availability for selected dates
- Calculates total rental price
- Processes booking and saves to database
- Shows confirmation to user

### **👤 User Management Files**

#### **login.php - User Authentication**
```php
Purpose: Secure user login system
Features:
- Email/password authentication
- Session management
- Redirect to intended page after login
- Password security (hashing verification)
```

**What it does:**
- Validates user credentials
- Creates secure session
- Redirects to requested page or homepage
- Handles login errors gracefully

#### **register.php - User Registration**
```php
Purpose: New customer account creation
Features:
- User information collection
- Email uniqueness validation
- Password hashing
- Account creation
```

**What it does:**
- Collects customer details (name, email, phone)
- Validates input data
- Hashes password securely
- Creates new customer account
- Automatically logs in new user

#### **profile.php - User Dashboard**
```php
Purpose: Customer account management and reservation history
Features:
- Personal information display
- Reservation history
- Booking status tracking
- Account settings
```

**What it does:**
- Shows user's personal information
- Lists all past and current reservations
- Allows viewing reservation details
- Provides account management options

### **🛠️ Shared Components (includes/)**

#### **config.php - Database Configuration**
```php
Purpose: Central database connection for entire system
Contains:
- Database server settings
- Connection establishment
- Error handling
- Character encoding setup
```

**Critical for:**
- Every page that needs database access
- Maintaining consistent connection settings
- Handling connection failures gracefully

#### **functions.php - Reusable Functions**
```php
Purpose: Common functions used throughout the system
Key Functions:
- cleanUserInput() - Sanitize user data
- isUserLoggedIn() - Check authentication
- calculateCarRentalPrice() - Price calculations
- checkCarAvailability() - Availability checking
```

**Used by:**
- All pages for common operations
- Security and validation
- Business logic calculations
- User session management

#### **header.php - Site Navigation**
```php
Purpose: Consistent navigation across all pages
Features:
- Logo and branding
- Main navigation menu
- User login status
- Mobile-responsive menu
```

**Included by:**
- Every public page
- Provides consistent user experience
- Handles user authentication display

#### **footer.php - Site Footer**
```php
Purpose: Consistent footer across all pages
Features:
- Copyright information
- Contact details
- Additional links
- Social media links (if any)
```

## 🔧 Admin System Files (admin/)

### **📊 Dashboard and Overview**

#### **dashboard.php - Admin Homepage**
```php
Purpose: Central admin control panel
Features:
- System statistics (cars, reservations, customers)
- Recent activity overview
- Quick action buttons
- Revenue tracking
```

**What it shows:**
- Total cars, reservations, customers
- Recent bookings and payments
- System status overview
- Links to management sections

### **🚗 Vehicle Management**

#### **cars.php - Fleet Management**
```php
Purpose: Complete vehicle inventory management
Features:
- List all vehicles
- Add new cars
- Edit car details
- Delete vehicles
- Status management
```

#### **add-cars.php - Add New Vehicles**
```php
Purpose: Add new cars to the rental fleet
Features:
- Car specification form
- Image upload
- Pricing setup
- Status assignment
```

#### **edit-car.php - Modify Vehicle Details**
```php
Purpose: Update existing vehicle information
Features:
- Edit all car details
- Update pricing
- Change availability status
- Modify specifications
```

### **📅 Reservation Management**

#### **reservations.php - Booking Overview**
```php
Purpose: Manage all customer reservations
Features:
- List all reservations
- Filter by status/date
- Quick actions (edit, delete)
- Payment status tracking
```

#### **reservation-details.php - Detailed Booking View**
```php
Purpose: Complete reservation information and management
Features:
- Full reservation details
- Customer information
- Vehicle details
- Payment management
- Edit capabilities
```

#### **edit-reservation.php - Modify Bookings**
```php
Purpose: Edit existing reservations
Features:
- Change dates
- Update customer details
- Modify vehicle assignment
- Update payment status
```

### **👥 Customer Management**

#### **clients.php - Customer Database**
```php
Purpose: Manage customer accounts and information
Features:
- List all customers
- Search customers
- View customer details
- Customer statistics
```

#### **client-details.php - Customer Profile**
```php
Purpose: Detailed customer information and history
Features:
- Complete customer profile
- Reservation history
- Payment history
- Contact information
```

## 🎨 Asset Files (assets/)

### **📱 Stylesheets (css/)**

#### **style.css - Main Website Styles**
```css
Purpose: Primary styling for customer-facing pages
Contains:
- Layout and typography
- Color scheme
- Component styles
- Responsive design rules
```

#### **admin.css - Admin Panel Styles**
```css
Purpose: Styling specific to admin dashboard
Contains:
- Admin layout styles
- Dashboard components
- Data tables
- Admin-specific UI elements
```

### **⚡ JavaScript Files (js/)**

#### **main.js - Core Functionality**
```javascript
Purpose: Interactive features for customer pages
Features:
- Form validation
- Date picker functionality
- AJAX requests
- User interface interactions
```

#### **admin.js - Admin Features**
```javascript
Purpose: Admin panel interactive features
Features:
- Dashboard interactions
- Data table functionality
- Admin form validation
- Bulk operations
```

## 🔗 File Relationships and Dependencies

### **Dependency Chain:**
```
config.php (database connection)
    ↓
functions.php (helper functions)
    ↓
All other PHP files (use connection and functions)
```

### **Include Pattern:**
```php
// Standard pattern for all PHP pages:
include 'includes/config.php';    // Database connection
include 'includes/functions.php'; // Helper functions
include 'includes/header.php';    // Page header
// Page content here
include 'includes/footer.php';    // Page footer
```

### **Admin Include Pattern:**
```php
// Admin pages use admin-specific includes:
include '../includes/config.php';     // Database (up one directory)
include '../includes/functions.php';  // Functions (up one directory)
include 'includes/admin-header.php';  // Admin header
// Admin content here
include 'includes/admin-footer.php';  // Admin footer
```

## 📊 File Usage Statistics

### **Most Important Files (Core System):**
1. **config.php** - Used by every PHP file
2. **functions.php** - Used by every PHP file
3. **cars.php** - Main customer feature
4. **reservation.php** - Core business function
5. **admin/dashboard.php** - Admin control center

### **File Categories by Purpose:**
- **🏠 Customer Interface:** 8 files (index, cars, reservation, login, etc.)
- **🛠️ Admin Interface:** 12 files (dashboard, management pages)
- **🔧 Shared Components:** 4 files (config, functions, header, footer)
- **🎨 Assets:** 6+ files (CSS, JS, images)
- **📚 Documentation:** 15+ files (comprehensive docs)

---

## 🎓 Key Points for Presentation

### **Highlight the Organization:**
1. **Clear Separation** - Customer vs Admin vs Shared components
2. **Logical Structure** - Related files grouped together
3. **Reusable Components** - DRY principle (Don't Repeat Yourself)
4. **Scalable Architecture** - Easy to add new features
5. **Professional Standards** - Industry-standard file organization

### **Technical Sophistication:**
- ✅ **Modular Design** - Separated concerns and responsibilities
- ✅ **Code Reusability** - Shared functions and components
- ✅ **Maintainability** - Clear file purposes and relationships
- ✅ **Scalability** - Easy to extend with new features
- ✅ **Documentation** - Comprehensive file explanations

**Next:** [Frontend Components](05-frontend-components.md) - Understanding the user interface

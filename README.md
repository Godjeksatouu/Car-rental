# 🚗 AutoDrive - Car Rental Management System

A comprehensive car rental management system built with PHP, MySQL, and modern web technologies. This system provides both customer-facing features and a complete admin dashboard for managing vehicles, reservations, and clients.

![AutoDrive Banner](https://img.shields.io/badge/AutoDrive-Car%20Rental%20System-blue?style=for-the-badge&logo=car)

## 📋 Table of Contents

- [Features](#-features)
- [Demo](#-demo)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Usage](#-usage)
- [Admin Panel](#-admin-panel)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🎯 Customer Features
- **Car Browsing** - View available vehicles with detailed specifications
- **Advanced Filtering** - Filter by brand, fuel type, transmission, price, and seats
- **Real-time Availability** - Check car availability for specific dates
- **Online Booking** - Reserve cars with date picker and price calculation
- **User Authentication** - Secure registration and login system
- **Responsive Design** - Mobile-friendly interface for all devices

### 🛠️ Admin Features
- **Dashboard Overview** - Statistics and recent activity monitoring
- **Vehicle Management** - Add, edit, and manage car inventory
- **Reservation Management** - View, edit, and track all bookings
- **Client Management** - Comprehensive customer database
- **Payment Tracking** - Monitor payment status and history
- **Detailed Reports** - Individual reservation and client details

### 🔧 Technical Features
- **Clean Architecture** - Well-organized, documented codebase
- **Security First** - SQL injection prevention, XSS protection
- **Responsive Design** - Bootstrap-based mobile-first approach
- **Date Management** - Advanced calendar integration with blocking
- **Multi-language Support** - French interface with easy localization

## 🎮 Demo

### Customer Interface
- **Homepage**: Modern landing page with featured vehicles
- **Car Listing**: Advanced filtering and search capabilities
- **Booking System**: Intuitive reservation process with real-time pricing

### Admin Dashboard
- **Overview**: Comprehensive statistics and recent activity
- **Management**: Full CRUD operations for all entities
- **Reporting**: Detailed views and analytics

## 🚀 Installation

### Prerequisites
- **PHP 7.4+** with MySQLi extension
- **MySQL 5.7+** or MariaDB 10.2+
- **Web Server** (Apache/Nginx)
- **Modern Browser** with JavaScript enabled

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/autodrive-car-rental.git
   cd autodrive-car-rental
   ```

2. **Configure web server**
   ```bash
   # For XAMPP/WAMP
   cp -r . /path/to/htdocs/autodrive/
   
   # For production
   cp -r . /var/www/html/autodrive/
   ```

3. **Database configuration**
   ```php
   // Edit includes/config.php
   $database_server_name = "localhost";
   $database_username = "your_username";
   $database_password = "your_password";
   $database_name = "car_rental";
   ```

4. **Import database**
   ```bash
   mysql -u username -p car_rental < database/car_rental.sql
   ```

5. **Set permissions** (Linux/Mac)
   ```bash
   chmod 755 -R .
   chmod 644 includes/config.php
   ```

## 🗄️ Database Setup

### Automatic Setup
Import the provided SQL file:
```sql
mysql -u root -p
CREATE DATABASE car_rental;
USE car_rental;
SOURCE database/car_rental.sql;
```

### Manual Setup
The database includes the following tables:
- `CLIENT` - Customer information
- `VOITURE` - Vehicle inventory
- `RESERVATION` - Booking records
- `LOCATION` - Rental transactions
- `PAIEMENT` - Payment details
- `ADMIN` - Administrator accounts

### Default Admin Account
```
Email: admin@autodrive.com
Password: admin@autodrive.com
```

## 📖 Usage

### For Customers

1. **Browse Cars**
   - Visit the homepage
   - Use filters to find suitable vehicles
   - View detailed car specifications

2. **Make Reservation**
   - Select desired dates
   - Choose a vehicle
   - Complete registration/login
   - Confirm booking with automatic pricing

3. **Manage Account**
   - View reservation history
   - Update personal information
   - Track booking status

### For Administrators

1. **Access Admin Panel**
   ```
   URL: /admin/dashboard.php
   Login with admin credentials
   ```

2. **Manage Inventory**
   - Add new vehicles with specifications
   - Update car status and pricing
   - Monitor vehicle availability

3. **Handle Reservations**
   - View all bookings in real-time
   - Edit reservation details
   - Update payment status
   - Contact customers directly

## 🎛️ Admin Panel

### Dashboard Features
- **Statistics Overview** - Total cars, reservations, clients
- **Recent Activity** - Latest bookings and payments
- **Quick Actions** - Fast access to common tasks
- **Status Monitoring** - Vehicle and reservation status

### Management Modules

#### Vehicle Management
- Add/Edit/Delete vehicles
- Manage specifications and pricing
- Upload vehicle images
- Set availability status

#### Reservation Management
- View detailed booking information
- Edit dates and customer details
- Update payment status
- Generate reports

#### Client Management
- Comprehensive customer database
- View reservation history
- Contact information management
- Activity tracking

## 🔧 API Documentation

### Core Functions

#### Authentication
```php
// Check if user is logged in
isUserLoggedIn()

// Check admin privileges
isUserAdmin()

// Secure login process
authenticateUser($email, $password)
```

#### Reservation Management
```php
// Check car availability
checkCarAvailability($car_id, $start_date, $end_date, $connection)

// Calculate rental price
calculateCarRentalPrice($car_id, $start_date, $end_date, $connection)

// Create new reservation
createReservation($client_id, $car_id, $start_date, $end_date)
```

#### Data Security
```php
// Clean user input
cleanUserInput($user_input)

// Prepared statements for all queries
$statement = mysqli_prepare($connection, $query);
```

## 🏗️ Project Structure

```
autodrive/
├── admin/                  # Admin dashboard
│   ├── dashboard.php      # Main admin interface
│   ├── cars.php          # Vehicle management
│   ├── reservations.php  # Booking management
│   ├── clients.php       # Customer management
│   └── includes/         # Admin-specific includes
├── assets/               # Static resources
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Image assets
├── includes/            # Core system files
│   ├── config.php       # Database configuration
│   ├── functions.php    # Helper functions
│   ├── header.php       # Site header
│   └── footer.php       # Site footer
├── cars.php            # Vehicle listing
├── reservation.php     # Booking interface
├── login.php          # User authentication
├── register.php       # User registration
└── index.php          # Homepage
```

## 🔒 Security Features

- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output escaping
- **Authentication Security** - Secure password hashing
- **Session Management** - Proper session handling
- **Input Validation** - Comprehensive data validation

## 🎨 Customization

### Styling
- Modify `assets/css/style.css` for visual changes
- Update color schemes in CSS variables
- Customize responsive breakpoints

### Functionality
- Extend `includes/functions.php` for new features
- Add custom validation rules
- Implement additional payment methods

### Localization
- Update text strings in template files
- Modify date formats in functions
- Add new language support

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit your changes**
   ```bash
   git commit -m 'Add amazing feature'
   ```
4. **Push to the branch**
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Open a Pull Request**

### Development Guidelines
- Follow PSR-12 coding standards
- Add comments for complex logic
- Test all functionality thoroughly
- Update documentation as needed

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **Mohamedamine Satou** - *Initial work* - [@Godjeksatouu](https://github.com/Godjeksatouu)

## 🙏 Acknowledgments

- Bootstrap for responsive design framework
- FontAwesome for icons
- Litepicker for date selection
- PHP community for excellent documentation

## 📞 Support

For support and questions:
- **Email**: godjeksatou@gmail.com
- **GitHub Issues**: [Create an issue](https://github.com/Godjeksatouu/Car-rental/issues)

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added admin dashboard and advanced filtering
- **v1.2.0** - Enhanced security and clean code implementation

---

**Made with ❤️ for efficient car rental management**

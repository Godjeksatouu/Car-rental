# 🗄️ Database Design - Complete Data Structure Explanation

## 📊 Database Overview

The AutoDrive system uses a **MySQL relational database** with **6 main tables** that work together to manage the entire car rental operation.

### **Database Name:** `car_rental`

### **Design Principles:**
- ✅ **Normalization** - Eliminates data redundancy
- ✅ **Referential Integrity** - Foreign keys maintain relationships
- ✅ **Data Types** - Appropriate types for each field
- ✅ **Constraints** - Ensures data quality and consistency

## 🏗️ Entity Relationship Diagram (ERD)

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   CLIENT    │    │  VOITURE    │    │    ADMIN    │
│             │    │             │    │             │
│ id_client   │    │ id_voiture  │    │ id_admin    │
│ nom         │    │ marque      │    │ nom_util... │
│ prénom      │    │ modele      │    │ mot_de_...  │
│ email       │    │ type        │    │             │
│ téléphone   │    │ prix_par... │    │             │
│ mot_de_...  │    │ statut      │    │             │
└─────────────┘    └─────────────┘    └─────────────┘
       │                   │
       │                   │
       └─────────┬─────────┘
                 │
         ┌─────────────┐
         │ RESERVATION │
         │             │
         │ id_reserv...│
         │ id_client   │ ←── Foreign Key to CLIENT
         │ id_voiture  │ ←── Foreign Key to VOITURE
         │ date_debut  │
         │ date_fin    │
         └─────────────┘
                 │
                 │
         ┌─────────────┐
         │  LOCATION   │
         │             │
         │ id_location │
         │ id_reserv...│ ←── Foreign Key to RESERVATION
         │ ETAT_PAIE...│
         └─────────────┘
                 │
                 │
         ┌─────────────┐
         │  PAIEMENT   │
         │             │
         │ id_paiement │
         │ id_location │ ←── Foreign Key to LOCATION
         │ montant     │
         │ date_paie...│
         │ mode_paie...│
         └─────────────┘
```

## 📋 Table Detailed Breakdown

### **1. CLIENT Table - Customer Information**

```sql
CREATE TABLE `client` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prénom` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `téléphone` varchar(20) NOT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `téléphone` (`téléphone`)
);
```

#### **Purpose:** Stores customer information for the rental system

#### **Field Explanations:**
- **`id_client`** - Unique identifier for each customer (Primary Key)
- **`nom`** - Customer's last name (Required)
- **`prénom`** - Customer's first name (Optional)
- **`email`** - Customer's email address (Unique, used for login)
- **`téléphone`** - Customer's phone number (Unique)
- **`mot_de_passe`** - Encrypted password for login (Hashed with PHP)

#### **Business Rules:**
- ✅ Each customer must have a unique email
- ✅ Each customer must have a unique phone number
- ✅ Passwords are hashed for security
- ✅ Email is used as the login username

### **2. VOITURE Table - Vehicle Inventory**

```sql
CREATE TABLE `voiture` (
  `id_voiture` int(11) NOT NULL AUTO_INCREMENT,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `immatriculation` varchar(50) DEFAULT NULL,
  `type` enum('diesel','essence') DEFAULT NULL,
  `image` text DEFAULT NULL,
  `nb_places` int(11) DEFAULT NULL,
  `statut` enum('réservé','en location','disponible','maintenance') DEFAULT NULL,
  `prix_par_jour` decimal(10,2) DEFAULT NULL,
  `gear` enum('automatique','manuel') DEFAULT NULL,
  PRIMARY KEY (`id_voiture`)
);
```

#### **Purpose:** Stores all vehicle information and specifications

#### **Field Explanations:**
- **`id_voiture`** - Unique identifier for each vehicle (Primary Key)
- **`marque`** - Car brand (e.g., "Toyota", "BMW")
- **`modele`** - Car model (e.g., "Corolla", "X3")
- **`immatriculation`** - License plate number
- **`type`** - Fuel type (diesel or essence/gasoline)
- **`image`** - URL to car image
- **`nb_places`** - Number of seats in the vehicle
- **`statut`** - Current availability status
- **`prix_par_jour`** - Daily rental price in currency
- **`gear`** - Transmission type (automatic or manual)

#### **Status Values Explained:**
- **`disponible`** - Available for booking
- **`réservé`** - Reserved by a customer
- **`en location`** - Currently rented out
- **`maintenance`** - Under maintenance, not available

### **3. RESERVATION Table - Booking Records**

```sql
CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `id_voiture` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_reservation`),
  FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  FOREIGN KEY (`id_voiture`) REFERENCES `voiture` (`id_voiture`)
);
```

#### **Purpose:** Records customer bookings and rental periods

#### **Field Explanations:**
- **`id_reservation`** - Unique booking identifier (Primary Key)
- **`id_client`** - Which customer made the booking (Foreign Key)
- **`date_debut`** - Rental start date
- **`date_fin`** - Rental end date
- **`id_voiture`** - Which car is being rented (Foreign Key)

#### **Business Logic:**
- ✅ Links customers to their bookings
- ✅ Links bookings to specific vehicles
- ✅ Defines the rental period
- ✅ Prevents double-booking through availability checks

### **4. LOCATION Table - Active Rentals**

```sql
CREATE TABLE `location` (
  `id_location` int(11) NOT NULL AUTO_INCREMENT,
  `id_reservation` int(11) DEFAULT NULL,
  `ETAT_PAIEMENT` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_location`),
  UNIQUE KEY `id_reservation` (`id_reservation`),
  FOREIGN KEY (`id_reservation`) REFERENCES `reservation` (`id_reservation`)
);
```

#### **Purpose:** Tracks active rentals and payment status

#### **Field Explanations:**
- **`id_location`** - Unique rental identifier (Primary Key)
- **`id_reservation`** - Links to the original reservation (Foreign Key)
- **`ETAT_PAIEMENT`** - Payment status (0 = unpaid, 1 = paid)

#### **Business Logic:**
- ✅ Converts reservations into active rentals
- ✅ Tracks payment status
- ✅ One location per reservation (unique constraint)

### **5. PAIEMENT Table - Payment Details**

```sql
CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_location` int(11) DEFAULT NULL,
  `date_paiement` date DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `mode_paiement` enum('espèce','par chèque','virement') DEFAULT NULL,
  PRIMARY KEY (`id_paiement`),
  FOREIGN KEY (`id_location`) REFERENCES `location` (`id_location`)
);
```

#### **Purpose:** Records detailed payment information

#### **Field Explanations:**
- **`id_paiement`** - Unique payment identifier (Primary Key)
- **`id_location`** - Links to the rental (Foreign Key)
- **`date_paiement`** - When payment was made
- **`montant`** - Payment amount
- **`mode_paiement`** - Payment method (cash, check, transfer)

### **6. ADMIN Table - Administrator Accounts**

```sql
CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `nom_utilisateur` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_admin`)
);
```

#### **Purpose:** Stores administrator login credentials

#### **Field Explanations:**
- **`id_admin`** - Unique admin identifier (Primary Key)
- **`nom_utilisateur`** - Admin username/email
- **`mot_de_passe`** - Admin password (should be hashed)

## 🔗 Relationship Explanations

### **1. Customer → Reservation (One-to-Many)**
```
One customer can have multiple reservations
CLIENT.id_client ← RESERVATION.id_client
```

### **2. Vehicle → Reservation (One-to-Many)**
```
One vehicle can have multiple reservations (at different times)
VOITURE.id_voiture ← RESERVATION.id_voiture
```

### **3. Reservation → Location (One-to-One)**
```
One reservation becomes one location (when confirmed)
RESERVATION.id_reservation ← LOCATION.id_reservation
```

### **4. Location → Payment (One-to-One)**
```
One location can have one payment record
LOCATION.id_location ← PAIEMENT.id_location
```

## 📈 Data Flow Through Tables

### **Complete Booking Process:**
```
1. Customer registers → CLIENT table
2. Customer books car → RESERVATION table
3. Booking confirmed → LOCATION table
4. Payment made → PAIEMENT table
```

### **Example Data Flow:**
```sql
-- 1. Customer "John Doe" registers
INSERT INTO CLIENT (nom, prénom, email, téléphone, mot_de_passe)
VALUES ('Doe', 'John', 'john@email.com', '123456789', 'hashed_password');

-- 2. John books car ID 5 for July 1-7
INSERT INTO RESERVATION (id_client, id_voiture, date_debut, date_fin)
VALUES (1, 5, '2025-07-01', '2025-07-07');

-- 3. Booking confirmed as location
INSERT INTO LOCATION (id_reservation, ETAT_PAIEMENT)
VALUES (1, 0); -- 0 = not paid yet

-- 4. Payment processed
INSERT INTO PAIEMENT (id_location, date_paiement, montant, mode_paiement)
VALUES (1, '2025-06-21', 1400.00, 'virement');

-- 5. Update payment status
UPDATE LOCATION SET ETAT_PAIEMENT = 1 WHERE id_location = 1;
```

## 🔍 Key Queries Used in the System

### **1. Check Car Availability**
```sql
-- Find if car is available for specific dates
SELECT id_reservation 
FROM RESERVATION 
WHERE id_voiture = ? 
AND ((date_debut BETWEEN ? AND ?) 
    OR (date_fin BETWEEN ? AND ?) 
    OR (date_debut <= ? AND date_fin >= ?))
```

### **2. Get Customer Reservations**
```sql
-- Get all reservations for a customer with car details
SELECT r.*, v.marque, v.modele 
FROM RESERVATION r 
JOIN VOITURE v ON r.id_voiture = v.id_voiture 
WHERE r.id_client = ?
```

### **3. Calculate Total Revenue**
```sql
-- Get total payments
SELECT SUM(montant) as total_revenue 
FROM PAIEMENT 
WHERE date_paiement BETWEEN ? AND ?
```

## 🛡️ Data Security & Integrity

### **Security Measures:**
- ✅ **Foreign Key Constraints** - Maintain data relationships
- ✅ **Unique Constraints** - Prevent duplicate emails/phones
- ✅ **Data Types** - Appropriate types prevent invalid data
- ✅ **Password Hashing** - Passwords stored securely

### **Data Validation:**
- ✅ **Email Format** - Validated in application layer
- ✅ **Date Logic** - End date must be after start date
- ✅ **Price Validation** - Must be positive numbers
- ✅ **Status Validation** - Only allowed enum values

## 📊 Database Performance

### **Indexes for Performance:**
```sql
-- Primary keys automatically indexed
-- Additional indexes for common queries:
CREATE INDEX idx_reservation_dates ON RESERVATION(date_debut, date_fin);
CREATE INDEX idx_voiture_status ON VOITURE(statut);
CREATE INDEX idx_client_email ON CLIENT(email);
```

### **Query Optimization:**
- ✅ **Prepared Statements** - Faster execution, security
- ✅ **Appropriate Joins** - Efficient data retrieval
- ✅ **Limited Results** - Use LIMIT for large datasets
- ✅ **Indexed Columns** - Fast searching on key fields

---

## 🎓 Key Database Points for Presentation

### **Highlight These Design Decisions:**

1. **Normalized Structure** - Eliminates data redundancy
2. **Referential Integrity** - Foreign keys maintain consistency
3. **Business Logic** - Database structure reflects real-world processes
4. **Scalability** - Design supports growth and additional features
5. **Security** - Proper constraints and data types

### **Technical Sophistication:**
- ✅ **Relational Design** - Proper table relationships
- ✅ **Data Integrity** - Constraints ensure data quality
- ✅ **Performance** - Indexed for common queries
- ✅ **Security** - Foreign keys and validation
- ✅ **Flexibility** - Easy to extend with new features

**Next:** [File Structure](04-file-structure.md) - Understanding every file's purpose

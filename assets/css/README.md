# CSS Files Organization

This document describes the organization of CSS files in the AutoDrive car rental system.

## 📁 File Structure

```
assets/css/
├── style.css              # Global styles and base components
├── about.css              # About page specific styles
├── admin-common.css        # Shared admin components (tables, forms, alerts)
├── admin-login.css         # Admin login page specific styles
├── auth.css               # User authentication pages (login/register)
├── cars.css               # Cars listing page styles
├── dashboard.css          # Admin dashboard specific styles
├── index.css              # Homepage specific styles
├── locations.css          # Admin locations management page
├── payment.css            # Payment page specific styles
├── profile.css            # User profile page styles
├── reservation.css        # Reservation-related pages styles
├── services.css           # Services page specific styles
└── README.md              # This documentation file
```

## 🎯 CSS File Purposes

### **style.css** - Global Base Styles
- **Purpose**: Contains global styles, CSS variables, and base components
- **Includes**:
  - CSS custom properties (variables)
  - Reset styles and typography
  - Header and footer styles
  - Button components
  - Form components
  - Utility classes
  - Global animations
- **Used by**: All pages as the base stylesheet

### **admin-common.css** - Admin Shared Components
- **Purpose**: Shared styles for all admin pages
- **Includes**:
  - Admin tables (.admin-table)
  - Status badges (.status-badge)
  - Action buttons (.btn-sm, .btn-primary, etc.)
  - Form controls (.form-control)
  - Alert messages (.alert)
  - Modal components
  - Loading spinners
  - Utility classes
- **Used by**: All admin pages

### **admin-login.css** - Admin Login Page
- **Purpose**: Specific styles for admin login page
- **Includes**:
  - Login page layout (.admin-login-page)
  - Login card styling (.admin-login-card)
  - Logo and branding (.admin-login-logo)
  - Form styling (.admin-login-form)
  - Password toggle functionality
- **Used by**: `admin/login.php`

### **auth.css** - User Authentication Pages
- **Purpose**: Styles for user login and registration pages
- **Includes**:
  - Auth page layout (.auth-page)
  - Two-column layout (.auth-container)
  - Form section styling (.auth-form-section)
  - Info section styling (.auth-info-section)
  - Password input with toggle
  - Error/success messages
- **Used by**: `login.php`, `register.php`

### **cars.css** - Cars Listing Page
- **Purpose**: Styles for the cars browsing and filtering page
- **Includes**:
  - Page header styling
  - Filter section (.filter-section)
  - Cars grid layout (.cars-grid)
  - Car card components (.car-card)
  - Car status badges
  - Loading states and skeletons
  - No results state
- **Used by**: `cars.php`

### **dashboard.css** - Admin Dashboard
- **Purpose**: Specific styles for admin dashboard
- **Includes**:
  - Admin header (.admin-header)
  - Navigation styling (.admin-nav)
  - Stats grid (.stats-grid)
  - Stat cards (.stat-card)
  - Admin content layout (.admin-content)
  - Status charts (.status-chart)
  - Quick actions (.quick-actions)
- **Used by**: `admin/dashboard.php`, and other admin pages for layout

### **index.css** - Homepage
- **Purpose**: Styles specific to the homepage
- **Includes**:
  - Hero section (.hero)
  - Search box (.search-box)
  - Features section (.features)
  - Featured cars display
  - CTA section (.cta)
  - Statistics section (.stats)
  - Homepage animations
- **Used by**: `index.php`

### **locations.css** - Admin Locations Management
- **Purpose**: Styles for the locations management page
- **Includes**:
  - Search form styling (.search-form)
  - Client/car info display (.client-info, .car-info)
  - Pagination components (.pagination)
  - Empty state styling (.empty-state)
  - Location-specific table styles
- **Used by**: `admin/locations.php`

### **profile.css** - User Profile Page
- **Purpose**: Styles for user profile and account management
- **Includes**:
  - Profile layout (.profile-content)
  - Sidebar navigation (.sidebar)
  - Profile avatar (.profile-avatar)
  - Form grid layout (.form-grid)
  - Profile statistics (.profile-stats)
  - Activity list (.activity-list)
- **Used by**: `profile.php`

### **reservation.css** - Reservation Pages
- **Purpose**: Styles for reservation-related pages
- **Includes**:
  - Reservation form (.reservation-form)
  - Car selection display (.car-selection)
  - Reservation summary (.reservation-summary)
  - Status badges (.reservation-status)
  - Reservation list (.reservations-list)
  - Date picker enhancements
- **Used by**: `reservation.php`, `reservations.php`, `reservation-details.php`

### **about.css** - About Page
- **Purpose**: Styles specific to the about page
- **Includes**:
  - Page header with background (.page-header)
  - About and mission sections (.about-section, .mission-section)
  - Values grid (.values-grid)
  - Team member cards (.team-member)
  - Testimonials section (.testimonials-section)
  - CTA section (.cta)
  - Animations (fadeInDown, fadeInUp)
- **Used by**: `about.php`

### **services.css** - Services Page
- **Purpose**: Styles for services listing and details pages
- **Includes**:
  - Services grid layout (.services-grid)
  - Service cards (.service-card)
  - Service details section (.service-details)
  - Service benefits list (.service-benefits)
  - Services CTA section (.services-cta)
  - Pricing display (.service-price)
- **Used by**: `services.php`, `service-details.php`

### **payment.css** - Payment Page
- **Purpose**: Styles for payment processing pages
- **Includes**:
  - Payment card layout (.payment-card)
  - Payment header (.payment-header)
  - Reservation summary (.reservation-summary)
  - Payment form (.payment-form)
  - Payment method selection (.payment-method)
  - Payment actions (.payment-actions)
- **Used by**: `payment.php`, `checkout.php`

## 🔧 Implementation Guidelines

### **Including CSS Files**
Each PHP page should include CSS files in this order:
1. `style.css` (always first - global styles)
2. Page-specific CSS file(s)
3. External libraries (Font Awesome, etc.)

Example for admin pages:
```html
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin-common.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

### **CSS Variables**
All CSS files use the global CSS variables defined in `style.css`:
- `--primary-color`: Main brand color
- `--secondary-color`: Accent color
- `--gray-*`: Grayscale palette
- `--success`, `--warning`, `--error`: Status colors
- `--border-radius`, `--shadow-*`: Design tokens

### **Responsive Design**
All CSS files include responsive breakpoints:
- Desktop: 992px and above
- Tablet: 768px to 991px
- Mobile: 576px to 767px
- Small mobile: 575px and below

### **Naming Conventions**
- Use BEM methodology where appropriate
- Prefix admin-specific classes with `admin-`
- Use semantic class names
- Maintain consistency across files

## 📱 Benefits of This Organization

1. **Modularity**: Each page loads only the CSS it needs
2. **Maintainability**: Easy to find and update page-specific styles
3. **Performance**: Reduced CSS file sizes per page
4. **Scalability**: Easy to add new pages with their own styles
5. **Debugging**: Clear separation of concerns
6. **Caching**: Better browser caching of individual files

## 🔄 Migration Notes

- Inline styles have been removed from PHP files
- Global styles remain in `style.css`
- Page-specific styles moved to dedicated files
- All PHP files updated to include appropriate CSS files
- Responsive design maintained across all files

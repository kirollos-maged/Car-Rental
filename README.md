# Car Rental Management System

A comprehensive web-based car rental management system built with PHP and MySQL, featuring role-based access control for administrators, staff, and customers. The system provides a complete solution for managing car inventory, reservations, invoices, and operational workflows.

## 🚀 Features

### Core Functionality
- **User Authentication & Role Management**: Secure login system with three user roles (ADMIN, STAFF, CUSTOMER)
- **Car Inventory Management**: Complete CRUD operations for cars, including status tracking and pricing
- **Reservation System**: Online booking with availability checking and conflict prevention
- **Invoice Management**: Automated invoice generation and payment tracking
- **Advanced Search**: Multi-criteria search for cars by location, dates, and specifications

### Admin Module
- **Dashboard**: Centralized control panel for system administration
- **Staff Management**: Add, edit, and manage staff accounts
- **Office Management**: Manage rental office locations and addresses
- **System Logs**: Comprehensive logging of user activities and security events
- **Reports**: Administrative reports and analytics

### Staff Module
- **Car Operations**: View car history, update status, and modify pricing
- **Invoice Management**: Search, edit, and update customer invoices
- **Operational Reports**: Daily reports for cars leaving and returning
- **Notification System**: Automated notifications for invoice updates

### Customer Module
- **Dashboard**: Personal booking history and account management
- **Online Booking**: Intuitive reservation process with real-time availability
- **Invoice Access**: Download and view booking invoices
- **Notifications**: Real-time alerts for booking updates

## 🛠️ Technologies Used

- **Backend**: PHP 8.2.12
- **Database**: MySQL/MariaDB 10.4.32
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache (XAMPP)
- **Security**: Session-based authentication, prepared statements, input validation

## 📋 Prerequisites

- XAMPP (or similar Apache/MySQL/PHP stack)
- Web browser with JavaScript enabled
- Internet connection for external resources (if any)

## 🚀 Installation & Setup

### 1. Environment Setup
1. Install XAMPP on your system
2. Start Apache and MySQL services
3. Copy the project folder to `C:\xampp\htdocs\DB_project_ANU-1\`

### 2. Database Configuration
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Create a new database named `CarRentalDB`
3. Import the complete database setup file:
   ```sql
   -- Run complete_database.sql to set up the entire database
   ```
   This file includes:
   - Full schema with all tables and relationships
   - Staff module updates
   - Admin seed data

### 3. Configuration
- Update database credentials in `conn.php` if needed (default: localhost, root, no password)
- Ensure proper file permissions for PHP file execution

### 4. Access the Application
- Open your browser and navigate to: `http://localhost/DB_project_ANU-1/`
- Register a new account or use existing credentials

## 📁 Project Structure

```
DB_project_ANU-1/
├── index.php                    # Homepage with car catalog
├── login.php                    # User authentication
├── register.php                 # User registration
├── dashboard.php                # Customer dashboard
├── reservation.php              # Car booking interface
├── invoice.php                  # Invoice viewing
├── advanced_search.php          # Advanced car search
├── process_reservation.php      # Booking processing logic
├── check_availability.php       # Availability checking
├── reservation_success.php      # Booking confirmation
├── feedback.php                 # Customer feedback
├── forgot_password.php          # Password recovery
├── about_us.php                 # About page
├── privacy_policy.php           # Privacy policy
├── terms_conditions.php         # Terms and conditions
├── logout.php                   # User logout
├── conn.php                     # Database connection
├── css/                         # External CSS files
│   ├── index.css
│   ├── dashboard.css
│   ├── login.css
│   └── register.css
├── js/                          # External JavaScript files
│   ├── index.js
│   ├── dashboard.js
│   ├── login.js
│   └── register.js
├── admin_*.php                  # Admin module files
├── staff_*.php                  # Staff module files
├── complete_database.sql        # Complete database setup
├── LICENSE                      # Project license
└── README.md                    # This file
```

## 🗄️ Database Schema

The system uses a relational database with the following key tables:

- **users**: User accounts with role-based access
- **cars**: Vehicle inventory with status and pricing
- **reservations**: Booking records
- **invoices**: Payment and billing information
- **offices**: Rental location management
- **car_status_history**: Audit trail for car status changes
- **notifications**: Customer notification system
- **login_activity**: Security logging
- **security_logs**: System security events

## 🔐 Security Features

- **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
- **Session Management**: Secure session handling with role-based access control
- **Input Validation**: Server-side validation for all user inputs
- **Password Security**: Secure password hashing
- **Activity Logging**: Comprehensive logging of user actions and security events

## 📊 Usage Guide

### For Customers
1. **Browse Cars**: View available vehicles on the homepage
2. **Search**: Use advanced search for specific requirements
3. **Book**: Select dates and complete reservation
4. **Manage**: View bookings and download invoices from dashboard

### For Staff
1. **Login**: Access staff dashboard
2. **Manage Cars**: Update status, pricing, and view history
3. **Handle Invoices**: Search and edit customer invoices
4. **View Reports**: Check daily operational reports

### For Administrators
1. **System Overview**: Access admin dashboard
2. **Manage Users**: Add/edit staff and customer accounts
3. **Office Management**: Configure rental locations
4. **Monitor System**: View logs and reports

## 🔧 Development Notes

- **Code Organization**: CSS and JavaScript extracted to external files for better maintainability
- **Responsive Design**: Mobile-friendly interface with CSS media queries
- **Error Handling**: User-friendly error messages with technical details logged
- **Session Security**: Automatic logout on inactivity
- **Database Integrity**: Foreign key constraints and transaction handling

## 📝 License

This project is licensed under the terms specified in the LICENSE file.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For technical support or questions about the system, please refer to the documentation or contact the development team.

---

**Built with ❤️ for efficient car rental management**
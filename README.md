# COFISEE Microfinance System

A comprehensive microfinance management system for COFISEE, designed to manage members, loans, repayments, and savings.

## 📋 Features

- **Member Management**: Register and manage microfinance members
- **Loan Management**: Disburse and track loans with interest rates
- **Dashboard**: Real-time statistics and charts
- **Repayment Tracking**: Track member repayments
- **Savings Account**: Track member savings
- **Audit Logs**: Keep records of all system activities
- **User Authentication**: Secure login system
- **Responsive Design**: Mobile-friendly interface

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Charts**: Chart.js
- **Security**: Prepared Statements, Password Hashing (bcrypt)

## 📦 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone or download the repository**
   ```bash
   git clone https://github.com/yourusername/cofisee-system.git
   cd cofisee-system
   ```

2. **Create the database**
   - Open phpMyAdmin or MySQL command line
   - Import `database.sql`:
   ```sql
   mysql -u root -p < database.sql
   ```

3. **Configure database connection**
   - Edit `php/db.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'cofisee_db');
   ```

4. **Set proper file permissions**
   ```bash
   chmod 755 php/
   chmod 644 php/*.php
   ```

5. **Access the system**
   - Open browser and go to `http://localhost/cofisee-system`
   - Login with default credentials:
     - Email: `admin@cofisee.com`
     - Password: `password123`

## 📁 Project Structure

```
cofisee-system/
├── css/
│   └── style.css              # Main stylesheet
├── php/
│   └── db.php                 # Database configuration
├── index.html                 # Home page
├── login.html                 # Login page
├── dashboard.php              # Dashboard with statistics
├── members.html               # Member registration form
├── members.php                # Member list and management
├── loans.html                 # Loan form
├── loans.php                  # Loan list and management
├── logout.php                 # Logout handler
├── database.sql               # Database schema
└── README.md                  # This file
```

## 🔒 Security Features

- **SQL Injection Prevention**: Prepared statements for all database queries
- **Password Security**: bcrypt hashing for user passwords
- **Input Validation**: Server-side validation for all forms
- **XSS Protection**: HTML escaping for user inputs
- **Session Management**: Secure session handling

## 🚀 Usage

### Adding a Member
1. Navigate to "Members" page
2. Fill in member details (Name, Phone, National ID)
3. Click "Register Member"
4. Member appears in the Members List

### Disbursing a Loan
1. Navigate to "Loans" page
2. Enter Member ID, Loan Amount, and Interest Rate
3. Click "Disburse Loan"
4. Loan appears in the Loans List

### Viewing Dashboard
1. Navigate to "Dashboard" page
2. View key statistics and trends
3. Charts show loan and member trends for the current year

## 🔧 Configuration

### Database Configuration
Edit `php/db.php`:
```php
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');           // Database user
define('DB_PASS', '');               // Database password
define('DB_NAME', 'cofisee_db');     // Database name
```

## 📝 Database Schema

### Tables
- **users**: System users (admin, staff)
- **members**: Microfinance members
- **loans**: Loan records
- **repayments**: Loan repayment tracking
- **savings**: Member savings accounts
- **audit_logs**: System activity logs

## 🐛 Known Issues

- Default sample users should be changed in production
- Email notifications not yet implemented
- SMS integration pending

## 📞 Support

For support, email: support@cofisee.com

## 📄 License

This project is licensed under the MIT License - see LICENSE file for details.

## 👥 Contributors

- COFISEE Development Team

## 🎯 Future Enhancements

- Email notification system
- SMS integration
- Mobile app
- Advanced reporting
- API integration
- Multi-currency support
- Two-factor authentication

---

**Version**: 1.0.0  
**Last Updated**: 2026-06-27

# OneStop - Booking.com Style Service Platform

OneStop is a comprehensive service booking platform that allows companies to list their services and users to discover and book them. Built with PHP, MySQL, and Bootstrap, it provides a modern, secure, and user-friendly experience similar to Booking.com.

## 🚀 Features

### For Users
- **Service Discovery**: Browse and search through available services
- **Advanced Search**: Filter by location, dates, and guest count
- **Booking Management**: Create, view, and manage bookings
- **User Dashboard**: Track booking history and status
- **Secure Authentication**: Role-based access with password hashing

### For Companies
- **Service Management**: Add, edit, and manage service listings
- **Booking Approval**: Review and approve/decline booking requests
- **Company Dashboard**: Monitor bookings and service performance
- **Real-time Updates**: Track booking status and customer information

### For Administrators
- **Platform Management**: Approve companies and services
- **Activity Monitoring**: Comprehensive audit logs
- **User Management**: Oversee all platform activities
- **Analytics Dashboard**: Platform statistics and insights

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ with MySQL
- **Frontend**: Bootstrap 5.3.1, HTML5, CSS3, JavaScript
- **Database**: MySQL with InnoDB engine
- **Security**: CSRF protection, password hashing, input validation
- **UI/UX**: Responsive design with modern Booking.com-style interface

## 📋 Database Schema

The system uses a well-structured database with the following main tables:

- **users**: User accounts with role-based access (user, company, admin)
- **companies**: Company profiles and approval status
- **services**: Service listings with pricing and availability
- **bookings**: Booking records with status tracking
- **activity_logs**: Comprehensive audit trail

## 🔧 Installation

1. **Database Setup**:
   ```sql
   -- Import the database schema
   mysql -u root -p < database_schema.sql
   ```

2. **Configuration**:
   - Update database credentials in `includes/db.php`
   - Ensure PHP 7.4+ is installed
   - Configure web server to point to the project directory

3. **Default Credentials**:
   - Admin: `admin@onestop.com` / `password`
   - Sample Company: `company@onestop.com` / `password`

## 🔐 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Input Validation**: Server-side validation for all user inputs
- **Session Security**: Secure session management with regeneration
- **CAPTCHA**: Math-based CAPTCHA for registration and login

## 🎨 User Interface

- **Modern Design**: Clean, professional Booking.com-inspired interface
- **Responsive Layout**: Works seamlessly on desktop, tablet, and mobile
- **Intuitive Navigation**: Easy-to-use navigation with role-based menus
- **Interactive Elements**: Smooth animations and hover effects
- **Accessibility**: Proper contrast ratios and keyboard navigation

## 📁 Project Structure

```
/workspace/
├── includes/           # Core PHP includes
│   ├── db.php         # Database connection
│   ├── session.php    # Session management
│   ├── auth.php       # Authentication functions
│   ├── csrf.php       # CSRF protection
│   ├── logger.php     # Activity logging
│   └── captcha.php    # CAPTCHA implementation
├── auth/              # Authentication pages
│   ├── login.php      # User login
│   ├── register.php   # User registration
│   └── logout.php     # User logout
├── user/              # User dashboard and features
├── company/           # Company management
├── admin/             # Admin panel
├── service/           # Service viewing and booking
├── index.php          # Homepage with search
├── services.php       # All services listing
└── database_schema.sql # Database setup
```

## 🚦 Getting Started

1. **Access the Platform**:
   - Visit the homepage to browse services
   - Register as a user or company
   - Login with your credentials

2. **For Companies**:
   - Complete company registration
   - Wait for admin approval
   - Add service listings
   - Manage bookings

3. **For Users**:
   - Browse available services
   - Use search filters to find specific services
   - Book services with date and guest selection
   - Track booking status in dashboard

4. **For Administrators**:
   - Approve pending companies and services
   - Monitor platform activity
   - View comprehensive logs

## 🔄 Booking Flow

1. **Service Discovery**: Users browse or search for services
2. **Service Details**: View detailed information and pricing
3. **Booking Creation**: Select dates, guests, and special requests
4. **Conflict Prevention**: System checks for overlapping bookings
5. **Company Review**: Company approves or declines booking
6. **Confirmation**: User receives booking confirmation

## 📊 Activity Logging

All critical actions are logged for audit purposes:
- User registrations and logins
- Service creation and updates
- Booking creation and status changes
- Admin approvals and rejections
- System errors and security events

## 🎯 Key Features

- **Role-Based Access Control**: Three distinct user types with appropriate permissions
- **Real-Time Availability**: Dynamic booking conflict prevention
- **Responsive Design**: Mobile-first approach with Bootstrap
- **Security First**: Multiple layers of security protection
- **Audit Trail**: Comprehensive activity logging
- **Modern UI**: Booking.com-inspired design and user experience

## 🔧 Customization

The system is designed to be easily customizable:
- Modify styling in CSS files
- Add new service categories
- Extend user roles and permissions
- Integrate with external payment systems
- Add email notifications

## 📞 Support

For technical support or questions about the OneStop platform, please contact the development team.

---

**OneStop** - Your one-stop destination for service booking and management.
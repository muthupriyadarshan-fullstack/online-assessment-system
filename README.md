# Assessment System

A comprehensive multiple choice assessment platform built with HTML, CSS, jQuery, PHP, and Bootstrap. Features a luxury purple and white design with full responsive support for desktop and mobile devices.

## Features

### User Features
- **User Registration & Login**: Secure authentication system with password hashing
- **Assessment Taking**: Interactive multiple choice questions with real-time timer
- **Results & Analytics**: Detailed performance tracking with charts and statistics
- **Score History**: Complete history of all assessment attempts
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

### Admin Features
- **Admin Dashboard**: Comprehensive overview of system statistics and user activity
- **Question Management**: Add questions manually or upload via CSV file
- **Assessment Management**: Create, edit, and manage assessments
- **User Management**: View and manage all user accounts
- **Reports & Analytics**: Detailed reports with export functionality
- **Real-time Monitoring**: Track user activity and assessment performance

### Technical Features
- **Luxury Design**: Beautiful purple and white color scheme with modern UI
- **Responsive Layout**: Mobile-first design that works on all devices
- **Real-time Updates**: Live progress tracking and timer functionality
- **Data Export**: CSV export for users and assessment data
- **Security**: Password hashing, SQL injection prevention, and session management
- **Performance**: Optimized database queries and efficient code structure

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/LAMP (for local development)

### Setup Instructions

1. **Clone or Download** the project files to your web server directory
   ```
   /xampp/htdocs/assessment-system/
   ```

2. **Database Setup**
   - Create a new MySQL database named `assessment_system`
   - Import the `database.sql` file to create all necessary tables
   - Update database credentials in `config/database.php` if needed

3. **Configure Database Connection**
   Edit `config/database.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'assessment_system');
   ```

4. **Set Permissions**
   Ensure the web server has read/write permissions to the project directory

5. **Access the Application**
   - Open your web browser
   - Navigate to `http://localhost/assessment-system/`
   - Register a new user account or use the default admin account

## Default Admin Account

- **Username**: admin
- **Password**: admin

## File Structure

```
assessment-system/
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet with luxury design
├── config/
│   ├── database.php           # Database connection and configuration
│   └── session.php            # Session management and authentication
├── admin_assessments.php      # Admin assessment management
├── admin_dashboard.php        # Admin main dashboard
├── admin_questions.php        # Admin question management
├── admin_reports.php          # Admin reports and analytics
├── admin_users.php            # Admin user management
├── assessment_result.php      # Assessment results display
├── assessments.php            # User assessments listing
├── database.sql               # Database schema and initial data
├── index.php                  # Homepage and landing page
├── login.php                  # User login page
├── logout.php                 # Logout functionality
├── my_results.php             # User results and analytics
├── profile.php                # User profile management
├── register.php               # User registration page
├── take_assessment.php        # Assessment taking interface
├── user_dashboard.php         # User main dashboard
└── README.md                  # This file
```

## Usage

### For Users
1. **Register**: Create a new account or login with existing credentials
2. **Browse Assessments**: View available assessments on the dashboard
3. **Take Assessment**: Click "Start Assessment" to begin
4. **View Results**: Check your performance and detailed results
5. **Track Progress**: Monitor your improvement over time

### For Admins
1. **Login**: Use admin credentials to access the admin panel
2. **Create Assessments**: Set up new assessments with titles and descriptions
3. **Add Questions**: Upload questions via CSV or add them manually
4. **Manage Users**: View user accounts and their performance
5. **Generate Reports**: Export data and view comprehensive analytics

## CSV Upload Format

When uploading questions via CSV, use the following format:
```csv
question,option_a,option_b,option_c,option_d,correct_answer,points
What is the capital of France?,Paris,London,Berlin,Madrid,A,1
What is 2+2?,3,4,5,6,B,1
```

## Customization

### Colors and Styling
The design uses CSS custom properties for easy customization. Edit `assets/css/style.css` to modify:
- Primary colors (purple theme)
- Typography
- Spacing and layout
- Component styles

### Database Schema
The system uses a well-structured database with the following main tables:
- `users` - User accounts and authentication
- `assessments` - Assessment information
- `questions` - Question bank
- `user_attempts` - User assessment attempts
- `user_answers` - Individual question answers

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: All queries use prepared statements
- **Session Management**: Secure session handling with proper validation
- **Input Validation**: All user inputs are validated and sanitized
- **Access Control**: Role-based access control for admin functions

## Browser Support

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **Session Issues**
   - Check PHP session configuration
   - Ensure proper file permissions
   - Clear browser cookies and cache

3. **File Upload Issues**
   - Check PHP upload limits in `php.ini`
   - Ensure proper file permissions
   - Verify CSV file format

4. **Styling Issues**
   - Clear browser cache
   - Check if CSS file is loading properly
   - Verify Bootstrap CDN is accessible

## Support

For technical support or questions about this assessment system, please refer to the code documentation or contact the development team.

## License

This project is created for educational and commercial use. Please ensure you have the necessary permissions before using in production environments.

---

**Assessment System** - Professional assessment platform for educational and corporate use.

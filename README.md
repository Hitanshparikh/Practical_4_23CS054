# Practical 4: PHP & MySQL Database Integration

**Made by Hitansh Parikh - 23CS054**

## Problem Definition
Develop a comprehensive web application using PHP and MySQL database integration, including user authentication, CRUD operations, session management, and data security. Create a complete library management system with advanced features.

## Features Implemented
- ✅ PHP object-oriented programming with classes and methods
- ✅ MySQL database design and normalization
- ✅ User authentication and authorization system
- ✅ Session management and security
- ✅ CRUD operations for multiple entities
- ✅ Advanced SQL queries with joins and subqueries
- ✅ Form validation and sanitization
- ✅ File upload and management
- ✅ Search and pagination functionality
- ✅ Report generation and data export
- ✅ Error handling and logging
- ✅ Responsive design with Bootstrap integration

## System Architecture
1. **Database Layer** - MySQL with normalized tables
2. **Model Layer** - PHP classes for data handling
3. **Controller Layer** - Business logic and routing
4. **View Layer** - HTML templates with PHP integration
5. **Authentication** - Secure login/logout system
6. **File Management** - Upload/download functionality

## Database Schema
- **users** - User accounts and profiles  
- **books** - Book catalog with metadata
- **authors** - Author information
- **categories** - Book categories/genres
- **loans** - Book borrowing records
- **reservations** - Book reservation system
- **reviews** - Book reviews and ratings
- **settings** - System configuration

## PHP Files Structure
1. **config/database.php** - Database connection and configuration
2. **classes/User.php** - User management class
3. **classes/Book.php** - Book management class
4. **classes/Loan.php** - Loan management class
5. **classes/Auth.php** - Authentication handler
6. **includes/header.php** - Common header template
7. **includes/footer.php** - Common footer template
8. **admin/** - Administrative interface
9. **user/** - User dashboard and features
10. **api/** - RESTful API endpoints

## Features by User Type
### Admin Features
- User management (CRUD)
- Book catalog management
- Loan tracking and management
- System reports and analytics
- Settings and configuration
- Backup and maintenance

### User Features  
- Book search and browsing
- Book reservation and borrowing
- Personal loan history
- Profile management
- Book reviews and ratings
- Reading lists and favorites

## Security Features
- Password hashing (bcrypt)
- SQL injection prevention
- XSS protection
- CSRF token validation
- Session hijacking prevention
- Input validation and sanitization
- File upload security
- Role-based access control

## Technologies Used
- PHP 7.4+ (OOP, PDO, Sessions)
- MySQL 8.0+ (Database, Stored Procedures)
- HTML5 (Semantic markup)
- CSS3 (Custom styling + Bootstrap 5)
- JavaScript (Form validation, AJAX)
- jQuery (DOM manipulation)
- Chart.js (Data visualization)
- PHPMailer (Email notifications)

## Installation Requirements
- Apache/Nginx web server
- PHP 7.4 or higher
- MySQL 8.0 or higher
- phpMyAdmin (recommended)
- mod_rewrite enabled

## Setup Instructions
1. Import database.sql into MySQL
2. Configure database connection in config/database.php
3. Set proper file permissions for uploads directory
4. Access index.php through web browser
5. Default admin login: admin@library.com / password123

## API Endpoints
- GET /api/books - List all books
- POST /api/books - Add new book
- GET /api/books/{id} - Get book details
- PUT /api/books/{id} - Update book
- DELETE /api/books/{id} - Delete book
- POST /api/auth/login - User login
- POST /api/auth/logout - User logout

## Browser Compatibility
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- Internet Explorer 11+

## Performance Optimizations
- Database query optimization
- Connection pooling
- Caching strategies
- Image optimization
- Minified CSS/JS
- Gzip compression
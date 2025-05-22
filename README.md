# Travel Booking System

A comprehensive travel booking and management website developed using PHP, HTML, CSS, and JavaScript. The system includes both a public-facing user interface and an admin dashboard to manage all travel-related data.

## Features

### Public Features
- User registration and login system
- OAuth integration with GitHub and Google
- Password reset functionality
- Browse and search travel offers
- Make bookings
- View booking history
- Contact form for support

### Admin Features
- Secure admin authentication
- Add, edit, and delete travel offers
- View and manage bookings
- Search and filter reservations
- View customer information

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for OAuth dependencies)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/travel-booking.git
cd travel-booking
```

2. Create a MySQL database and import the schema:
```bash
mysql -u root -p < database/schema.sql
```

3. Configure the database connection:
- Copy `config.example.php` to `config.php`
- Update the database credentials in `config.php`

4. Set up OAuth credentials:
- Create a GitHub OAuth application
- Create a Google OAuth application
- Update the OAuth configuration in `config.php`

5. Configure your web server:
- Point the document root to the project directory
- Ensure mod_rewrite is enabled (for Apache)
- Set proper file permissions

6. Install dependencies:
```bash
composer install
```

## Directory Structure

```
travel-booking/
├── css/
│   └── style.css
├── images/
├── js/
│   └── main.js
├── oauth/
│   ├── github.php
│   └── google.php
├── config.php
├── index.html
├── login.php
├── register.php
├── admin.php
├── about.html
├── contact.html
└── README.md
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- Secure session management
- OAuth 2.0 for social login
- CSRF protection
- XSS prevention

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@travelbooking.com or use the contact form on the website.

## Acknowledgments

- PHP Documentation
- MySQL Documentation
- OAuth 2.0 Documentation
- Various open-source libraries and frameworks 
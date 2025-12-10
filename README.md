# 🏠 House Rent - Property Rental Management System

Welcome to **House Rent**, a comprehensive web-based platform designed to simplify the process of finding and renting properties in Bangladesh. This system connects tenants with landlords, making property rental transparent, efficient, and hassle-free.

![House Rent Banner](https://via.placeholder.com/1200x400?text=House+Rent+Platform)

## ✨ Features

### 🔍 For Tenants
- **Advanced Property Search**: Filter by location, price range, property type, and amenities.
- **Detailed Listings**: View high-quality images, video tours, floor plans, and detailed descriptions.
- **Direct Messaging**: Contact landlords securely through the platform.
- **Booking System**: Request visits and book properties online.
- **User Dashboard**: Manage saved properties, booking requests, and profile settings.

### 🏢 For Landlords & Admins
- **Property Management**: Add, edit, and remove property listings with ease.
- **Booking Management**: Accept or reject viewing requests and rental applications.
- **Admin Dashboard**: Comprehensive overview of users, properties, and bookings.
- **Message Center**: specific centralized inbox for all inquiries (recently upgraded).
- **Secure Authentication**: Robust login and registration system.

### 🛠️ Technical Highlights
- **Backend**: Built with PHP (Native) using PDO for secure database interactions.
- **Frontend**: HTML5, CSS3, JavaScript, and Bootstrap 5 for a responsive design.
- **Database**: MySQL relational database for structured data storage.
- **Email System**: Integrated SMTP support (Mailtrap) for reliable notifications:
  - Welcome emails
  - Password resets
  - Booking confirmations
- **Security**:
  - CSRF protection
  - Input sanitization
  - Password hashing (Bcrypt)
  - SQL injection prevention via prepared statements

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server (XAMPP/WAMP/MAMP)
- Composer (for PHPMailer dependency)

### Steps
1.  **Clone the Repository**
    ```bash
    git clone https://github.com/asif-newera/house-rent.git
    cd house-rent
    ```

2.  **Database Setup**
    - Open `config/config.php` and configure your database settings.
    - Import the database schema from `database/house_rent.sql` (if available) or standard migration files.

3.  **Email Configuration**
    - The system uses Mailtrap for testing emails.
    - Open `config/email.php` (create if missing from example) and add your credentials:
      ```php
      define('SMTP_USERNAME', 'your_mailtrap_username');
      define('SMTP_PASSWORD', 'your_mailtrap_password');
      ```

4.  **Install Dependencies**
    ```bash
    composer install
    ```

5.  **Run the Application**
    - Start Apache and MySQL from XAMPP control panel.
    - Visit `http://localhost/house-rent` in your browser.

## 🤝 Contributing

Contributions are welcome! If you find any bugs or want to add new features, please open an issue or submit a pull request.

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Contact

**Asif Newera**
- GitHub: [@asif-newera](https://github.com/asif-newera)
- Email: contact@swapnonibash.com

---
*Built with ❤️ in 2025*

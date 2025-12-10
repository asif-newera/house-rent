# Admin Panel Documentation

## Overview
This is a modern, professional admin panel system for the House Rent application with beautiful UI/UX design.

## Features
- ✅ Modern, clean admin dashboard with statistics and charts
- ✅ Complete CRUD operations for properties, users, and bookings
- ✅ Beautiful UI with Bootstrap 5 and custom CSS
- ✅ Interactive charts using Chart.js
- ✅ Secure authentication with session management
- ✅ CSRF protection on all forms
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ SQL injection protection (PDO prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ Search and filter functionality
- ✅ Pagination for large datasets

## Initial Setup

### Step 1: Database Setup
1. Navigate to `/HOUSE RENT/database/run_admin_schema.php` in your browser
2. This will create/update all necessary database tables
3. A default admin user will be created with credentials:
   - **Email**: admin@houserent.com
   - **Password**: password
4. **IMPORTANT**: Change the default password immediately after first login!

### Step 2: Login
1. Navigate to `/HOUSE RENT/admin/login.php`
2. Use the default credentials (see above)
3. You will be redirected to the dashboard

## Admin Panel Pages

### Dashboard (`dashboard.php`)
- Overview of key statistics
- Revenue and booking charts
- Recent properties, users, and bookings
- Quick access to all management sections

### Properties Management (`properties.php`)
- View all properties in a table
- Add new properties
- Edit existing properties
- Delete properties
- Search and filter by status
- Pagination support

### Users Management (`users.php`)
- View all registered users
- Toggle user active/inactive status
- Delete users
- Search functionality
- Pagination support

### Bookings Management (`bookings.php`)
- View all bookings
- Update booking status (pending, confirmed, cancelled, completed)
- Delete bookings
- Filter by status
- Pagination support

## Security Features

### Authentication
- Secure session management
- Password hashing with `password_hash()`
- Remember me functionality (30 days)
- Account lockout after 5 failed login attempts (15 minutes)

### CSRF Protection
- All forms include CSRF tokens
- Tokens verified on form submission
- Prevents cross-site request forgery attacks

### SQL Injection Protection
- All database queries use PDO prepared statements
- No direct SQL injection possible

### XSS Protection
- All user input sanitized with `htmlspecialchars()`
- Prevents cross-site scripting attacks

## File Structure

```
admin/
├── assets/
│   ├── css/
│   │   └── admin.css          # Custom admin styles
│   └── js/
│       └── admin.js           # Custom admin scripts
├── includes/
│   ├── header.php             # Admin header (if needed)
│   ├── footer.php             # Admin footer (if needed)
│   └── functions.php          # Admin-specific functions
├── dashboard.php              # Main dashboard
├── properties.php             # Properties management
├── users.php                  # Users management
├── bookings.php               # Bookings management
├── login.php                  # Admin login
├── logout.php                 # Admin logout
└── README.md                  # This file
```

## Design Guidelines

### Color Scheme
- **Primary**: #4e73df (Blue)
- **Success**: #1cc88a (Green)
- **Info**: #36b9cc (Teal)
- **Warning**: #f6c23e (Yellow)
- **Danger**: #e74a3b (Red)

### Typography
- Font Family: 'Nunito', sans-serif
- Weights: 400 (normal), 600 (semi-bold), 700 (bold), 800 (extra-bold)

### Components
- Bootstrap 5.3.0 for responsive layout
- Bootstrap Icons 1.10.5 for iconography
- Chart.js 4.4.0 for data visualization
- Custom CSS for unique styling

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Can't Login
1. Check that database is properly set up
2. Run `/database/run_admin_schema.php` again
3. Ensure PHP session is working
4. Check browser console for JavaScript errors

### Charts Not Loading
1. Ensure internet connection (Chart.js loaded from CDN)
2. Check browser console for errors
3. Verify database has data for charts

### Styling Issues
1. Clear browser cache
2. Ensure all CSS files are loaded
3. Check browser console for 404 errors

## Development

### Adding New Admin Pages
1. Create new PHP file in `/admin/` directory
2. Include authentication check at top:
   ```php
   if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
       header('Location: login.php');
       exit;
   }
   ```
3. Use same sidebar and topbar structure
4. Include custom CSS and JS files

### Customizing Styles
- Edit `/admin/assets/css/admin.css`
- Follow existing naming conventions
- Use CSS variables for colors

### Adding Charts
- Use Chart.js library (already included)
- Follow examples in `dashboard.php`
- See Chart.js documentation: https://www.chartjs.org/

## Support
For issues or questions, please contact the development team.

## Version
Current Version: 1.0.0
Last Updated: December 2024

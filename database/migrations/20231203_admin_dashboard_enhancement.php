<?php
// Database migration for admin dashboard enhancements
$migration = new class {
    public function up($pdo) {
        // Skip session and CSRF initialization in CLI mode
        if (php_sapi_name() !== 'cli') {
            require_once __DIR__ . '/../../config/config.php';
        }
        
        // Create roles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create permissions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create role_permissions junction table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )
        ");
        
        // Create user_roles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            )
        ");
        
        // Create bookings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                property_id INT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
            )
        ");
        
        // Create analytics table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                metric VARCHAR(50) NOT NULL,
                value INT NOT NULL,
                recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert initial roles
        $pdo->exec("
            INSERT INTO roles (name, description) VALUES
            ('super_admin', 'Full access to all features'),
            ('manager', 'Manage properties and bookings'),
            ('support', 'Handle user support requests')
        ");
        
        // Insert initial permissions
        $pdo->exec("
            INSERT INTO permissions (name, description) VALUES
            ('manage_users', 'Create, edit and delete users'),
            ('manage_properties', 'Create, edit and delete properties'),
            ('manage_bookings', 'View and manage property bookings'),
            ('view_analytics', 'Access analytics dashboard'),
            ('manage_settings', 'Change system settings')
        ");
        
        // Assign permissions to roles
        $pdo->exec("
            INSERT INTO role_permissions (role_id, permission_id) VALUES
            (1, 1), (1, 2), (1, 3), (1, 4), (1, 5),  // Super admin gets all
            (2, 2), (2, 3), (2, 4),                   // Manager gets property, booking and analytics access
            (3, 3)                                    // Support gets booking access
        ");
        
        // Add is_admin column to users table if not exists
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT 0");
        
        return true;
    }

    public function down($pdo) {
        // Drop tables in reverse order
        $tables = [
            'role_permissions',
            'user_roles',
            'permissions',
            'roles',
            'bookings',
            'analytics'
        ];
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS $table");
        }
        
        return true;
    }
};

return $migration;

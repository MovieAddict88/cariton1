<?php
/**
 * Auto Installation Script
 * Car Rental System - Complete Database Setup
 */

$step = $_GET['step'] ?? 1;
$errors = [];
$success = false;

// Database connection parameters
$db_host = $_POST['db_host'] ?? 'localhost';
$db_name = $_POST['db_name'] ?? 'car_rental_db';
$db_user = $_POST['db_user'] ?? 'root';
$db_pass = $_POST['db_pass'] ?? '';
$admin_user = $_POST['admin_user'] ?? 'admin';
$admin_pass = $_POST['admin_pass'] ?? 'admin123';
$admin_email = $_POST['admin_email'] ?? 'admin@carrental.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Connect without database first
        $pdo = new PDO(
            "mysql:host=$db_host",
            $db_user,
            $db_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Create tables
        $sql = "
-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(50),
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    vin_number VARCHAR(50) UNIQUE,
    vehicle_type ENUM('sedan', 'suv', 'luxury', 'electric', 'van', 'truck') DEFAULT 'sedan',
    transmission ENUM('automatic', 'manual') DEFAULT 'automatic',
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    seats INT DEFAULT 5,
    luggage_capacity INT DEFAULT 2,
    daily_rate DECIMAL(10,2) NOT NULL,
    images JSON,
    features JSON,
    status ENUM('available', 'rented', 'reserved', 'maintenance', 'out_of_service') DEFAULT 'available',
    mileage INT DEFAULT 0,
    last_service_date DATE,
    next_service_date DATE,
    insurance_expiry DATE,
    registration_expiry DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (vehicle_type),
    INDEX idx_plate (plate_number)
);

-- Drivers Table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    license_expiry DATE NOT NULL,
    license_image VARCHAR(255),
    phone VARCHAR(20) NOT NULL,
    emergency_contact VARCHAR(20),
    emergency_name VARCHAR(100),
    experience_years INT DEFAULT 0,
    status ENUM('active', 'inactive', 'suspended', 'on_leave') DEFAULT 'active',
    hire_date DATE NOT NULL,
    assigned_vehicle_id INT,
    rating DECIMAL(3,2) DEFAULT 0,
    total_trips INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    INDEX idx_license (license_number),
    INDEX idx_status (status),
    INDEX idx_employee (employee_id)
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    vehicle_id INT,
    driver_id INT,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255) NOT NULL,
    pickup_date DATETIME NOT NULL,
    dropoff_date DATETIME NOT NULL,
    actual_pickup DATETIME,
    actual_dropoff DATETIME,
    rental_days INT GENERATED ALWAYS AS (DATEDIFF(dropoff_date, pickup_date)) STORED,
    daily_rate DECIMAL(10,2) NOT NULL,
    insurance_amount DECIMAL(10,2) DEFAULT 0,
    service_fee DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    downpayment_amount DECIMAL(10,2),
    downpayment_paid TINYINT(1) DEFAULT 0,
    payment_status ENUM('pending', 'partial', 'paid', 'refunded', 'cancelled') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    special_requests TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_ref (reference_number),
    INDEX idx_user (user_id),
    INDEX idx_status (booking_status),
    INDEX idx_dates (pickup_date, dropoff_date)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PHP',
    payment_method ENUM('gcash', 'paymaya', 'cash', 'credit_card', 'bank_transfer') NOT NULL,
    payment_type ENUM('downpayment', 'full_payment', 'additional', 'refund') NOT NULL,
    transaction_reference VARCHAR(100),
    proof_of_payment VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected', 'refunded') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_status (status),
    INDEX idx_transaction (transaction_reference)
);

-- Attachments Table
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    driver_id INT,
    vehicle_id INT,
    payment_id INT,
    user_id INT,
    file_type ENUM('image', 'document', 'receipt', 'license', 'insurance', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    description TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_type (file_type)
);

-- Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    vehicle_id INT,
    driver_id INT,
    overall_rating DECIMAL(2,1) NOT NULL,
    vehicle_rating DECIMAL(2,1) NOT NULL,
    driver_rating DECIMAL(2,1) NOT NULL,
    service_rating DECIMAL(2,1) NOT NULL,
    vehicle_comment TEXT,
    driver_comment TEXT,
    service_comment TEXT,
    vehicle_images JSON,
    is_verified TINYINT(1) DEFAULT 1,
    is_public TINYINT(1) DEFAULT 1,
    admin_response TEXT,
    responded_by INT,
    responded_at TIMESTAMP NULL,
    status ENUM('pending', 'published', 'hidden', 'flagged') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'staff',
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- System Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    key_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Company Information Table
CREATE TABLE IF NOT EXISTS company_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    business_hours VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pricing Settings Table
CREATE TABLE IF NOT EXISTS pricing_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_type VARCHAR(50) UNIQUE,
    base_price DECIMAL(10,2),
    price_per_hour DECIMAL(10,2),
    late_fee DECIMAL(10,2),
    cleaning_fee DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity Log Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    entity_type VARCHAR(50),
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'booking', 'payment', 'review') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);
        ";
        
        $pdo->exec($sql);
        
        // Insert default admin user
        $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'super_admin')");
        $stmt->execute([$admin_user, $admin_email, $hashed_pass, 'System', 'Administrator']);
        
        // Insert default settings
        $settings = [
            ['site_name', 'VeloDrive Car Rental', 'Site name'],
            ['currency', 'PHP', 'Default currency'],
            ['downpayment_percentage', '20', 'Downpayment percentage'],
            ['insurance_daily_rate', '150', 'Daily insurance rate'],
            ['service_fee', '50', 'Service fee per booking'],
            ['max_rental_days', '30', 'Maximum rental days'],
            ['cancelation_policy', 'Free cancellation up to 24 hours before pickup', 'Cancellation policy'],
        ];
        
        foreach ($settings as $setting) {
            $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value, description) VALUES (?, ?, ?)");
            $stmt->execute($setting);
        }
        
        // Insert default company info
        $stmt = $pdo->prepare("INSERT INTO company_info (id, company_name, address, phone, email, business_hours) VALUES (1, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'VeloDrive Car Rental Services',
            '123 Business Avenue, Metro Manila, Philippines',
            '+63 2 8123 4567',
            'info@velodrive.com',
            'Mon-Sun: 6:00 AM - 10:00 PM'
        ]);

        // Insert default pricing settings
        $pricing = [
            ['sedan', 1500.00, 200.00, 300.00, 150.00],
            ['suv', 2500.00, 350.00, 500.00, 250.00],
            ['luxury', 5000.00, 750.00, 1000.00, 500.00],
            ['electric', 2000.00, 300.00, 450.00, 200.00],
        ];

        foreach ($pricing as $p) {
            $stmt = $pdo->prepare("INSERT INTO pricing_settings (setting_type, base_price, price_per_hour, late_fee, cleaning_fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($p);
        }

        // Insert sample vehicles
        $vehicles = [
            ['Tesla', 'Model 3', 2023, 'White', 'ABC-1234', 'electric', 'automatic', 'electric', 5, 85.00],
            ['BMW', 'X5', 2022, 'Black', 'XYZ-9876', 'suv', 'automatic', 'hybrid', 7, 120.00],
            ['Mercedes', 'S-Class', 2023, 'Silver', 'M-BW-450', 'luxury', 'automatic', 'petrol', 5, 250.00],
            ['Toyota', 'Camry', 2022, 'Blue', 'TC-2023', 'sedan', 'automatic', 'hybrid', 5, 75.00],
            ['Porsche', '911 Carrera', 2023, 'Red', 'PC-9111', 'luxury', 'automatic', 'petrol', 2, 350.00],
        ];
        
        foreach ($vehicles as $v) {
            $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, color, plate_number, vehicle_type, transmission, fuel_type, seats, daily_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($v);
        }
        
        // Insert sample drivers
        $drivers = [
            ['Johnathan', 'Doe', 'TX-882190', '2028-10-15', 8],
            ['Sarah', 'Jenkins', 'NY-441203', '2024-01-25', 5],
            ['Michael', 'Chen', 'CA-993011', '2027-06-20', 10],
            ['Robert', 'Wilson', 'FL-221098', '2026-03-15', 6],
        ];
        
        foreach ($drivers as $i => $d) {
            $stmt = $pdo->prepare("INSERT INTO drivers (employee_id, first_name, last_name, license_number, license_expiry, experience_years, phone, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURDATE())");
            $stmt->execute([
                'DRV-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                $d[0], $d[1], $d[2], $d[3], $d[4], '+639' . rand(100000000, 999999999)
            ]);
        }
        
        $success = true;
        
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if ($e->getCode() == 2002 || strpos($msg, '2002') !== false) {
            $msg .= ". Hint: If using 'localhost', try '127.0.0.1'. On InfinityFree, use the specific MySQL hostname provided in your control panel.";
        }
        $errors[] = "Installation failed: " . $msg;
    }

    if ($success) {
        // Automatically update config.php
        $config_file = 'includes/config.php';
        if (file_exists($config_file)) {
            // Try to make it writable
            chmod($config_file, 0666);

            $config_content = file_get_contents($config_file);
            $config_content = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '$db_host');", $config_content);
            $config_content = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '$db_name');", $config_content);
            $config_content = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '$db_user');", $config_content);
            $config_content = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '$db_pass');", $config_content);

            if (file_put_contents($config_file, $config_content)) {
                chmod($config_file, 0644); // Set back to secure permissions
            } else {
                $errors[] = "Warning: Could not automatically update includes/config.php. Please update it manually with the provided credentials.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental System - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    
        /* Enhanced responsive design for all devices */
        @media (min-width: 280px) {
            :root {
                font-size: clamp(12px, 3vw, 16px);
            }
        }
        @media (min-width: 640px) {
            :root {
                font-size: clamp(14px, 2vw, 16px);
            }
        }
        @media (min-width: 1024px) {
            :root {
                font-size: clamp(15px, 1.2vw, 18px);
            }
        }
        @media (min-width: 1920px) {
            :root {
                font-size: clamp(16px, 1vw, 20px);
            }
        }
        @media (min-width: 2560px) {
            :root {
                font-size: clamp(18px, 0.8vw, 24px);
            }
        }
        
        .container-responsive {
            max-width: clamp(280px, 100%, 1920px);
            margin: 0 auto;
            padding: clamp(0.5rem, 2vw, 2rem);
        }
        
        .text-responsive {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        .heading-responsive {
            font-size: clamp(1.25rem, 3vw, 2rem);
        }

    </style>
</head>
<body class="bg-gradient-to-br from-blue-600 to-blue-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8">
        <div class="text-center mb-8">
            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl text-blue-600">directions_car</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Car Rental System</h1>
            <p class="text-gray-500 mt-2">Installation Wizard</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-green-600">check</span>
                </div>
                <h2 class="text-xl font-bold text-green-800 mb-2">Installation Complete!</h2>
                <p class="text-green-700 mb-4">Your car rental system has been successfully installed.</p>
                <div class="bg-white rounded-lg p-4 mb-4 text-left text-sm">
                    <p><strong>Admin Panel:</strong> <a href="index.php" class="text-blue-600 hover:underline">admin/index.php</a></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($admin_user) ?></p>
                    <p><strong>Password:</strong> <?= htmlspecialchars($admin_pass) ?></p>
                </div>
                <a href="index.php" class="inline-block bg-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-green-700 transition">
                    Go to Admin Panel →
                </a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-red-700 text-sm"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($db_host) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($db_name) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database User</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($db_user) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars($db_pass ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <hr class="border-gray-200">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Username</label>
                    <input type="text" name="admin_user" value="<?= htmlspecialchars($admin_user) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Password</label>
                        <input type="password" name="admin_pass" value="<?= htmlspecialchars($admin_pass) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars($admin_email) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <button type="submit" name="install" class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition mt-6">
                    Install System
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>This will create all necessary tables and default data.</p>
        </div>
    </div>
</body>
</html>

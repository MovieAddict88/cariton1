<?php
/**
 * Self-healing Database Migrations
 * Automatically ensures the database schema matches the expected state.
 */

try {
    $pdo = getDBConnection();

    // 1. Ensure 'archived' status exists in drivers table
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'status'");
    $column = $stmt->fetch();

    if ($column && strpos($column['Type'], "'archived'") === false) {
        $currentType = $column['Type'];
        $newType = str_replace(")", ",'archived')", $currentType);
        $pdo->exec("ALTER TABLE drivers MODIFY COLUMN status $newType");
    }

    // 2. Ensure hire_date column exists in drivers table
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'hire_date'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN hire_date DATE NULL AFTER status");
        $pdo->exec("UPDATE drivers SET hire_date = CURDATE() WHERE hire_date IS NULL");
    }

    // Ensure 'email' column exists in drivers table
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'email'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN email VARCHAR(255) UNIQUE NULL AFTER last_name");
    }

    // 3. Ensure favorites table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, vehicle_id)
    )");

    // 4. Ensure is_featured column exists in vehicles table
    $stmt = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'is_featured'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER status");
        // Mark some vehicles as featured by default if any exist
        $pdo->exec("UPDATE vehicles SET is_featured = 1 LIMIT 3");
    }

    // 5. Ensure pickup location details exist in bookings table
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'pickup_description'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN pickup_description TEXT NULL AFTER pickup_location");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN pickup_latitude DECIMAL(10, 8) NULL AFTER pickup_description");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN pickup_longitude DECIMAL(11, 8) NULL AFTER pickup_latitude");
    }

    // Ensure dropoff location details exist in bookings table
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'dropoff_description'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN dropoff_description TEXT NULL AFTER dropoff_location");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN dropoff_latitude DECIMAL(10, 8) NULL AFTER dropoff_description");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN dropoff_longitude DECIMAL(11, 8) NULL AFTER dropoff_latitude");
    }

    // 6. Ensure balance_amount column exists in bookings table
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'balance_amount'");
    if (!$stmt->fetch()) {
        try {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN balance_amount DECIMAL(10, 2) GENERATED ALWAYS AS (total_amount - downpayment_amount) STORED AFTER downpayment_amount");
        } catch (Exception $e) {
            // Fallback for older MySQL versions that don't support generated columns
            $pdo->exec("ALTER TABLE bookings ADD COLUMN balance_amount DECIMAL(10, 2) NULL AFTER downpayment_amount");
            $pdo->exec("UPDATE bookings SET balance_amount = total_amount - downpayment_amount");
        }
    }

} catch (Exception $e) {
    error_log("Migration failed: " . $e->getMessage());
}
?>

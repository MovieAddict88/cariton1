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
        // Current ENUM doesn't have 'archived'. We need to add it.
        // column['Type'] looks like: enum('active','inactive','suspended','on_leave')
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

} catch (Exception $e) {
    // We fail silently to avoid breaking the UI, but in a real app we'd log this.
    error_log("Migration failed: " . $e->getMessage());
}
?>

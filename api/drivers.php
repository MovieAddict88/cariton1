<?php
/**
 * Drivers API Endpoint
 * GET - Get list of available drivers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../admin/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDBConnection();

        $pickup_date = $_GET['pickup_date'] ?? null;
        $dropoff_date = $_GET['dropoff_date'] ?? null;

        $query = "SELECT id, first_name, last_name, phone, employee_id, experience_years, rating, status FROM drivers WHERE status = 'active'";
        $params = [];

        if ($pickup_date && $dropoff_date) {
            // Filter out drivers who are already booked for the given period
            $query .= " AND id NOT IN (
                SELECT DISTINCT driver_id FROM bookings
                WHERE driver_id IS NOT NULL
                AND booking_status IN ('pending', 'confirmed', 'active')
                AND NOT (dropoff_date < ? OR pickup_date > ?)
            )";
            $params[] = $pickup_date;
            $params[] = $dropoff_date;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $drivers = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $drivers
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

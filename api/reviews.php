<?php
/**
 * Reviews API Endpoint
 * POST - Submit a new review
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../admin/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $booking_id = $input['booking_id'] ?? null;
    $user_id = $input['user_id'] ?? null;
    $overall_rating = $input['overall_rating'] ?? 5;
    $vehicle_rating = $input['vehicle_rating'] ?? 5;
    $driver_rating = $input['driver_rating'] ?? 5;
    $service_rating = $input['service_rating'] ?? 5;
    $vehicle_comment = $input['vehicle_comment'] ?? '';
    $driver_comment = $input['driver_comment'] ?? '';
    $service_comment = $input['service_comment'] ?? '';

    if (!$booking_id || !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $pdo = getDBConnection();

    // Get booking details to get vehicle_id and driver_id
    $stmt = $pdo->prepare("SELECT vehicle_id, driver_id FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }

    // Insert review
    $stmt = $pdo->prepare("
        INSERT INTO reviews (
            booking_id, user_id, vehicle_id, driver_id,
            overall_rating, vehicle_rating, driver_rating, service_rating,
            vehicle_comment, driver_comment, service_comment,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW())
    ");

    $stmt->execute([
        $booking_id,
        $user_id,
        $booking['vehicle_id'],
        $booking['driver_id'],
        $overall_rating,
        $vehicle_rating,
        $driver_rating,
        $service_rating,
        $vehicle_comment,
        $driver_comment,
        $service_comment
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}

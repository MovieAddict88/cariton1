<?php
/**
 * AJAX endpoint for real-time stats refresh
 * Returns JSON data for dashboard and reports
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'includes/config.php';

try {
    $pdo = getDBConnection();
    
    // Get real-time booking stats
    $stmt = $pdo->query("SELECT 
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as bookings_today,
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as bookings_week,
                            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as bookings_month
                         FROM bookings");
    $stats = $stmt->fetch();
    
    // Get fleet stats
    $stmt = $pdo->query("SELECT 
                            COUNT(*) as total_vehicles,
                            COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                            COUNT(CASE WHEN status = 'rented' THEN 1 END) as rented,
                            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance
                         FROM vehicles");
    $fleet_stats = $stmt->fetch();
    
    // Get payments stats
    $stmt = $pdo->query("SELECT COUNT(*) as pending_payments FROM payments WHERE status = 'pending'");
    $payment_stats = $stmt->fetch();
    
    // Get review stats
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reviews GROUP BY status");
    $review_stats = [];
    while ($row = $stmt->fetch()) {
        $review_stats[$row['status']] = $row['count'];
    }
    
    // Get monthly revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as monthly_revenue 
                         FROM bookings 
                         WHERE MONTH(created_at) = MONTH(CURDATE()) 
                         AND booking_status = 'completed'");
    $revenue = $stmt->fetch()['monthly_revenue'] ?? 0;
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => [
            'bookings_today' => (int)($stats['bookings_today'] ?? 0),
            'bookings_week' => (int)($stats['bookings_week'] ?? 0),
            'bookings_month' => (int)($stats['bookings_month'] ?? 0),
            'total_vehicles' => (int)($fleet_stats['total_vehicles'] ?? 0),
            'available_vehicles' => (int)($fleet_stats['available'] ?? 0),
            'rented_vehicles' => (int)($fleet_stats['rented'] ?? 0),
            'maintenance_vehicles' => (int)($fleet_stats['maintenance'] ?? 0),
            'pending_payments' => (int)($payment_stats['pending_payments'] ?? 0),
            'monthly_revenue' => (float)($revenue ?? 0),
            'review_stats' => [
                'pending' => (int)($review_stats['pending'] ?? 0),
                'published' => (int)($review_stats['published'] ?? 0),
                'flagged' => (int)($review_stats['flagged'] ?? 0),
                'hidden' => (int)($review_stats['hidden'] ?? 0)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    // Return zero values if database error
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'data' => [
            'bookings_today' => 0,
            'bookings_week' => 0,
            'bookings_month' => 0,
            'total_vehicles' => 0,
            'available_vehicles' => 0,
            'rented_vehicles' => 0,
            'maintenance_vehicles' => 0,
            'pending_payments' => 0,
            'monthly_revenue' => 0,
            'review_stats' => [
                'pending' => 0,
                'published' => 0,
                'flagged' => 0,
                'hidden' => 0
            ]
        ]
    ]);
}
?>
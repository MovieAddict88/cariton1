<?php
/**
 * Bookings API Endpoint
 * GET - Get user's bookings
 * POST - Create new booking or Cancel booking
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../admin/includes/config.php';

// GET - Retrieve user's bookings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $user_id = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
        $booking_id = $_GET['booking_id'] ?? null;
        
        if (!$user_id && !$booking_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID or Booking ID is required']);
            exit;
        }
        
        $pdo = getDBConnection();
        
        if ($booking_id) {
            $stmt = $pdo->prepare("
                SELECT b.*, v.make, v.model, v.images, v.vehicle_type, v.plate_number, v.daily_rate,
                       v.transmission, v.fuel_type, v.seats,
                       p.status as payment_status, p.amount as payment_amount, p.payment_method, p.transaction_reference
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Booking not found']);
                exit;
            }

            // Process booking data
            $images = json_decode($booking['images'] ?? '[]', true);
            $booking['vehicle_image'] = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
            $booking['vehicle_name'] = $booking['make'] . ' ' . $booking['model'];
            unset($booking['images']);

            echo json_encode([
                'success' => true,
                'data' => $booking
            ]);
        } else {
            $stmt = $pdo->prepare("
                SELECT b.*, v.make, v.model, v.images, v.vehicle_type, v.plate_number, v.daily_rate,
                       p.status as payment_status, p.amount as payment_amount, p.payment_method
                FROM bookings b
                LEFT JOIN vehicles v ON b.vehicle_id = v.id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $bookings = $stmt->fetchAll();

            // Process bookings data
            foreach ($bookings as &$booking) {
                $images = json_decode($booking['images'] ?? '[]', true);
                if (!is_array($images)) {
                    $images = [];
                }
                $booking['vehicle_image'] = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
                $booking['vehicle_name'] = $booking['make'] . ' ' . $booking['model'];
                unset($booking['images']);
            }

            echo json_encode([
                'success' => true,
                'data' => $bookings
            ]);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'message' => $e->getMessage()
        ]);
    }
}

// POST - Create or Cancel booking
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        $pdo = getDBConnection();

        // Handle Cancellation
        if (isset($input['action']) && $input['action'] === 'cancel') {
            $booking_id = $input['booking_id'] ?? null;
            $user_id = $_SESSION['user_id'] ?? $input['user_id'] ?? null;

            if (!$booking_id || !$user_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Booking ID and User ID are required']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, vehicle_id FROM bookings WHERE id = ? AND user_id = ? AND booking_status IN ('pending', 'confirmed')");
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch();

            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Booking not found or cannot be cancelled']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);

            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
            $stmt->execute([$booking['vehicle_id']]);

            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            exit;
        }

        // Create new booking
        $user_id = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        $vehicle_id = $input['vehicle_id'] ?? null;
        $pickup_date = $input['pickup_date'] ?? null;
        $dropoff_date = $input['dropoff_date'] ?? null;
        $pickup_location = $input['pickup_location'] ?? '';
        $dropoff_location = $input['dropoff_location'] ?? '';
        
        if (!$user_id || !$vehicle_id || !$pickup_date || !$dropoff_date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'available'");
        $stmt->execute([$vehicle_id]);
        $vehicle = $stmt->fetch();
        
        if (!$vehicle) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Vehicle not available']);
            exit;
        }
        
        $pickup = new DateTime($pickup_date);
        $dropoff = new DateTime($dropoff_date);
        $days = max(1, $dropoff->diff($pickup)->days);
        
        $daily_rate = floatval($vehicle['daily_rate']);
        $subtotal = $daily_rate * $days;
        $insurance = $subtotal * 0.075;
        $service_fee = $subtotal * 0.02;
        $total_amount = $subtotal + $insurance + $service_fee;
        $downpayment = $total_amount * 0.20;
        
        $reference_number = 'BK-' . strtoupper(bin2hex(random_bytes(4)));
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                reference_number, user_id, vehicle_id, pickup_date, dropoff_date, 
                pickup_location, dropoff_location, daily_rate, 
                insurance_amount, service_fee, total_amount, downpayment_amount,
                booking_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $reference_number, $user_id, $vehicle_id, $pickup_date, $dropoff_date,
            $pickup_location, $dropoff_location, $daily_rate,
            $insurance, $service_fee, $total_amount, $downpayment
        ]);
        
        $booking_id = $pdo->lastInsertId();
        
        // IMPROVEMENT: Set vehicle status to 'reserved' to prevent double booking
        // but not 'rented' yet as it is not yet active.
        $stmt = $pdo->prepare("UPDATE vehicles SET status = 'reserved' WHERE id = ?");
        $stmt->execute([$vehicle_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => [
                'booking_id' => $booking_id,
                'reference_number' => $reference_number,
                'total_amount' => $total_amount,
                'downpayment_amount' => $downpayment
            ]
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

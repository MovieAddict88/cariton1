<?php
/**
 * Payments API Endpoint
 * POST - Submit payment with proof
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
    // Handle multipart form data (with file upload)
    $booking_id = $_POST['booking_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $reference_number = $_POST['reference_number'] ?? '';
    
    // Validation
    if (!$booking_id || !$amount || !$payment_method) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $pdo = getDBConnection();
    
    // Verify booking exists
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    // Handle proof of payment upload
    $proof_of_payment_url = null;
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof_of_payment'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
        
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP, and PDF are allowed.']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            exit;
        }
        
        // Generate URL (adjust based on your server configuration)
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $proof_of_payment_url = $base_url . '/uploads/payments/' . $filename;
    }
    
    // Generate payment reference
    $payment_ref = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
    
    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            reference_number, booking_id, amount, payment_method, 
            payment_type, proof_of_payment, transaction_reference, 
            status, created_at
        ) VALUES (?, ?, ?, ?, 'downpayment', ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $payment_ref,
        $booking_id,
        $amount,
        $payment_method,
        $proof_of_payment_url,
        $reference_number
    ]);
    
    $payment_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully',
        'data' => [
            'payment_id' => $payment_id,
            'reference_number' => $payment_ref,
            'status' => 'pending',
            'proof_url' => $proof_of_payment_url
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred',
        'message' => $e->getMessage()
    ]);
}

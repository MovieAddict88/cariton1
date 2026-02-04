<?php
/**
 * Image Upload Handler
 * Handles vehicle image uploads
 */

header('Content-Type: application/json');

require_once '../admin/includes/config.php';

// Check if file was uploaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No image file provided'
    ]);
    exit;
}

$file = $_FILES['image'];
$uploadDir = '../uploads/vehicles/';

// Validate file
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.'
    ]);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'File size exceeds 5MB limit'
    ]);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload error occurred'
    ]);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'vehicle_' . uniqid() . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Get the base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));
    $imageUrl = $baseUrl . '/uploads/vehicles/' . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $imageUrl,
        'filename' => $filename,
        'path' => 'uploads/vehicles/' . $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save uploaded file'
    ]);
}

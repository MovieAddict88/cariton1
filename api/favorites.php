<?php
/**
 * Favorites API Endpoint
 * Handles adding, removing, and listing user favorites
 */

header('Content-Type: application/json');
require_once '../admin/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // List user favorites
        $stmt = $pdo->prepare("
            SELECT v.* FROM vehicles v
            JOIN favorites f ON v.id = f.vehicle_id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll();

        foreach ($favorites as &$vehicle) {
            $images = json_decode($vehicle['images'] ?? '[]', true);
            $vehicle['images'] = is_array($images) ? $images : [];
            $vehicle['display_image'] = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
            $vehicle['is_favorited'] = true;
        }

        echo json_encode(['success' => true, 'data' => $favorites]);

    } elseif ($method === 'POST') {
        // Toggle favorite
        $data = json_decode(file_get_contents('php://input'), true);
        $vehicle_id = $data['vehicle_id'] ?? null;

        if (!$vehicle_id) {
            echo json_encode(['success' => false, 'error' => 'Vehicle ID is required']);
            exit;
        }

        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND vehicle_id = ?");
        $stmt->execute([$user_id, $vehicle_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Remove from favorites
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND vehicle_id = ?");
            $stmt->execute([$user_id, $vehicle_id]);
            echo json_encode(['success' => true, 'action' => 'removed', 'is_favorited' => false]);
        } else {
            // Add to favorites
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, vehicle_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $vehicle_id]);
            echo json_encode(['success' => true, 'action' => 'added', 'is_favorited' => true]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'message' => $e->getMessage()]);
}

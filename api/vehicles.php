<?php
/**
 * Vehicles API Endpoint
 * Returns vehicles data as JSON for the user website
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../admin/includes/config.php';

try {
    $pdo = getDBConnection();
    
    // Get query parameters
    $status = $_GET['status'] ?? 'available';
    $vehicle_type = $_GET['type'] ?? null;
    $search = $_GET['search'] ?? null;
    $featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Build query
    $conditions = [];
    $params = [];
    
    if ($status) {
        $conditions[] = "status = ?";
        $params[] = $status;
    }
    
    if ($vehicle_type) {
        $conditions[] = "vehicle_type = ?";
        $params[] = $vehicle_type;
    }
    
    if ($search) {
        $conditions[] = "(make LIKE ? OR model LIKE ? OR description LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($featured !== null) {
        // Check if is_featured column exists to avoid SQL error
        $checkCol = $pdo->query("SHOW COLUMNS FROM vehicles LIKE 'is_featured'");
        if ($checkCol->rowCount() > 0) {
            $conditions[] = "is_featured = ?";
            $params[] = $featured ? 1 : 0;
        }
    }
    
    $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM vehicles {$whereClause}";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get vehicles
    $query = "SELECT * FROM vehicles {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    // Explicitly bind limit and offset as integers for better compatibility
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param);
    }
    $stmt->bindValue($paramIndex++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $vehicles = $stmt->fetchAll();
    
    // Process vehicles data
    foreach ($vehicles as &$vehicle) {
        // Parse images JSON
        $images = json_decode($vehicle['images'] ?? '[]', true);
        if (!is_array($images)) {
            $images = [];
        }
        $vehicle['images'] = $images;
        
        // Add display image (first image or fallback)
        $vehicle['display_image'] = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&q=80&w=400';
        
        // Format price
        $vehicle['daily_rate_formatted'] = formatCurrency($vehicle['daily_rate'], DEFAULT_CURRENCY);
        
        // Convert boolean fields
        $vehicle['is_featured'] = (bool)$vehicle['is_featured'];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => $vehicles,
        'meta' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($vehicles)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

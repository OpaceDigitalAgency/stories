<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../lib/ai/core/Config.php';

try {
    $db = new PDO(
        "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
        "stories_user",
        '$tw1cac3+sOt'
    );

    // Get page number from query string
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;

    // Get total count
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM ai_generations 
        WHERE type = 'image'
    ");
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();

    // Get generations with provider information
    $stmt = $db->prepare("
        SELECT 
            g.*,
            p.name as provider_name,
            u.cost
        FROM ai_generations g
        LEFT JOIN ai_providers p ON g.provider_id = p.id
        LEFT JOIN ai_usage u ON u.provider_id = p.id 
            AND DATE(u.created_at) = DATE(g.created_at)
        WHERE g.type = 'image'
        ORDER BY g.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $generations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse metadata JSON
        $metadata = json_decode($row['metadata'], true) ?? [];
        
        // Build generation record
        $generations[] = [
            'id' => $row['id'],
            'prompt' => $row['prompt'],
            'result_url' => $row['result_url'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'provider' => $row['provider_name'],
            'metadata' => array_merge($metadata, [
                'cost' => (float)$row['cost']
            ])
        ];
    }

    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;

    echo json_encode([
        'success' => true,
        'generations' => $generations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalCount,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]);

} catch (PDOException $e) {
    error_log("History API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch generation history'
    ]);
}
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

    // Get today's usage
    $todayStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM ai_generations
        WHERE DATE(created_at) = CURDATE()
        AND type = 'image'
    ");
    $todayStmt->execute();
    $todayCount = $todayStmt->fetchColumn();

    // Get monthly usage
    $monthlyStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM ai_generations
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND type = 'image'
    ");
    $monthlyStmt->execute();
    $monthlyCount = $monthlyStmt->fetchColumn();

    // Get total cost
    $costStmt = $db->prepare("
        SELECT COALESCE(SUM(cost), 0) as total_cost
        FROM ai_usage
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND type = 'image'
    ");
    $costStmt->execute();
    $totalCost = (float)$costStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'today' => $todayCount,
        'monthly' => $monthlyCount,
        'cost' => $totalCost
    ]);

} catch (PDOException $e) {
    error_log("Usage API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch usage information'
    ]);
}
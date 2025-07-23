<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get active banner
    $stmt = $pdo->prepare("SELECT script_code FROM banner_ads WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $banner = $stmt->fetch();
    
    if ($banner) {
        echo json_encode([
            'success' => true,
            'script_code' => $banner['script_code']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active banner found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
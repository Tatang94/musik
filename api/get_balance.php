<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$userIp = $_SERVER['REMOTE_ADDR'];

try {
    $user = getOrCreateUser($pdo, $userIp);
    
    echo json_encode([
        'success' => true,
        'balance' => $user['balance'],
        'total_minutes' => $user['total_minutes']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

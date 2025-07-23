<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$userIp = $_SERVER['REMOTE_ADDR'];

if (!isset($input['song_id']) || !isset($input['minutes_listened']) || !isset($input['reward_earned'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $user = getOrCreateUser($pdo, $userIp);
    
    // Update user balance and total minutes
    $newBalance = $user['balance'] + $input['reward_earned'];
    $newTotalMinutes = $user['total_minutes'] + $input['minutes_listened'];
    
    $stmt = $pdo->prepare("UPDATE users SET balance = ?, total_minutes = ? WHERE user_ip = ?");
    $stmt->execute([$newBalance, $newTotalMinutes, $userIp]);
    
    // Update listening session
    $stmt = $pdo->prepare("
        UPDATE listening_sessions 
        SET minutes_listened = ?, reward_earned = ? 
        WHERE user_ip = ? AND song_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$input['minutes_listened'], $input['reward_earned'], $userIp, $input['song_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'new_balance' => $newBalance,
        'total_minutes' => $newTotalMinutes
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

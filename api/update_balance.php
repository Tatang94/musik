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
    $user = getOrCreateUser($pdo, $userIp);
    
    // Calculate new balance
    $newBalance = $user['balance'] + $input['reward_earned'];
    
    // Update user balance
    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE user_ip = ?");
    $stmt->execute([$newBalance, $userIp]);
    
    // Update listening session with final stats (get the latest session for this user and song)
    $stmt = $pdo->prepare("
        UPDATE listening_sessions 
        SET minutes_listened = ?, reward_earned = ?, completed = 1
        WHERE id = (
            SELECT id FROM listening_sessions 
            WHERE user_ip = ? AND song_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        )
    ");
    $stmt->execute([$input['minutes_listened'], $input['reward_earned'], $userIp, $input['song_id']]);
    
    echo json_encode([
        'success' => true,
        'new_balance' => $newBalance,
        'reward_added' => $input['reward_earned'],
        'minutes_listened' => $input['minutes_listened']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
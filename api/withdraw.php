<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$userIp = $_SERVER['REMOTE_ADDR'];

if (!isset($input['dana_phone']) || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $user = getOrCreateUser($pdo, $userIp);
    
    // Validate withdrawal amount
    if ($input['amount'] < 10000) {
        throw new Exception('Minimal penarikan Rp 10.000');
    }
    
    if ($input['amount'] > $user['balance']) {
        throw new Exception('Saldo tidak mencukupi');
    }
    
    // Validate Dana phone number
    if (!preg_match('/^08[0-9]{8,11}$/', $input['dana_phone'])) {
        throw new Exception('Format nomor HP Dana tidak valid');
    }
    
    // Create withdrawal request
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_ip, dana_phone, amount) VALUES (?, ?, ?)");
    $stmt->execute([$userIp, $input['dana_phone'], $input['amount']]);
    
    // Deduct from user balance
    $newBalance = $user['balance'] - $input['amount'];
    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE user_ip = ?");
    $stmt->execute([$newBalance, $userIp]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Permintaan penarikan berhasil disubmit',
        'new_balance' => $newBalance
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

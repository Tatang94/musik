<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $userIp = $_SERVER['REMOTE_ADDR'];
    $amount = floatval($input['amount']);
    $phoneNumber = trim($input['phone_number']);
    
    // Validation
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    if ($amount < 50000) {
        throw new Exception('Minimum withdrawal amount is Rp 50,000');
    }
    
    if (empty($phoneNumber)) {
        throw new Exception('Phone number is required');
    }
    
    // Check user balance
    $user = getOrCreateUser($pdo, $userIp);
    
    if ($user['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }
    
    // Check for pending withdrawals
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_ip = ? AND status = 'pending'");
    $stmt->execute([$userIp]);
    $pendingCount = $stmt->fetchColumn();
    
    if ($pendingCount > 0) {
        throw new Exception('You already have a pending withdrawal request');
    }
    
    // Create withdrawal request
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_ip, amount, phone_number, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$userIp, $amount, $phoneNumber]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal request submitted successfully. Please wait for admin approval.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
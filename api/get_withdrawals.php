<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $userIp = $_SERVER['REMOTE_ADDR'];
    
    // Get user's withdrawals
    $stmt = $pdo->prepare("
        SELECT id, amount, phone_number, status, admin_notes, created_at, processed_at 
        FROM withdrawals 
        WHERE user_ip = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userIp]);
    $withdrawals = $stmt->fetchAll();
    
    // Format dates and amounts
    foreach ($withdrawals as &$withdrawal) {
        $withdrawal['amount_formatted'] = 'Rp ' . number_format($withdrawal['amount'], 0, ',', '.');
        $withdrawal['created_at_formatted'] = date('d/m/Y H:i', strtotime($withdrawal['created_at']));
        
        if ($withdrawal['processed_at']) {
            $withdrawal['processed_at_formatted'] = date('d/m/Y H:i', strtotime($withdrawal['processed_at']));
        }
        
        // Status labels
        switch ($withdrawal['status']) {
            case 'pending':
                $withdrawal['status_label'] = 'Menunggu Persetujuan';
                $withdrawal['status_class'] = 'warning';
                break;
            case 'approved':
                $withdrawal['status_label'] = 'Disetujui';
                $withdrawal['status_class'] = 'success';
                break;
            case 'rejected':
                $withdrawal['status_label'] = 'Ditolak';
                $withdrawal['status_class'] = 'danger';
                break;
            default:
                $withdrawal['status_label'] = ucfirst($withdrawal['status']);
                $withdrawal['status_class'] = 'secondary';
        }
    }
    
    echo json_encode([
        'success' => true,
        'withdrawals' => $withdrawals
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
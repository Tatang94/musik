<?php
require_once 'auth.php';
checkAdminAuth();
require_once '../config/database.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_withdrawal':
                try {
                    $withdrawalId = $_POST['withdrawal_id'];
                    $adminNotes = $_POST['admin_notes'] ?? '';
                    
                    // Get withdrawal details
                    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'");
                    $stmt->execute([$withdrawalId]);
                    $withdrawal = $stmt->fetch();
                    
                    if (!$withdrawal) {
                        throw new Exception('Withdrawal request not found or already processed');
                    }
                    
                    // Check user balance
                    $user = getOrCreateUser($pdo, $withdrawal['user_ip']);
                    
                    if ($user['balance'] < $withdrawal['amount']) {
                        throw new Exception('User has insufficient balance');
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Deduct balance from user
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE user_ip = ?");
                    $stmt->execute([$withdrawal['amount'], $withdrawal['user_ip']]);
                    
                    // Update withdrawal status
                    $stmt = $pdo->prepare("
                        UPDATE withdrawals 
                        SET status = 'approved', admin_notes = ?, processed_at = CURRENT_TIMESTAMP, processed_by = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$adminNotes, $_SESSION['admin_user'], $withdrawalId]);
                    
                    $pdo->commit();
                    
                    $message = 'Withdrawal approved successfully!';
                    $messageType = 'success';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'reject_withdrawal':
                try {
                    $withdrawalId = $_POST['withdrawal_id'];
                    $adminNotes = $_POST['admin_notes'] ?? '';
                    
                    $stmt = $pdo->prepare("
                        UPDATE withdrawals 
                        SET status = 'rejected', admin_notes = ?, processed_at = CURRENT_TIMESTAMP, processed_by = ?
                        WHERE id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$adminNotes, $_SESSION['admin_user'], $withdrawalId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'Withdrawal rejected successfully!';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Withdrawal request not found or already processed');
                    }
                    
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all withdrawals
$stmt = $pdo->query("
    SELECT w.*, u.balance as user_balance
    FROM withdrawals w
    LEFT JOIN users u ON w.user_ip = u.user_ip
    ORDER BY 
        CASE w.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END,
        w.created_at DESC
");
$withdrawals = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved_amount
    FROM withdrawals
");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Management - MusikReward Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-money-check-alt me-2"></i>Withdrawal Management</h1>
            <p class="mb-0">Manage user withdrawal requests</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12 mb-4">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="admin-card stat-card">
                    <div class="stat-value"><?= $stats['total_requests'] ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card stat-card">
                    <div class="stat-value text-warning"><?= $stats['pending_requests'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card stat-card">
                    <div class="stat-value text-success"><?= $stats['approved_requests'] ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-card stat-card">
                    <div class="stat-value">Rp <?= number_format($stats['total_approved_amount'], 0, ',', '.') ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
            </div>
        </div>

        <!-- Withdrawals Table -->
        <div class="admin-card">
            <h5 class="mb-4">Withdrawal Requests</h5>
            
            <?php if (empty($withdrawals)): ?>
            <div class="text-center py-4">
                <i class="fas fa-money-check-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No withdrawal requests found</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User IP</th>
                            <th>Amount</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>User Balance</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td>#<?= $withdrawal['id'] ?></td>
                            <td><?= htmlspecialchars($withdrawal['user_ip']) ?></td>
                            <td><strong>Rp <?= number_format($withdrawal['amount'], 0, ',', '.') ?></strong></td>
                            <td><?= htmlspecialchars($withdrawal['phone_number']) ?></td>
                            <td>
                                <?php
                                $statusClass = 'secondary';
                                $statusLabel = ucfirst($withdrawal['status']);
                                
                                switch ($withdrawal['status']) {
                                    case 'pending':
                                        $statusClass = 'warning';
                                        $statusLabel = 'Pending';
                                        break;
                                    case 'approved':
                                        $statusClass = 'success';
                                        $statusLabel = 'Approved';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'danger';
                                        $statusLabel = 'Rejected';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>Rp <?= number_format($withdrawal['user_balance'] ?? 0, 0, ',', '.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($withdrawal['created_at'])) ?></td>
                            <td>
                                <?php if ($withdrawal['status'] === 'pending'): ?>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="showApproveModal(<?= $withdrawal['id'] ?>, '<?= htmlspecialchars($withdrawal['user_ip'], ENT_QUOTES) ?>', <?= $withdrawal['amount'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="showRejectModal(<?= $withdrawal['id'] ?>, '<?= htmlspecialchars($withdrawal['user_ip'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                <small class="text-muted">
                                    <?= $withdrawal['processed_at'] ? date('d/m/Y H:i', strtotime($withdrawal['processed_at'])) : '' ?>
                                    <?php if ($withdrawal['processed_by']): ?>
                                    <br>by <?= htmlspecialchars($withdrawal['processed_by']) ?>
                                    <?php endif; ?>
                                </small>
                                <?php endif; ?>
                                
                                <?php if ($withdrawal['admin_notes']): ?>
                                <br><small class="text-info">
                                    <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($withdrawal['admin_notes']) ?>
                                </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="approve_withdrawal">
                    <input type="hidden" name="withdrawal_id" id="approve_withdrawal_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Withdrawal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Approve withdrawal request</strong>
                            <p class="mb-0">User: <span id="approve_user_ip"></span></p>
                            <p class="mb-0">Amount: Rp <span id="approve_amount"></span></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="approve_admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="approve_admin_notes" name="admin_notes" 
                                      placeholder="Add any notes about this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Approve Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="reject_withdrawal">
                    <input type="hidden" name="withdrawal_id" id="reject_withdrawal_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Withdrawal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>Reject withdrawal request</strong>
                            <p class="mb-0">User: <span id="reject_user_ip"></span></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reject_admin_notes" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="reject_admin_notes" name="admin_notes" 
                                      placeholder="Please provide reason for rejection..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Reject Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showApproveModal(withdrawalId, userIp, amount) {
            document.getElementById('approve_withdrawal_id').value = withdrawalId;
            document.getElementById('approve_user_ip').textContent = userIp;
            document.getElementById('approve_amount').textContent = amount.toLocaleString('id-ID');
            
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }
        
        function showRejectModal(withdrawalId, userIp) {
            document.getElementById('reject_withdrawal_id').value = withdrawalId;
            document.getElementById('reject_user_ip').textContent = userIp;
            
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
    </script>
</body>
</html>
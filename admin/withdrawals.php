<?php
require_once '../config/database.php';

$message = '';
$messageType = '';

// Handle withdrawal status updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        try {
            $withdrawalId = $_POST['withdrawal_id'];
            $newStatus = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $withdrawalId]);
            
            $message = 'Withdrawal status updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all withdrawals
$stmt = $pdo->query("
    SELECT w.*, u.balance as current_balance
    FROM withdrawals w
    LEFT JOIN users u ON w.user_ip = u.user_ip
    ORDER BY w.created_at DESC
");
$withdrawals = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(amount) as total FROM withdrawals WHERE status = 'pending'");
$pendingStats = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(amount) as total FROM withdrawals WHERE status = 'completed'");
$completedStats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Management - MusikReward Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-money-bill-wave me-2"></i>Withdrawal Management</h1>
            <p class="mb-0">Process and manage withdrawal requests</p>
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

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $pendingStats['count'] ?: 0 ?></div>
                    <div class="stat-label">Pending Withdrawals</div>
                    <small class="text-muted">Total: Rp <?= number_format($pendingStats['total'] ?: 0, 0, ',', '.') ?></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $completedStats['count'] ?: 0 ?></div>
                    <div class="stat-label">Completed Withdrawals</div>
                    <small class="text-muted">Total: Rp <?= number_format($completedStats['total'] ?: 0, 0, ',', '.') ?></small>
                </div>
            </div>
        </div>

        <!-- Withdrawals List -->
        <div class="admin-card">
            <h5 class="mb-4">All Withdrawal Requests (<?= count($withdrawals) ?>)</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User IP</th>
                            <th>Dana Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Current Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td>#<?= $withdrawal['id'] ?></td>
                            <td><?= htmlspecialchars($withdrawal['user_ip']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($withdrawal['dana_phone']) ?></strong>
                            </td>
                            <td>
                                <span class="fw-bold">Rp <?= number_format($withdrawal['amount'], 0, ',', '.') ?></span>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'rejected' => 'danger'
                                ][$withdrawal['status']];
                                ?>
                                <span class="badge bg-<?= $statusClass ?>">
                                    <?= ucfirst($withdrawal['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($withdrawal['created_at'])) ?></td>
                            <td>
                                <?php if ($withdrawal['current_balance'] !== null): ?>
                                    Rp <?= number_format($withdrawal['current_balance'], 0, ',', '.') ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($withdrawal['status'] === 'pending'): ?>
                                <div class="btn-group" role="group">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="withdrawal_id" value="<?= $withdrawal['id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark as completed?')">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="withdrawal_id" value="<?= $withdrawal['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this withdrawal?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

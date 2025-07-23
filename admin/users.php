<?php
require_once '../config/database.php';

// Get users with statistics
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(ls.id) as listening_sessions,
           SUM(ls.minutes_listened) as total_minutes_listened,
           SUM(ls.reward_earned) as total_rewards_earned
    FROM users u
    LEFT JOIN listening_sessions ls ON u.user_ip = ls.user_ip
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MusikReward Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-users me-2"></i>User Management</h1>
            <p class="mb-0">View and manage MusikReward users</p>
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

        <div class="admin-card">
            <h5 class="mb-4">All Users (<?= count($users) ?>)</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User IP</th>
                            <th>Current Balance</th>
                            <th>Total Minutes</th>
                            <th>Listening Sessions</th>
                            <th>Total Rewards Earned</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['user_ip']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    Rp <?= number_format($user['balance'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td><?= number_format($user['total_minutes'] ?: 0) ?> min</td>
                            <td><?= number_format($user['listening_sessions'] ?: 0) ?></td>
                            <td>Rp <?= number_format($user['total_rewards_earned'] ?: 0, 0, ',', '.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails('<?= $user['user_ip'] ?>')">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function viewUserDetails(userIp) {
            const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            const content = document.getElementById('userDetailsContent');
            
            content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            modal.show();
            
            try {
                const response = await fetch(`user_details.php?ip=${encodeURIComponent(userIp)}`);
                const html = await response.text();
                content.innerHTML = html;
            } catch (error) {
                content.innerHTML = '<div class="alert alert-danger">Error loading user details</div>';
            }
        }
    </script>
</body>
</html>

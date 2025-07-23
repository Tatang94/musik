<?php
require_once 'auth.php';
checkAdminAuth();
require_once '../config/database.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_songs FROM songs WHERE is_active = 1");
$totalSongs = $stmt->fetch()['total_songs'];

$stmt = $pdo->query("SELECT SUM(balance) as total_balance FROM users");
$totalBalance = $stmt->fetch()['total_balance'] ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) as pending_withdrawals FROM withdrawals WHERE status = 'pending'");
$pendingWithdrawals = $stmt->fetch()['pending_withdrawals'];

$stmt = $pdo->query("SELECT SUM(minutes_listened) as total_minutes FROM listening_sessions");
$totalMinutes = $stmt->fetch()['total_minutes'] ?: 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MusikReward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
                    <p class="mb-0">MusikReward Management System</p>
                </div>
                <div>
                    <span class="text-light me-3">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['admin_username']) ?>
                    </span>
                    <a href="?logout=1" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalUsers) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalSongs) ?></div>
                <div class="stat-label">Active Songs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Rp <?= number_format($totalBalance, 0, ',', '.') ?></div>
                <div class="stat-label">Total Balance</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($pendingWithdrawals) ?></div>
                <div class="stat-label">Pending Withdrawals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalMinutes) ?></div>
                <div class="stat-label">Total Minutes Listened</div>
            </div>
        </div>

        <!-- Navigation Cards -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="admin-card text-center">
                    <i class="fas fa-music text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5>Manage Songs</h5>
                    <p class="text-muted">Add, edit, and manage playlist songs</p>
                    <a href="songs.php" class="btn btn-primary">Manage Songs</a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="admin-card text-center">
                    <i class="fas fa-users text-success mb-3" style="font-size: 3rem;"></i>
                    <h5>User Management</h5>
                    <p class="text-muted">View user statistics and listening history</p>
                    <a href="users.php" class="btn btn-success">View Users</a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="admin-card text-center">
                    <i class="fas fa-money-bill-wave text-warning mb-3" style="font-size: 3rem;"></i>
                    <h5>Withdrawals</h5>
                    <p class="text-muted">Process withdrawal requests</p>
                    <a href="withdrawals.php" class="btn btn-warning">Process Withdrawals</a>
                </div>
            </div>
        </div>

        <!-- Additional Management Cards -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="admin-card text-center">
                    <i class="fas fa-ad text-info mb-3" style="font-size: 3rem;"></i>
                    <h5>Banner Ads</h5>
                    <p class="text-muted">Kelola iklan banner Adsterra</p>
                    <a href="banner_ads.php" class="btn btn-info">Kelola Banner</a>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="admin-card text-center">
                    <i class="fas fa-cog text-secondary mb-3" style="font-size: 3rem;"></i>
                    <h5>Settings</h5>
                    <p class="text-muted">Pengaturan aplikasi umum</p>
                    <a href="#" class="btn btn-secondary">Settings</a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="admin-card">
            <h5 class="mb-4">Recent Listening Sessions</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User IP</th>
                            <th>Song</th>
                            <th>Minutes</th>
                            <th>Reward</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT ls.*, s.title, s.artist 
                            FROM listening_sessions ls
                            JOIN songs s ON ls.song_id = s.id
                            ORDER BY ls.created_at DESC
                            LIMIT 10
                        ");
                        $sessions = $stmt->fetchAll();
                        
                        foreach ($sessions as $session): ?>
                        <tr>
                            <td><?= htmlspecialchars($session['user_ip']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($session['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($session['artist']) ?></small>
                            </td>
                            <td><?= $session['minutes_listened'] ?> min</td>
                            <td>Rp <?= number_format($session['reward_earned'], 0, ',', '.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($session['created_at'])) ?></td>
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

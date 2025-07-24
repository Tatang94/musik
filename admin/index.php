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
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* Enhanced admin dashboard styles */
        body {
            background: var(--wave-gradient);
            background-size: 400% 400%;
            animation: waveAnimation 20s ease-in-out infinite;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        body.loaded {
            opacity: 1;
        }
        
        @keyframes waveAnimation {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 0;
            margin-bottom: 30px;
            color: white;
        }
        
        .admin-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
        }
        
        .container {
            max-width: 1200px;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(107, 70, 193, 0.2);
            opacity: 0;
            transform: translateY(30px);
            animation: cardSlideIn 0.6s ease forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        
        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(107, 70, 193, 0.2);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(107, 70, 193, 0.3);
        }
        
        .admin-card h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .table {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .table td {
            border-color: rgba(107, 70, 193, 0.1);
            vertical-align: middle;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .admin-header h1 {
                font-size: 1.5rem;
            }
            
            .admin-card {
                padding: 20px;
            }
        }
        
        /* Loading states */
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
    </style>
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
    <script>
        // Enhanced admin dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize page state
            document.body.classList.add('loaded');
            document.body.style.opacity = '1';
            
            // Add fade-in effect to cards
            const adminCards = document.querySelectorAll('.admin-card');
            adminCards.forEach((card, index) => {
                card.style.animationDelay = `${0.6 + (index * 0.1)}s`;
                card.classList.add('fade-in');
            });
            
            // Table fade-in
            const table = document.querySelector('.table');
            if (table) {
                table.style.animationDelay = '1.2s';
                table.classList.add('fade-in');
            }
            
            // Real-time stats updates (optional enhancement)
            function updateStats() {
                // This could be enhanced to fetch real-time stats via AJAX
                console.log('Stats updated');
            }
            
            // Update stats every 30 seconds
            setInterval(updateStats, 30000);
            
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add loading state for navigation links
            const navLinks = document.querySelectorAll('.admin-card a.btn');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    this.style.pointerEvents = 'none';
                    
                    // Restore after 2 seconds if page doesn't navigate
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });
            
            // Auto-refresh for logout link
            const logoutLink = document.querySelector('a[href*="logout"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    if (confirm('Are you sure you want to logout?')) {
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Logging out...';
                    } else {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // Error handling
        window.addEventListener('error', function(e) {
            console.log('Resource loading handled gracefully');
        });
        
        // Performance optimization
        window.addEventListener('load', function() {
            // Trigger any final animations
            document.body.style.visibility = 'visible';
        });
    </script>
</body>
</html>

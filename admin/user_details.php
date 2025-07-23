<?php
require_once '../config/database.php';

$userIp = $_GET['ip'] ?? '';

if (!$userIp) {
    echo '<div class="alert alert-danger">Invalid user IP</div>';
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_ip = ?");
$stmt->execute([$userIp]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit;
}

// Get listening sessions
$stmt = $pdo->prepare("
    SELECT ls.*, s.title, s.artist 
    FROM listening_sessions ls
    JOIN songs s ON ls.song_id = s.id
    WHERE ls.user_ip = ?
    ORDER BY ls.created_at DESC
    LIMIT 20
");
$stmt->execute([$userIp]);
$sessions = $stmt->fetchAll();

// Get withdrawals
$stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_ip = ? ORDER BY created_at DESC");
$stmt->execute([$userIp]);
$withdrawals = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-6">
        <h6>User Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>IP Address:</strong></td>
                <td><?= htmlspecialchars($user['user_ip']) ?></td>
            </tr>
            <tr>
                <td><strong>Current Balance:</strong></td>
                <td><span class="badge bg-success">Rp <?= number_format($user['balance'], 0, ',', '.') ?></span></td>
            </tr>
            <tr>
                <td><strong>Total Minutes:</strong></td>
                <td><?= number_format($user['total_minutes']) ?> minutes</td>
            </tr>
            <tr>
                <td><strong>Join Date:</strong></td>
                <td><?= date('d/m/Y H:i:s', strtotime($user['created_at'])) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Statistics</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Listening Sessions:</strong></td>
                <td><?= count($sessions) ?></td>
            </tr>
            <tr>
                <td><strong>Withdrawal Requests:</strong></td>
                <td><?= count($withdrawals) ?></td>
            </tr>
            <tr>
                <td><strong>Avg. Minutes per Session:</strong></td>
                <td>
                    <?php
                    $totalMinutes = array_sum(array_column($sessions, 'minutes_listened'));
                    $avgMinutes = count($sessions) > 0 ? $totalMinutes / count($sessions) : 0;
                    echo number_format($avgMinutes, 1);
                    ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6>Recent Listening Sessions</h6>
        <div style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Song</th>
                        <th>Minutes</th>
                        <th>Reward</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td>
                            <small>
                                <strong><?= htmlspecialchars($session['title']) ?></strong><br>
                                <?= htmlspecialchars($session['artist']) ?>
                            </small>
                        </td>
                        <td><?= $session['minutes_listened'] ?></td>
                        <td>Rp <?= number_format($session['reward_earned'], 0, ',', '.') ?></td>
                        <td><small><?= date('d/m H:i', strtotime($session['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <h6>Withdrawal History</h6>
        <div style="max-height: 300px; overflow-y: auto;">
            <?php if (count($withdrawals) > 0): ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Dana Phone</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td>Rp <?= number_format($withdrawal['amount'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($withdrawal['dana_phone']) ?></td>
                        <td>
                            <?php
                            $statusClass = [
                                'pending' => 'warning',
                                'completed' => 'success',
                                'rejected' => 'danger'
                            ][$withdrawal['status']];
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($withdrawal['status']) ?></span>
                        </td>
                        <td><small><?= date('d/m H:i', strtotime($withdrawal['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted">No withdrawal requests</p>
            <?php endif; ?>
        </div>
    </div>
</div>

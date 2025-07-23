<?php
require_once 'config/database.php';

$userIp = $_SERVER['REMOTE_ADDR'];
$user = getOrCreateUser($pdo, $userIp);

// Get active songs
$stmt = $pdo->prepare("SELECT * FROM songs WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$songs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusikReward - Dengar Musik Dapat Reward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-play-circle text-primary me-2" style="font-size: 2rem;"></i>
                    <h4 class="mb-0 fw-bold">MusikReward</h4>
                </div>
                <div class="balance-display">
                    <span class="text-muted small">Saldo Anda</span>
                    <div class="fw-bold" id="user-balance">Rp <?= number_format($user['balance'], 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- Current Page Indicator -->
        <div class="page-indicator mb-4">
            <h2 class="fw-bold mb-0">Daftar Lagu</h2>
            <p class="text-muted">Dengarkan lagu dan dapatkan Rp 0.5 per menit</p>
        </div>

        <!-- Songs List -->
        <div class="songs-container mb-5">
            <?php foreach ($songs as $song): ?>
            <div class="song-card" data-song-id="<?= $song['id'] ?>" data-youtube-id="<?= $song['youtube_id'] ?>">
                <div class="song-thumbnail">
                    <div class="play-overlay">
                        <i class="fas fa-play play-icon"></i>
                    </div>
                    <?php if ($song['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($song['thumbnail_url']) ?>" alt="<?= htmlspecialchars($song['title']) ?>">
                    <?php else: ?>
                        <div class="default-thumbnail gradient-bg-1"></div>
                    <?php endif; ?>
                </div>
                <div class="song-info">
                    <h6 class="song-title"><?= htmlspecialchars($song['title']) ?></h6>
                    <p class="song-artist"><?= htmlspecialchars($song['artist']) ?></p>
                </div>
                <div class="play-button">
                    <i class="fas fa-play text-primary"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Music Player (Hidden by default) -->
        <div class="music-player" id="music-player" style="display: none;">
            <div class="player-content">
                <div class="player-info">
                    <img id="player-thumbnail" src="" alt="">
                    <div>
                        <h6 id="player-title" class="mb-1"></h6>
                        <p id="player-artist" class="mb-0 text-muted small"></p>
                    </div>
                </div>
                <div class="player-controls">
                    <button id="play-pause-btn" class="btn btn-primary btn-sm rounded-circle">
                        <i class="fas fa-pause"></i>
                    </button>
                </div>
                <div class="player-progress">
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="time-info">
                        <span id="current-time">0:00</span>
                        <span id="total-time">0:00</span>
                    </div>
                </div>
            </div>
            <div class="reward-tracker">
                <div class="text-center">
                    <small class="text-muted">Waktu Mendengar</small>
                    <div class="fw-bold" id="listening-time">0 menit</div>
                    <small class="text-success" id="reward-earned">+Rp 0</small>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <div class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </div>
            <div class="nav-item">
                <i class="fas fa-music"></i>
                <span>Musik</span>
            </div>
            <div class="nav-item" onclick="showWithdrawModal()">
                <i class="fas fa-user"></i>
                <span>Akun</span>
            </div>
        </div>
    </div>

    <!-- Withdrawal Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tarik Saldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="withdrawForm">
                        <div class="mb-3">
                            <label class="form-label">Saldo Tersedia</label>
                            <div class="form-control-plaintext fw-bold">Rp <?= number_format($user['balance'], 0, ',', '.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="danaPhone" class="form-label">Nomor HP Dana</label>
                            <input type="tel" class="form-control" id="danaPhone" required placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="mb-3">
                            <label for="withdrawAmount" class="form-label">Jumlah Penarikan</label>
                            <input type="number" class="form-control" id="withdrawAmount" required min="10000" max="<?= $user['balance'] ?>" placeholder="Minimal Rp 10.000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitWithdraw()">Tarik Saldo</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

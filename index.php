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
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* Critical CSS for landing page stability */
        .landing-hero {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            contain: layout style;
        }
        
        .hero-content {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.2s;
        }
        
        .modern-logo-large {
            opacity: 0;
            animation: logoFadeIn 1s ease forwards;
            animation-delay: 0.1s;
        }
        
        .hero-title {
            opacity: 0;
            animation: slideInFromLeft 0.8s ease forwards;
            animation-delay: 0.4s;
        }
        
        .hero-subtitle {
            opacity: 0;
            animation: slideInFromRight 0.8s ease forwards;
            animation-delay: 0.6s;
        }
        
        .hero-description {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.8s;
        }
        
        .hero-features {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1s;
        }
        
        .feature-card {
            opacity: 0;
            animation: cardSlideIn 0.6s ease forwards;
            transform: translateY(30px);
        }
        
        .feature-card:nth-child(1) { animation-delay: 1.2s; }
        .feature-card:nth-child(2) { animation-delay: 1.4s; }
        .feature-card:nth-child(3) { animation-delay: 1.6s; }
        
        .cta-section {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.8s;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes logoFadeIn {
            from {
                opacity: 0;
                transform: scale(0.8) rotate(-10deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
        
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Prevent layout shift */
        .hero-content > * {
            contain: layout;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="modern-logo me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="20" cy="20" r="18" fill="url(#logoGradient)" stroke="white" stroke-width="2"/>
                            <path d="M15 14L28 20L15 26V14Z" fill="white"/>
                            <circle cx="20" cy="20" r="3" fill="white" opacity="0.8"/>
                            <defs>
                                <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#FFD700"/>
                                    <stop offset="50%" style="stop-color:#FFA500"/>
                                    <stop offset="100%" style="stop-color:#FF6B35"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h4 class="mb-0 fw-bold">MusikReward</h4>
                </div>
                <div class="balance-display">
                    <span class="text-muted small">Saldo Anda</span>
                    <div class="fw-bold" id="user-balance">Rp <?= number_format($user['balance'], 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- Landing Page Content -->
        <div class="landing-hero">
            <div class="hero-content text-center">
                <div class="hero-icon mb-4">
                    <div class="modern-logo-large">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="60" cy="60" r="55" fill="url(#heroGradient)" stroke="white" stroke-width="4"/>
                            <path d="M45 42L84 60L45 78V42Z" fill="white"/>
                            <circle cx="60" cy="60" r="8" fill="white" opacity="0.8"/>
                            <defs>
                                <linearGradient id="heroGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#FFD700"/>
                                    <stop offset="30%" style="stop-color:#FFA500"/>
                                    <stop offset="70%" style="stop-color:#FF6B35"/>
                                    <stop offset="100%" style="stop-color:#8B5CF6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
                
                <h1 class="hero-title mb-3">MusikReward</h1>
                <h2 class="hero-subtitle mb-4">Monetisasi Penikmat Lagu</h2>
                
                <p class="hero-description mb-5">
                    Dengarkan musik favorit Anda dan dapatkan reward nyata! 
                    Platform revolusioner yang memberikan penghasilan kepada para penikmat musik.
                    Setiap menit mendengar = Rp 0.5 langsung ke saldo Anda.
                </p>
                
                <!-- Adsterra Banner Ad -->
                <div class="ad-banner mt-3 mb-5" id="adsterra-banner">
                    <!-- Adsterra banner will be inserted here dynamically -->
                </div>
                
                <div class="hero-features mb-5">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <h5>Dapatkan Reward</h5>
                                <p>Rp 0.5 setiap menit mendengar musik</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <h5>Musik Berkualitas</h5>
                                <p>Streaming langsung dari YouTube</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <h5>Mudah Digunakan</h5>
                                <p>Interface modern dan responsif</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <button onclick="window.location.href='songs.php'" class="btn-cta">
                        <i class="fas fa-play me-2"></i>
                        Mulai Sekarang
                    </button>
                    <p class="cta-note mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Gratis untuk memulai • Tanpa biaya tersembunyi
                    </p>
                </div>
            </div>
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
            <div class="nav-item" onclick="goToMusic()">
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
    <script>
        // Enhanced landing page loading and navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize page state
            document.body.classList.add('loaded');
            
            // Preload critical resources
            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.visibility = 'visible';
            }
            
            // Load banner ad
            loadBannerAd();
            
            // Smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
        });
        
        // Navigation functions
        function goToMusic() {
            window.location.href = 'songs.php';
        }
        
        // Banner ad loading
        async function loadBannerAd() {
            try {
                const response = await fetch('/api/get_banner.php');
                const data = await response.json();
                
                if (data.success && data.banner_code) {
                    const bannerContainer = document.getElementById('adsterra-banner');
                    if (bannerContainer) {
                        bannerContainer.innerHTML = data.banner_code;
                    }
                }
            } catch (error) {
                console.log('Banner loading skipped');
            }
        }
        
        // Performance optimizations
        window.addEventListener('load', function() {
            // Trigger any delayed animations
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, index * 100);
            });
            
            // Ensure all elements are visible
            document.body.style.visibility = 'visible';
        });
        
        // Error handling for missing elements
        window.addEventListener('error', function(e) {
            console.log('Resource loading handled gracefully');
        });
    </script>
    <style>
        /* Additional landing page stability */
        body {
            visibility: hidden;
        }
        
        body.loaded {
            visibility: visible;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        /* CTA Button styles */
        .btn-cta {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(107, 70, 193, 0.3);
        }
        
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(107, 70, 193, 0.4);
        }
        
        .cta-note {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Prevent flash of unstyled content */
        .landing-hero * {
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
        }
    </style>
</body>
</html>

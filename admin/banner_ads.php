<?php
require_once 'auth.php';
checkAdminAuth();
require_once '../config/database.php';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_banner') {
            $banner_script = $_POST['banner_script'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Create banner_ads table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS banner_ads (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    script_code TEXT,
                    is_active INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Delete existing active banners
            $pdo->exec("UPDATE banner_ads SET is_active = 0");
            
            // Insert new banner
            $stmt = $pdo->prepare("INSERT INTO banner_ads (script_code, is_active) VALUES (?, ?)");
            $stmt->execute([$banner_script, $is_active]);
            
            $success_message = "Banner berhasil disimpan!";
        }
    }
}

// Get current active banner
$stmt = $pdo->prepare("SELECT * FROM banner_ads WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$current_banner = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Ads Management - MusikReward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-ad me-2"></i>Banner Ads Management</h1>
                    <p class="mb-0">Kelola iklan banner Adsterra</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                    <a href="?logout=1" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="admin-card">
                    <h5 class="mb-4">Adsterra Banner Configuration</h5>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save_banner">
                        
                        <div class="mb-3">
                            <label for="banner_script" class="form-label">Adsterra Banner Script Code</label>
                            <textarea 
                                class="form-control" 
                                id="banner_script" 
                                name="banner_script" 
                                rows="8" 
                                placeholder="Paste your Adsterra banner script code here..."
                            ><?= htmlspecialchars($current_banner['script_code'] ?? '') ?></textarea>
                            <div class="form-text">
                                Paste the complete Adsterra banner script code you received from your Adsterra dashboard.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="is_active" 
                                name="is_active"
                                <?= ($current_banner && $current_banner['is_active']) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="is_active">
                                Aktifkan banner di halaman utama
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Banner
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <h6 class="mb-3">Banner Status</h6>
                    <?php if ($current_banner && $current_banner['is_active']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>Banner aktif
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Banner tidak aktif
                        </div>
                    <?php endif; ?>
                    
                    <h6 class="mb-3 mt-4">Panduan</h6>
                    <ol class="small">
                        <li>Login ke dashboard Adsterra Anda</li>
                        <li>Buat banner ad baru atau pilih yang sudah ada</li>
                        <li>Salin kode script banner</li>
                        <li>Paste di form sebelah kiri</li>
                        <li>Aktifkan checkbox dan simpan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
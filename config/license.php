<?php
/**
 * Music Reward System - License Protection System
 * Copyright (c) 2025 - All Rights Reserved
 * Unauthorized copying, modification, or distribution is strictly prohibited
 */

class LicenseManager {
    private $licenseKey;
    private $serverUrl = 'https://license.musikreward.com/api/verify'; // Your license server
    private $lockFile = __DIR__ . '/.license_lock';
    private $configFile = __DIR__ . '/license_config.enc';
    
    public function __construct() {
        $this->licenseKey = $this->getLicenseKey();
    }
    
    /**
     * Initialize license system with client key
     */
    public function initializeLicense($clientKey) {
        if ($clientKey !== 'client') {
            $this->blockSystem('Invalid license key provided');
            return false;
        }
        
        // Generate unique installation fingerprint
        $fingerprint = $this->generateFingerprint();
        
        // Verify with remote server
        if (!$this->verifyWithServer($clientKey, $fingerprint)) {
            $this->blockSystem('License verification failed');
            return false;
        }
        
        // Save encrypted license data
        $this->saveLicenseData($clientKey, $fingerprint);
        return true;
    }
    
    /**
     * Check license validity (called on every page load)
     */
    public function checkLicense() {
        // Check if system is blocked
        if ($this->isSystemBlocked()) {
            $this->showBlockedMessage();
            exit;
        }
        
        // Get saved license data
        $licenseData = $this->getLicenseData();
        if (!$licenseData) {
            $this->blockSystem('No valid license found');
            return false;
        }
        
        // Verify current fingerprint matches saved
        if ($licenseData['fingerprint'] !== $this->generateFingerprint()) {
            $this->blockSystem('Installation fingerprint mismatch - Unauthorized copy detected');
            return false;
        }
        
        // Periodic remote verification (every 24 hours)
        if ($this->shouldVerifyRemote($licenseData['last_check'])) {
            if (!$this->verifyWithServer($licenseData['key'], $licenseData['fingerprint'])) {
                $this->blockSystem('Remote license verification failed');
                return false;
            }
            $this->updateLastCheck();
        }
        
        return true;
    }
    
    /**
     * Generate unique server fingerprint
     */
    private function generateFingerprint() {
        $data = [
            $_SERVER['HTTP_HOST'] ?? 'unknown',
            $_SERVER['SERVER_NAME'] ?? 'unknown',
            $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
            php_uname('n'), // hostname
            php_uname('m'), // machine type
        ];
        
        return hash('sha256', implode('|', $data) . 'MUSIKREWARD_SALT_2025');
    }
    
    /**
     * Verify license with remote server
     */
    private function verifyWithServer($key, $fingerprint) {
        $data = [
            'license_key' => $key,
            'fingerprint' => $fingerprint,
            'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'script_version' => '1.0.0',
            'timestamp' => time()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: MusicReward-License-Client/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return false;
        }
        
        $result = json_decode($response, true);
        return isset($result['valid']) && $result['valid'] === true;
    }
    
    /**
     * Save encrypted license data
     */
    private function saveLicenseData($key, $fingerprint) {
        $data = [
            'key' => $key,
            'fingerprint' => $fingerprint,
            'activated_at' => time(),
            'last_check' => time()
        ];
        
        $encrypted = $this->encrypt(json_encode($data));
        file_put_contents($this->configFile, $encrypted);
    }
    
    /**
     * Get saved license data
     */
    private function getLicenseData() {
        if (!file_exists($this->configFile)) {
            return null;
        }
        
        $encrypted = file_get_contents($this->configFile);
        $decrypted = $this->decrypt($encrypted);
        
        return json_decode($decrypted, true);
    }
    
    /**
     * Check if should verify with remote server
     */
    private function shouldVerifyRemote($lastCheck) {
        return (time() - $lastCheck) > 86400; // 24 hours
    }
    
    /**
     * Update last check timestamp
     */
    private function updateLastCheck() {
        $data = $this->getLicenseData();
        if ($data) {
            $data['last_check'] = time();
            $encrypted = $this->encrypt(json_encode($data));
            file_put_contents($this->configFile, $encrypted);
        }
    }
    
    /**
     * Block system permanently
     */
    private function blockSystem($reason) {
        $blockData = [
            'blocked_at' => time(),
            'reason' => $reason,
            'fingerprint' => $this->generateFingerprint()
        ];
        
        file_put_contents($this->lockFile, json_encode($blockData));
        
        // Also try to notify license server about violation
        $this->reportViolation($reason);
    }
    
    /**
     * Check if system is blocked
     */
    private function isSystemBlocked() {
        return file_exists($this->lockFile);
    }
    
    /**
     * Show blocked message and exit
     */
    private function showBlockedMessage() {
        $blockData = json_decode(file_get_contents($this->lockFile), true);
        
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>License Violation Detected</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f44336; color: white; text-align: center; padding: 50px; }
                .container { max-width: 600px; margin: 0 auto; background: rgba(0,0,0,0.8); padding: 40px; border-radius: 10px; }
                .icon { font-size: 64px; margin-bottom: 20px; }
                h1 { color: #ffeb3b; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🚫</div>
                <h1>SYSTEM BLOCKED</h1>
                <h2>License Violation Detected</h2>
                <p><strong>Reason:</strong> <?= htmlspecialchars($blockData['reason']) ?></p>
                <p><strong>Blocked at:</strong> <?= date('Y-m-d H:i:s', $blockData['blocked_at']) ?></p>
                <hr>
                <p>This copy of Music Reward System has been permanently disabled due to license violation.</p>
                <p>Contact the original developer to obtain a valid license.</p>
                <p><strong>Warning:</strong> Attempting to bypass this protection is illegal and will result in legal action.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Report violation to license server
     */
    private function reportViolation($reason) {
        $data = [
            'violation_type' => 'license_breach',
            'reason' => $reason,
            'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'fingerprint' => $this->generateFingerprint(),
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace('/verify', '/violation', $this->serverUrl));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Simple encryption for license data
     */
    private function encrypt($data) {
        $key = hash('sha256', 'MUSIKREWARD_LICENSE_KEY_2025');
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Simple decryption for license data
     */
    private function decrypt($data) {
        $key = hash('sha256', 'MUSIKREWARD_LICENSE_KEY_2025');
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get license key from environment or config
     */
    private function getLicenseKey() {
        return 'client'; // Default license key
    }
    
    /**
     * Generate license activation page
     */
    public function showActivationPage() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Music Reward System - License Activation</title>
            <style>
                body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 50px; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
                .logo { text-align: center; color: #667eea; margin-bottom: 30px; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; font-weight: bold; }
                input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
                button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
                button:hover { background: #5a6fd8; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>🎵 Music Reward System</h1>
                    <h3>License Activation Required</h3>
                </div>
                
                <div class="warning">
                    <strong>⚠️ License Protection Active</strong><br>
                    This script requires a valid license key to operate. Enter "client" to activate.
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="license_key">License Key:</label>
                        <input type="text" id="license_key" name="license_key" placeholder="Enter your license key..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="domain">Domain:</label>
                        <input type="text" id="domain" name="domain" value="<?= $_SERVER['HTTP_HOST'] ?>" readonly>
                    </div>
                    
                    <button type="submit" name="activate">Activate License</button>
                </form>
                
                <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
                    <p>© 2025 Music Reward System. All rights reserved.</p>
                    <p>Unauthorized copying or distribution is prohibited.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

// Handle license activation
if (isset($_POST['activate'])) {
    $license = new LicenseManager();
    if ($license->initializeLicense($_POST['license_key'])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid license key or activation failed.";
    }
}
?>
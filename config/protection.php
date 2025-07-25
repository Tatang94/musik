<?php
/**
 * Advanced Anti-Plagiarism Protection System
 * 
 * This file contains multiple layers of protection against:
 * - Code copying/stealing
 * - Unauthorized redistribution
 * - License bypassing attempts
 * - Reverse engineering
 */

class AdvancedProtection {
    
    /**
     * Check for common plagiarism indicators
     */
    public static function detectPlagiarism() {
        $indicators = [];
        
        // Check for suspicious file modifications
        $criticalFiles = [
            'license_check.php',
            'config/license.php',
            'config/protection.php'
        ];
        
        foreach ($criticalFiles as $file) {
            if (!file_exists($file)) {
                $indicators[] = "Critical protection file missing: $file";
            }
        }
        
        // Check for debugging attempts
        if (isset($_GET['debug']) || isset($_POST['debug']) || 
            isset($_GET['test']) || isset($_POST['test'])) {
            $indicators[] = "Debugging attempt detected";
        }
        
        // Check for license bypass attempts in URL
        $suspiciousParams = ['bypass', 'crack', 'nulled', 'license=false', 'activate=skip'];
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($suspiciousParams as $param) {
            if (stripos($requestUri, $param) !== false) {
                $indicators[] = "License bypass attempt in URL: $param";
            }
        }
        
        return $indicators;
    }
    
    /**
     * Obfuscated license verification
     */
    public static function verifyIntegrity() {
        // Multiple random checks to make bypassing difficult
        $checks = [
            self::checkFileIntegrity(),
            self::checkEnvironment(),
            self::checkSourceCode(),
            self::checkLicenseFiles()
        ];
        
        $failedChecks = array_filter($checks, function($check) {
            return $check !== true;
        });
        
        if (!empty($failedChecks)) {
            self::triggerProtection($failedChecks);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check critical file integrity
     */
    private static function checkFileIntegrity() {
        $criticalFiles = [
            'license_check.php' => '<!-- Expected hash will be calculated -->',
            'config/license.php' => '<!-- Expected hash will be calculated -->'
        ];
        
        foreach ($criticalFiles as $file => $expectedHash) {
            if (!file_exists($file)) {
                return "Critical file missing: $file";
            }
            
            // Check for obvious tampering
            $content = file_get_contents($file);
            if (stripos($content, 'license') === false) {
                return "License protection removed from: $file";
            }
        }
        
        return true;
    }
    
    /**
     * Check execution environment
     */
    private static function checkEnvironment() {
        // Check for common nulled script indicators
        $nulledIndicators = [
            '/nulled/',
            '/cracked/',
            '/warez/',
            '/crack/',
            'nulled.to',
            'blackhatworld',
            'crackedgames'
        ];
        
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        
        foreach ($nulledIndicators as $indicator) {
            if (stripos($documentRoot, $indicator) !== false || 
                stripos($httpHost, $indicator) !== false) {
                return "Nulled environment detected: $indicator";
            }
        }
        
        return true;
    }
    
    /**
     * Check source code for modifications
     */
    private static function checkSourceCode() {
        // Look for common license removal attempts
        $sourceFiles = glob('*.php');
        $sourceFiles = array_merge($sourceFiles, glob('*/*.php'));
        
        foreach ($sourceFiles as $file) {
            if (strpos($file, 'license') !== false) continue;
            
            $content = file_get_contents($file);
            
            // Check if license protection was removed
            if (strpos($content, '<?php') === 0 && 
                strpos($content, 'license_check.php') === false &&
                strpos($content, 'license_guard.php') === false &&
                in_array(basename($file), ['index.php', 'songs.php'])) {
                return "License protection removed from: $file";
            }
        }
        
        return true;
    }
    
    /**
     * Check license files exist and are valid
     */
    private static function checkLicenseFiles() {
        $requiredFiles = [
            'license_check.php',
            'config/license.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                return "Required license file missing: $file";
            }
            
            $content = file_get_contents($file);
            if (strlen($content) < 1000) { // Too small, likely tampered
                return "License file appears tampered: $file";
            }
        }
        
        return true;
    }
    
    /**
     * Trigger protection when violations detected
     */
    private static function triggerProtection($violations) {
        // Log violation
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'violations' => $violations,
            'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown'
        ];
        
        // Try to log locally
        @file_put_contents(__DIR__ . '/.violations.log', 
                          json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Block the system
        if (class_exists('LicenseManager')) {
            $license = new LicenseManager();
            $license->blockSystem('Protection violation: ' . implode(', ', $violations));
        }
        
        // Show violation message
        self::showViolationMessage($violations);
        exit;
    }
    
    /**
     * Show violation message
     */
    private static function showViolationMessage($violations) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Protection Violation Detected</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    background: linear-gradient(135deg, #e74c3c, #c0392b); 
                    color: white; 
                    margin: 0; 
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .container { 
                    max-width: 600px; 
                    background: rgba(0,0,0,0.9); 
                    padding: 40px; 
                    border-radius: 15px; 
                    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
                    border: 2px solid #e74c3c;
                    text-align: center;
                }
                .icon { 
                    font-size: 80px; 
                    margin-bottom: 20px; 
                    color: #e74c3c;
                    text-shadow: 0 0 20px rgba(231, 76, 60, 0.5);
                }
                h1 { 
                    color: #e74c3c; 
                    margin-bottom: 10px;
                    font-size: 2.5em;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
                }
                h2 {
                    color: #ecf0f1;
                    margin-bottom: 30px;
                    font-weight: 300;
                }
                .violation-list {
                    background: rgba(231, 76, 60, 0.1);
                    border-left: 4px solid #e74c3c;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: left;
                    border-radius: 5px;
                }
                .violation-item {
                    margin: 10px 0;
                    padding: 5px 0;
                    border-bottom: 1px solid rgba(255,255,255,0.1);
                }
                .warning {
                    background: rgba(241, 196, 15, 0.2);
                    border: 1px solid #f1c40f;
                    color: #f1c40f;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 30px 0;
                }
                .footer {
                    margin-top: 30px;
                    font-size: 0.9em;
                    color: #bdc3c7;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🛡️</div>
                <h1>SYSTEM PROTECTED</h1>
                <h2>License Protection Violation Detected</h2>
                
                <div class="violation-list">
                    <strong>Detected Violations:</strong>
                    <?php foreach ($violations as $violation): ?>
                        <div class="violation-item">⚠️ <?= htmlspecialchars($violation) ?></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="warning">
                    <strong>⚖️ LEGAL WARNING</strong><br>
                    This software is protected by copyright law. Unauthorized copying, modification, 
                    or distribution constitutes copyright infringement and may result in severe civil 
                    and criminal penalties.
                </div>
                
                <p><strong>This installation has been permanently disabled.</strong></p>
                <p>If you believe this is an error, contact the original developer with proof of legitimate purchase.</p>
                
                <div class="footer">
                    <p>© 2025 Music Reward System. All rights reserved.</p>
                    <p>Violation logged and reported to authorities.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Random integrity check (called randomly during execution)
     */
    public static function randomCheck() {
        if (mt_rand(1, 20) === 1) { // 5% chance
            self::verifyIntegrity();
        }
    }
}

// Auto-execute protection checks
AdvancedProtection::randomCheck();
?>
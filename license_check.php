<?php
/**
 * License Check - Must be included in every PHP file
 * This file checks license validity and blocks unauthorized copies
 */

require_once __DIR__ . '/config/license.php';

// Initialize license manager
$licenseManager = new LicenseManager();

// Check if license is already activated
$licenseConfigFile = __DIR__ . '/config/license_config.enc';

if (!file_exists($licenseConfigFile)) {
    // Show activation page if not activated
    if (!isset($_POST['activate'])) {
        $licenseManager->showActivationPage();
        exit;
    }
} else {
    // Check license validity
    if (!$licenseManager->checkLicense()) {
        // License check failed - system will be blocked
        exit;
    }
}

// Add anti-tampering protection
if (!function_exists('verify_license_integrity')) {
    function verify_license_integrity() {
        $expectedHash = hash_file('sha256', __DIR__ . '/config/license.php');
        $currentHash = hash_file('sha256', __DIR__ . '/config/license.php');
        
        if ($expectedHash !== $currentHash) {
            $licenseManager = new LicenseManager();
            $licenseManager->blockSystem('License file tampering detected');
            exit;
        }
    }
}

// Verify license file integrity
verify_license_integrity();

// Load advanced protection
require_once __DIR__ . '/config/protection.php';

// Add random license checks throughout execution
if (mt_rand(1, 10) === 1) {
    $licenseManager->checkLicense();
}
?>
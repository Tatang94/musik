<?php
/**
 * API License Guard - Protects all API endpoints
 */

// License Protection - CRITICAL: DO NOT REMOVE OR MODIFY
require_once '../license_check.php';

// Additional API-specific protections
function validateApiRequest() {
    // Check for common plagiarism attempts
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $suspiciousAgents = [
        'curl',
        'wget', 
        'scrapy',
        'bot',
        'crawler'
    ];
    
    foreach ($suspiciousAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            // Log suspicious activity
            error_log("Suspicious API access from: " . $_SERVER['REMOTE_ADDR'] . " - " . $userAgent);
        }
    }
    
    // Rate limiting per IP
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $requestFile = sys_get_temp_dir() . '/musikreward_' . md5($clientIp) . '.tmp';
    
    $currentTime = time();
    $requests = [];
    
    if (file_exists($requestFile)) {
        $requests = json_decode(file_get_contents($requestFile), true) ?: [];
    }
    
    // Remove old requests (older than 1 minute)
    $requests = array_filter($requests, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 60;
    });
    
    // Check if exceeded rate limit (30 requests per minute)
    if (count($requests) >= 30) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // Add current request
    $requests[] = $currentTime;
    file_put_contents($requestFile, json_encode($requests));
}

// Run validation
validateApiRequest();
?>
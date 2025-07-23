<?php
// Configuration example for hosting environments
// Copy this file to config.php and modify according to your hosting setup

// Database configuration
// For SQLite (default)
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/data.db');

// For MySQL (uncomment and configure if needed)
/*
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
*/

// YouTube API configuration (optional - for enhanced features)
define('YOUTUBE_API_KEY', ''); // Add your YouTube API key here

// Application settings
define('APP_URL', 'https://yourdomain.com'); // Your site URL
define('APP_NAME', 'MusikReward');

// Admin credentials
define('ADMIN_USERNAME', '089663596711');
define('ADMIN_PASSWORD', 'boar');

// Error reporting (set to false for production)
define('DEBUG_MODE', true);
?>
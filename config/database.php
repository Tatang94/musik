<?php
// Database configuration - flexible for hosting environments
$dbPath = $_ENV['DATABASE_PATH'] ?? __DIR__ . '/../data.db';

// Ensure database directory exists
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys for SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to get or create user by IP
function getOrCreateUser($pdo, $ip) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_ip = ?");
    $stmt->execute([$ip]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (user_ip) VALUES (?)");
        $stmt->execute([$ip]);
        return getOrCreateUser($pdo, $ip);
    }
    
    return $user;
}
?>

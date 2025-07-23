<?php
// Get the correct path to database file
$dbPath = __DIR__ . '/../musikreward.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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

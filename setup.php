<?php
// Database setup script - run this first to create tables
try {
    $pdo = new PDO("sqlite:musikreward.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "Database connection established successfully<br>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_ip TEXT NOT NULL UNIQUE,
            balance REAL DEFAULT 0.00,
            total_minutes INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Users table created successfully<br>";
    
    // Create songs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS songs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            youtube_id TEXT NOT NULL,
            title TEXT NOT NULL,
            artist TEXT NOT NULL,
            thumbnail_url TEXT,
            duration INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Songs table created successfully<br>";
    
    // Create listening_sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS listening_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_ip TEXT NOT NULL,
            song_id INTEGER NOT NULL,
            minutes_listened INTEGER DEFAULT 0,
            reward_earned REAL DEFAULT 0.00,
            completed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (song_id) REFERENCES songs(id)
        )
    ");
    echo "Listening sessions table created successfully<br>";
    
    // Create withdrawals table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS withdrawals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_ip TEXT NOT NULL,
            dana_phone TEXT NOT NULL,
            amount REAL NOT NULL,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'rejected')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Withdrawals table created successfully<br>";
    
    // Insert sample songs
    $sampleSongs = [
        ['dQw4w9WgXcQ', 'Never Gonna Give You Up', 'Rick Astley'],
        ['9bZkp7q19f0', 'Gangnam Style', 'PSY'],
        ['kJQP7kiw5Fk', 'Despacito', 'Luis Fonsi ft. Daddy Yankee'],
        ['fJ9rUzIMcZQ', 'Bohemian Rhapsody', 'Queen']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO songs (youtube_id, title, artist) VALUES (?, ?, ?)");
    foreach ($sampleSongs as $song) {
        $stmt->execute($song);
    }
    echo "Sample songs inserted successfully<br>";
    
    echo "<br><strong>Setup completed! You can now use the application.</strong>";
    echo "<br><a href='index.php'>Go to Main Page</a>";
    echo "<br><a href='admin/index.php'>Go to Admin Panel</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

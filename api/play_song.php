<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/youtube.php';

$input = json_decode(file_get_contents('php://input'), true);
$userIp = $_SERVER['REMOTE_ADDR'];

if (!isset($input['song_id']) || !isset($input['youtube_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $user = getOrCreateUser($pdo, $userIp);
    
    // Get song details
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ? AND is_active = 1");
    $stmt->execute([$input['song_id']]);
    $song = $stmt->fetch();
    
    if (!$song) {
        throw new Exception('Song not found or inactive');
    }
    
    // Create new listening session
    $stmt = $pdo->prepare("INSERT INTO listening_sessions (user_ip, song_id) VALUES (?, ?)");
    $stmt->execute([$userIp, $input['song_id']]);
    
    // If no thumbnail, try to get from YouTube API
    if (!$song['thumbnail_url']) {
        $youtube = new YouTubeAPI();
        $videoDetails = $youtube->getVideoDetails($input['youtube_id']);
        
        if ($videoDetails) {
            $stmt = $pdo->prepare("UPDATE songs SET thumbnail_url = ?, duration = ? WHERE id = ?");
            $stmt->execute([$videoDetails['thumbnail'], $videoDetails['duration'], $song['id']]);
            $song['thumbnail_url'] = $videoDetails['thumbnail'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'song' => [
            'id' => $song['id'],
            'title' => $song['title'],
            'artist' => $song['artist'],
            'thumbnail_url' => $song['thumbnail_url'],
            'youtube_id' => $song['youtube_id']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

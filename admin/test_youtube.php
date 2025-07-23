<?php
require_once '../config/youtube.php';

header('Content-Type: application/json');

try {
    $youtube = new YouTubeAPI();
    $result = $youtube->searchVideos('taylor swift', 3);
    
    echo json_encode([
        'success' => true,
        'raw_response' => $result,
        'test' => 'API working'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
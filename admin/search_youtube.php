<?php
session_start();

// Set content type first to prevent any HTML output
header('Content-Type: application/json');

try {
    require_once 'auth.php';
    checkAdminAuth();
    require_once '../config/youtube.php';

    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!isset($input['query']) || empty(trim($input['query']))) {
        throw new Exception('Query is required');
    }

    $youtube = new YouTubeAPI();
    $searchResults = $youtube->searchVideos($input['query'], 12);
    
    if (!$searchResults) {
        throw new Exception('Failed to connect to YouTube API');
    }
    
    if (isset($searchResults['error'])) {
        throw new Exception('YouTube API Error: ' . $searchResults['error']['message']);
    }
    
    if (!isset($searchResults['items']) || empty($searchResults['items'])) {
        echo json_encode([
            'success' => true,
            'videos' => [],
            'total' => 0,
            'message' => 'No videos found for this search'
        ]);
        exit;
    }
    
    $videos = [];
    foreach ($searchResults['items'] as $item) {
        if (isset($item['id']['videoId'])) {
            $videos[] = [
                'videoId' => $item['id']['videoId'],
                'title' => htmlspecialchars($item['snippet']['title']),
                'channelTitle' => htmlspecialchars($item['snippet']['channelTitle']),
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                'description' => htmlspecialchars(substr($item['snippet']['description'], 0, 150) . '...')
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'total' => count($videos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'videos' => []
    ]);
}
?>
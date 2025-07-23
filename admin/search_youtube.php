<?php
session_start();
require_once 'auth.php';
checkAdminAuth();
require_once '../config/youtube.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['query']) || empty(trim($input['query']))) {
    echo json_encode(['success' => false, 'message' => 'Query is required']);
    exit;
}

try {
    $youtube = new YouTubeAPI();
    $searchResults = $youtube->searchVideos($input['query'], 12);
    
    if (!$searchResults || !isset($searchResults['items'])) {
        throw new Exception('Failed to fetch data from YouTube API');
    }
    
    $videos = [];
    foreach ($searchResults['items'] as $item) {
        $videos[] = [
            'videoId' => $item['id']['videoId'],
            'title' => $item['snippet']['title'],
            'channelTitle' => $item['snippet']['channelTitle'],
            'thumbnail' => $item['snippet']['thumbnails']['medium']['url'],
            'description' => $item['snippet']['description']
        ];
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
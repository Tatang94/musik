<?php
// Start output buffering to prevent any accidental output
ob_start();

// Suppress any PHP warnings/notices
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Clean output buffer and set headers
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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
        // Clean any remaining buffer content
        ob_clean();
        echo json_encode([
            'success' => true,
            'videos' => [],
            'total' => 0,
            'message' => 'No videos found for this search'
        ]);
        ob_end_flush();
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
    
    // Clean any remaining buffer content
    ob_clean();
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'total' => count($videos)
    ]);
    
} catch (Exception $e) {
    // Clean any remaining buffer content
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'videos' => []
    ]);
}

// End output buffering and flush
ob_end_flush();
?>
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
            // Clean and sanitize all text data to prevent JSON issues
            $title = isset($item['snippet']['title']) ? trim($item['snippet']['title']) : 'Untitled';
            $channelTitle = isset($item['snippet']['channelTitle']) ? trim($item['snippet']['channelTitle']) : 'Unknown Channel';
            $description = isset($item['snippet']['description']) ? trim($item['snippet']['description']) : '';
            
            // Remove any control characters that might break JSON
            $title = preg_replace('/[\x00-\x1F\x7F]/', '', $title);
            $channelTitle = preg_replace('/[\x00-\x1F\x7F]/', '', $channelTitle);
            $description = preg_replace('/[\x00-\x1F\x7F]/', '', $description);
            
            $videos[] = [
                'videoId' => $item['id']['videoId'],
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'channelTitle' => htmlspecialchars($channelTitle, ENT_QUOTES, 'UTF-8'),
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'] ?? '',
                'description' => htmlspecialchars(substr($description, 0, 150) . '...', ENT_QUOTES, 'UTF-8')
            ];
        }
    }
    
    // Clean any remaining buffer content and ensure proper JSON output
    ob_clean();
    
    // Final data cleaning before JSON encode
    $responseData = [
        'success' => true,
        'videos' => $videos,
        'total' => count($videos)
    ];
    
    // Ensure UTF-8 encoding
    $jsonResponse = json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'JSON encoding error: ' . json_last_error_msg(),
            'videos' => []
        ]);
    } else {
        echo $jsonResponse;
    }
    
} catch (Exception $e) {
    // Clean any remaining buffer content
    ob_clean();
    
    $errorResponse = [
        'success' => false, 
        'message' => $e->getMessage(),
        'videos' => []
    ];
    
    // Ensure clean JSON output for errors too
    $jsonResponse = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Fallback error response if even error JSON fails
        echo '{"success":false,"message":"Critical JSON error","videos":[]}';
    } else {
        echo $jsonResponse;
    }
}

// End output buffering and flush
ob_end_flush();
?>
<?php
class YouTubeAPI {
    private $apiKey;
    
    public function __construct() {
        // Get API key from environment or use provided key
        $this->apiKey = getenv('YOUTUBE_API_KEY') ?: 'AIzaSyCdgmEsPW59-U4bNKj-u-FSHHVaFfFO_VM';
    }
    
    public function getVideoDetails($videoId) {
        $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->apiKey}&part=snippet,contentDetails";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['items'][0])) {
            $item = $data['items'][0];
            return [
                'title' => $item['snippet']['title'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'],
                'duration' => $this->parseDuration($item['contentDetails']['duration'])
            ];
        }
        
        return false;
    }
    
    private function parseDuration($duration) {
        // Parse YouTube duration format (PT4M13S) to seconds
        preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches);
        $hours = isset($matches[1]) ? (int)str_replace('H', '', $matches[1]) : 0;
        $minutes = isset($matches[2]) ? (int)str_replace('M', '', $matches[2]) : 0;
        $seconds = isset($matches[3]) ? (int)str_replace('S', '', $matches[3]) : 0;
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
    
    public function searchVideos($query, $maxResults = 10) {
        $url = "https://www.googleapis.com/youtube/v3/search?q=" . urlencode($query) . "&key={$this->apiKey}&part=snippet&type=video&maxResults={$maxResults}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; YouTube API Client)');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        return $data;
    }
}
?>

<?php
require_once '../config/database.php';
require_once '../config/youtube.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_song':
                try {
                    $youtubeId = $_POST['youtube_id'];
                    $title = $_POST['title'];
                    $artist = $_POST['artist'];
                    
                    // Extract YouTube ID from URL if needed
                    if (strpos($youtubeId, 'youtube.com') !== false || strpos($youtubeId, 'youtu.be') !== false) {
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtubeId, $matches);
                        if (isset($matches[1])) {
                            $youtubeId = $matches[1];
                        }
                    }
                    
                    // Get video details from YouTube API
                    $youtube = new YouTubeAPI();
                    $videoDetails = $youtube->getVideoDetails($youtubeId);
                    
                    if ($videoDetails) {
                        $stmt = $pdo->prepare("INSERT INTO songs (youtube_id, title, artist, thumbnail_url, duration) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $youtubeId,
                            $title ?: $videoDetails['title'],
                            $artist,
                            $videoDetails['thumbnail'],
                            $videoDetails['duration']
                        ]);
                        $message = 'Song added successfully!';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Could not fetch video details from YouTube');
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'toggle_status':
                try {
                    $songId = $_POST['song_id'];
                    $newStatus = $_POST['status'] == '1' ? 0 : 1;
                    
                    $stmt = $pdo->prepare("UPDATE songs SET is_active = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $songId]);
                    
                    $message = 'Song status updated successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_song':
                try {
                    $songId = $_POST['song_id'];
                    
                    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                    $stmt->execute([$songId]);
                    
                    $message = 'Song deleted successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all songs
$stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
$songs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Management - MusikReward Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-music me-2"></i>Song Management</h1>
            <p class="mb-0">Manage playlist songs for MusikReward</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12 mb-4">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Add New Song -->
        <div class="admin-card mb-4">
            <h5 class="mb-4">Add New Song</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_song">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="youtube_id" class="form-label">YouTube Video ID or URL</label>
                        <input type="text" class="form-control" id="youtube_id" name="youtube_id" required placeholder="dQw4w9WgXcQ or full YouTube URL">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="artist" class="form-label">Artist Name</label>
                        <input type="text" class="form-control" id="artist" name="artist" required placeholder="Artist Name">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Song Title (Optional - will auto-fetch from YouTube)</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Leave empty to auto-fetch">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Song
                </button>
            </form>
        </div>

        <!-- Songs List -->
        <div class="admin-card">
            <h5 class="mb-4">Current Songs (<?= count($songs) ?>)</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thumbnail</th>
                            <th>Song Details</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songs as $song): ?>
                        <tr>
                            <td>
                                <?php if ($song['thumbnail_url']): ?>
                                    <img src="<?= htmlspecialchars($song['thumbnail_url']) ?>" 
                                         alt="<?= htmlspecialchars($song['title']) ?>" 
                                         style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 45px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-music text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($song['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($song['artist']) ?></small><br>
                                <small class="text-muted">ID: <?= htmlspecialchars($song['youtube_id']) ?></small>
                            </td>
                            <td>
                                <?php if ($song['duration']): ?>
                                    <?= gmdate("i:s", $song['duration']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $song['is_active'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $song['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $song['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td><?= date('d/m/Y', strtotime($song['created_at'])) ?></td>
                            <td>
                                <a href="https://youtube.com/watch?v=<?= $song['youtube_id'] ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this song?')">
                                    <input type="hidden" name="action" value="delete_song">
                                    <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

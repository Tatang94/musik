<?php
// License Protection - CRITICAL: DO NOT REMOVE OR MODIFY
require_once '../license_check.php';

require_once 'auth.php';
checkAdminAuth();
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
                
            case 'add_songs_bulk':
                try {
                    $selectedSongs = json_decode($_POST['selected_songs'], true);
                    if (!$selectedSongs || !is_array($selectedSongs)) {
                        throw new Exception('No songs selected');
                    }
                    
                    $youtube = new YouTubeAPI();
                    $addedCount = 0;
                    $skippedCount = 0;
                    $errors = [];
                    
                    foreach ($selectedSongs as $songData) {
                        try {
                            $youtubeId = $songData['videoId'];
                            $title = $songData['title'];
                            $artist = $songData['channelTitle'];
                            
                            // Check if song already exists
                            $stmt = $pdo->prepare("SELECT id FROM songs WHERE youtube_id = ?");
                            $stmt->execute([$youtubeId]);
                            if ($stmt->fetch()) {
                                $skippedCount++;
                                continue;
                            }
                            
                            // Get video details from YouTube API for duration and high-quality thumbnail
                            $videoDetails = $youtube->getVideoDetails($youtubeId);
                            
                            if ($videoDetails) {
                                $stmt = $pdo->prepare("INSERT INTO songs (youtube_id, title, artist, thumbnail_url, duration) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([
                                    $youtubeId,
                                    $title,
                                    $artist,
                                    $videoDetails['thumbnail'],
                                    $videoDetails['duration']
                                ]);
                                $addedCount++;
                            } else {
                                $errors[] = "Failed to get details for: $title";
                            }
                        } catch (Exception $e) {
                            $errors[] = "Error adding '$title': " . $e->getMessage();
                        }
                    }
                    
                    $message = "Successfully added $addedCount songs";
                    if ($skippedCount > 0) {
                        $message .= ", skipped $skippedCount existing songs";
                    }
                    if (!empty($errors)) {
                        $message .= ". Errors: " . implode(', ', array_slice($errors, 0, 3));
                        if (count($errors) > 3) {
                            $message .= " and " . (count($errors) - 3) . " more";
                        }
                    }
                    $messageType = 'success';
                    
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'add_song_from_search':
                try {
                    $youtubeId = $_POST['youtube_id'];
                    $title = $_POST['title'];
                    $artist = $_POST['artist'];
                    
                    // Check if song already exists
                    $stmt = $pdo->prepare("SELECT id FROM songs WHERE youtube_id = ?");
                    $stmt->execute([$youtubeId]);
                    if ($stmt->fetch()) {
                        throw new Exception('Song already exists in the playlist');
                    }
                    
                    // Get video details from YouTube API for duration and high-quality thumbnail
                    $youtube = new YouTubeAPI();
                    $videoDetails = $youtube->getVideoDetails($youtubeId);
                    
                    if ($videoDetails) {
                        $stmt = $pdo->prepare("INSERT INTO songs (youtube_id, title, artist, thumbnail_url, duration) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $youtubeId,
                            $title,
                            $artist,
                            $videoDetails['thumbnail'],
                            $videoDetails['duration']
                        ]);
                        $message = 'Song added successfully from search!';
                        $messageType = 'success';
                    } else {
                        // Fallback - add without detailed info
                        $stmt = $pdo->prepare("INSERT INTO songs (youtube_id, title, artist) VALUES (?, ?, ?)");
                        $stmt->execute([$youtubeId, $title, $artist]);
                        $message = 'Song added successfully (basic info)!';
                        $messageType = 'success';
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
                    
                    // First delete related listening sessions to avoid foreign key constraint
                    $stmt = $pdo->prepare("DELETE FROM listening_sessions WHERE song_id = ?");
                    $stmt->execute([$songId]);
                    
                    // Then delete the song
                    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                    $stmt->execute([$songId]);
                    
                    $message = 'Song and related data deleted successfully!';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_songs_bulk':
                try {
                    $selectedSongs = json_decode($_POST['selected_songs'], true);
                    if (!$selectedSongs || !is_array($selectedSongs)) {
                        throw new Exception('No songs selected for deletion');
                    }
                    
                    $deletedCount = 0;
                    $errors = [];
                    
                    foreach ($selectedSongs as $songId) {
                        try {
                            // First delete related listening sessions to avoid foreign key constraint
                            $stmt = $pdo->prepare("DELETE FROM listening_sessions WHERE song_id = ?");
                            $stmt->execute([$songId]);
                            
                            // Then delete the song
                            $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
                            $stmt->execute([$songId]);
                            $deletedCount++;
                        } catch (Exception $e) {
                            $errors[] = "Failed to delete song ID $songId: " . $e->getMessage();
                        }
                    }
                    
                    $message = "Successfully deleted $deletedCount songs";
                    if (!empty($errors)) {
                        $message .= ". Errors: " . implode(', ', array_slice($errors, 0, 3));
                        if (count($errors) > 3) {
                            $message .= " and " . (count($errors) - 3) . " more";
                        }
                    }
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
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
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

        <!-- Messages will be shown as toast notifications -->

        <!-- Search and Add Songs from YouTube -->
        <div class="admin-card mb-4">
            <h5 class="mb-4">Search Songs from YouTube</h5>
            
            <!-- Search Form -->
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="search_query" class="form-label">Search YouTube</label>
                        <input type="text" class="form-control" id="search_query" placeholder="Cari lagu, artis, atau kata kunci..." />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="searchYouTube()">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search Results -->
            <div id="search-results" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Hasil Pencarian:</h6>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllSongs()">
                            <i class="fas fa-check-square me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="deselectAllSongs()">
                            <i class="fas fa-square me-1"></i>Deselect All
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="addSelectedSongs()" id="add-selected-btn" disabled>
                            <i class="fas fa-plus-circle me-1"></i>Add Selected (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </div>
                <div id="search-results-container" class="row">
                    <!-- Results will be loaded here -->
                </div>
            </div>

            <!-- Manual Add (fallback) -->
            <div class="border-top pt-4 mt-4">
                <h6 class="mb-3">Atau Tambah Manual</h6>
                <form method="POST" id="manual-add-form">
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
                    <button type="submit" class="btn btn-secondary" id="manual-add-btn">
                        <i class="fas fa-plus me-2"></i>Add Manual
                    </button>
                </form>
            </div>
        </div>

        <!-- Songs List -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Current Songs (<?= count($songs) ?>)</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllExistingSongs()">
                        <i class="fas fa-check-square me-1"></i>Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="deselectAllExistingSongs()">
                        <i class="fas fa-square me-1"></i>Deselect All
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelectedSongs()" id="delete-selected-btn" disabled>
                        <i class="fas fa-trash me-1"></i>Delete Selected (<span id="selected-delete-count">0</span>)
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="50px">
                                <input type="checkbox" class="form-check-input" id="select-all-checkbox" onchange="toggleAllExistingSongs()">
                            </th>
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
                                <input type="checkbox" class="form-check-input song-delete-checkbox" 
                                       data-song-id="<?= $song['id'] ?>" 
                                       onchange="updateDeleteSelectedCount()">
                            </td>
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
    <script>
        async function searchYouTube() {
            const query = document.getElementById('search_query').value.trim();
            if (!query) {
                alert('Masukkan kata kunci pencarian');
                return;
            }

            const resultsDiv = document.getElementById('search-results');
            const containerDiv = document.getElementById('search-results-container');
            
            // Show loading
            containerDiv.innerHTML = '<div class="col-12 text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Mencari lagu...</p></div>';
            resultsDiv.style.display = 'block';

            try {
                const response = await fetch('search_youtube.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query: query })
                });

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Get response text first to debug potential JSON issues
                const responseText = await response.text();
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('JSON Parse Error in searchYouTube:', jsonError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from search API');
                }

                if (result.success && result.videos.length > 0) {
                    let html = '';
                    result.videos.forEach(video => {
                        html += `
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="position-relative">
                                        <img src="${video.thumbnail}" class="card-img-top" alt="${video.title}" style="height: 200px; object-fit: cover;">
                                        <div class="position-absolute top-0 start-0 p-2">
                                            <input type="checkbox" class="form-check-input song-checkbox" 
                                                   data-video-id="${video.videoId}" 
                                                   data-title="${video.title.replace(/"/g, '&quot;')}" 
                                                   data-channel="${video.channelTitle.replace(/"/g, '&quot;')}"
                                                   onchange="updateSelectedCount()">
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title" style="font-size: 0.9rem; line-height: 1.2;">${video.title}</h6>
                                        <p class="card-text text-muted small">${video.channelTitle}</p>
                                        <div class="mt-auto">
                                            <button class="btn btn-outline-success btn-sm w-100 mb-2" onclick="addSongFromSearch('${video.videoId}', '${video.title.replace(/'/g, "\\'")}', '${video.channelTitle.replace(/'/g, "\\'")}')">
                                                <i class="fas fa-plus me-1"></i>Add This Song
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    containerDiv.innerHTML = html;
                    updateSelectedCount();
                } else {
                    containerDiv.innerHTML = '<div class="col-12 text-center"><p class="text-muted">Tidak ada hasil ditemukan</p></div>';
                }
            } catch (error) {
                containerDiv.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error: ' + error.message + '</p></div>';
            }
        }

        function selectAllSongs() {
            document.querySelectorAll('.song-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }

        function deselectAllSongs() {
            document.querySelectorAll('.song-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.song-checkbox:checked').length;
            const countElement = document.getElementById('selected-count');
            const btnElement = document.getElementById('add-selected-btn');
            
            if (countElement) {
                countElement.textContent = selectedCount;
            }
            if (btnElement) {
                btnElement.disabled = selectedCount === 0;
            }
        }

        async function addSelectedSongs() {
            const selectedCheckboxes = document.querySelectorAll('.song-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Pilih minimal satu lagu');
                return;
            }

            const selectedSongs = Array.from(selectedCheckboxes).map(checkbox => ({
                videoId: checkbox.dataset.videoId,
                title: checkbox.dataset.title,
                channelTitle: checkbox.dataset.channel
            }));

            if (!confirm(`Apakah Anda yakin ingin menambahkan ${selectedSongs.length} lagu ke playlist?`)) {
                return;
            }

            const addBtn = document.getElementById('add-selected-btn');
            if (!addBtn) return;
            
            const originalText = addBtn.innerHTML;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
            addBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add_songs_bulk');
                formData.append('selected_songs', JSON.stringify(selectedSongs));

                const response = await fetch('songs.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    showToast('Lagu-lagu berhasil ditambahkan ke playlist!', 'success');
                    // Reset checkboxes
                    deselectAllSongs();
                    // Reload page to see updated song list
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error('Server error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'danger');
            } finally {
                if (addBtn) {
                    addBtn.innerHTML = originalText;
                    addBtn.disabled = false;
                }
            }
        }

        function showToast(message, type = 'info') {
            // Remove any existing toasts
            const existingToast = document.querySelector('.toast');
            if (existingToast) {
                existingToast.remove();
            }

            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'primary'} border-0" 
                     role="alert" aria-live="assertive" aria-atomic="true" 
                     style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            
            // Show the toast
            const toastElement = document.querySelector('.toast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toastElement) {
                    toastElement.remove();
                }
            }, 5000);
        }

        async function addSongFromSearch(videoId, title, artist) {
            try {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
                button.disabled = true;

                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_song_from_search&youtube_id=${videoId}&title=${encodeURIComponent(title)}&artist=${encodeURIComponent(artist)}`
                });

                const text = await response.text();
                
                // Show success notification
                showToast('Lagu berhasil ditambahkan!', 'success');
                
                // Wait 1 second then reload
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } catch (error) {
                showToast('Error: ' + error.message, 'danger');
                // Reset button
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Show PHP messages as toasts
        <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?= addslashes($message) ?>', '<?= $messageType ?>');
        });
        <?php endif; ?>
        
        // Initialize after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Allow search on Enter key
            const searchInput = document.getElementById('search_query');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchYouTube();
                    }
                });
            }
        });

        // Functions for bulk delete existing songs
        function selectAllExistingSongs() {
            document.querySelectorAll('.song-delete-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateDeleteSelectedCount();
        }

        function deselectAllExistingSongs() {
            document.querySelectorAll('.song-delete-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateDeleteSelectedCount();
        }

        function toggleAllExistingSongs() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            if (!selectAllCheckbox) return;
            
            document.querySelectorAll('.song-delete-checkbox').forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteSelectedCount();
        }

        function updateDeleteSelectedCount() {
            const selectedCount = document.querySelectorAll('.song-delete-checkbox:checked').length;
            const countElement = document.getElementById('selected-delete-count');
            const btnElement = document.getElementById('delete-selected-btn');
            
            if (countElement) {
                countElement.textContent = selectedCount;
            }
            if (btnElement) {
                btnElement.disabled = selectedCount === 0;
            }
        }

        async function deleteSelectedSongs() {
            const selectedCheckboxes = document.querySelectorAll('.song-delete-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Pilih minimal satu lagu untuk dihapus');
                return;
            }

            const selectedSongs = Array.from(selectedCheckboxes).map(checkbox => checkbox.dataset.songId);

            if (!confirm(`Apakah Anda yakin ingin menghapus ${selectedSongs.length} lagu dari playlist?`)) {
                return;
            }

            const deleteBtn = document.getElementById('delete-selected-btn');
            if (!deleteBtn) return;
            
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
            deleteBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'delete_songs_bulk');
                formData.append('selected_songs', JSON.stringify(selectedSongs));

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    showToast('Lagu-lagu berhasil dihapus dari playlist!', 'success');
                    // Reset checkboxes
                    deselectAllExistingSongs();
                    // Reload page to see updated song list
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error('Server error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'danger');
            } finally {
                if (deleteBtn) {
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                }
            }
        }
    </script>
</body>
</html>

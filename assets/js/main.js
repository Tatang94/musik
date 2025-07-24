class MusicReward {
    constructor() {
        this.player = null; // YouTube player instance
        this.currentVideoId = null;
        this.currentSongId = null;
        this.startTime = null;
        this.pausedTime = 0; // Total time spent paused
        this.songProgress = 0; // Current song position in seconds
        this.lastPauseTime = null; // When the song was paused
        this.isPlaying = false;
        this.isPaused = false;
        this.isToggling = false; // Prevent rapid button clicks
        this.minutesListened = 0;
        this.rewardEarned = 0;
        this.updateTimer = null;
        this.progressTimer = null;
        this.userBalance = 0;
        
        this.initializeEventListeners();
        this.loadUserBalance();
        this.loadBannerAd();
    }
    
    initializeEventListeners() {
        // Song card click events
        document.querySelectorAll('.song-card').forEach(card => {
            card.addEventListener('click', () => {
                const songId = card.dataset.songId;
                const youtubeId = card.dataset.youtubeId;
                this.playSong(songId, youtubeId, card);
            });
        });
        
        // Play/pause button (remove existing listeners first)
        const playPauseBtn = document.getElementById('play-pause-btn');
        if (playPauseBtn) {
            // Clone node to remove all event listeners
            const newBtn = playPauseBtn.cloneNode(true);
            playPauseBtn.parentNode.replaceChild(newBtn, playPauseBtn);
            
            // Add single event listener
            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.togglePlayPause();
            });
        }
    }
    
    async playSong(songId, youtubeId, cardElement) {
        try {
            // Show loading state
            cardElement.classList.add('loading');
            
            // Stop current song if playing
            if (this.player) {
                this.stopCurrentSong();
            }
            
            // Start listening session
            const response = await fetch('api/play_song.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    song_id: songId,
                    youtube_id: youtubeId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentSongId = songId;
                this.startTime = Date.now();
                this.pausedTime = 0;
                this.songProgress = 0;
                this.lastPauseTime = null;
                this.minutesListened = 0;
                this.rewardEarned = 0;
                
                // Initialize reward display
                document.getElementById('reward-earned').textContent = '+Rp 0.0';
                
                // Update player UI
                this.updatePlayerUI(result.song);
                this.showPlayer();
                
                // Create YouTube player
                this.createYouTubePlayer(youtubeId);
                
                // Tracking timer will be started by onPlayerReady
            } else {
                throw new Error(result.message || 'Gagal memutar lagu');
            }
        } catch (error) {
            console.error('Error playing song:', error);
            this.showError('Gagal memutar lagu: ' + error.message);
        } finally {
            cardElement.classList.remove('loading');
        }
    }
    
    createYouTubePlayer(videoId) {
        // Create or get YouTube player container in songs list
        let playerContainer = document.getElementById('youtube-player-container');
        
        if (!playerContainer) {
            // Find the songs container
            const songsContainer = document.querySelector('.songs-container');
            if (songsContainer) {
                // Create container and insert at the top of songs list
                playerContainer = document.createElement('div');
                playerContainer.id = 'youtube-player-container';
                playerContainer.style.cssText = 'width: 100%; margin-bottom: 20px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.15); background: #000; position: relative;';
                
                // Insert at the beginning of songs container
                songsContainer.insertBefore(playerContainer, songsContainer.firstChild);
            } else {
                // Fallback: create floating container
                playerContainer = document.createElement('div');
                playerContainer.id = 'youtube-player-container';
                playerContainer.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 560px; z-index: 1000; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.3); background: #000;';
                document.body.appendChild(playerContainer);
            }
        }
        
        // Clear previous player
        playerContainer.innerHTML = '<div id="youtube-player"></div>';
        
        // Create YouTube player using IFrame API
        this.currentVideoId = videoId;
        this.playerContainer = playerContainer; // Store reference
        this.initYouTubePlayer(videoId);
    }
    
    initYouTubePlayer(videoId) {
        // Ensure YouTube API is loaded
        if (typeof YT === 'undefined' || !YT.Player) {
            console.log('YouTube API not ready, retrying...');
            setTimeout(() => this.initYouTubePlayer(videoId), 500);
            return;
        }
        
        // Calculate responsive height for mobile
        const containerWidth = this.playerContainer ? (this.playerContainer.offsetWidth || 300) : 300;
        const aspectRatio = 9/16; // 16:9 aspect ratio
        const playerHeight = Math.max(200, Math.min(315, containerWidth * aspectRatio));
        
        this.player = new YT.Player('youtube-player', {
            height: playerHeight,
            width: '100%',
            videoId: videoId,
            playerVars: {
                autoplay: 1,
                controls: 1,
                fs: 1,
                playsinline: 1,
                rel: 0,
                // PASTIKAN IKLAN YOUTUBE MUNCUL - gunakan parameter minimal
                // Jangan gunakan parameter yang bisa memblokir iklan seperti:
                // - modestbranding: 1 (bisa sembunyikan logo YouTube dan iklan)
                // - iv_load_policy: 3 (bisa memblokir anotasi dan iklan overlay)
                // - cc_load_policy: 0 (tidak perlu)
                // - showinfo: 0 (deprecated dan bisa memblokir info video)
                
                // Parameter yang aman untuk iklan:
                origin: window.location.origin,
                enablejsapi: 1  // Diperlukan untuk API kontrol tapi tidak memblokir iklan
            },
            events: {
                onReady: (event) => this.onPlayerReady(event),
                onStateChange: (event) => this.onPlayerStateChange(event)
            }
        });
        
        this.isPlaying = true;
        this.isPaused = false;
        this.isToggling = false;
        
        // Update play button to pause state initially
        const playBtn = document.getElementById('play-pause-btn');
        if (playBtn) {
            const icon = playBtn.querySelector('i');
            icon.classList.remove('fa-play');
            icon.classList.add('fa-pause');
        }
    }
    
    onPlayerStateChange(event) {
        // Handle YouTube player state changes
        if (event.data === YT.PlayerState.PLAYING) {
            if (this.isPaused) {
                // Player resumed externally, update our state
                this.isPaused = false;
                const playBtn = document.getElementById('play-pause-btn');
                if (playBtn) {
                    const icon = playBtn.querySelector('i');
                    icon.classList.remove('fa-play');
                    icon.classList.add('fa-pause');
                }
                this.resumeTracking();
            }
        } else if (event.data === YT.PlayerState.PAUSED) {
            if (!this.isPaused) {
                // Player paused externally, update our state
                this.isPaused = true;
                const playBtn = document.getElementById('play-pause-btn');
                if (playBtn) {
                    const icon = playBtn.querySelector('i');
                    icon.classList.remove('fa-pause');
                    icon.classList.add('fa-play');
                }
                this.pauseTracking();
            }
        }
    }
    
    onPlayerReady(event) {
        console.log('Player ready');
        // Start progress tracking
        this.startProgressTracking();
        // Start tracking timer
        this.startTrackingTimer();
    }
    
    startProgressTracking() {
        // Simulate progress tracking since we can't access YouTube iframe API directly
        const duration = 180; // Assume 3 minutes average song length
        
        this.progressTimer = setInterval(() => {
            if (this.isPaused) return; // Don't update progress when paused
            
            this.songProgress += 1;
            const percentage = (this.songProgress / duration) * 100;
            
            document.getElementById('progress-bar').style.width = percentage + '%';
            document.getElementById('current-time').textContent = this.formatTime(this.songProgress);
            document.getElementById('total-time').textContent = this.formatTime(duration);
            
            if (this.songProgress >= duration) {
                this.onSongEnd();
                clearInterval(this.progressTimer);
            }
        }, 1000);
    }
    
    startTrackingTimer() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            const currentTime = Date.now();
            
            // Calculate actual elapsed time (excluding paused periods)
            let totalElapsedMs = currentTime - this.startTime - this.pausedTime;
            
            // If currently paused, subtract the current pause duration
            if (this.isPaused && this.lastPauseTime) {
                totalElapsedMs -= (currentTime - this.lastPauseTime);
            }
            
            const elapsedSeconds = Math.floor(totalElapsedMs / 1000);
            const elapsedMinutes = Math.floor(elapsedSeconds / 60);
            
            // Update UI every second for smooth experience
            const displayMinutes = Math.floor(elapsedSeconds / 60);
            const displaySeconds = elapsedSeconds % 60;
            document.getElementById('listening-time').textContent = `${displayMinutes}:${displaySeconds.toString().padStart(2, '0')}`;
            
            // Update reward and balance every minute (only when not paused)
            if (!this.isPaused && elapsedMinutes > this.minutesListened) {
                this.minutesListened = elapsedMinutes;
                this.rewardEarned = this.minutesListened * 0.5;
                
                // Update reward display
                document.getElementById('reward-earned').textContent = '+Rp ' + this.rewardEarned.toFixed(1);
                
                // Update balance in database
                this.updateBalance();
                
                console.log(`Minutes listened: ${this.minutesListened}, Reward: Rp ${this.rewardEarned}`);
            }
        }, 1000);
    }
    
    async updateBalance() {
        try {
            const response = await fetch('api/update_balance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    song_id: this.currentSongId,
                    minutes_listened: this.minutesListened,
                    reward_earned: this.rewardEarned
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.userBalance = result.new_balance;
                document.getElementById('user-balance').textContent = 'Rp ' + this.formatNumber(result.new_balance);
            }
        } catch (error) {
            console.error('Error updating balance:', error);
        }
    }
    
    updatePlayerUI(song) {
        document.getElementById('player-title').textContent = song.title;
        document.getElementById('player-artist').textContent = song.artist;
        
        if (song.thumbnail_url) {
            document.getElementById('player-thumbnail').src = song.thumbnail_url;
        } else {
            document.getElementById('player-thumbnail').style.display = 'none';
        }
    }
    
    showPlayer() {
        document.getElementById('music-player').style.display = 'block';
    }
    
    hidePlayer() {
        document.getElementById('music-player').style.display = 'none';
    }
    
    togglePlayPause() {
        // Prevent multiple rapid clicks
        if (this.isToggling) return;
        this.isToggling = true;
        
        const btn = document.getElementById('play-pause-btn');
        const icon = btn.querySelector('i');
        
        if (!this.isPlaying) {
            // Song is not playing at all
            this.isToggling = false;
            return;
        }
        
        if (!this.isPaused) {
            // Currently playing - pause it
            console.log('Pausing playback');
            if (this.player && this.player.pauseVideo) {
                this.player.pauseVideo();
            }
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
            this.isPaused = true;
            this.pauseTracking();
        } else {
            // Currently paused - resume it
            console.log('Resuming playback');
            if (this.player && this.player.playVideo) {
                this.player.playVideo();
            }
            icon.classList.remove('fa-play');
            icon.classList.add('fa-pause');
            this.isPaused = false;
            this.resumeTracking();
        }
        
        // Allow next toggle after a brief delay
        setTimeout(() => {
            this.isToggling = false;
        }, 500);
    }
    
    pauseTracking() {
        this.lastPauseTime = Date.now();
        // Don't clear timers - just pause the tracking logic
        console.log('Tracking paused');
    }
    
    resumeTracking() {
        if (this.lastPauseTime) {
            // Add the pause duration to total paused time
            this.pausedTime += Date.now() - this.lastPauseTime;
            this.lastPauseTime = null;
        }
        console.log('Tracking resumed');
    }
    
    stopCurrentSong() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        if (this.progressTimer) {
            clearInterval(this.progressTimer);
        }
        
        // Final balance update
        if (this.currentSongId && this.minutesListened > 0) {
            this.updateBalance();
        }
        
        this.hidePlayer();
        
        // Destroy YouTube player
        if (this.player && this.player.destroy) {
            this.player.destroy();
        }
        this.player = null;
        this.currentSongId = null;
    }
    
    onSongEnd() {
        // Mark as completed and update final balance
        this.updateBalance();
        this.stopCurrentSong();
        this.showSuccess('Lagu selesai! Reward telah ditambahkan ke saldo Anda.');
    }
    
    async loadUserBalance() {
        try {
            const response = await fetch('api/get_balance.php');
            const result = await response.json();
            
            if (result.success) {
                this.userBalance = result.balance;
                document.getElementById('user-balance').textContent = 'Rp ' + this.formatNumber(result.balance);
            }
        } catch (error) {
            console.error('Error loading balance:', error);
        }
    }
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    async loadBannerAd() {
        try {
            const response = await fetch('api/get_banner.php');
            const result = await response.json();
            
            if (result.success && result.script_code) {
                const bannerContainer = document.getElementById('adsterra-banner');
                if (bannerContainer) {
                    bannerContainer.innerHTML = result.script_code;
                    bannerContainer.style.display = 'block';
                }
            } else {
                // Hide banner container if no active banner
                const bannerContainer = document.getElementById('adsterra-banner');
                if (bannerContainer) {
                    bannerContainer.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error loading banner ad:', error);
            const bannerContainer = document.getElementById('adsterra-banner');
            if (bannerContainer) {
                bannerContainer.style.display = 'none';
            }
        }
    }
    

    
    showError(message) {
        this.showAlert(message, 'danger');
    }
    
    showSuccess(message) {
        this.showAlert(message, 'success');
    }
    
    showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.querySelector('.main-container');
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
}

// Withdrawal functions
function showWithdrawModal() {
    const modal = new bootstrap.Modal(document.getElementById('withdrawModal'));
    modal.show();
}

async function submitWithdraw() {
    const danaPhone = document.getElementById('danaPhone').value;
    const withdrawAmount = document.getElementById('withdrawAmount').value;
    
    if (!danaPhone || !withdrawAmount) {
        alert('Mohon lengkapi semua field');
        return;
    }
    
    if (withdrawAmount < 10000) {
        alert('Minimal penarikan Rp 10.000');
        return;
    }
    
    try {
        const response = await fetch('api/withdraw.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                dana_phone: danaPhone,
                amount: parseFloat(withdrawAmount)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Permintaan penarikan berhasil disubmit!');
            bootstrap.Modal.getInstance(document.getElementById('withdrawModal')).hide();
            // Refresh page to update balance
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

// Navigation functions
function goToMusic() {
    window.location.href = '/songs.php';
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    window.musicReward = new MusicReward();
});

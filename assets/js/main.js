class MusicReward {
    constructor() {
        this.currentPlayer = null;
        this.currentSongId = null;
        this.startTime = null;
        this.minutesListened = 0;
        this.rewardEarned = 0;
        this.updateTimer = null;
        this.userBalance = 0;
        
        this.initializeEventListeners();
        this.loadUserBalance();
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
        
        // Play/pause button
        document.getElementById('play-pause-btn')?.addEventListener('click', () => {
            this.togglePlayPause();
        });
    }
    
    async playSong(songId, youtubeId, cardElement) {
        try {
            // Show loading state
            cardElement.classList.add('loading');
            
            // Stop current song if playing
            if (this.currentPlayer) {
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
                this.minutesListened = 0;
                this.rewardEarned = 0;
                
                // Update player UI
                this.updatePlayerUI(result.song);
                this.showPlayer();
                
                // Create YouTube player
                this.createYouTubePlayer(youtubeId);
                
                // Start tracking timer
                this.startTrackingTimer();
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
        // Create iframe for YouTube player (hidden)
        const playerContainer = document.getElementById('youtube-player-container') || (() => {
            const container = document.createElement('div');
            container.id = 'youtube-player-container';
            container.style.display = 'none';
            document.body.appendChild(container);
            return container;
        })();
        
        playerContainer.innerHTML = `
            <iframe id="youtube-player" 
                    width="560" 
                    height="315" 
                    src="https://www.youtube.com/embed/${videoId}?autoplay=1&enablejsapi=1&origin=${window.location.origin}" 
                    frameborder="0" 
                    allow="autoplay; encrypted-media" 
                    allowfullscreen>
            </iframe>
        `;
        
        // Simulate player ready state
        setTimeout(() => {
            this.onPlayerReady();
        }, 1000);
    }
    
    onPlayerReady() {
        console.log('Player ready');
        // Start progress tracking
        this.startProgressTracking();
    }
    
    startProgressTracking() {
        // Simulate progress tracking since we can't access YouTube iframe API directly
        let progress = 0;
        const duration = 180; // Assume 3 minutes average song length
        
        this.progressTimer = setInterval(() => {
            progress += 1;
            const percentage = (progress / duration) * 100;
            
            document.getElementById('progress-bar').style.width = percentage + '%';
            document.getElementById('current-time').textContent = this.formatTime(progress);
            document.getElementById('total-time').textContent = this.formatTime(duration);
            
            if (progress >= duration) {
                this.onSongEnd();
                clearInterval(this.progressTimer);
            }
        }, 1000);
    }
    
    startTrackingTimer() {
        this.updateTimer = setInterval(() => {
            const currentTime = Date.now();
            const elapsedMinutes = Math.floor((currentTime - this.startTime) / 60000);
            
            if (elapsedMinutes > this.minutesListened) {
                this.minutesListened = elapsedMinutes;
                this.rewardEarned = this.minutesListened * 0.5;
                
                // Update UI
                document.getElementById('listening-time').textContent = this.minutesListened + ' menit';
                document.getElementById('reward-earned').textContent = '+Rp ' + this.rewardEarned.toFixed(0);
                
                // Update balance in database
                this.updateBalance();
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
        const btn = document.getElementById('play-pause-btn');
        const icon = btn.querySelector('i');
        
        if (icon.classList.contains('fa-pause')) {
            // Pause
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
            this.pauseTracking();
        } else {
            // Play
            icon.classList.remove('fa-play');
            icon.classList.add('fa-pause');
            this.resumeTracking();
        }
    }
    
    pauseTracking() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        if (this.progressTimer) {
            clearInterval(this.progressTimer);
        }
    }
    
    resumeTracking() {
        this.startTrackingTimer();
        this.startProgressTracking();
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
        this.currentPlayer = null;
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

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    window.musicReward = new MusicReward();
});

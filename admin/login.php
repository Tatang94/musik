<?php
session_start();

$message = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Check credentials
    if ($username === '089663596711' && $password === 'boar') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $message = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MusikReward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* Enhanced admin login page styles */
        body {
            background: var(--wave-gradient);
            background-size: 400% 400%;
            animation: waveAnimation 15s ease-in-out infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        
        @keyframes waveAnimation {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(107, 70, 193, 0.3);
            width: 100%;
            max-width: 420px;
            opacity: 0;
            transform: translateY(30px);
            animation: slideInUp 0.8s ease forwards;
            animation-delay: 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-icon {
            background: var(--wave-gradient);
            background-size: 200% 200%;
            animation: iconPulse 3s ease-in-out infinite;
            color: white;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.2rem;
            box-shadow: 0 10px 30px rgba(107, 70, 193, 0.4);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        @keyframes iconPulse {
            0%, 100% { 
                background-position: 0% 50%; 
                transform: scale(1);
            }
            50% { 
                background-position: 100% 50%; 
                transform: scale(1.05);
            }
        }
        
        .login-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.8rem;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid rgba(107, 70, 193, 0.1);
            padding: 14px 18px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(107, 70, 193, 0.1);
            background: white;
        }
        
        .btn-primary {
            background: var(--wave-gradient);
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(107, 70, 193, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(107, 70, 193, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .login-container {
                padding: 30px 25px;
            }
            .login-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
        }
        
        /* Loading state */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>Admin Login</h2>
            <p class="text-muted">MusikReward Management</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required 
                       placeholder="Masukkan username admin" autocomplete="username">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required 
                       placeholder="Masukkan password admin" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="../" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Kembali ke Aplikasi
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced admin login functionality
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const loginButton = document.querySelector('button[type="submit"]');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // Add smooth loading state
            document.body.style.opacity = '1';
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                // Add loading state
                loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
                loginButton.disabled = true;
                
                // Create loading overlay
                const overlay = document.createElement('div');
                overlay.className = 'loading-overlay';
                overlay.style.display = 'flex';
                overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
                document.body.appendChild(overlay);
            });
            
            // Auto-focus username field
            usernameInput.focus();
            
            // Enter key navigation
            usernameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    passwordInput.focus();
                }
            });
            
            // Enhanced form validation
            function validateForm() {
                const username = usernameInput.value.trim();
                const password = passwordInput.value.trim();
                
                if (username && password) {
                    loginButton.disabled = false;
                    loginButton.classList.remove('disabled');
                } else {
                    loginButton.disabled = true;
                    loginButton.classList.add('disabled');
                }
            }
            
            usernameInput.addEventListener('input', validateForm);
            passwordInput.addEventListener('input', validateForm);
            
            // Initial validation
            validateForm();
            
            // Add ripple effect to button
            loginButton.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Error handling
        window.addEventListener('error', function(e) {
            console.log('Resource loading handled gracefully');
        });
    </script>
    <style>
        /* Additional button animations */
        .btn-primary {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: rippleEffect 0.6s linear;
        }
        
        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .btn-primary.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Loading state enhancements */
        body {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    </style>
</body>
</html>
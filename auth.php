<?php
/**
 * WeatherGPT - Authentication Portal
 * 
 * Standalone login & registration page with embedded CSS/JS styling.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherGPT — Login & Portal Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --panel-border: rgba(255, 255, 255, 0.1);
            --accent-primary: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error-color: #f43f5e;
            --success-color: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--panel-border);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc, #6366f1, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 6px;
        }

        .tabs {
            display: flex;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 24px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: var(--accent-primary);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            color: var(--text-main);
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--accent-glow);
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: none;
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fecdd3;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }

        .demo-credentials {
            margin-top: 24px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: center;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .back-link:hover {
            color: var(--text-main);
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="brand-header">
            <div class="brand-logo">☁️ WeatherGPT</div>
            <div class="brand-subtitle">Conversational Weather Intelligence Platform</div>
        </div>

        <div class="tabs">
            <button type="button" class="tab-btn active" id="btn-tab-login" onclick="switchTab('login')">Login</button>
            <button type="button" class="tab-btn" id="btn-tab-register" onclick="switchTab('register')">Register</button>
        </div>

        <div id="alert-message" class="alert-box"></div>

        <!-- Login Form -->
        <form id="form-login" onsubmit="handleAuthSubmit(event, 'login')">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group">
                <label class="form-label" for="login-username">Username or Email</label>
                <input type="text" id="login-username" name="username" class="form-input" placeholder="admin" required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="login-password">Password</label>
                <input type="password" id="login-password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" class="submit-btn">Sign In to WeatherGPT</button>
        </form>

        <!-- Register Form -->
        <form id="form-register" style="display: none;" onsubmit="handleAuthSubmit(event, 'register')">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group">
                <label class="form-label" for="reg-username">Username</label>
                <input type="text" id="reg-username" name="username" class="form-input" placeholder="johndoe" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="reg-email">Email Address</label>
                <input type="email" id="reg-email" name="email" class="form-input" placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label class="form-label" for="reg-password">Password (min. 6 chars)</label>
                <input type="password" id="reg-password" name="password" class="form-input" placeholder="••••••••" required autocomplete="new-password">
            </div>
            <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <div class="demo-credentials">
            🔑 Prototype Admin: <strong>admin</strong> / <strong>admin</strong><br>
            <small>(Password change forced upon initial login)</small>
        </div>

        <a href="index.php" class="back-link">← Return to WeatherGPT App</a>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('form-login');
            const regForm = document.getElementById('form-register');
            const loginBtn = document.getElementById('btn-tab-login');
            const regBtn = document.getElementById('btn-tab-register');
            const alertBox = document.getElementById('alert-message');
            
            alertBox.style.display = 'none';

            if (tab === 'login') {
                loginForm.style.display = 'block';
                regForm.style.display = 'none';
                loginBtn.classList.add('active');
                regBtn.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                regForm.style.display = 'block';
                regBtn.classList.add('active');
                loginBtn.classList.remove('active');
            }
        }

        async function handleAuthSubmit(event, action) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', action);

            const alertBox = document.getElementById('alert-message');
            alertBox.style.display = 'none';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.textContent = action === 'login' ? 'Authentication successful! Redirecting...' : 'Account created! Redirecting...';
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        if (result.data && result.data.user && result.data.user.role === 'admin') {
                            window.location.href = 'admin.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    }, 1000);
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.textContent = result.error ? result.error.message : 'Authentication failed.';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.className = 'alert-box alert-error';
                alertBox.textContent = 'Server connection error. Please try again.';
                alertBox.style.display = 'block';
            }
        }
    </script>
</body>
</html>

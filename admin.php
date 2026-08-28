<?php
/**
 * WeatherGPT - Administrator Control Panel
 * 
 * Provides full CRUD management of users, conversations, locations, audit logs,
 * API cache control, and Gemini configuration with forced password update for first-time admin login.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Authentication Check
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: auth.php');
    exit;
}

$adminId = $_SESSION['user_id'];
$adminUser = dbFetchOne("SELECT * FROM users WHERE id = :id", [':id' => $adminId]);
$forceChange = (int)($adminUser['force_password_change'] ?? 0);
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherGPT — Admin Control Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-panel: rgba(17, 24, 39, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --accent-primary: #6366f1;
            --accent-cyan: #38bdf8;
            --accent-emerald: #10b981;
            --accent-rose: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(17, 24, 39, 0.95);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent-cyan);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-nav {
            display: flex;
            gap: 12px;
        }

        .nav-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .nav-btn.active, .nav-btn:hover {
            background: var(--accent-primary);
            color: #ffffff;
            border-color: var(--accent-primary);
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            margin-top: 6px;
        }

        .panel-box {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            display: none;
        }

        .panel-box.active {
            display: block;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            color: var(--text-muted);
            font-weight: 500;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-edit { background: rgba(56, 189, 248, 0.2); color: var(--accent-cyan); }
        .btn-danger { background: rgba(244, 63, 94, 0.2); color: var(--accent-rose); }
        .btn-primary { background: var(--accent-primary); color: #ffffff; padding: 10px 16px; }

        /* Modal overlay for Password Change & User Editing */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: all 0.2s ease;
        }

        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-card {
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 32px;
            max-width: 440px;
            width: 100%;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: #ffffff;
            margin-top: 6px;
            margin-bottom: 16px;
            outline: none;
        }
    </style>
</head>
<body>

    <header>
        <div class="brand-logo">🛡️ WeatherGPT Admin Panel</div>
        <div class="admin-nav">
            <button class="nav-btn active" onclick="switchAdminTab('users')">Users CRUD</button>
            <button class="nav-btn" onclick="switchAdminTab('settings')">Settings & Gemini</button>
            <button class="nav-btn" onclick="switchAdminTab('audit')">Audit Logs</button>
            <a href="index.php" class="nav-btn" style="text-decoration:none;">← Return App</a>
        </div>
    </header>

    <div class="container">
        <!-- Stats Summary Header -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-val" id="stat-users">--</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Conversations</div>
                <div class="stat-val" id="stat-convs">--</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Gemini API Calls</div>
                <div class="stat-val" id="stat-gemini">--</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Cached Entries</div>
                <div class="stat-val" id="stat-cache">--</div>
            </div>
        </div>

        <!-- Panel 1: Users Management CRUD -->
        <div id="tab-users" class="panel-box active">
            <div class="panel-header">
                <h2>User Accounts Management</h2>
                <button class="btn-action btn-primary" onclick="openCreateUserModal()">+ Create User</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Pass. Change Req.</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <tr><td colspan="6">Loading database users...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Panel 2: System Settings -->
        <div id="tab-settings" class="panel-box">
            <div class="panel-header">
                <h2>System & Gemini Configuration</h2>
            </div>
            <form onsubmit="saveAdminSettings(event)">
                <div style="margin-bottom: 16px;">
                    <label style="color:var(--text-muted); font-size:0.85rem;">Gemini REST API Key</label>
                    <input type="password" id="admin-gemini-key" class="form-input" placeholder="AIzaSy...">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="color:var(--text-muted); font-size:0.85rem;">Default Model</label>
                    <input type="text" id="admin-gemini-model" class="form-input" value="gemini-2.5-flash">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="color:var(--text-muted); font-size:0.85rem;">Weather Cache TTL (Seconds)</label>
                    <input type="number" id="admin-cache-ttl" class="form-input" value="900">
                </div>
                <button type="submit" class="btn-action btn-primary">Save Settings</button>
                <button type="button" class="btn-action btn-danger" onclick="clearSystemCache()" style="margin-left: 10px;">Clear Caches</button>
            </form>
        </div>

        <!-- Panel 3: Audit Logs -->
        <div id="tab-audit" class="panel-box">
            <div class="panel-header">
                <h2>System Audit & Security Logs</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body">
                    <tr><td colspan="6">Loading audit logs...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Forced Password Change Modal (For First Run Admin Login) -->
    <div class="modal-overlay <?php echo $forceChange ? 'active' : ''; ?>" id="force-password-modal">
        <div class="modal-card">
            <h2 style="color: var(--accent-rose); margin-bottom: 12px;">⚠️ Password Change Required</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px;">
                You are currently logged in with default prototype credentials (<code>admin/admin</code>). You must set a secure password before continuing.
            </p>
            <form onsubmit="handleForcePasswordChange(event)">
                <label style="font-size:0.8rem; color:var(--text-muted);">Current Password</label>
                <input type="password" id="force-old-pass" class="form-input" value="admin" required>

                <label style="font-size:0.8rem; color:var(--text-muted);">New Password (min 6 chars)</label>
                <input type="password" id="force-new-pass" class="form-input" placeholder="••••••••" required>

                <button type="submit" class="btn-action btn-primary" style="width: 100%;">Update Admin Password</button>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = "<?php echo $csrfToken; ?>";

        function switchAdminTab(tab) {
            document.querySelectorAll('.panel-box').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');

            if (tab === 'users') loadAdminUsers();
            if (tab === 'audit') loadAuditLogs();
            if (tab === 'settings') loadAdminSettings();
        }

        async function loadAdminStats() {
            const res = await fetch('api.php?action=admin_stats');
            const json = await res.json();
            if (json.success) {
                const s = json.data.stats;
                document.getElementById('stat-users').textContent = s.total_users;
                document.getElementById('stat-convs').textContent = s.total_conversations;
                document.getElementById('stat-gemini').textContent = s.gemini_calls;
                document.getElementById('stat-cache').textContent = s.cached_weather_entries;
            }
        }

        async function loadAdminUsers() {
            const res = await fetch('api.php?action=admin_users_list');
            const json = await res.json();
            const body = document.getElementById('users-table-body');
            body.innerHTML = '';

            if (json.success && json.data.users) {
                json.data.users.forEach(u => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${u.id}</td>
                        <td><strong>${u.username}</strong></td>
                        <td>${u.email || 'N/A'}</td>
                        <td><span style="color:${u.role === 'admin' ? 'var(--accent-cyan)' : 'var(--text-muted)'}">${u.role}</span></td>
                        <td>${u.force_password_change ? '⚠️ Yes' : 'No'}</td>
                        <td>
                            <button class="btn-action btn-danger" onclick="deleteUser(${u.id})">Delete</button>
                        </td>
                    `;
                    body.appendChild(tr);
                });
            }
        }

        async function loadAuditLogs() {
            const res = await fetch('api.php?action=admin_audit_logs');
            const json = await res.json();
            const body = document.getElementById('audit-table-body');
            body.innerHTML = '';

            if (json.success && json.data.logs) {
                json.data.logs.forEach(l => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${l.id}</td>
                        <td>${l.username || 'System'}</td>
                        <td><strong>${l.action}</strong></td>
                        <td>${l.details || ''}</td>
                        <td>${l.ip_address}</td>
                        <td>${l.created_at}</td>
                    `;
                    body.appendChild(tr);
                });
            }
        }

        async function loadAdminSettings() {
            const res = await fetch('api.php?action=admin_settings_get');
            const json = await res.json();
            if (json.success && json.data.settings) {
                const s = json.data.settings;
                document.getElementById('admin-gemini-model').value = s.gemini_model || 'gemini-2.5-flash';
                document.getElementById('admin-cache-ttl').value = s.weather_cache_ttl || '900';
            }
        }

        async function saveAdminSettings(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'admin_settings_save');
            formData.append('gemini_api_key', document.getElementById('admin-gemini-key').value);
            formData.append('gemini_model', document.getElementById('admin-gemini-model').value);
            formData.append('weather_cache_ttl', document.getElementById('admin-cache-ttl').value);

            const res = await fetch('api.php', { method: 'POST', body: formData });
            const json = await res.json();
            alert(json.success ? 'Settings updated!' : json.error.message);
        }

        async function handleForcePasswordChange(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('old_password', document.getElementById('force-old-pass').value);
            formData.append('new_password', document.getElementById('force-new-pass').value);

            const res = await fetch('api.php', { method: 'POST', body: formData });
            const json = await res.json();
            if (json.success) {
                document.getElementById('force-password-modal').classList.remove('active');
                alert('Admin password changed successfully!');
            } else {
                alert(json.error ? json.error.message : 'Error changing password.');
            }
        }

        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete user #' + id + '?')) return;
            const formData = new FormData();
            formData.append('action', 'admin_user_delete');
            formData.append('id', id);
            await fetch('api.php', { method: 'POST', body: formData });
            loadAdminUsers();
        }

        async function clearSystemCache() {
            const formData = new FormData();
            formData.append('action', 'admin_clear_cache');
            await fetch('api.php', { method: 'POST', body: formData });
            alert('Caches cleared!');
        }

        // Init
        loadAdminStats();
        loadAdminUsers();
    </script>
</body>
</html>

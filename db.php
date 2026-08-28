<?php
/**
 * WeatherGPT - SQLite Database Connection & Management
 * 
 * Auto-creates the data directory, initializes SQLite schema via PDO_SQLITE,
 * handles automatic database migrations, seeds default admin credentials,
 * and provides secure database query helpers.
 */

require_once __DIR__ . '/config.php';

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Ensure data directory exists
        if (!file_exists(WEATHERGPT_DATA_DIR)) {
            mkdir(WEATHERGPT_DATA_DIR, 0755, true);
        }

        // Connect to SQLite
        $pdo = new PDO('sqlite:' . WEATHERGPT_DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable Foreign Keys and Performance Settings
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');

        // Run auto-initialization schema
        initDatabaseSchema($pdo);

        return $pdo;
    } catch (PDOException $e) {
        error_log('Database Connection Error: ' . $e->getMessage());
        sendJsonResponse(false, null, [
            'code' => 'DATABASE_ERROR',
            'message' => 'Failed to initialize database connection. Ensure PDO_SQLITE is enabled on Hostinger PHP.'
        ], 500);
    }
}

function initDatabaseSchema(PDO $pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        force_password_change INTEGER NOT NULL DEFAULT 0,
        language_preference TEXT DEFAULT 'en',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");

    // 2. Conversations Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        metadata_json TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )");

    // 4. Saved Locations Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_locations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        latitude REAL NOT NULL,
        longitude REAL NOT NULL,
        country TEXT,
        is_default INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 5. System Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 6. Weather Cache Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS weather_cache (
        cache_key TEXT PRIMARY KEY,
        response_data TEXT NOT NULL,
        expires_at INTEGER NOT NULL
    )");

    // 7. Search Cache Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS search_cache (
        cache_key TEXT PRIMARY KEY,
        results_json TEXT NOT NULL,
        expires_at INTEGER NOT NULL
    )");

    // 8. API Usage Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_usage_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service TEXT NOT NULL,
        endpoint TEXT NOT NULL,
        status_code INTEGER NOT NULL,
        response_time_ms INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 9. Admin Audit Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        details TEXT,
        ip_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create Indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_conversations_user ON conversations(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_conv ON messages(conversation_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_weather_cache_exp ON weather_cache(expires_at)");

    // Seed Default Admin User if no users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        $adminPassHash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, force_password_change) VALUES (:username, :email, :pass, 'admin', 1)");
        $insertAdmin->execute([
            ':username' => DEFAULT_ADMIN_USER,
            ':email' => 'admin@weathergpt.local',
            ':pass' => $adminPassHash
        ]);

        // Default System Settings
        $insertSetting = $pdo->prepare("INSERT OR IGNORE INTO system_settings (setting_key, setting_value) VALUES (:key, :val)");
        $defaultSettings = [
            'gemini_api_key' => '',
            'gemini_model' => DEFAULT_GEMINI_MODEL,
            'weather_cache_ttl' => '900', // 15 mins
            'duckduckgo_enabled' => '1',
            'system_notice' => 'Welcome to WeatherGPT Conversational Intelligence Platform.'
        ];
        foreach ($defaultSettings as $k => $v) {
            $insertSetting->execute([':key' => $k, ':val' => $v]);
        }
    }
}

// Database Helper Functions
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetchOne($sql, $params = []) {
    return dbQuery($sql, $params)->fetch();
}

function dbFetchAll($sql, $params = []) {
    return dbQuery($sql, $params)->fetchAll();
}

function logAuditAction($userId, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        dbQuery("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:uid, :act, :det, :ip)", [
            ':uid' => $userId,
            ':act' => $action,
            ':det' => is_array($details) ? json_encode($details) : (string)$details,
            ':ip' => $ip
        ]);
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

function logApiCall($service, $endpoint, $statusCode, $responseTimeMs = 0) {
    try {
        dbQuery("INSERT INTO api_usage_logs (service, endpoint, status_code, response_time_ms) VALUES (:svc, :ep, :st, :rt)", [
            ':svc' => $service,
            ':ep'  => $endpoint,
            ':st'  => $statusCode,
            ':rt'  => $responseTimeMs
        ]);
    } catch (Exception $e) {
        error_log('API usage log failed: ' . $e->getMessage());
    }
}

function getSystemSetting($key, $default = '') {
    $row = dbFetchOne("SELECT setting_value FROM system_settings WHERE setting_key = :k", [':k' => $key]);
    return $row ? $row['setting_value'] : $default;
}

function setSystemSetting($key, $value) {
    dbQuery("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, CURRENT_TIMESTAMP)
             ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v, updated_at = CURRENT_TIMESTAMP", [
        ':k' => $key,
        ':v' => $value
    ]);
}

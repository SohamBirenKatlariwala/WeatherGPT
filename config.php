<?php
/**
 * WeatherGPT - Hostinger Configuration & Global Helper File
 * 
 * Provides session management, security headers, CSRF token handling,
 * Gemini API configuration, supported languages dictionary, and JSON output helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters where HTTPS is available
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Global Path & File Constants
define('WEATHERGPT_ROOT', __DIR__);
define('WEATHERGPT_DATA_DIR', __DIR__ . '/data');
define('WEATHERGPT_DB_FILE', WEATHERGPT_DATA_DIR . '/weathergpt.sqlite');
define('WEATHERGPT_VERSION', '1.0.0');

// Default Admin Credentials
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin');

// Default API Configurations (Can be overridden in Settings via DB)
define('DEFAULT_GEMINI_MODEL', 'gemini-2.5-flash');
define('DEFAULT_OPEN_METEO_BASE', 'https://api.open-meteo.com/v1');
define('DEFAULT_GEOCODING_BASE', 'https://geocoding-api.open-meteo.com/v1');
define('DEFAULT_ARCHIVE_BASE', 'https://archive-api.open-meteo.com/v1');

// CSRF Protection Functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// JSON Output Helper
function sendJsonResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode([
        'success' => (bool)$success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Supported Languages Dictionary
$GLOBALS['SUPPORTED_LANGUAGES'] = [
    'auto' => ['name' => 'Auto Detect', 'native' => 'Auto Detect', 'speech_code' => 'en-US'],
    'en'   => ['name' => 'English',   'native' => 'English',     'speech_code' => 'en-US'],
    'hi'   => ['name' => 'Hindi',     'native' => 'हिन्दी',       'speech_code' => 'hi-IN'],
    'bn'   => ['name' => 'Bengali',   'native' => 'বাংলা',       'speech_code' => 'bn-IN'],
    'te'   => ['name' => 'Telugu',    'native' => 'తెలుగు',      'speech_code' => 'te-IN'],
    'mr'   => ['name' => 'Marathi',   'native' => 'मराठी',       'speech_code' => 'mr-IN'],
    'ta'   => ['name' => 'Tamil',     'native' => 'தமிழ்',       'speech_code' => 'ta-IN'],
    'gu'   => ['name' => 'Gujarati',  'native' => 'ગુજરાતી',     'speech_code' => 'gu-IN'],
    'kn'   => ['name' => 'Kannada',   'native' => 'ಕನ್ನಡ',      'speech_code' => 'kn-IN'],
    'ml'   => ['name' => 'Malayalam', 'native' => 'മലയാളം',   'speech_code' => 'ml-IN'],
    'pa'   => ['name' => 'Punjabi',   'native' => 'ਪੰਜਾਬੀ',      'speech_code' => 'pa-IN'],
    'ur'   => ['name' => 'Urdu',      'native' => 'اردو',        'speech_code' => 'ur-PK'],
    'or'   => ['name' => 'Odia',      'native' => 'ଓଡ଼ିଆ',       'speech_code' => 'or-IN'],
    'as'   => ['name' => 'Assamese',  'native' => 'অসমীয়া',     'speech_code' => 'as-IN'],
];

// Sanitize Output Helper
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

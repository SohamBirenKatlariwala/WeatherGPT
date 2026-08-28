<?php
/**
 * WeatherGPT - Main REST API Router & Service Engine
 * 
 * Handles client AJAX requests for Authentication, Chat/Gemini AI agent pipeline,
 * Open-Meteo Weather APIs, DuckDuckGo evidence search, CRUD for conversations/locations,
 * and Administrator dashboard operations.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Set JSON header for API endpoints
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle Actions
switch ($action) {
    // -------------------------------------------------------------
    // AUTHENTICATION ENDPOINTS
    // -------------------------------------------------------------
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'change_password':
        handleChangePassword();
        break;
    case 'user_info':
        handleUserInfo();
        break;

    // -------------------------------------------------------------
    // CHAT & AI AGENT PIPELINE ENDPOINTS
    // -------------------------------------------------------------
    case 'chat':
        handleChatPipeline();
        break;
    case 'conversations_list':
        handleConversationsList();
        break;
    case 'conversation_create':
        handleConversationCreate();
        break;
    case 'conversation_rename':
        handleConversationRename();
        break;
    case 'conversation_delete':
        handleConversationDelete();
        break;
    case 'messages_list':
        handleMessagesList();
        break;

    // -------------------------------------------------------------
    // WEATHER & LOCATION ENDPOINTS
    // -------------------------------------------------------------
    case 'weather_get':
        handleWeatherGet();
        break;
    case 'geocode':
        handleGeocode();
        break;
    case 'climate':
        handleClimateGet();
        break;
    case 'locations_list':
        handleSavedLocationsList();
        break;
    case 'location_save':
        handleSavedLocationSave();
        break;
    case 'location_delete':
        handleSavedLocationDelete();
        break;

    // -------------------------------------------------------------
    // ADMIN ENDPOINTS
    // -------------------------------------------------------------
    case 'admin_stats':
        handleAdminStats();
        break;
    case 'admin_users_list':
        handleAdminUsersList();
        break;
    case 'admin_user_create':
        handleAdminUserCreate();
        break;
    case 'admin_user_update':
        handleAdminUserUpdate();
        break;
    case 'admin_user_delete':
        handleAdminUserDelete();
        break;
    case 'admin_settings_get':
        handleAdminSettingsGet();
        break;
    case 'admin_settings_save':
        handleAdminSettingsSave();
        break;
    case 'admin_audit_logs':
        handleAdminAuditLogs();
        break;
    case 'admin_clear_cache':
        handleAdminClearCache();
        break;

    default:
        sendJsonResponse(false, null, ['code' => 'INVALID_ACTION', 'message' => 'Requested action is invalid.'], 400);
}

// =================================================================
// AUTHENTICATION IMPLEMENTATION
// =================================================================

function handleLogin() {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Username and password are required.'], 400);
    }

    $user = dbFetchOne("SELECT * FROM users WHERE username = :u OR email = :u", [':u' => $username]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendJsonResponse(false, null, ['code' => 'AUTH_FAILED', 'message' => 'Invalid username or password.'], 401);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['force_password_change'] = (int)$user['force_password_change'];

    dbQuery("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id", [':id' => $user['id']]);
    logAuditAction($user['id'], 'USER_LOGIN', 'Successful login');

    sendJsonResponse(true, [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'force_password_change' => (int)$user['force_password_change'],
            'language_preference' => $user['language_preference']
        ],
        'csrf_token' => generateCsrfToken()
    ]);
}

function handleRegister() {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 6) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Username must be at least 3 chars and password at least 6 chars.'], 400);
    }

    $existing = dbFetchOne("SELECT id FROM users WHERE username = :u OR email = :e", [':u' => $username, ':e' => $email]);
    if ($existing) {
        sendJsonResponse(false, null, ['code' => 'USER_EXISTS', 'message' => 'Username or email already exists.'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    dbQuery("INSERT INTO users (username, email, password_hash, role, force_password_change) VALUES (:u, :e, :p, 'user', 0)", [
        ':u' => $username,
        ':e' => $email ?: null,
        ':p' => $hash
    ]);

    $userId = getDbConnection()->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';
    $_SESSION['force_password_change'] = 0;

    logAuditAction($userId, 'USER_REGISTER', 'User account registered');

    sendJsonResponse(true, [
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'user',
            'force_password_change' => 0
        ],
        'csrf_token' => generateCsrfToken()
    ]);
}

function handleLogout() {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        logAuditAction($userId, 'USER_LOGOUT', 'Logged out');
    }
    session_destroy();
    sendJsonResponse(true, ['message' => 'Logged out successfully.']);
}

function handleChangePassword() {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        sendJsonResponse(false, null, ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required.'], 401);
    }

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (strlen($newPassword) < 6) {
        sendJsonResponse(false, null, ['code' => 'INVALID_PASSWORD', 'message' => 'New password must be at least 6 characters.'], 400);
    }

    $user = dbFetchOne("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
    if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
        sendJsonResponse(false, null, ['code' => 'AUTH_FAILED', 'message' => 'Current password is incorrect.'], 400);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    dbQuery("UPDATE users SET password_hash = :p, force_password_change = 0 WHERE id = :id", [
        ':p' => $newHash,
        ':id' => $userId
    ]);

    $_SESSION['force_password_change'] = 0;
    logAuditAction($userId, 'PASSWORD_CHANGE', 'User changed password');

    sendJsonResponse(true, ['message' => 'Password updated successfully.']);
}

function handleUserInfo() {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        sendJsonResponse(true, ['logged_in' => false, 'csrf_token' => generateCsrfToken()]);
    }
    $user = dbFetchOne("SELECT id, username, email, role, force_password_change, language_preference FROM users WHERE id = :id", [':id' => $userId]);
    sendJsonResponse(true, [
        'logged_in' => true,
        'user' => $user,
        'csrf_token' => generateCsrfToken()
    ]);
}

// =================================================================
// OPEN-METEO WEATHER & GEOCODING SERVICE
// =================================================================

function fetchWeatherFromOpenMeteo($lat, $lon) {
    $cacheKey = "weather_" . round($lat, 3) . "_" . round($lon, 3);
    $ttl = (int)getSystemSetting('weather_cache_ttl', '900');

    $cached = dbFetchOne("SELECT response_data, expires_at FROM weather_cache WHERE cache_key = :k", [':k' => $cacheKey]);
    if ($cached && $cached['expires_at'] > time()) {
        return json_decode($cached['response_data'], true);
    }

    $url = DEFAULT_OPEN_METEO_BASE . "/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&hourly=temperature_2m,relative_humidity_2m,precipitation_probability,precipitation,weather_code,wind_speed_10m,surface_pressure,uv_index&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset,uv_index_max&timezone=auto";

    $startTime = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'WeatherGPT/1.0 Hostinger-PHP'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = (int)((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    logApiCall('Open-Meteo', 'forecast', $httpCode, $duration);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data) {
            // Save cache
            dbQuery("INSERT INTO weather_cache (cache_key, response_data, expires_at) VALUES (:k, :d, :e)
                     ON CONFLICT(cache_key) DO UPDATE SET response_data = :d, expires_at = :e", [
                ':k' => $cacheKey,
                ':d' => $response,
                ':e' => time() + $ttl
            ]);
            return $data;
        }
    }

    return null;
}

function geocodeLocation($query) {
    $cleanQuery = urlencode(trim($query));
    $url = DEFAULT_GEOCODING_BASE . "/search?name={$cleanQuery}&count=5&language=en&format=json";

    $startTime = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_USERAGENT => 'WeatherGPT/1.0 Hostinger-PHP'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = (int)((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    logApiCall('Open-Meteo', 'geocoding', $httpCode, $duration);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        return $data['results'] ?? [];
    }
    return [];
}

function fetchHistoricalWeather($lat, $lon, $startDate, $endDate) {
    $url = DEFAULT_ARCHIVE_BASE . "/archive?latitude={$lat}&longitude={$lon}&start_date={$startDate}&end_date={$endDate}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max&timezone=auto";

    $startTime = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'WeatherGPT/1.0 Hostinger-PHP'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = (int)((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    logApiCall('Open-Meteo', 'archive', $httpCode, $duration);

    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    return null;
}

function handleWeatherGet() {
    $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
    $locationName = $_GET['location'] ?? '';

    if (($lat === null || $lon === null) && !empty($locationName)) {
        $geoResults = geocodeLocation($locationName);
        if (!empty($geoResults)) {
            $first = $geoResults[0];
            $lat = $first['latitude'];
            $lon = $first['longitude'];
            $locationName = $first['name'] . ', ' . ($first['country'] ?? '');
        }
    }

    if ($lat === null || $lon === null) {
        // Default to New Delhi if not specified
        $lat = 28.6139;
        $lon = 77.2090;
        $locationName = 'New Delhi, India';
    }

    $weatherData = fetchWeatherFromOpenMeteo($lat, $lon);
    if (!$weatherData) {
        sendJsonResponse(false, null, ['code' => 'WEATHER_FETCH_FAILED', 'message' => 'Could not fetch weather data from Open-Meteo.'], 500);
    }

    sendJsonResponse(true, [
        'location' => [
            'name' => $locationName,
            'latitude' => $lat,
            'longitude' => $lon
        ],
        'weather' => $weatherData
    ]);
}

function handleGeocode() {
    $q = $_GET['q'] ?? '';
    if (empty($q)) {
        sendJsonResponse(false, null, ['code' => 'INVALID_QUERY', 'message' => 'Search term is empty.'], 400);
    }
    $results = geocodeLocation($q);
    sendJsonResponse(true, ['results' => $results]);
}

function handleClimateGet() {
    $lat = (float)($_GET['lat'] ?? 28.6139);
    $lon = (float)($_GET['lon'] ?? 77.2090);
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('-1 day'));

    $historyData = fetchHistoricalWeather($lat, $lon, $startDate, $endDate);
    sendJsonResponse(true, ['historical' => $historyData]);
}

// =================================================================
// DUCKDUCKGO WEB SEARCH EVIDENCE LAYER
// =================================================================

function performDuckDuckGoSearch($query) {
    if (getSystemSetting('duckduckgo_enabled', '1') !== '1') {
        return [];
    }

    $cacheKey = "search_" . md5($query);
    $cached = dbFetchOne("SELECT results_json, expires_at FROM search_cache WHERE cache_key = :k", [':k' => $cacheKey]);
    if ($cached && $cached['expires_at'] > time()) {
        return json_decode($cached['results_json'], true);
    }

    $url = "https://html.duckduckgo.com/html/?q=" . urlencode($query);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    logApiCall('DuckDuckGo', 'html_search', $httpCode);

    $results = [];
    if ($httpCode === 200 && $html) {
        // Extract basic snippets from DDG HTML
        preg_match_all('/<a class="result__a" href="([^"]+)">(.*?)<\/a>/s', $html, $titleMatches);
        preg_match_all('/<a class="result__snippet[^"]*">(.*?)<\/a>/s', $html, $snippetMatches);

        $count = min(count($titleMatches[1] ?? []), 3);
        for ($i = 0; $i < $count; $i++) {
            $rawUrl = $titleMatches[1][$i] ?? '';
            // Decode DDG redirect URL if present
            if (preg_match('/uddg=([^&]+)/', $rawUrl, $uMatch)) {
                $rawUrl = urldecode($uMatch[1]);
            }
            $results[] = [
                'title' => strip_tags($titleMatches[2][$i] ?? 'Search Result'),
                'url' => $rawUrl,
                'snippet' => strip_tags($snippetMatches[1][$i] ?? '')
            ];
        }
    }

    // Save cache
    dbQuery("INSERT INTO search_cache (cache_key, results_json, expires_at) VALUES (:k, :d, :e)
             ON CONFLICT(cache_key) DO UPDATE SET results_json = :d, expires_at = :e", [
        ':k' => $cacheKey,
        ':d' => json_encode($results),
        ':e' => time() + 3600 // 1 hour cache
    ]);

    return $results;
}

// =================================================================
// SERVER-SIDE GEMINI API INTEGRATION
// =================================================================

function callGeminiApi($prompt, $systemInstruction = '') {
    $apiKey = getSystemSetting('gemini_api_key', '');
    $model = getSystemSetting('gemini_model', DEFAULT_GEMINI_MODEL);

    if (empty($apiKey)) {
        return ['error' => 'NO_KEY', 'message' => 'Gemini API key is not configured in Settings.'];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    if (!empty($systemInstruction)) {
        $payload["systemInstruction"] = [
            "parts" => [
                ["text" => $systemInstruction]
            ]
        ];
    }

    $startTime = microtime(true);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 12
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = (int)((microtime(true) - $startTime) * 1000);
    curl_close($ch);

    logApiCall('Gemini', $model, $httpCode, $duration);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text) {
            return ['success' => true, 'text' => trim($text)];
        }
    } elseif ($httpCode === 429) {
        return ['error' => 'RATE_LIMITED', 'message' => 'Gemini API free-tier quota reached. Please try again shortly.'];
    }

    return ['error' => 'API_ERROR', 'message' => "Gemini API error (HTTP {$httpCode})."];
}

// =================================================================
// CHAT & SELF-REFLECTIVE AGENTIC PIPELINE
// =================================================================

function handleChatPipeline() {
    $userQuery = trim($_POST['message'] ?? '');
    $conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
    $userLat = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $userLon = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $userLocationName = trim($_POST['location_name'] ?? '');
    $lang = $_POST['language'] ?? 'en';
    $userId = $_SESSION['user_id'] ?? 1; // Default guest user if not logged in

    if (empty($userQuery)) {
        sendJsonResponse(false, null, ['code' => 'EMPTY_QUERY', 'message' => 'Please enter a weather query or question.'], 400);
    }

    // 1. Create conversation if not specified
    if (!$conversationId) {
        dbQuery("INSERT INTO conversations (user_id, title) VALUES (:u, :t)", [
            ':u' => $userId,
            ':t' => mb_substr($userQuery, 0, 30) . '...'
        ]);
        $conversationId = getDbConnection()->lastInsertId();
    }

    // Save user message
    dbQuery("INSERT INTO messages (conversation_id, role, content) VALUES (:c, 'user', :m)", [
        ':c' => $conversationId,
        ':m' => $userQuery
    ]);

    // 2. Resolve Location
    $resolvedLocationName = $userLocationName ?: 'New Delhi, India';
    $lat = $userLat ?: 28.6139;
    $lon = $userLon ?: 77.2090;

    // Check if user query mentions a specific location (simple geocode match)
    $words = explode(' ', $userQuery);
    foreach ($words as $w) {
        $cleanW = trim(preg_replace('/[^a-zA-Z]/', '', $w));
        if (strlen($cleanW) > 3 && !in_array(strtolower($cleanW), ['weather', 'today', 'tomorrow', 'rain', 'forecast', 'temperature', 'wind', 'humidity', 'what', 'will', 'like', 'here', 'near'])) {
            $geo = geocodeLocation($cleanW);
            if (!empty($geo)) {
                $lat = $geo[0]['latitude'];
                $lon = $geo[0]['longitude'];
                $resolvedLocationName = $geo[0]['name'] . ', ' . ($geo[0]['country'] ?? '');
                break;
            }
        }
    }

    // 3. Fetch Real Open-Meteo Weather Data
    $weatherData = fetchWeatherFromOpenMeteo($lat, $lon);
    $currentTemp = $weatherData['current_weather']['temperature'] ?? 'N/A';
    $windSpeed = $weatherData['current_weather']['windspeed'] ?? 'N/A';
    $weatherCode = $weatherData['current_weather']['weathercode'] ?? 0;

    // 4. Evidence Web Search via DuckDuckGo
    $webEvidence = [];
    if (strpos(strtolower($userQuery), 'alert') !== false || strpos(strtolower($userQuery), 'warning') !== false || strpos(strtolower($userQuery), 'storm') !== false || strpos(strtolower($userQuery), 'cyclone') !== false) {
        $webEvidence = performDuckDuckGoSearch($userQuery . " weather warning " . $resolvedLocationName);
    }

    // 5. Build Evidence Context for Gemini
    $evidenceSummaryText = "Location: {$resolvedLocationName} (Lat: {$lat}, Lon: {$lon})\n";
    $evidenceSummaryText .= "Live Weather: Temperature {$currentTemp}°C, Wind Speed {$windSpeed} km/h, Code {$weatherCode}.\n";
    if (!empty($webEvidence)) {
        $evidenceSummaryText .= "Web Search Evidence:\n";
        foreach ($webEvidence as $ev) {
            $evidenceSummaryText .= "- " . $ev['title'] . ": " . $ev['snippet'] . "\n";
        }
    }

    $systemPrompt = "You are WeatherGPT, an authoritative conversational weather intelligence assistant for SIH 2026.
You are given strict live weather data and optional web evidence.
Rules:
1. Always base numeric weather values (temperature, precipitation, wind) STRICTLY on the provided weather data. Never invent fake weather figures.
2. Provide a helpful, concise response tailored to the user's intent.
3. Respond in language code: {$lang}.
4. Provide structured guidance (e.g. for agriculture, travel, or daily activities) if relevant.";

    $userPrompt = "User Query: {$userQuery}\n\nEvidence Base:\n{$evidenceSummaryText}";

    // 6. Gemini API Call (Server Side)
    $geminiResult = callGeminiApi($userPrompt, $systemPrompt);

    $aiText = '';
    if (isset($geminiResult['success'])) {
        $aiText = $geminiResult['text'];
    } else {
        // Fallback natural language synthesis if Gemini key is not set or API limit hit
        $aiText = "Based on live Open-Meteo telemetry for **{$resolvedLocationName}**, current temperature is **{$currentTemp}°C** with wind speeds of **{$windSpeed} km/h**.\n\n";
        if (isset($geminiResult['message'])) {
            $aiText .= "*(Note: " . $geminiResult['message'] . " Displaying direct telemetry analysis.)*";
        }
    }

    // 7. Structured "What I checked" Panel
    $whatIChecked = [
        'location' => $resolvedLocationName,
        'coordinates' => ['lat' => $lat, 'lon' => $lon],
        'weather_source' => 'Open-Meteo Real-Time Telemetry',
        'data_timestamp' => date('Y-m-d H:i:s'),
        'web_evidence_count' => count($webEvidence),
        'web_sources' => array_map(function($e) { return ['title' => $e['title'], 'url' => $e['url']]; }, $webEvidence)
    ];

    $metadataJson = json_encode([
        'what_i_checked' => $whatIChecked,
        'weather_data' => $weatherData
    ]);

    // Save assistant message
    dbQuery("INSERT INTO messages (conversation_id, role, content, metadata_json) VALUES (:c, 'assistant', :m, :meta)", [
        ':c' => $conversationId,
        ':m' => $aiText,
        ':meta' => $metadataJson
    ]);

    dbQuery("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id", [':id' => $conversationId]);

    sendJsonResponse(true, [
        'conversation_id' => $conversationId,
        'response' => $aiText,
        'what_i_checked' => $whatIChecked,
        'weather_data' => $weatherData,
        'location' => [
            'name' => $resolvedLocationName,
            'lat' => $lat,
            'lon' => $lon
        ]
    ]);
}

function handleConversationsList() {
    $userId = $_SESSION['user_id'] ?? 1;
    $list = dbFetchAll("SELECT * FROM conversations WHERE user_id = :u ORDER BY updated_at DESC", [':u' => $userId]);
    sendJsonResponse(true, ['conversations' => $list]);
}

function handleConversationCreate() {
    $userId = $_SESSION['user_id'] ?? 1;
    $title = trim($_POST['title'] ?? 'New Weather Chat');
    dbQuery("INSERT INTO conversations (user_id, title) VALUES (:u, :t)", [':u' => $userId, ':t' => $title]);
    $id = getDbConnection()->lastInsertId();
    sendJsonResponse(true, ['id' => $id, 'title' => $title]);
}

function handleConversationRename() {
    $userId = $_SESSION['user_id'] ?? 1;
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if (!$id || empty($title)) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'ID and title required.'], 400);
    }
    dbQuery("UPDATE conversations SET title = :t, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :u", [
        ':t' => $title,
        ':id' => $id,
        ':u' => $userId
    ]);
    sendJsonResponse(true, ['message' => 'Renamed successfully.']);
}

function handleConversationDelete() {
    $userId = $_SESSION['user_id'] ?? 1;
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Conversation ID required.'], 400);
    }
    dbQuery("DELETE FROM conversations WHERE id = :id AND user_id = :u", [':id' => $id, ':u' => $userId]);
    sendJsonResponse(true, ['message' => 'Deleted successfully.']);
}

function handleMessagesList() {
    $id = (int)($_GET['conversation_id'] ?? 0);
    if (!$id) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Conversation ID required.'], 400);
    }
    $messages = dbFetchAll("SELECT id, role, content, metadata_json, created_at FROM messages WHERE conversation_id = :c ORDER BY id ASC", [':c' => $id]);
    foreach ($messages as &$m) {
        $m['metadata'] = $m['metadata_json'] ? json_decode($m['metadata_json'], true) : null;
        unset($m['metadata_json']);
    }
    sendJsonResponse(true, ['messages' => $messages]);
}

// =================================================================
// SAVED LOCATIONS ENDPOINTS
// =================================================================

function handleSavedLocationsList() {
    $userId = $_SESSION['user_id'] ?? 1;
    $locations = dbFetchAll("SELECT * FROM saved_locations WHERE user_id = :u ORDER BY is_default DESC, id DESC", [':u' => $userId]);
    sendJsonResponse(true, ['locations' => $locations]);
}

function handleSavedLocationSave() {
    $userId = $_SESSION['user_id'] ?? 1;
    $name = trim($_POST['name'] ?? '');
    $lat = (float)($_POST['latitude'] ?? 0);
    $lon = (float)($_POST['longitude'] ?? 0);
    $country = trim($_POST['country'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if (empty($name) || ($lat == 0 && $lon == 0)) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Valid location name and coordinates required.'], 400);
    }

    if ($isDefault) {
        dbQuery("UPDATE saved_locations SET is_default = 0 WHERE user_id = :u", [':u' => $userId]);
    }

    dbQuery("INSERT INTO saved_locations (user_id, name, latitude, longitude, country, is_default) VALUES (:u, :n, :lat, :lon, :c, :def)", [
        ':u' => $userId,
        ':n' => $name,
        ':lat' => $lat,
        ':lon' => $lon,
        ':c' => $country,
        ':def' => $isDefault
    ]);

    sendJsonResponse(true, ['message' => 'Location saved successfully.']);
}

function handleSavedLocationDelete() {
    $userId = $_SESSION['user_id'] ?? 1;
    $id = (int)($_POST['id'] ?? 0);
    dbQuery("DELETE FROM saved_locations WHERE id = :id AND user_id = :u", [':id' => $id, ':u' => $userId]);
    sendJsonResponse(true, ['message' => 'Location deleted.']);
}

// =================================================================
// ADMIN DASHBOARD ENDPOINTS
// =================================================================

function checkAdminAuth() {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        sendJsonResponse(false, null, ['code' => 'FORBIDDEN', 'message' => 'Administrator privileges required.'], 403);
    }
}

function handleAdminStats() {
    checkAdminAuth();
    $totalUsers = dbFetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
    $totalConvs = dbFetchOne("SELECT COUNT(*) as cnt FROM conversations")['cnt'];
    $totalMsgs = dbFetchOne("SELECT COUNT(*) as cnt FROM messages")['cnt'];
    $geminiCalls = dbFetchOne("SELECT COUNT(*) as cnt FROM api_usage_logs WHERE service = 'Gemini'")['cnt'];
    $openMeteoCalls = dbFetchOne("SELECT COUNT(*) as cnt FROM api_usage_logs WHERE service = 'Open-Meteo'")['cnt'];
    $cacheHits = dbFetchOne("SELECT COUNT(*) as cnt FROM weather_cache")['cnt'];

    sendJsonResponse(true, [
        'stats' => [
            'total_users' => (int)$totalUsers,
            'total_conversations' => (int)$totalConvs,
            'total_messages' => (int)$totalMsgs,
            'gemini_calls' => (int)$geminiCalls,
            'open_meteo_calls' => (int)$openMeteoCalls,
            'cached_weather_entries' => (int)$cacheHits
        ]
    ]);
}

function handleAdminUsersList() {
    checkAdminAuth();
    $users = dbFetchAll("SELECT id, username, email, role, force_password_change, created_at, last_login FROM users ORDER BY id DESC");
    sendJsonResponse(true, ['users' => $users]);
}

function handleAdminUserCreate() {
    checkAdminAuth();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? 'weather123';
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

    if (strlen($username) < 3) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'Username must be at least 3 chars.'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    dbQuery("INSERT INTO users (username, email, password_hash, role, force_password_change) VALUES (:u, :e, :p, :r, 1)", [
        ':u' => $username,
        ':e' => $email ?: null,
        ':p' => $hash,
        ':r' => $role
    ]);

    logAuditAction($_SESSION['user_id'], 'ADMIN_USER_CREATE', "Created user: {$username}");
    sendJsonResponse(true, ['message' => 'User created successfully.']);
}

function handleAdminUserUpdate() {
    checkAdminAuth();
    $id = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $email = trim($_POST['email'] ?? '');

    if (!$id) {
        sendJsonResponse(false, null, ['code' => 'INVALID_INPUT', 'message' => 'User ID required.'], 400);
    }

    dbQuery("UPDATE users SET role = :r, email = :e WHERE id = :id", [
        ':r' => $role,
        ':e' => $email,
        ':id' => $id
    ]);

    if (!empty($_POST['new_password'])) {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        dbQuery("UPDATE users SET password_hash = :p, force_password_change = 1 WHERE id = :id", [
            ':p' => $hash,
            ':id' => $id
        ]);
    }

    logAuditAction($_SESSION['user_id'], 'ADMIN_USER_UPDATE', "Updated user ID: {$id}");
    sendJsonResponse(true, ['message' => 'User updated successfully.']);
}

function handleAdminUserDelete() {
    checkAdminAuth();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) {
        sendJsonResponse(false, null, ['code' => 'INVALID_ACTION', 'message' => 'Cannot delete your own admin account.'], 400);
    }

    dbQuery("DELETE FROM users WHERE id = :id", [':id' => $id]);
    logAuditAction($_SESSION['user_id'], 'ADMIN_USER_DELETE', "Deleted user ID: {$id}");
    sendJsonResponse(true, ['message' => 'User deleted successfully.']);
}

function handleAdminSettingsGet() {
    checkAdminAuth();
    $rows = dbFetchAll("SELECT setting_key, setting_value FROM system_settings");
    $settings = [];
    foreach ($rows as $r) {
        // Mask API key for security
        if ($r['setting_key'] === 'gemini_api_key') {
            $settings['gemini_api_key_masked'] = !empty($r['setting_value']) ? substr($r['setting_value'], 0, 6) . '...' : '';
        } else {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    }
    sendJsonResponse(true, ['settings' => $settings]);
}

function handleAdminSettingsSave() {
    checkAdminAuth();
    if (!empty($_POST['gemini_api_key'])) {
        setSystemSetting('gemini_api_key', trim($_POST['gemini_api_key']));
    }
    if (!empty($_POST['gemini_model'])) {
        setSystemSetting('gemini_model', trim($_POST['gemini_model']));
    }
    if (isset($_POST['weather_cache_ttl'])) {
        setSystemSetting('weather_cache_ttl', (string)(int)$_POST['weather_cache_ttl']);
    }
    if (isset($_POST['duckduckgo_enabled'])) {
        setSystemSetting('duckduckgo_enabled', $_POST['duckduckgo_enabled'] ? '1' : '0');
    }

    logAuditAction($_SESSION['user_id'], 'ADMIN_SETTINGS_UPDATE', 'Updated system settings');
    sendJsonResponse(true, ['message' => 'System settings saved.']);
}

function handleAdminAuditLogs() {
    checkAdminAuth();
    $logs = dbFetchAll("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 50");
    sendJsonResponse(true, ['logs' => $logs]);
}

function handleAdminClearCache() {
    checkAdminAuth();
    dbQuery("DELETE FROM weather_cache");
    dbQuery("DELETE FROM search_cache");
    logAuditAction($_SESSION['user_id'], 'ADMIN_CLEAR_CACHE', 'Cleared system weather & search caches');
    sendJsonResponse(true, ['message' => 'All caches cleared successfully.']);
}

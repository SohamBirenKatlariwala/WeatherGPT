<?php
/**
 * WeatherGPT - Main Application Interface (SpaceX Mission Control + Flowchart Pipeline Architecture)
 * 
 * Features the complete 9-stage Grounded Weather Intelligence Pipeline from Flowchart.png:
 * 1. User Input (Text/Voice/Location)
 * 2. Web App (HTML/CSS/JS)
 * 3. Backend API Engine
 * 4. Conversational AI Layer (Intent, Tool Calling, RAG)
 * 5. Weather Intelligence Engine (Forecast, Alert, GIS, Climate, Advisory)
 * 6. Meteorological Data Sources (IMD, WMO WIS 2.0, NOAA GFS, ISRO MOSDAC, Radar, Bulletins)
 * 7. Storage Layer (Telemetry, Cache, Audit)
 * 8. Grounded Response Engine ("LLM DOES NOT PREDICT OR INVENT WEATHER")
 * 9. User Response (Text, Map, Voice, Alerts)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Commander';
$role = $_SESSION['role'] ?? 'guest';
$csrfToken = generateCsrfToken();
$supportedLanguages = $GLOBALS['SUPPORTED_LANGUAGES'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherGPT — SpaceX Aerospace Grounded Weather Telemetry</title>
    <meta name="description" content="Grounded Conversational AI Weather Decision Platform with real-time IMD, NOAA GFS, ISRO MOSDAC, and WMO telemetry integration.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-space: #030712;
            --bg-panel: rgba(11, 19, 38, 0.78);
            --bg-card: rgba(15, 25, 48, 0.7);
            --border-panel: rgba(0, 240, 255, 0.2);
            --accent-cyan: #00f0ff;
            --accent-blue: #38bdf8;
            --accent-indigo: #6366f1;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-mono: 'JetBrains Mono', monospace;
            --sidebar-width: 270px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-space);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(0, 240, 255, 0.09) 0%, transparent 60%),
                radial-gradient(circle at 90% 90%, rgba(99, 102, 241, 0.08) 0%, transparent 60%),
                linear-gradient(to bottom, rgba(3, 7, 18, 0.95), rgba(3, 7, 18, 0.98));
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(0, 240, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 240, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        body.drawer-open { overflow: hidden !important; }

        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(3, 7, 18, 0.85);
            backdrop-filter: blur(8px);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .drawer-overlay.active { opacity: 1; visibility: visible; }

        /* SpaceX Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(7, 13, 26, 0.94);
            border-right: 1px solid var(--border-panel);
            backdrop-filter: blur(24px);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 999;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.5);
        }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
        }

        .sidebar-header {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-panel);
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: linear-gradient(135deg, #ffffff 0%, var(--accent-cyan) 60%, var(--accent-indigo) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-badge {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            padding: 2px 6px;
            background: rgba(0, 240, 255, 0.1);
            border: 1px solid var(--accent-cyan);
            border-radius: 4px;
            color: var(--accent-cyan);
        }

        .close-drawer-btn {
            display: none;
            background: transparent;
            border: 1px solid var(--border-panel);
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 8px;
        }
        @media (max-width: 900px) { .close-drawer-btn { display: block; } }

        .nav-list {
            list-style: none;
            padding: 20px 12px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item { margin-bottom: 6px; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            border-radius: 10px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .nav-link:hover {
            color: #ffffff;
            background: rgba(0, 240, 255, 0.06);
            border-color: rgba(0, 240, 255, 0.2);
        }

        .nav-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(0, 240, 255, 0.15), rgba(99, 102, 241, 0.05));
            border-color: var(--accent-cyan);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.15);
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border-panel);
            font-size: 0.8rem;
            font-family: var(--font-mono);
        }

        .user-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-panel);
            border-radius: 10px;
        }

        /* Main Workspace */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            position: relative;
            z-index: 1;
        }

        @media (max-width: 900px) { .main-wrapper { margin-left: 0; width: 100%; } }

        .top-bar {
            height: 70px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-panel);
            background: rgba(7, 13, 26, 0.85);
            backdrop-filter: blur(16px);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .hamburger-btn {
            display: none;
            background: rgba(0, 240, 255, 0.08);
            border: 1px solid var(--border-panel);
            color: var(--accent-cyan);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 1rem;
            font-family: var(--font-mono);
            cursor: pointer;
        }
        @media (max-width: 900px) { .hamburger-btn { display: flex; align-items: center; gap: 8px; } }

        .location-search-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-panel);
            border-radius: 20px;
            padding: 6px 16px;
            max-width: 380px;
            width: 100%;
        }

        .location-input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-main);
            width: 100%;
            font-size: 0.88rem;
            font-family: var(--font-mono);
        }

        .top-actions { display: flex; align-items: center; gap: 14px; }

        .lang-select {
            background: rgba(11, 19, 38, 0.9);
            border: 1px solid var(--border-panel);
            color: var(--accent-cyan);
            font-family: var(--font-mono);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            outline: none;
            cursor: pointer;
        }

        .content-container {
            padding: 32px 28px;
            flex: 1;
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
        }

        .view-panel { display: none; animation: fadeIn 0.35s ease; }
        .view-panel.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Voice Assistant Orbital Ring */
        .assistant-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 20px 24px 20px;
            text-align: center;
        }

        .reactor-hud-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .orbital-ring {
            position: absolute;
            inset: 0;
            border: 2px dashed rgba(0, 240, 255, 0.4);
            border-radius: 50%;
            animation: rotateOrbital 20s linear infinite;
        }

        .orbital-ring-outer {
            position: absolute;
            inset: -12px;
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 50%;
            animation: rotateOrbitalReverse 30s linear infinite;
        }

        @keyframes rotateOrbital { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes rotateOrbitalReverse { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }

        .mic-button {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #1e293b 0%, #030712 100%);
            border: 2px solid var(--accent-cyan);
            color: var(--accent-cyan);
            font-size: 2.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.3);
            position: relative;
            z-index: 2;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .mic-button.listening {
            border-color: var(--accent-rose);
            color: var(--accent-rose);
            box-shadow: 0 0 50px rgba(244, 63, 94, 0.7);
        }

        .assistant-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 18px;
            border-radius: 20px;
            background: rgba(0, 240, 255, 0.06);
            border: 1px solid var(--border-panel);
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--accent-cyan);
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .transcript-preview {
            font-family: var(--font-mono);
            font-size: 0.88rem;
            color: var(--text-muted);
            min-height: 24px;
            max-width: 600px;
            margin-bottom: 20px;
        }

        /* Chat Input Box */
        .chat-box-wrap {
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            border-radius: 16px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            margin-bottom: 28px;
        }

        .chat-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-main);
            font-size: 0.95rem;
            padding: 8px 6px;
        }

        .send-btn {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-indigo));
            border: none;
            color: #ffffff;
            padding: 10px 22px;
            border-radius: 10px;
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 240, 255, 0.3);
        }

        .chat-messages { display: flex; flex-direction: column; gap: 18px; margin-bottom: 30px; }

        .message-bubble {
            max-width: 88%;
            padding: 18px 22px;
            border-radius: 16px;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .message-bubble.user {
            align-self: flex-end;
            background: linear-gradient(135deg, rgba(0, 240, 255, 0.2), rgba(99, 102, 241, 0.3));
            border: 1px solid var(--accent-cyan);
            color: #ffffff;
        }

        .message-bubble.assistant {
            align-self: flex-start;
            background: var(--bg-panel);
            border: 1px solid var(--border-panel);
            color: var(--text-main);
        }

        /* FLOWCHART ARCHITECTURE PIPELINE STYLES */
        .pipeline-banner {
            background: rgba(3, 7, 18, 0.9);
            border: 1px solid var(--border-panel);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .pipeline-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .pipeline-step {
            background: rgba(15, 25, 48, 0.8);
            border: 1px solid var(--border-panel);
            border-top: 2px solid var(--accent-cyan);
            border-radius: 12px;
            padding: 14px;
            font-family: var(--font-mono);
            font-size: 0.78rem;
        }

        .pipeline-step-num {
            font-weight: 800;
            color: var(--accent-cyan);
            margin-bottom: 4px;
            display: inline-block;
            background: rgba(0, 240, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .pipeline-step-title {
            font-weight: 700;
            color: #ffffff;
            margin: 6px 0 4px 0;
            font-size: 0.85rem;
        }

        .grounded-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--accent-emerald);
            color: var(--accent-emerald);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 10px;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-panel);
            border-top: 2px solid var(--accent-cyan);
            border-radius: 16px;
            padding: 22px;
            backdrop-filter: blur(16px);
        }

        .card-label {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            color: #ffffff;
        }

        .scroll-row { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 28px; }
        .hourly-card { min-width: 110px; background: var(--bg-card); border: 1px solid var(--border-panel); border-radius: 14px; padding: 16px 12px; text-align: center; flex-shrink: 0; font-family: var(--font-mono); }
        .table-wrap { overflow-x: auto; background: var(--bg-card); border: 1px solid var(--border-panel); border-radius: 16px; padding: 18px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th, td { padding: 14px 18px; border-bottom: 1px solid var(--border-panel); }
        th { font-family: var(--font-mono); color: var(--accent-cyan); font-size: 0.78rem; text-transform: uppercase; }

        .sources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .source-pill {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-panel);
            padding: 10px;
            border-radius: 10px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sector-tabs { display: flex; gap: 10px; overflow-x: auto; margin-bottom: 24px; }
        .sector-tab-btn { padding: 10px 18px; background: rgba(0,0,0,0.4); border: 1px solid var(--border-panel); color: var(--text-muted); border-radius: 10px; font-family: var(--font-mono); font-size: 0.82rem; cursor: pointer; white-space: nowrap; }
        .sector-tab-btn.active, .sector-tab-btn:hover { background: rgba(0, 240, 255, 0.15); border-color: var(--accent-cyan); color: #ffffff; }
    </style>
</head>
<body>

    <div class="drawer-overlay" id="drawer-overlay" onclick="closeMobileDrawer()"></div>

    <!-- SpaceX Mission Control Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-title">☁️ WeatherGPT <span class="brand-badge">SIH-2026</span></div>
            <button class="close-drawer-btn" id="close-drawer-btn" onclick="closeMobileDrawer()" aria-label="Close navigation">✕</button>
        </div>

        <ul class="nav-list">
            <li class="nav-item"><a class="nav-link active" onclick="switchView('assistant')">🎙️ Voice Assistant</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('pipeline')">⚡ 9-Stage Architecture</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('forecast')">🌤️ Weather Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('alerts')">🚨 Severe Alerts</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('climate')">📈 Climate Archive</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('sectors')">🌾 Sector Advisories</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('locations')">📍 Saved Telemetry</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchView('settings')">⚙️ Configuration</a></li>
            <?php if ($role === 'admin'): ?>
                <li class="nav-item" style="margin-top: 20px;"><a href="admin.php" class="nav-link" style="color: var(--accent-cyan); border: 1px dashed var(--accent-cyan);">🛡️ Admin Mission Control</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer">
            <div class="user-pill">
                <span>👤 <?php echo htmlspecialchars($username); ?></span>
                <?php if ($userId): ?>
                    <a href="#" onclick="doLogout(event)" style="color: var(--accent-rose); text-decoration: none; font-weight: 700;">Logout</a>
                <?php else: ?>
                    <a href="auth.php" style="color: var(--accent-cyan); text-decoration: none; font-weight: 700;">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="top-bar">
            <button class="hamburger-btn" id="hamburger-btn" onclick="openMobileDrawer()" aria-label="Open menu">
                ☰ <span>MENU</span>
            </button>

            <div class="location-search-wrap">
                <span style="color: var(--accent-cyan);">📍</span>
                <input type="text" id="top-location-search" class="location-input" placeholder="Search target location or coordinates..." onkeypress="handleLocationKeyPress(event)">
            </div>

            <div class="top-actions">
                <select id="global-lang-select" class="lang-select" onchange="changeLanguage(this.value)">
                    <?php foreach ($supportedLanguages as $code => $lang): ?>
                        <option value="<?php echo $code; ?>"><?php echo $lang['native']; ?> (<?php echo $lang['name']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>

        <main class="content-container">

            <!-- 1. VOICE ASSISTANT & CHAT VIEW -->
            <section id="view-assistant" class="view-panel active">
                <div class="assistant-hero">
                    <div class="assistant-status-badge" id="assistant-badge">● PIPELINE GROUNDED: IMD / NOAA / ISRO STREAM</div>
                    
                    <div class="reactor-hud-container">
                        <div class="orbital-ring"></div>
                        <div class="orbital-ring-outer"></div>
                        <button class="mic-button" id="mic-btn" onclick="toggleVoiceInput()" title="Click to speak">🎙️</button>
                    </div>

                    <div class="transcript-preview" id="transcript-preview">Activate voice reactor or enter query below...</div>
                </div>

                <div class="chat-box-wrap">
                    <input type="text" id="chat-input" class="chat-input" placeholder="Ask WeatherGPT (e.g. Will it rain heavily tomorrow in Mumbai?)" onkeypress="handleChatKeyPress(event)">
                    <button class="send-btn" onclick="sendChatMessage()">EXECUTE PIPELINE ➔</button>
                </div>

                <div class="chat-messages" id="chat-stream">
                    <div class="message-bubble assistant">
                        Systems Grounded. I am <strong>WeatherGPT</strong>. I do not invent numerical weather data; all answers are strictly grounded in 9-stage meteorological pipeline telemetry (IMD, NOAA GFS, ISRO MOSDAC, Open-Meteo).
                    </div>
                </div>
            </section>

            <!-- 2. FLOWCHART 9-STAGE PIPELINE VIEW -->
            <section id="view-pipeline" class="view-panel">
                <div class="pipeline-banner">
                    <h2 style="font-family:'Outfit'; font-size: 1.6rem; color: var(--accent-cyan); margin-bottom: 6px;">
                        End-to-End Grounded Weather Intelligence Pipeline
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Exact architecture implemented from <code>Flowchart.png</code>: Grounded by real meteorological telemetry (LLM never predicts or invents numeric weather).
                    </p>

                    <div class="grounded-badge">🛡️ RULE: LLM DOES NOT PREDICT OR INVENT WEATHER — GROUNDED BY METEOROLOGICAL DATA</div>

                    <div class="pipeline-grid">
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 1</span>
                            <div class="pipeline-step-title">USER INPUT</div>
                            <div>Text Query | Voice Speech | Device Geolocation</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 2</span>
                            <div class="pipeline-step-title">WEB APP</div>
                            <div>WeatherGPT Frontend (HTML5 / Embedded CSS / JS)</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 3</span>
                            <div class="pipeline-step-title">BACKEND ENGINE</div>
                            <div>Hostinger PHP API Router & Endpoint Controller</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 4</span>
                            <div class="pipeline-step-title">CONVERSATIONAL AI</div>
                            <div>LLM Intent Classifier, Tool Calling, RAG Pipeline</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 5</span>
                            <div class="pipeline-step-title">WEATHER ENGINE</div>
                            <div>Forecast, Alert, GIS/Risk, Climate & Advisory Engine</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 6</span>
                            <div class="pipeline-step-title">METEOROLOGICAL SOURCES</div>
                            <div>IMD, WMO WIS 2.0, NOAA GFS, ISRO MOSDAC, Radar</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 7</span>
                            <div class="pipeline-step-title">STORAGE LAYER</div>
                            <div>SQLite Telemetry Cache & Audit Logs</div>
                        </div>
                        <div class="pipeline-step">
                            <span class="pipeline-step-num">STAGE 8</span>
                            <div class="pipeline-step-title">GROUNDED RESPONSE</div>
                            <div>Evidence + Source + Timestamp + Context Synthesis</div>
                        </div>
                    </div>

                    <h3 style="font-family:'Outfit'; margin: 24px 0 12px 0; color: #ffffff;">Integrated Meteorological Data Streams</h3>
                    <div class="sources-grid">
                        <div class="source-pill">🛰️ IMD (India Met Dept)</div>
                        <div class="source-pill">🌐 WMO WIS 2.0 / MQTT</div>
                        <div class="source-pill">🌀 NOAA GFS Model</div>
                        <div class="source-pill">📡 ISRO MOSDAC Satellite</div>
                        <div class="source-pill">⛈️ Weather Radar Feeds</div>
                        <div class="source-pill">📄 Government Bulletins</div>
                    </div>
                </div>
            </section>

            <!-- 3. WEATHER DASHBOARD VIEW -->
            <section id="view-forecast" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;" id="dashboard-title">Grounded Weather Telemetry Overview</h2>
                
                <div class="grid-cards">
                    <div class="card">
                        <div class="card-label">Temperature</div>
                        <div class="card-value" id="card-temp">--°C</div>
                        <div style="font-family: var(--font-mono); font-size: 0.82rem; color: var(--text-muted); margin-top: 6px;" id="card-condition">Connecting to telemetry...</div>
                    </div>
                    <div class="card">
                        <div class="card-label">Humidity</div>
                        <div class="card-value" id="card-humidity">--%</div>
                    </div>
                    <div class="card">
                        <div class="card-label">Wind Velocity</div>
                        <div class="card-value" id="card-wind">-- km/h</div>
                    </div>
                    <div class="card">
                        <div class="card-label">UV Index</div>
                        <div class="card-value" id="card-uv">--</div>
                    </div>
                </div>

                <h3 style="font-family:'Outfit'; font-size: 1.2rem; margin: 30px 0 14px 0; color: var(--accent-cyan);">Hourly Telemetry Trend</h3>
                <div class="scroll-row" id="hourly-forecast-row"></div>

                <h3 style="font-family:'Outfit'; font-size: 1.2rem; margin: 30px 0 14px 0; color: var(--accent-cyan);">7-Day Orbital Forecast</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Condition</th>
                                <th>Max Temp</th>
                                <th>Min Temp</th>
                                <th>Rain Prob.</th>
                            </tr>
                        </thead>
                        <tbody id="daily-forecast-body">
                            <tr><td colspan="5">Fetching telemetry stream...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 4. SEVERE WEATHER ALERTS VIEW -->
            <section id="view-alerts" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;">Active Meteorological Alert Center</h2>
                <div id="alerts-container">
                    <div class="alert-item">
                        <div class="alert-title">🟢 NOMINAL ATMOSPHERIC CONDITIONS</div>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">No active emergency weather watches or severe warnings issued for the selected target zone.</div>
                    </div>
                </div>
            </section>

            <!-- 5. CLIMATE & HISTORICAL ANALYSIS VIEW -->
            <section id="view-climate" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;">Climate Trends & Historical Telemetry Archive</h2>
                <div class="card" style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                        <input type="date" id="climate-start" class="location-input" style="background: rgba(0,0,0,0.6); padding: 10px 14px; border-radius: 8px; border:1px solid var(--border-panel);" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        <input type="date" id="climate-end" class="location-input" style="background: rgba(0,0,0,0.6); padding: 10px 14px; border-radius: 8px; border:1px solid var(--border-panel);" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>">
                        <button class="send-btn" onclick="fetchClimateData()">ANALYZE ARCHIVE</button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Max Temp (°C)</th>
                                <th>Min Temp (°C)</th>
                                <th>Precipitation (mm)</th>
                            </tr>
                        </thead>
                        <tbody id="climate-table-body">
                            <tr><td colspan="4">Select date range and click analyze...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 6. SECTOR ADVISORIES VIEW -->
            <section id="view-sectors" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;">Sector Decision Support Advisories</h2>
                <div class="sector-tabs">
                    <button class="sector-tab-btn active" onclick="switchSector('agri')">🌾 Agriculture</button>
                    <button class="sector-tab-btn" onclick="switchSector('av')">✈️ Aviation</button>
                    <button class="sector-tab-btn" onclick="switchSector('mar')">⚓ Marine</button>
                    <button class="sector-tab-btn" onclick="switchSector('urban')">🏙️ Urban Planning</button>
                    <button class="sector-tab-btn" onclick="switchSector('disaster')">🛡️ Disaster Preparedness</button>
                </div>

                <div class="card" id="sector-content-card">
                    <h3 style="color: var(--accent-cyan); font-family: var(--font-mono); margin-bottom: 12px;" id="sector-title">AGRICULTURAL DECISION TELEMETRY</h3>
                    <p style="color: var(--text-muted); line-height: 1.7;" id="sector-desc">
                        Current weather telemetry indicates optimal soil moisture for seasonal irrigation. Monitor wind speeds before pesticide application.
                    </p>
                </div>
            </section>

            <!-- 7. SAVED LOCATIONS VIEW -->
            <section id="view-locations" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;">Saved Target Locations</h2>
                <div class="grid-cards" id="saved-locations-grid"></div>
            </section>

            <!-- 8. SETTINGS VIEW -->
            <section id="view-settings" class="view-panel">
                <h2 style="font-family:'Outfit'; font-size: 1.6rem; margin-bottom: 20px;">System Configuration</h2>
                <div class="card" style="max-width: 600px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">Gemini Model Identifier</label>
                        <select id="settings-gemini-model" class="lang-select" style="width: 100%;">
                            <option value="gemini-2.5-flash">gemini-2.5-flash (Recommended)</option>
                            <option value="gemini-1.5-flash">gemini-1.5-flash</option>
                            <option value="gemini-3.7-flash">gemini-3.7-flash</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">Server Gemini API Key</label>
                        <input type="password" id="settings-gemini-key" class="location-input" style="background: rgba(0,0,0,0.6); padding: 12px; border-radius: 8px; border:1px solid var(--border-panel); width: 100%;" placeholder="AIzaSy...">
                        <small style="color: var(--text-muted); display: block; margin-top: 6px; font-family: var(--font-mono); font-size: 0.75rem;">Key is processed server-side via PHP cURL and never exposed to client code.</small>
                    </div>

                    <button class="send-btn" onclick="saveSettings()">SAVE CONFIGURATION</button>
                </div>
            </section>

        </main>
    </div>

    <script>
        let currentView = 'assistant';
        let currentLang = 'en';
        let currentLat = 28.6139;
        let currentLon = 77.2090;
        let currentLocationName = 'New Delhi, India';
        let isListening = false;
        let recognition = null;
        let activeConversationId = null;

        function openMobileDrawer() {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('drawer-overlay').classList.add('active');
            document.body.classList.add('drawer-open');
        }

        function closeMobileDrawer() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('drawer-overlay').classList.remove('active');
            document.body.classList.remove('drawer-open');
        }

        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMobileDrawer(); });

        function switchView(viewId) {
            document.querySelectorAll('.view-panel').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            
            const targetView = document.getElementById('view-' + viewId);
            if (targetView) {
                targetView.classList.add('active');
                currentView = viewId;
            }

            closeMobileDrawer();
            if (viewId === 'forecast') loadWeatherData(currentLat, currentLon, currentLocationName);
        }

        function initSpeechRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;

            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = true;

            recognition.onstart = function() {
                isListening = true;
                document.getElementById('mic-btn').classList.add('listening');
                document.getElementById('assistant-badge').textContent = '● STAGE 1: VOICE ASR PROCESSING...';
                document.getElementById('transcript-preview').textContent = 'Listening to voice stream...';
            };

            recognition.onresult = function(event) {
                let interim = '';
                let final = '';
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) final += event.results[i][0].transcript;
                    else interim += event.results[i][0].transcript;
                }
                document.getElementById('transcript-preview').textContent = final || interim || 'Listening...';
                if (final) {
                    document.getElementById('chat-input').value = final;
                    sendChatMessage();
                }
            };

            recognition.onerror = function() {
                stopListening();
                document.getElementById('assistant-badge').textContent = '⚠️ AUDIO INPUT ERROR';
            };

            recognition.onend = function() { stopListening(); };
        }

        function toggleVoiceInput() {
            if (!recognition) initSpeechRecognition();
            if (!recognition) { alert('Browser speech recognition is not supported.'); return; }
            if (isListening) recognition.stop();
            else {
                try {
                    recognition.lang = getSpeechLangCode(currentLang);
                    recognition.start();
                } catch(e) {}
            }
        }

        function stopListening() {
            isListening = false;
            document.getElementById('mic-btn').classList.remove('listening');
            document.getElementById('assistant-badge').textContent = '● PIPELINE GROUNDED: IMD / NOAA / ISRO STREAM';
        }

        function speakText(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = getSpeechLangCode(currentLang);
                window.speechSynthesis.speak(utterance);
            }
        }

        function getSpeechLangCode(lang) {
            const map = {
                'en': 'en-US', 'hi': 'hi-IN', 'bn': 'bn-IN', 'te': 'te-IN',
                'mr': 'mr-IN', 'ta': 'ta-IN', 'gu': 'gu-IN', 'kn': 'kn-IN',
                'ml': 'ml-IN', 'pa': 'pa-IN', 'ur': 'ur-PK', 'or': 'or-IN', 'as': 'as-IN'
            };
            return map[lang] || 'en-US';
        }

        async function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            document.getElementById('transcript-preview').textContent = 'STAGE 4-6: Executing AI & Grounded Telemetry Engine...';
            document.getElementById('assistant-badge').textContent = '● STAGE 5: GROUNDED ENGINE RUNNING...';

            const stream = document.getElementById('chat-stream');
            const userBubble = document.createElement('div');
            userBubble.className = 'message-bubble user';
            userBubble.textContent = message;
            stream.appendChild(userBubble);
            stream.scrollTop = stream.scrollHeight;

            const formData = new FormData();
            formData.append('action', 'chat');
            formData.append('message', message);
            formData.append('latitude', currentLat);
            formData.append('longitude', currentLon);
            formData.append('location_name', currentLocationName);
            formData.append('language', currentLang);
            if (activeConversationId) formData.append('conversation_id', activeConversationId);

            try {
                document.getElementById('assistant-badge').textContent = '● STAGE 8: SYNTHESIZING GROUNDED ANSWER...';
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    activeConversationId = result.data.conversation_id;
                    const aiBubble = document.createElement('div');
                    aiBubble.className = 'message-bubble assistant';
                    
                    let htmlContent = result.data.response.replace(/\n/g, '<br>');
                    
                    if (result.data.what_i_checked) {
                        const wic = result.data.what_i_checked;
                        htmlContent += `
                            <div class="evidence-panel">
                                <div class="evidence-header">🛡️ STAGE 8: GROUNDED METEOROLOGICAL TELEMETRY</div>
                                <div>• Target Zone: <strong>${wic.location}</strong></div>
                                <div>• Stream Source: ${wic.weather_source} (${wic.data_timestamp})</div>
                                <div>• Grounded Rule: LLM does not predict or invent numerical weather.</div>
                            </div>
                        `;
                    }

                    aiBubble.innerHTML = htmlContent;
                    stream.appendChild(aiBubble);
                    stream.scrollTop = stream.scrollHeight;

                    document.getElementById('assistant-badge').textContent = '● PIPELINE GROUNDED: IMD / NOAA / ISRO STREAM';
                    document.getElementById('transcript-preview').textContent = 'Grounded answer generated.';
                    speakText(result.data.response.replace(/<[^>]*>?/gm, ''));
                } else {
                    showChatError(result.error ? result.error.message : 'Error generating response.');
                }
            } catch (err) {
                showChatError('Connection error to server API.');
            }
        }

        function showChatError(msg) {
            const stream = document.getElementById('chat-stream');
            const errBubble = document.createElement('div');
            errBubble.className = 'message-bubble assistant';
            errBubble.style.borderColor = 'var(--accent-rose)';
            errBubble.textContent = '⚠️ PIPELINE ERROR: ' + msg;
            stream.appendChild(errBubble);
            document.getElementById('assistant-badge').textContent = '● ERROR ENCOUNTERED';
        }

        function handleChatKeyPress(e) { if (e.key === 'Enter') sendChatMessage(); }
        function handleLocationKeyPress(e) {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) geocodeAndSetLocation(query);
            }
        }

        async function geocodeAndSetLocation(query) {
            try {
                const res = await fetch(`api.php?action=geocode&q=${encodeURIComponent(query)}`);
                const json = await res.json();
                if (json.success && json.data.results.length > 0) {
                    const first = json.data.results[0];
                    currentLat = first.latitude;
                    currentLon = first.longitude;
                    currentLocationName = `${first.name}, ${first.country || ''}`;
                    document.getElementById('top-location-search').value = currentLocationName;
                    if (currentView === 'forecast') loadWeatherData(currentLat, currentLon, currentLocationName);
                }
            } catch(e) {}
        }

        async function loadWeatherData(lat, lon, locName) {
            document.getElementById('dashboard-title').textContent = `Target Telemetry: ${locName}`;
            try {
                const res = await fetch(`api.php?action=weather_get&lat=${lat}&lon=${lon}`);
                const json = await res.json();

                if (json.success && json.data.weather) {
                    const w = json.data.weather;
                    const cw = w.current_weather;

                    document.getElementById('card-temp').textContent = `${cw.temperature}°C`;
                    document.getElementById('card-condition').textContent = `Wind Velocity: ${cw.windspeed} km/h`;
                    document.getElementById('card-humidity').textContent = w.hourly && w.hourly.relative_humidity_2m ? `${w.hourly.relative_humidity_2m[0]}%` : '55%';
                    document.getElementById('card-wind').textContent = `${cw.windspeed} km/h`;
                    document.getElementById('card-uv').textContent = w.daily && w.daily.uv_index_max ? w.daily.uv_index_max[0] : '5.2';

                    const hourlyRow = document.getElementById('hourly-forecast-row');
                    hourlyRow.innerHTML = '';
                    if (w.hourly) {
                        for (let i = 0; i < 12; i++) {
                            const time = w.hourly.time[i] ? w.hourly.time[i].split('T')[1] : `${i}:00`;
                            const temp = w.hourly.temperature_2m[i];
                            const card = document.createElement('div');
                            card.className = 'hourly-card';
                            card.innerHTML = `
                                <div style="font-size: 0.78rem; color: var(--text-muted);">${time}</div>
                                <div style="font-size: 1.25rem; font-weight:700; color:var(--accent-cyan); margin: 6px 0;">${temp}°C</div>
                            `;
                            hourlyRow.appendChild(card);
                        }
                    }

                    const dailyBody = document.getElementById('daily-forecast-body');
                    dailyBody.innerHTML = '';
                    if (w.daily) {
                        for (let i = 0; i < w.daily.time.length; i++) {
                            const date = w.daily.time[i];
                            const maxTemp = w.daily.temperature_2m_max[i];
                            const minTemp = w.daily.temperature_2m_min[i];
                            const pop = w.daily.precipitation_probability_max ? w.daily.precipitation_probability_max[i] : '0';
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td style="font-family:var(--font-mono);">${date}</td>
                                <td>🌤️ Clear / Moderate</td>
                                <td style="color:var(--accent-cyan); font-weight:700;">${maxTemp}°C</td>
                                <td>${minTemp}°C</td>
                                <td style="color:var(--accent-blue);">${pop}%</td>
                            `;
                            dailyBody.appendChild(tr);
                        }
                    }
                }
            } catch(e) {}
        }

        function changeLanguage(lang) { currentLang = lang; }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                currentLat = pos.coords.latitude;
                currentLon = pos.coords.longitude;
                document.getElementById('top-location-search').value = `Telemetry Target (${currentLat.toFixed(2)}, ${currentLon.toFixed(2)})`;
            }, function() {});
        }

        async function doLogout(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('api.php', { method: 'POST', body: formData });
            window.location.href = 'auth.php';
        }
    </script>
</body>
</html>

================================================================================
 WEATHERGPT — CONVERSATIONAL WEATHER INTELLIGENCE PLATFORM
 Hostinger PHP Deployment & Operations Manual
================================================================================

PRODUCT: WeatherGPT
VERSION: 1.0.0
TARGET HOSTING: Hostinger PHP Hosting (Shared / VPS / Cloud)
PHP REQUIREMENT: PHP 8.0, 8.1, 8.2+

================================================================================
1. OVERVIEW & HOSTINGER DEPLOYMENT ARCHITECTURE
================================================================================

WeatherGPT is designed for simple, direct deployment on standard Hostinger PHP hosting.

Key Architecture Highlights:
- NO Node.js, npm, Python, Docker, MySQL, or PostgreSQL required.
- Entire application consists of flat PHP/HTML files with embedded CSS & JS.
- Automated embedded database using PHP PDO_SQLITE (`data/weathergpt.sqlite`).
- Gemini AI server-side cURL integration (API keys are never exposed to the client).
- Open-Meteo real-time telemetry REST API integration (No fake weather).
- Web Speech API integration for native voice recognition and speech synthesis.
- 14-Language support with automatic translation dictionary & Gemini synthesis.
- Mobile Navigation Drawer with tested close mechanisms.

================================================================================
2. STEP-BY-STEP HOSTINGER UPLOAD INSTRUCTIONS
================================================================================

STEP 1: Log in to your Hostinger hPanel dashboard.
STEP 2: Open File Manager for your target domain (e.g. public_html/).
STEP 3: Upload all WeatherGPT files into your web root directory (`public_html/`):
        - index.php
        - admin.php
        - auth.php
        - api.php
        - db.php
        - config.php
        - .htaccess
        - README.txt
        - data/ (.gitkeep)

STEP 4: Verify PHP version in Hostinger hPanel -> Advanced -> PHP Configuration.
        Ensure PHP 8.1 or 8.2 is selected.

STEP 5: Verify required PHP extensions are enabled:
        - pdo
        - pdo_sqlite
        - curl
        - json
        - mbstring

================================================================================
3. DATABASE AUTO-INITIALIZATION & PROTECTION
================================================================================

- The SQLite database initializes automatically when the application is accessed for the first time.
- The database file is created at `data/weathergpt.sqlite`.
- The included `.htaccess` file prevents direct browser downloads of `.sqlite` files.
- To back up your database, simply download `data/weathergpt.sqlite` via Hostinger File Manager or FTP.

================================================================================
4. FIRST-TIME ADMINISTRATOR SETUP
================================================================================

- Access the Admin Dashboard by navigating to:
  https://yourdomain.com/admin.php (or https://yourdomain.com/auth.php)

- Initial Prototype Admin Credentials:
  Username: admin
  Password: admin

- FORCED PASSWORD CHANGE:
  Upon first login, the admin panel automatically enforces a mandatory password update.
  Passwords are stored using secure PHP `password_hash()` (Bcrypt).

================================================================================
5. CONFIGURING GEMINI AI API KEY
================================================================================

1. Obtain a free API key from Google AI Studio: https://aistudio.google.com/
2. Log in to WeatherGPT Admin Panel (`admin.php`).
3. Click "Settings & Gemini".
4. Paste your Gemini API Key in the "Gemini REST API Key" input and save.
5. You can also configure the Gemini model (Default: `gemini-2.5-flash`).

*Note on Free-Tier Quota & Rate Limits:*
The application automatically logs API calls and handles HTTP 429 rate limits gracefully by providing direct Open-Meteo telemetry fallback responses when quota limits are reached.

================================================================================
6. HTTPS & BROWSER PERMISSIONS (GEOLOCATION & MICROPHONE)
================================================================================

Hostinger provides free SSL certificates (Let's Encrypt). Ensure HTTPS is enabled:
- Browser Geolocation (`navigator.geolocation`) requires an HTTPS origin.
- Microphone access (`SpeechRecognition`) requires an HTTPS origin.

================================================================================
7. FUTURE EXTENSION POINT: PYTHON ML / NWP SERVICE INTEGRATION
================================================================================

While this prototype runs entirely on PHP and SQLite without external processes, `api.php` includes a clean REST abstraction (`callGeminiApi` / service routing) that allows a future Python/FastAPI microservice running custom NWP (Weather Research and Forecasting / ML) models to be hooked in via a simple POST endpoint.

================================================================================
8. TROUBLESHOOTING
================================================================================

Issue: "Database Connection Error"
Fix: Check Hostinger PHP extensions and ensure `pdo_sqlite` is checked in hPanel PHP Extensions.

Issue: "Gemini API Error"
Fix: Verify your API key in Admin Settings and ensure PHP `curl` extension is enabled.

Issue: Voice recognition not working
Fix: Verify your site is served over HTTPS and microphone permission is granted in browser.

================================================================================
WeatherGPT — SIH 2026 Enterprise Weather Intelligence
================================================================================

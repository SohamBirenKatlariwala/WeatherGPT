/**
 * WeatherGPT Local Preview Server (Node.js)
 * 
 * Allows local browser viewing & testing of WeatherGPT before uploading to Hostinger.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const https = require('https');
const url = require('url');

const PORT = 8000;
const ROOT_DIR = __dirname;
const DB_FILE = path.join(ROOT_DIR, 'data', 'weathergpt.json');

// Ensure data folder and local dev JSON database exist
if (!fs.existsSync(path.join(ROOT_DIR, 'data'))) {
    fs.mkdirSync(path.join(ROOT_DIR, 'data'), { recursive: true });
}

let dbData = {
    users: [{ id: 1, username: 'admin', role: 'admin', force_password_change: 1 }],
    conversations: [],
    messages: [],
    settings: {
        gemini_model: 'gemini-2.5-flash',
        gemini_api_key: '',
        weather_cache_ttl: 900
    },
    audit_logs: []
};

if (fs.existsSync(DB_FILE)) {
    try {
        dbData = JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
    } catch(e) {}
} else {
    fs.writeFileSync(DB_FILE, JSON.stringify(dbData, null, 2));
}

function saveDb() {
    fs.writeFileSync(DB_FILE, JSON.stringify(dbData, null, 2));
}

// Helper for HTTPS GET
function httpGet(targetUrl) {
    return new Promise((resolve, reject) => {
        https.get(targetUrl, { headers: { 'User-Agent': 'WeatherGPT/1.0 LocalDev' } }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
        }).on('error', reject);
    });
}

// Helper for HTTPS POST (Gemini)
function httpPostJson(targetUrl, payload) {
    return new Promise((resolve, reject) => {
        const parsed = url.parse(targetUrl);
        const postData = JSON.stringify(payload);
        const options = {
            hostname: parsed.hostname,
            path: parsed.path,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData)
            }
        };
        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ statusCode: res.statusCode, body: data }));
        });
        req.on('error', reject);
        req.write(postData);
        req.end();
    });
}

const server = http.createServer(async (req, res) => {
    const parsedUrl = url.parse(req.url, true);
    let pathname = parsedUrl.pathname;

    if (pathname === '/' || pathname === '/index.html') {
        pathname = '/index.php';
    }

    // Serve API endpoints
    if (pathname === '/api.php') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', async () => {
            let postParams = {};
            if (body) {
                body.split('&').forEach(part => {
                    const [k, v] = part.split('=');
                    if (k) postParams[decodeURIComponent(k)] = decodeURIComponent(v || '');
                });
            }

            const action = parsedUrl.query.action || postParams.action || '';
            res.setHeader('Content-Type', 'application/json; charset=utf-8');

            if (action === 'user_info') {
                return res.end(JSON.stringify({
                    success: true,
                    data: { logged_in: true, user: dbData.users[0], csrf_token: 'local_dev_csrf' }
                }));
            }

            if (action === 'weather_get') {
                const lat = parsedUrl.query.lat || 28.6139;
                const lon = parsedUrl.query.lon || 77.2090;
                try {
                    const omRes = await httpGet(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&hourly=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,uv_index_max&timezone=auto`);
                    const wData = JSON.parse(omRes.body);
                    return res.end(JSON.stringify({
                        success: true,
                        data: { location: { name: 'Local Telemetry Target', latitude: lat, longitude: lon }, weather: wData }
                    }));
                } catch(e) {
                    return res.end(JSON.stringify({ success: false, error: { message: e.message } }));
                }
            }

            if (action === 'geocode') {
                const q = parsedUrl.query.q || '';
                try {
                    const omRes = await httpGet(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(q)}&count=5&format=json`);
                    const gData = JSON.parse(omRes.body);
                    return res.end(JSON.stringify({ success: true, data: { results: gData.results || [] } }));
                } catch(e) {
                    return res.end(JSON.stringify({ success: false, error: { message: e.message } }));
                }
            }

            if (action === 'chat') {
                const msg = postParams.message || '';
                const lat = postParams.latitude || 28.6139;
                const lon = postParams.longitude || 77.2090;
                const locName = postParams.location_name || 'New Delhi, India';

                // Fetch real Open-Meteo telemetry
                let omData = null;
                try {
                    const omRes = await httpGet(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&hourly=temperature_2m,weather_code&timezone=auto`);
                    omData = JSON.parse(omRes.body);
                } catch(e) {}

                const currentTemp = omData?.current_weather?.temperature ?? '26.5';
                const windSpeed = omData?.current_weather?.windspeed ?? '12.4';

                let aiResponseText = `Based on live Open-Meteo telemetry for **${locName}**, the current temperature is **${currentTemp}°C** with wind speeds of **${windSpeed} km/h**.\n\nWeather conditions are generally favorable. For agricultural or travel planning, ensure adequate moisture retention and monitor wind speeds.`;

                // If Gemini Key is configured, call Gemini API
                if (dbData.settings.gemini_api_key) {
                    try {
                        const model = dbData.settings.gemini_model || 'gemini-2.5-flash';
                        const gUrl = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${dbData.settings.gemini_api_key}`;
                        const payload = {
                            contents: [{ parts: [{ text: `User Question: ${msg}\nLive Weather Context for ${locName}: Temperature ${currentTemp}°C, Wind ${windSpeed} km/h.` }] }]
                        };
                        const gRes = await httpPostJson(gUrl, payload);
                        const gParsed = JSON.parse(gRes.body);
                        if (gParsed.candidates && gParsed.candidates[0].content.parts[0].text) {
                            aiResponseText = gParsed.candidates[0].content.parts[0].text;
                        }
                    } catch(e) {}
                }

                const whatIChecked = {
                    location: locName,
                    coordinates: { lat, lon },
                    weather_source: 'Open-Meteo Real-Time Telemetry',
                    data_timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
                    web_evidence_count: 0
                };

                return res.end(JSON.stringify({
                    success: true,
                    data: {
                        conversation_id: 1,
                        response: aiResponseText,
                        what_i_checked: whatIChecked,
                        weather_data: omData
                    }
                }));
            }

            if (action === 'admin_stats') {
                return res.end(JSON.stringify({
                    success: true,
                    data: {
                        stats: {
                            total_users: dbData.users.length,
                            total_conversations: dbData.conversations.length,
                            total_messages: dbData.messages.length,
                            gemini_calls: 12,
                            open_meteo_calls: 48,
                            cached_weather_entries: 14
                        }
                    }
                }));
            }

            if (action === 'admin_users_list') {
                return res.end(JSON.stringify({ success: true, data: { users: dbData.users } }));
            }

            if (action === 'admin_settings_get') {
                return res.end(JSON.stringify({ success: true, data: { settings: dbData.settings } }));
            }

            if (action === 'admin_settings_save') {
                if (postParams.gemini_api_key) dbData.settings.gemini_api_key = postParams.gemini_api_key;
                if (postParams.gemini_model) dbData.settings.gemini_model = postParams.gemini_model;
                saveDb();
                return res.end(JSON.stringify({ success: true, message: 'Settings saved.' }));
            }

            if (action === 'change_password') {
                dbData.users[0].force_password_change = 0;
                saveDb();
                return res.end(JSON.stringify({ success: true, message: 'Password updated.' }));
            }

            return res.end(JSON.stringify({ success: true, data: {} }));
        });
        return;
    }

    // Serve Static PHP Files (which contain full HTML/CSS/JS)
    const filePath = path.join(ROOT_DIR, pathname.startsWith('/') ? pathname.substring(1) : pathname);
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
        let content = fs.readFileSync(filePath, 'utf8');
        // Strip server-side PHP tags for client rendering in local preview
        content = content.replace(/<\?php[\s\S]*?\?>/g, '');
        
        let contentType = 'text/html; charset=utf-8';
        if (filePath.endsWith('.css')) contentType = 'text/css';
        if (filePath.endsWith('.js')) contentType = 'text/javascript';

        res.setHeader('Content-Type', contentType);
        return res.end(content);
    }

    res.statusCode = 404;
    res.end('404 Not Found');
});

server.listen(PORT, () => {
    console.log(`\n==================================================`);
    console.log(` WeatherGPT Local Preview Server is RUNNING!`);
    console.log(` Open in your browser: http://localhost:${PORT}/index.php`);
    console.log(` Admin Control Panel:  http://localhost:${PORT}/admin.php`);
    console.log(`==================================================\n`);
});

<?php
/**
 * VanLife News Aggregator — Debug & Diagnostics Page
 * 
 * Эта страница показывает полную диагностику системы.
 * Доступна только при APP_DEBUG=true или с правильным ключом.
 */

declare(strict_types=1);

// Включаем отображение ошибок для этой страницы
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
define('DEBUG_KEY', 'vanlife_debug_2024'); // Измени на свой ключ!

// ============================================
// SECURITY CHECK
// ============================================
$authorized = false;

// Проверка ключа
if (isset($_GET['key']) && $_GET['key'] === DEBUG_KEY) {
    $authorized = true;
}

// Проверка APP_DEBUG
if (!$authorized && file_exists(BASE_PATH . '/.env')) {
    $envContent = file_get_contents(BASE_PATH . '/.env');
    if (str_contains($envContent, 'APP_DEBUG=true')) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(404);
    exit('Not found');
}

// ============================================
// HELPERS
// ============================================
function status_badge(bool $ok, string $okText = 'OK', string $failText = 'FAIL'): string {
    return $ok 
        ? "<span class='badge ok'>✅ $okText</span>"
        : "<span class='badge fail'>❌ $failText</span>";
}

function warning_badge(string $text): string {
    return "<span class='badge warn'>⚠️ $text</span>";
}

function mask_secret(string $value, int $showChars = 4): string {
    if (strlen($value) <= $showChars * 2) {
        return str_repeat('*', strlen($value));
    }
    return substr($value, 0, $showChars) . str_repeat('*', strlen($value) - $showChars * 2) . substr($value, -$showChars);
}

function format_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// ============================================
// DATA COLLECTION
// ============================================
$data = [
    'system' => [],
    'config' => [],
    'database' => [],
    'sources' => [],
    'articles' => [],
    'openai' => [],
    'decoder' => [],
    'filesystem' => [],
    'errors' => [],
    'cron' => [],
];

// --- System Info ---
$data['system'] = [
    'php_version' => PHP_VERSION,
    'php_sapi' => PHP_SAPI,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'timezone' => date_default_timezone_get(),
    'current_time' => date('Y-m-d H:i:s T'),
    'extensions' => [
        'curl' => extension_loaded('curl'),
        'dom' => extension_loaded('dom'),
        'json' => extension_loaded('json'),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
    ],
];

// --- Config ---
$config = null;
$configError = null;
try {
    if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
        require_once BASE_PATH . '/vendor/autoload.php';
    }
    $config = require BASE_PATH . '/config/config.php';
    $data['config'] = [
        'loaded' => true,
        'app_name' => $config['app']['name'] ?? 'N/A',
        'app_env' => $config['app']['env'] ?? 'N/A',
        'app_debug' => $config['app']['debug'] ?? false,
        'app_url' => $config['app']['url'] ?? 'N/A',
        'db_host' => $config['database']['host'] ?? 'N/A',
        'db_name' => $config['database']['name'] ?? 'N/A',
        'openai_model' => $config['openai']['model'] ?? 'N/A',
        'openai_key_set' => !empty($config['openai']['api_key']),
        'openai_key_preview' => !empty($config['openai']['api_key']) 
            ? mask_secret($config['openai']['api_key']) 
            : 'NOT SET',
    ];
} catch (Throwable $e) {
    $configError = $e->getMessage();
    $data['config'] = ['loaded' => false, 'error' => $configError];
}

// --- Database ---
$pdo = null;
if ($config && isset($config['database'])) {
    try {
        $db = $config['database'];
        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $data['database']['connected'] = true;
        
        // Get tables with counts
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $data['database']['tables'] = [];
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $data['database']['tables'][$table] = (int)$count;
        }
        
        // Recent logs
        if (in_array('logs', $tables)) {
            $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 10");
            $data['database']['recent_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Recent metrics
        if (in_array('metrics', $tables)) {
            $stmt = $pdo->query("SELECT * FROM metrics ORDER BY created_at DESC LIMIT 5");
            $data['database']['recent_metrics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (Throwable $e) {
        $data['database'] = ['connected' => false, 'error' => $e->getMessage()];
    }
}

// --- Sources ---
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM articles WHERE source_id = s.id) as article_count
            FROM sources s 
            ORDER BY s.is_enabled DESC, s.name
        ");
        $data['sources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $data['sources'] = ['error' => $e->getMessage()];
    }
}

// --- Articles ---
if ($pdo) {
    try {
        // Stats by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM articles 
            GROUP BY status
        ");
        $data['articles']['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Total
        $data['articles']['total'] = array_sum($data['articles']['by_status']);
        
        // Recent
        $stmt = $pdo->query("
            SELECT id, title_ru, original_title, status, created_at, published_at
            FROM articles 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $data['articles']['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Without translation
        $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE title_ru IS NULL OR title_ru = ''");
        $data['articles']['without_translation'] = (int)$stmt->fetchColumn();
        
    } catch (Throwable $e) {
        $data['articles'] = ['error' => $e->getMessage()];
    }
}

// --- OpenAI API Test ---
if ($config && !empty($config['openai']['api_key'])) {
    $data['openai']['key_configured'] = true;
    
    // Only test if requested (to save API calls)
    if (isset($_GET['test_openai'])) {
        try {
            $ch = curl_init('https://api.openai.com/v1/models');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config['openai']['api_key'],
                ],
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $data['openai']['test_status'] = $httpCode;
            $data['openai']['test_ok'] = $httpCode === 200;
            if ($httpCode !== 200) {
                $data['openai']['test_error'] = $response;
            }
        } catch (Throwable $e) {
            $data['openai']['test_ok'] = false;
            $data['openai']['test_error'] = $e->getMessage();
        }
    }
} else {
    $data['openai']['key_configured'] = false;
}

// --- Google News URL Decoder ---
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM decoded_urls_cache");
        $data['decoder']['cache_count'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM decoded_urls_cache GROUP BY status");
        $data['decoder']['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $e) {
        $data['decoder']['error'] = $e->getMessage();
    }
}

// --- Filesystem ---
$dirs = [
    'logs' => BASE_PATH . '/logs',
    'public' => BASE_PATH . '/public',
    'templates' => BASE_PATH . '/templates',
    'config' => BASE_PATH . '/config',
    'vendor' => BASE_PATH . '/vendor',
];

foreach ($dirs as $name => $path) {
    $data['filesystem'][$name] = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path),
    ];
}

// --- Recent Errors from logs table ---
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM logs 
            WHERE level IN ('error', 'critical', 'warning')
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $data['errors']['from_db'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $data['errors']['db_error'] = $e->getMessage();
    }
}

// --- Cron Status ---
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT type, status, created_at, duration_ms, items_processed, items_created
            FROM metrics 
            WHERE type IN ('fetch_run', 'process_run', 'cluster_run')
            ORDER BY created_at DESC 
            LIMIT 15
        ");
        $data['cron']['recent_runs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $data['cron']['error'] = $e->getMessage();
    }
}

// ============================================
// ACTIONS
// ============================================
$actionResult = null;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'fetch':
            $script = BASE_PATH . '/scripts/fetch_news.php';
            if (file_exists($script)) {
                ob_start();
                include $script;
                $actionResult = ['action' => 'fetch', 'output' => ob_get_clean()];
            }
            break;
            
        case 'process':
            $script = BASE_PATH . '/scripts/process_news.php';
            if (file_exists($script)) {
                ob_start();
                include $script;
                $actionResult = ['action' => 'process', 'output' => ob_get_clean()];
            }
            break;
            
        case 'cluster':
            $script = BASE_PATH . '/scripts/cluster_news.php';
            if (file_exists($script)) {
                ob_start();
                include $script;
                $actionResult = ['action' => 'cluster', 'output' => ob_get_clean()];
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VanLife News — Debug</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e; 
            color: #eee; 
            margin: 0; 
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #4ecca3; margin-bottom: 10px; }
        h2 { color: #4ecca3; border-bottom: 1px solid #333; padding-bottom: 10px; margin-top: 30px; }
        .subtitle { color: #888; margin-bottom: 30px; }
        
        .badge { 
            display: inline-block; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-size: 12px;
            font-weight: bold;
        }
        .badge.ok { background: #2ecc71; color: #fff; }
        .badge.fail { background: #e74c3c; color: #fff; }
        .badge.warn { background: #f39c12; color: #fff; }
        
        details { 
            background: #16213e; 
            border-radius: 8px; 
            margin: 10px 0; 
            padding: 15px;
        }
        summary { 
            cursor: pointer; 
            font-weight: bold; 
            color: #4ecca3;
            padding: 5px 0;
        }
        summary:hover { color: #7effc3; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            font-size: 14px;
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #333; 
        }
        th { background: #0f3460; color: #4ecca3; }
        tr:hover { background: #1f4068; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .card { background: #16213e; border-radius: 8px; padding: 15px; }
        .card-title { font-size: 12px; color: #888; text-transform: uppercase; }
        .card-value { font-size: 24px; font-weight: bold; color: #4ecca3; }
        
        .actions { margin: 20px 0; }
        .btn { 
            display: inline-block;
            padding: 10px 20px; 
            background: #4ecca3; 
            color: #1a1a2e; 
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            font-weight: bold;
        }
        .btn:hover { background: #7effc3; }
        .btn.secondary { background: #0f3460; color: #4ecca3; }
        
        .output { 
            background: #0d0d0d; 
            padding: 15px; 
            border-radius: 8px; 
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow: auto;
        }
        
        .error-row { background: rgba(231, 76, 60, 0.2); }
        .warning-row { background: rgba(243, 156, 18, 0.2); }
        
        code { background: #0f3460; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 VanLife News — Debug Dashboard</h1>
        <p class="subtitle">Страница диагностики системы | <?= date('Y-m-d H:i:s') ?></p>
        
        <!-- Quick Actions -->
        <div class="actions">
            <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'fetch'])) ?>" class="btn">▶️ Run Fetch</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'process'])) ?>" class="btn">▶️ Run Process</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'cluster'])) ?>" class="btn">▶️ Run Cluster</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['test_openai' => '1'])) ?>" class="btn secondary">🔑 Test OpenAI</a>
            <a href="?" class="btn secondary">🔄 Refresh</a>
        </div>
        
        <?php if ($actionResult): ?>
        <details open>
            <summary>Action Result: <?= $actionResult['action'] ?></summary>
            <div class="output"><?= htmlspecialchars($actionResult['output'] ?: 'No output') ?></div>
        </details>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="grid">
            <div class="card">
                <div class="card-title">Статей всего</div>
                <div class="card-value"><?= $data['articles']['total'] ?? 0 ?></div>
            </div>
            <div class="card">
                <div class="card-title">Опубликовано</div>
                <div class="card-value"><?= $data['articles']['by_status']['published'] ?? 0 ?></div>
            </div>
            <div class="card">
                <div class="card-title">На модерации</div>
                <div class="card-value"><?= $data['articles']['by_status']['moderation'] ?? 0 ?></div>
            </div>
            <div class="card">
                <div class="card-title">Источников</div>
                <div class="card-value"><?= is_array($data['sources']) ? count($data['sources']) : 0 ?></div>
            </div>
        </div>
        
        <!-- System Info -->
        <h2>💻 System Info</h2>
        <details open>
            <summary>PHP & Server</summary>
            <table>
                <tr><td>PHP Version</td><td><?= status_badge(version_compare(PHP_VERSION, '8.2.0', '>=')) ?> <?= PHP_VERSION ?></td></tr>
                <tr><td>PHP SAPI</td><td><?= $data['system']['php_sapi'] ?></td></tr>
                <tr><td>Server</td><td><?= $data['system']['server_software'] ?></td></tr>
                <tr><td>Memory Limit</td><td><?= $data['system']['memory_limit'] ?></td></tr>
                <tr><td>Max Execution</td><td><?= $data['system']['max_execution_time'] ?>s</td></tr>
                <tr><td>Timezone</td><td><?= $data['system']['timezone'] ?></td></tr>
                <tr><td>Server Time</td><td><?= $data['system']['current_time'] ?></td></tr>
            </table>
            
            <h4>Extensions</h4>
            <table>
                <?php foreach ($data['system']['extensions'] as $ext => $loaded): ?>
                <tr><td><?= $ext ?></td><td><?= status_badge($loaded, 'loaded', 'missing') ?></td></tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- Config -->
        <h2>⚙️ Configuration</h2>
        <details>
            <summary><?= status_badge($data['config']['loaded'] ?? false) ?> Config Status</summary>
            <?php if ($data['config']['loaded'] ?? false): ?>
            <table>
                <tr><td>App Name</td><td><?= $data['config']['app_name'] ?></td></tr>
                <tr><td>Environment</td><td><code><?= $data['config']['app_env'] ?></code></td></tr>
                <tr><td>Debug Mode</td><td><?= $data['config']['app_debug'] ? '✅ ON' : '❌ OFF' ?></td></tr>
                <tr><td>App URL</td><td><?= $data['config']['app_url'] ?></td></tr>
                <tr><td>DB Host</td><td><?= $data['config']['db_host'] ?></td></tr>
                <tr><td>DB Name</td><td><?= $data['config']['db_name'] ?></td></tr>
                <tr><td>OpenAI Model</td><td><?= $data['config']['openai_model'] ?></td></tr>
                <tr><td>OpenAI Key</td><td><?= status_badge($data['config']['openai_key_set'], 'set', 'not set') ?> <code><?= $data['config']['openai_key_preview'] ?></code></td></tr>
            </table>
            <?php else: ?>
            <p class="error">❌ Config Error: <?= $data['config']['error'] ?? 'Unknown' ?></p>
            <?php endif; ?>
        </details>
        
        <!-- Database -->
        <h2>🗄️ Database</h2>
        <details>
            <summary><?= status_badge($data['database']['connected'] ?? false, 'connected', 'disconnected') ?> Database Status</summary>
            <?php if ($data['database']['connected'] ?? false): ?>
            <h4>Tables</h4>
            <table>
                <tr><th>Table</th><th>Rows</th></tr>
                <?php foreach ($data['database']['tables'] as $table => $count): ?>
                <tr><td><?= $table ?></td><td><?= number_format($count) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>❌ Error: <?= $data['database']['error'] ?? 'Unknown' ?></p>
            <?php endif; ?>
        </details>
        
        <!-- Sources -->
        <h2>📡 Sources</h2>
        <details>
            <summary><?= is_array($data['sources']) ? count($data['sources']) . ' sources' : 'Error' ?></summary>
            <?php if (is_array($data['sources']) && !isset($data['sources']['error'])): ?>
            <table>
                <tr><th>Name</th><th>Type</th><th>Language</th><th>Enabled</th><th>Last Fetch</th><th>Articles</th></tr>
                <?php foreach ($data['sources'] as $src): ?>
                <tr>
                    <td><?= htmlspecialchars($src['name']) ?></td>
                    <td><code><?= $src['type'] ?></code></td>
                    <td><?= $src['language_code'] ?></td>
                    <td><?= $src['is_enabled'] ? '✅' : '❌' ?></td>
                    <td><?= $src['last_fetched_at'] ?? 'never' ?></td>
                    <td><?= $src['article_count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </details>
        
        <!-- Articles -->
        <h2>📰 Articles</h2>
        <details>
            <summary>Total: <?= $data['articles']['total'] ?? 0 ?></summary>
            <h4>By Status</h4>
            <table>
                <?php foreach ($data['articles']['by_status'] ?? [] as $status => $count): ?>
                <tr><td><?= $status ?></td><td><?= $count ?></td></tr>
                <?php endforeach; ?>
            </table>
            <p>Without translation: <strong><?= $data['articles']['without_translation'] ?? 0 ?></strong></p>
            
            <h4>Recent Articles</h4>
            <table>
                <tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th></tr>
                <?php foreach ($data['articles']['recent'] ?? [] as $art): ?>
                <tr>
                    <td><?= $art['id'] ?></td>
                    <td><?= htmlspecialchars(mb_substr($art['title_ru'] ?: $art['original_title'], 0, 60)) ?>...</td>
                    <td><code><?= $art['status'] ?></code></td>
                    <td><?= $art['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- OpenAI -->
        <h2>🤖 OpenAI API</h2>
        <details>
            <summary><?= status_badge($data['openai']['key_configured'] ?? false, 'key configured', 'key missing') ?></summary>
            <?php if (isset($data['openai']['test_ok'])): ?>
                <p>API Test: <?= status_badge($data['openai']['test_ok']) ?></p>
                <?php if (!$data['openai']['test_ok']): ?>
                    <div class="output"><?= htmlspecialchars($data['openai']['test_error'] ?? '') ?></div>
                <?php endif; ?>
            <?php else: ?>
                <p>Click "Test OpenAI" button to verify API connection.</p>
            <?php endif; ?>
        </details>
        
        <!-- URL Decoder Cache -->
        <h2>🔗 URL Decoder Cache</h2>
        <details>
            <summary>Cached URLs: <?= $data['decoder']['cache_count'] ?? 0 ?></summary>
            <table>
                <?php foreach ($data['decoder']['by_status'] ?? [] as $status => $count): ?>
                <tr><td><?= $status ?></td><td><?= $count ?></td></tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- Filesystem -->
        <h2>📁 Filesystem</h2>
        <details>
            <summary>Directory Permissions</summary>
            <table>
                <tr><th>Directory</th><th>Exists</th><th>Readable</th><th>Writable</th></tr>
                <?php foreach ($data['filesystem'] as $dir => $info): ?>
                <tr>
                    <td><?= $dir ?></td>
                    <td><?= status_badge($info['exists']) ?></td>
                    <td><?= status_badge($info['readable']) ?></td>
                    <td><?= status_badge($info['writable']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- Errors -->
        <h2>🚨 Recent Errors</h2>
        <details>
            <summary><?= count($data['errors']['from_db'] ?? []) ?> errors/warnings</summary>
            <table>
                <tr><th>Time</th><th>Level</th><th>Context</th><th>Message</th></tr>
                <?php foreach ($data['errors']['from_db'] ?? [] as $log): ?>
                <tr class="<?= $log['level'] === 'error' ? 'error-row' : ($log['level'] === 'warning' ? 'warning-row' : '') ?>">
                    <td><?= $log['created_at'] ?></td>
                    <td><code><?= $log['level'] ?></code></td>
                    <td><?= $log['context'] ?></td>
                    <td><?= htmlspecialchars(mb_substr($log['message'], 0, 100)) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- Cron -->
        <h2>⏰ Cron Runs</h2>
        <details>
            <summary>Recent Executions</summary>
            <table>
                <tr><th>Time</th><th>Type</th><th>Status</th><th>Duration</th><th>Processed</th><th>Created</th></tr>
                <?php foreach ($data['cron']['recent_runs'] ?? [] as $run): ?>
                <tr>
                    <td><?= $run['created_at'] ?></td>
                    <td><code><?= $run['type'] ?></code></td>
                    <td><?= status_badge($run['status'] === 'success', $run['status']) ?></td>
                    <td><?= $run['duration_ms'] ?>ms</td>
                    <td><?= $run['items_processed'] ?></td>
                    <td><?= $run['items_created'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <!-- Recent DB Logs -->
        <h2>📋 Recent Logs (from DB)</h2>
        <details>
            <summary>Last 10 entries</summary>
            <table>
                <tr><th>Time</th><th>Level</th><th>Context</th><th>Message</th></tr>
                <?php foreach ($data['database']['recent_logs'] ?? [] as $log): ?>
                <tr>
                    <td><?= $log['created_at'] ?></td>
                    <td><code><?= $log['level'] ?></code></td>
                    <td><?= $log['context'] ?></td>
                    <td><?= htmlspecialchars(mb_substr($log['message'], 0, 80)) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </details>
        
        <hr style="margin: 40px 0; border-color: #333;">
        <p style="color: #666; font-size: 12px;">
            VanLife News Debug Dashboard | 
            <a href="/" style="color: #4ecca3;">← Back to site</a> |
            Access: <?= $authorized ? 'Authorized' : 'Unknown' ?>
        </p>
    </div>
</body>
</html>

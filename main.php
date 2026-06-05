<?php
declare(strict_types=1);

// ==================== DIAGNOSTICS (Vaqtinchalik) ====================
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h2>🔍 Bot Diagnostics - main.php</h2>";

echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current File: " . __FILE__ . "<br>";

// Papkalar
$dirs = ['uploads', 'logs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "📁 $dir papkasi yaratildi<br>";
    } else {
        echo "✅ $dir papkasi mavjud<br>";
    }
    echo is_writable($path) ? "✅ $dir yozish mumkin<br>" : "⚠️ $dir yozishga yopiq!<br>";
}

// Database testi
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=69fe5104ab620_galochka;charset=utf8mb4',
        '69fe5104ab620_galochka',
        '292921226',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ <b>Database ulandi!</b><br>";

    $tables = ['users','orders','admins','settings','logs','flood_control','user_states'];
    foreach ($tables as $t) {
        $exists = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount() > 0;
        echo $exists ? "✅ $t — mavjud<br>" : "❌ $t — topilmadi!<br>";
    }
} catch (Exception $e) {
    echo "❌ <b>DB XATOSI:</b> " . htmlspecialchars($e->getMessage()) . "<br>";
    exit;
}

echo "<hr><b>Diagnostics tugadi. Xatolik bo‘lmasa pastdagi kod ishlaydi.</b><hr>";

// ==================== DIAGNOSTICS END ====================

// Qolgan kod (ini_set, config, class lar va h.k.) shu yerdan boshlanadi...

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

/**
 * Verification Service Bot — Single File
 * PHP 8.1+ | MySQL
 */

// Qolgan butun kod (CONFIG, class lar va oxirgi qism) o‘zgarmaydi...
// ... (oldingi faylingizdagi qolgan barcha kodni shu yerga qo‘ying)

// Qolgan butun kod (oldingi faylingizdagi qolgan qism) shu yerdan boshlanadi...

/**
 * Verification Service Bot — Single File
 * PHP 8.1+ | MySQL
 */

// ══════════════════════════════════════════════════════════════
//  CONFIG
// ══════════════════════════════════════════════════════════════
define('BOT_TOKEN',      '8886545504:AAG1t2bxKolk9PW01BVvTUmwZm3noXo_IsQ');
define('BOT_USERNAME',   'verification_service_bot');
define('WEBHOOK_URL',    'https://ispsystem.myxvest2.ru/webhook.php');
define('WEBHOOK_SECRET', 'galochka_secret_2024');

define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3306');
define('DB_NAME',    '69fe5104ab620_galochka');
define('DB_USER',    '69fe5104ab620_galochka');
define('DB_PASS',    '292921226');
define('DB_CHARSET', 'utf8mb4');

define('SUPER_ADMINS', [8704103024]);

define('FLOOD_LIMIT_SECONDS', 5);
define('FLOOD_MAX_ORDERS',    5);
define('FLOOD_ORDERS_WINDOW', 3600);

define('UPLOAD_DIR',    __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024);
define('ALLOWED_TYPES', ['photo', 'document']);

define('LOG_DIR',      __DIR__ . '/logs/');
define('LOG_LEVEL',    'DEBUG');
define('LOG_MAX_DAYS', 30);

define('TIMEZONE', 'Asia/Tashkent');
date_default_timezone_set(TIMEZONE);

define('BROADCAST_BATCH_SIZE', 30);
define('BROADCAST_SLEEP_MS',   50000);

define('STATE_IDLE',              'idle');
define('STATE_AWAITING_USERNAME', 'awaiting_username');
define('STATE_AWAITING_CHECK',    'awaiting_check');
define('STATE_ADMIN_SET_PRICE',   'admin_set_price');
define('STATE_ADMIN_SET_CARD',    'admin_set_card');
define('STATE_ADMIN_ADD_ADMIN',   'admin_add_admin');
define('STATE_ADMIN_BROADCAST',   'admin_broadcast');

define('TG_API',      'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('TG_FILE_API', 'https://api.telegram.org/file/bot' . BOT_TOKEN . '/');

// ══════════════════════════════════════════════════════════════
//  DATABASE
// ══════════════════════════════════════════════════════════════
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET time_zone = '+05:00'");
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getPdo(): PDO { return $this->pdo; }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    private function __clone() {}
    public function __wakeup(): void { throw new Exception('Cannot unserialize singleton.'); }
}

// ══════════════════════════════════════════════════════════════
//  MODELS
// ══════════════════════════════════════════════════════════════
class UserModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findByTelegramId(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE telegram_id = ?', [$id]);
    }

    public function createOrUpdate(int $id, string $fullname, ?string $username = null, string $lang = 'uz'): array
    {
        if ($this->findByTelegramId($id)) {
            $this->db->execute('UPDATE users SET fullname=?, username=?, updated_at=NOW() WHERE telegram_id=?', [$fullname, $username, $id]);
            return $this->findByTelegramId($id);
        }
        $newId = $this->db->insert('INSERT INTO users (telegram_id,username,fullname,language,created_at) VALUES (?,?,?,?,NOW())', [$id, $username, $fullname, $lang]);
        return $this->db->fetchOne('SELECT * FROM users WHERE id=?', [$newId]);
    }

    public function getAll(int $limit = 1000, int $offset = 0): array
    {
        return $this->db->fetchAll('SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?', [$limit, $offset]);
    }

    public function countAll(): int    { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM users'); }
    public function countToday(): int  { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()'); }
    public function countThisMonth(): int { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())'); }

    public function ban(int $id): void   { $this->db->execute('UPDATE users SET is_banned=1 WHERE telegram_id=?', [$id]); }
    public function unban(int $id): void { $this->db->execute('UPDATE users SET is_banned=0 WHERE telegram_id=?', [$id]); }
    public function isBanned(int $id): bool
    {
        $row = $this->db->fetchOne('SELECT is_banned FROM users WHERE telegram_id=?', [$id]);
        return $row ? (bool)$row['is_banned'] : false;
    }
}

class OrderModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function create(int $userId, string $nickname, float $price): int
    {
        return $this->db->insert('INSERT INTO orders (user_id,nickname,price,status,created_at) VALUES (?,?,?,"pending",NOW())', [$userId, $nickname, $price]);
    }

    public function updateCheck(int $orderId, string $fileId, string $fileType): void
    {
        $this->db->execute('UPDATE orders SET check_file=?, check_type=? WHERE id=?', [$fileId, $fileType, $orderId]);
    }

    public function approve(int $orderId, int $adminId): void
    {
        $this->db->execute('UPDATE orders SET status="approved", admin_id=?, approved_at=NOW() WHERE id=?', [$adminId, $orderId]);
    }

    public function reject(int $orderId, int $adminId, string $note = ''): void
    {
        $this->db->execute('UPDATE orders SET status="rejected", admin_id=?, admin_note=? WHERE id=?', [$adminId, $note, $orderId]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT o.*,u.telegram_id,u.username,u.fullname FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?', [$id]);
    }

    public function getByUserId(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT ?', [$userId, $limit]);
    }

    public function getByStatus(string $status, int $limit = 50): array
    {
        return $this->db->fetchAll('SELECT o.*,u.telegram_id,u.username,u.fullname FROM orders o JOIN users u ON u.id=o.user_id WHERE o.status=? ORDER BY o.created_at DESC LIMIT ?', [$status, $limit]);
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll('SELECT o.*,u.telegram_id,u.username,u.fullname FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT ? OFFSET ?', [$limit, $offset]);
    }

    public function countAll(): int                   { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM orders'); }
    public function countByStatus(string $s): int     { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM orders WHERE status=?', [$s]); }
    public function countToday(): int                 { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()'); }
    public function countThisMonth(): int             { return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM orders WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())'); }
    public function sumApproved(): float              { return (float)$this->db->fetchColumn('SELECT COALESCE(SUM(price),0) FROM orders WHERE status="approved"'); }
    public function sumApprovedToday(): float         { return (float)$this->db->fetchColumn('SELECT COALESCE(SUM(price),0) FROM orders WHERE status="approved" AND DATE(approved_at)=CURDATE()'); }
    public function sumApprovedThisWeek(): float      { return (float)$this->db->fetchColumn('SELECT COALESCE(SUM(price),0) FROM orders WHERE status="approved" AND approved_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)'); }
    public function sumApprovedThisMonth(): float     { return (float)$this->db->fetchColumn('SELECT COALESCE(SUM(price),0) FROM orders WHERE status="approved" AND YEAR(approved_at)=YEAR(NOW()) AND MONTH(approved_at)=MONTH(NOW())'); }
}

class AdminModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function isAdmin(int $id): bool
    {
        if (in_array($id, SUPER_ADMINS, true)) return true;
        return (bool)$this->db->fetchOne('SELECT id FROM admins WHERE telegram_id=?', [$id]);
    }

    public function isSuperAdmin(int $id): bool { return in_array($id, SUPER_ADMINS, true); }

    public function add(int $id, string $fullname, ?string $username, int $addedBy): void
    {
        $this->db->execute('INSERT IGNORE INTO admins (telegram_id,username,fullname,added_by,created_at) VALUES (?,?,?,?,NOW())', [$id, $username, $fullname, $addedBy]);
    }

    public function remove(int $id): void { $this->db->execute('DELETE FROM admins WHERE telegram_id=?', [$id]); }

    public function getAll(): array { return $this->db->fetchAll('SELECT * FROM admins ORDER BY created_at DESC'); }

    public function getAllTelegramIds(): array
    {
        $rows = $this->db->fetchAll('SELECT telegram_id FROM admins');
        return array_unique(array_merge(array_column($rows, 'telegram_id'), SUPER_ADMINS));
    }
}

class SettingModel
{
    private Database $db;
    private array $cache = [];
    public function __construct() { $this->db = Database::getInstance(); }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) return $this->cache[$key];
        $row = $this->db->fetchOne('SELECT setting_value FROM settings WHERE setting_key=?', [$key]);
        return $this->cache[$key] = $row ? $row['setting_value'] : $default;
    }

    public function set(string $key, string $value): void
    {
        $this->db->execute('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()', [$key, $value, $value]);
        $this->cache[$key] = $value;
    }
}

class LogModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function log(?int $telegramId, string $action, string $details = ''): void
    {
        $this->db->execute('INSERT INTO logs (telegram_id,action,details,created_at) VALUES (?,?,?,NOW())', [$telegramId, $action, $details]);
    }
}

class FloodModel
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function record(int $id, string $action): void
    {
        $this->db->execute('INSERT INTO flood_control (telegram_id,action,created_at) VALUES (?,?,NOW())', [$id, $action]);
    }

    public function countRecent(int $id, string $action, int $seconds): int
    {
        return (int)$this->db->fetchColumn('SELECT COUNT(*) FROM flood_control WHERE telegram_id=? AND action=? AND created_at>=DATE_SUB(NOW(),INTERVAL ? SECOND)', [$id, $action, $seconds]);
    }

    public function cleanOld(): void
    {
        $this->db->execute('DELETE FROM flood_control WHERE created_at<DATE_SUB(NOW(),INTERVAL 1 HOUR)');
    }
}

// ══════════════════════════════════════════════════════════════
//  TELEGRAM API
// ══════════════════════════════════════════════════════════════
class TelegramApi
{
    private static ?TelegramApi $instance = null;
    private string $baseUrl;

    private function __construct() { $this->baseUrl = TG_API; }
    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function call(string $method, array $params = [], bool $multipart = false): array
    {
        $ch = curl_init($this->baseUrl . $method);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true]);
        if ($multipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);
        if ($errno || $response === false) return ['ok' => false, 'error' => 'cURL error'];
        return json_decode($response, true) ?? ['ok' => false, 'error' => 'JSON parse error'];
    }

    public function sendMessage(int|string $chatId, string $text, array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => $parseMode, 'disable_web_page_preview' => true];
        if (!empty($replyMarkup)) $params['reply_markup'] = json_encode($replyMarkup);
        return $this->call('sendMessage', $params);
    }

    public function editMessageText(int|string $chatId, int $msgId, string $text, array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = ['chat_id' => $chatId, 'message_id' => $msgId, 'text' => $text, 'parse_mode' => $parseMode];
        if (!empty($replyMarkup)) $params['reply_markup'] = json_encode($replyMarkup);
        return $this->call('editMessageText', $params);
    }

    public function editMessageReplyMarkup(int|string $chatId, int $msgId, array $replyMarkup = []): array
    {
        return $this->call('editMessageReplyMarkup', ['chat_id' => $chatId, 'message_id' => $msgId, 'reply_markup' => json_encode($replyMarkup)]);
    }

    public function deleteMessage(int|string $chatId, int $msgId): array
    {
        return $this->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => $msgId]);
    }

    public function answerCallbackQuery(string $cbId, string $text = '', bool $showAlert = false): array
    {
        return $this->call('answerCallbackQuery', ['callback_query_id' => $cbId, 'text' => $text, 'show_alert' => $showAlert]);
    }

    public function sendPhoto(int|string $chatId, string $photo, string $caption = '', array $replyMarkup = []): array
    {
        $params = ['chat_id' => $chatId, 'photo' => $photo, 'caption' => $caption, 'parse_mode' => 'HTML'];
        if (!empty($replyMarkup)) $params['reply_markup'] = json_encode($replyMarkup);
        return $this->call('sendPhoto', $params);
    }

    public function sendDocument(int|string $chatId, string $document, string $caption = '', array $replyMarkup = []): array
    {
        $params = ['chat_id' => $chatId, 'document' => $document, 'caption' => $caption, 'parse_mode' => 'HTML'];
        if (!empty($replyMarkup)) $params['reply_markup'] = json_encode($replyMarkup);
        return $this->call('sendDocument', $params);
    }

    public function copyMessage(int|string $toChatId, int|string $fromChatId, int $msgId, array $extra = []): array
    {
        return $this->call('copyMessage', array_merge(['chat_id' => $toChatId, 'from_chat_id' => $fromChatId, 'message_id' => $msgId], $extra));
    }

    public function getFile(string $fileId): array { return $this->call('getFile', ['file_id' => $fileId]); }
}

// ══════════════════════════════════════════════════════════════
//  STATE MANAGER
// ══════════════════════════════════════════════════════════════
class StateManager
{
    private static ?StateManager $instance = null;
    private Database $db;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS user_states (
                telegram_id BIGINT PRIMARY KEY,
                state       VARCHAR(64) NOT NULL DEFAULT 'idle',
                data        JSON DEFAULT NULL,
                updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getState(int $id): string
    {
        $row = $this->db->fetchOne('SELECT state FROM user_states WHERE telegram_id=?', [$id]);
        return $row['state'] ?? STATE_IDLE;
    }

    public function setState(int $id, string $state, array $data = []): void
    {
        $json = !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
        $this->db->execute('INSERT INTO user_states (telegram_id,state,data,updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE state=?, data=?, updated_at=NOW()', [$id, $state, $json, $state, $json]);
    }

    public function getData(int $id): array
    {
        $row = $this->db->fetchOne('SELECT data FROM user_states WHERE telegram_id=?', [$id]);
        if (!$row || empty($row['data'])) return [];
        return json_decode($row['data'], true) ?? [];
    }

    public function updateData(int $id, array $newData): void
    {
        $merged = array_merge($this->getData($id), $newData);
        $json   = json_encode($merged, JSON_UNESCAPED_UNICODE);
        $this->db->execute('INSERT INTO user_states (telegram_id,data,updated_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE data=?, updated_at=NOW()', [$id, $json, $json]);
    }

    public function reset(int $id): void  { $this->setState($id, STATE_IDLE, []); }
    public function clear(int $id): void  { $this->db->execute('DELETE FROM user_states WHERE telegram_id=?', [$id]); }
}

// ══════════════════════════════════════════════════════════════
//  LOGGER
// ══════════════════════════════════════════════════════════════
class Logger
{
    private static ?Logger $instance = null;
    private ?LogModel $logModel = null;
    private string $logFile;

    private const LEVELS = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];

    private function __construct()
    {
        $logDir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/logs/';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        if (!is_dir($logDir)) $logDir = sys_get_temp_dir() . '/bot_logs/';
        @mkdir($logDir, 0755, true);
        $this->logFile = $logDir . date('Y-m-d') . '.log';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function getLogModel(): ?LogModel
    {
        if ($this->logModel === null) {
            try { $this->logModel = new LogModel(); } catch (Throwable) {}
        }
        return $this->logModel;
    }

    public function log(string $level, string $action, string $details = '', ?int $telegramId = null): void
    {
        if (!isset(self::LEVELS[$level])) $level = 'INFO';
        $configLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'DEBUG';
        if ((self::LEVELS[$level] ?? 1) < (self::LEVELS[$configLevel] ?? 0)) return;

        $line = sprintf("[%s] [%s] [%s]%s%s\n", date('Y-m-d H:i:s'), $level, $action,
            $telegramId ? " [UID:$telegramId]" : '', $details ? " $details" : '');

        try { file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX); } catch (Throwable) {}

        if (in_array($level, ['INFO', 'WARNING', 'ERROR'], true)) {
            try { $this->getLogModel()?->log($telegramId, $action, $details); } catch (Throwable) {}
        }
    }

    public function info(string $action, string $details = '', ?int $id = null): void    { $this->log('INFO',    $action, $details, $id); }
    public function debug(string $action, string $details = '', ?int $id = null): void   { $this->log('DEBUG',   $action, $details, $id); }
    public function warning(string $action, string $details = '', ?int $id = null): void { $this->log('WARNING', $action, $details, $id); }
    public function error(string $action, string $details = '', ?int $id = null): void   { $this->log('ERROR',   $action, $details, $id); }
}

// ══════════════════════════════════════════════════════════════
//  ANTI-FLOOD
// ══════════════════════════════════════════════════════════════
class AntiFlood
{
    private FloodModel  $floodModel;
    private TelegramApi $api;
    private Logger      $logger;
    private static array $inMemory = [];

    public function __construct()
    {
        $this->floodModel = new FloodModel();
        $this->api        = TelegramApi::getInstance();
        $this->logger     = Logger::getInstance();
    }

    public function checkMessageFlood(int $id): bool
    {
        $key = "msg_{$id}";
        $now = microtime(true);
        if (isset(self::$inMemory[$key]) && ($now - self::$inMemory[$key]) < FLOOD_LIMIT_SECONDS) {
            $this->logger->warning('FLOOD', "User {$id} flood detected");
            return false;
        }
        self::$inMemory[$key] = $now;
        return true;
    }

    public function checkOrderFlood(int $userId, int $telegramId): bool
    {
        $count = $this->floodModel->countRecent($telegramId, 'order', FLOOD_ORDERS_WINDOW);
        if ($count >= FLOOD_MAX_ORDERS) {
            $this->logger->warning('ORDER_FLOOD', "User {$telegramId} too many orders ({$count})");
            return false;
        }
        return true;
    }

    public function recordOrder(int $id): void { $this->floodModel->record($id, 'order'); }

    public function handleFloodViolation(int $chatId, string $type = 'message'): void
    {
        if ($type === 'message') {
            $this->api->sendMessage($chatId, "⚠️ <b>Juda tez!</b>\n\nIltimos, " . FLOOD_LIMIT_SECONDS . " soniya kuting.");
        } elseif ($type === 'order') {
            $this->api->sendMessage($chatId, "⚠️ <b>Buyurtmalar limiti!</b>\n\nSiz 1 soat ichida " . FLOOD_MAX_ORDERS . " ta buyurtmadan ortiq yuborolmaysiz.\n\nKeyinroq qaytib keling.");
        }
    }
}

// ══════════════════════════════════════════════════════════════
//  KEYBOARDS
// ══════════════════════════════════════════════════════════════
class UserKeyboard
{
    public static function mainMenu(): array
    {
        return [
            'keyboard' => [
                [['text' => '✅ Galochka olish'], ['text' => '📊 Holatim']],
                [['text' => 'ℹ️ Ma\'lumot']],
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => false,
        ];
    }

    public static function cancel(): array
    {
        return ['keyboard' => [[['text' => '❌ Bekor qilish']]], 'resize_keyboard' => true];
    }

    public static function remove(): array { return ['remove_keyboard' => true]; }
}

class AdminKeyboard
{
    public static function panel(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📊 Statistika', 'callback_data' => 'admin_stats'], ['text' => '💰 Moliya', 'callback_data' => 'admin_finance']],
            [['text' => '📦 Buyurtmalar', 'callback_data' => 'admin_orders'], ['text' => '👥 Foydalanuvchilar', 'callback_data' => 'admin_users']],
            [['text' => '⚙️ Sozlamalar', 'callback_data' => 'admin_settings'], ['text' => '📢 Reklama', 'callback_data' => 'admin_broadcast']],
            [['text' => '📤 Eksport', 'callback_data' => 'admin_export']],
        ]];
    }

    public static function orderActions(int $orderId): array
    {
        return ['inline_keyboard' => [[
            ['text' => '✅ Tasdiqlash', 'callback_data' => "approve_{$orderId}"],
            ['text' => '❌ Bekor qilish', 'callback_data' => "reject_{$orderId}"],
        ]]];
    }

    public static function settings(): array
    {
        return ['inline_keyboard' => [
            [['text' => '💰 Narxni o\'zgartirish', 'callback_data' => 'set_price']],
            [['text' => '💳 Kartani o\'zgartirish', 'callback_data' => 'set_card']],
            [['text' => '➕ Admin qo\'shish', 'callback_data' => 'add_admin'], ['text' => '➖ Admin o\'chirish', 'callback_data' => 'remove_admin']],
            [['text' => '🔙 Orqaga', 'callback_data' => 'admin_back']],
        ]];
    }

    public static function back(string $cb = 'admin_back'): array
    {
        return ['inline_keyboard' => [[['text' => '🔙 Orqaga', 'callback_data' => $cb]]]];
    }

    public static function ordersFilter(): array
    {
        return ['inline_keyboard' => [
            [['text' => '🕒 Kutilmoqda', 'callback_data' => 'orders_pending'], ['text' => '✅ Tasdiqlangan', 'callback_data' => 'orders_approved']],
            [['text' => '❌ Bekor qilingan', 'callback_data' => 'orders_rejected'], ['text' => '📋 Barchasi', 'callback_data' => 'orders_all']],
            [['text' => '🔙 Orqaga', 'callback_data' => 'admin_back']],
        ]];
    }

    public static function broadcastConfirm(): array
    {
        return ['inline_keyboard' => [[
            ['text' => '✅ Yuborish', 'callback_data' => 'broadcast_confirm'],
            ['text' => '❌ Bekor', 'callback_data' => 'broadcast_cancel'],
        ]]];
    }
}

// ══════════════════════════════════════════════════════════════
//  USER HANDLER
// ══════════════════════════════════════════════════════════════
class UserHandler
{
    private TelegramApi  $api;
    private UserModel    $userModel;
    private OrderModel   $orderModel;
    private SettingModel $settingModel;
    private AdminModel   $adminModel;
    private StateManager $state;
    private AntiFlood    $flood;
    private Logger       $logger;

    public function __construct()
    {
        $this->api          = TelegramApi::getInstance();
        $this->userModel    = new UserModel();
        $this->orderModel   = new OrderModel();
        $this->settingModel = new SettingModel();
        $this->adminModel   = new AdminModel();
        $this->state        = StateManager::getInstance();
        $this->flood        = new AntiFlood();
        $this->logger       = Logger::getInstance();
    }

    public function handle(array $update): void
    {
        $message    = $update['message'] ?? null;
        if (!$message) return;

        $chatId     = $message['chat']['id'];
        $telegramId = $message['from']['id'];
        $text       = $message['text'] ?? '';
        $fullname   = trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));
        $username   = $message['from']['username'] ?? null;

        if (!$this->flood->checkMessageFlood($telegramId)) {
            $this->flood->handleFloodViolation($chatId, 'message');
            return;
        }

        if ($this->userModel->isBanned($telegramId)) {
            $this->api->sendMessage($chatId, '🚫 Siz botdan bloklangansiz. Admin bilan bog\'laning.');
            return;
        }

        $user = $this->userModel->createOrUpdate($telegramId, $fullname, $username);

        if (($text === '🔧 Admin Panel' || $text === '/admin') && $this->adminModel->isAdmin($telegramId)) {
            $this->state->reset($telegramId);
            (new AdminHandler())->sendPanel($chatId, $telegramId);
            return;
        }

        if ($text === '❌ Bekor qilish') {
            $this->state->reset($telegramId);
            $this->sendMainMenu($chatId);
            return;
        }

        $currentState = $this->state->getState($telegramId);

        match ($currentState) {
            STATE_AWAITING_USERNAME => $this->handleUsernameInput($chatId, $telegramId, $text, $user),
            STATE_AWAITING_CHECK    => $this->handleCheckUpload($chatId, $telegramId, $message, $user),
            default                 => $this->handleMenu($chatId, $telegramId, $text, $user),
        };
    }

    private function handleMenu(int $chatId, int $telegramId, string $text, array $user): void
    {
        match ($text) {
            '/start'            => $this->sendWelcome($chatId, $telegramId, $user),
            '✅ Galochka olish' => $this->startOrder($chatId, $telegramId),
            '📊 Holatim'        => $this->sendMyStatus($chatId, $telegramId, $user),
            'ℹ️ Ma\'lumot'      => $this->sendInfo($chatId),
            default             => $this->sendWelcome($chatId, $telegramId, $user),
        };
    }

    private function sendWelcome(int $chatId, int $telegramId, array $user): void
    {
        $welcomeMsg = $this->settingModel->get('welcome_msg', 'Xush kelibsiz!');
        $text = "📌 <b>{$welcomeMsg}</b>\n\n✅ Rasmiy galochka xizmatiga buyurtma berishingiz mumkin.\n\nQuyidagi tugmalardan birini tanlang:";
        $this->api->sendMessage($chatId, $text, UserKeyboard::mainMenu());
        $this->logger->info('START', "User {$telegramId} started bot", $telegramId);
    }

    private function startOrder(int $chatId, int $telegramId): void
    {
        if (!$this->flood->checkOrderFlood($telegramId, $telegramId)) {
            $this->flood->handleFloodViolation($chatId, 'order');
            return;
        }
        $this->state->setState($telegramId, STATE_AWAITING_USERNAME);
        $this->api->sendMessage($chatId, "✅ <b>Galochka olish</b>\n\n1-qadam: Username yoki Nickname kiriting\n\n📝 <i>Misol: @username</i>", UserKeyboard::cancel());
        $this->logger->info('ORDER_START', "User {$telegramId} started order", $telegramId);
    }

    private function handleUsernameInput(int $chatId, int $telegramId, string $text, array $user): void
    {
        if (empty(trim($text))) {
            $this->api->sendMessage($chatId, '⚠️ Username bo\'sh bo\'lmasligi kerak. Qaytadan kiriting:');
            return;
        }

        $nickname = htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');

        if (strlen($nickname) > 128) {
            $this->api->sendMessage($chatId, '⚠️ Username juda uzun (max 128 belgi). Qaytadan kiriting:');
            return;
        }

        $price      = (float)$this->settingModel->get('price', '50000');
        $cardNumber = $this->settingModel->get('card_number', '8600 0000 0000 0000');
        $cardOwner  = $this->settingModel->get('card_owner', 'Admin');

        $orderId = $this->orderModel->create($user['id'], $nickname, $price);

        $this->state->setState($telegramId, STATE_AWAITING_CHECK, [
            'order_id' => $orderId,
            'nickname' => $nickname,
            'price'    => $price,
        ]);

        $formattedPrice = number_format($price, 0, '.', ' ');

        $text = "💳 <b>To'lov rekvizitlari:</b>\n\n"
              . "👤 <b>Buyurtma:</b> Galochka\n"
              . "📛 <b>Nickname:</b> <code>{$nickname}</code>\n\n"
              . "🏦 <b>Karta:</b> <code>{$cardNumber}</code>\n"
              . "👤 <b>Egasi:</b> {$cardOwner}\n"
              . "💰 <b>Summa:</b> <code>{$formattedPrice}</code> so'm\n\n"
              . "⚠️ To'lov qilgandan so'ng <b>chek yuboring.</b>\n"
              . "⚠️ Chek yubormasangiz buyurtma ko'rib chiqilmaydi.\n"
              . "⚠️ Chek <b>aniq ko'rinishi</b> kerak.";

        $this->api->sendMessage($chatId, $text, UserKeyboard::cancel());
        $this->logger->info('ORDER_USERNAME', "Order #{$orderId} nickname={$nickname}", $telegramId);
    }

    private function handleCheckUpload(int $chatId, int $telegramId, array $message, array $user): void
    {
        $stateData = $this->state->getData($telegramId);
        $orderId   = $stateData['order_id'] ?? null;

        if (!$orderId) {
            $this->state->reset($telegramId);
            $this->sendMainMenu($chatId);
            return;
        }

        $fileId   = null;
        $fileType = null;

        if (!empty($message['photo'])) {
            $photo    = end($message['photo']);
            $fileId   = $photo['file_id'];
            $fileType = 'photo';
        } elseif (!empty($message['document'])) {
            $fileId   = $message['document']['file_id'];
            $fileType = 'document';
        } else {
            $this->api->sendMessage($chatId, "⚠️ Faqat <b>rasm</b> (foto) yoki <b>hujjat</b> (PDF) yuboring!\n\nChek faylini yuboring:");
            return;
        }

        $this->orderModel->updateCheck($orderId, $fileId, $fileType);
        $this->flood->recordOrder($telegramId);
        $this->state->reset($telegramId);

        $this->api->sendMessage(
            $chatId,
            "✅ <b>Chek qabul qilindi!</b>\n\n📋 Buyurtma #<code>{$orderId}</code> admin tekshiruviga yuborildi.\n\n⏳ Tez orada javob olasiz.",
            UserKeyboard::mainMenu()
        );

        $this->notifyAdmins($orderId, $fileId, $fileType, $stateData, $telegramId, $message['from']);
        $this->logger->info('ORDER_CHECK', "Order #{$orderId} check uploaded type={$fileType}", $telegramId);
    }

    private function notifyAdmins(int $orderId, string $fileId, string $fileType, array $stateData, int $telegramId, array $fromUser): void
    {
        $adminIds    = $this->adminModel->getAllTelegramIds();
        $nickname    = htmlspecialchars($stateData['nickname'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $price       = number_format((float)($stateData['price'] ?? 0), 0, '.', ' ');
        $username    = $fromUser['username'] ?? null;
        $fullname    = trim(($fromUser['first_name'] ?? '') . ' ' . ($fromUser['last_name'] ?? ''));
        $userMention = $username ? "@{$username}" : $fullname;
        $dateStr     = date('d.m.Y H:i:s');

        $caption = "🆕 <b>Yangi buyurtma</b>\n\n"
                 . "👤 <b>User:</b>\n"
                 . "   🆔 ID: <code>{$telegramId}</code>\n"
                 . "   📛 Username: {$userMention}\n"
                 . "   👤 Ism: {$fullname}\n\n"
                 . "🎯 <b>Buyurtma:</b> Galochka\n"
                 . "📝 <b>Nickname:</b> <code>{$nickname}</code>\n"
                 . "💰 <b>Narx:</b> {$price} so'm\n"
                 . "📅 <b>Sana:</b> {$dateStr}\n"
                 . "🔖 <b>Buyurtma #:</b> {$orderId}";

        $keyboard = AdminKeyboard::orderActions($orderId);

        foreach ($adminIds as $adminId) {
            try {
                if ($fileType === 'photo') {
                    $this->api->sendPhoto((int)$adminId, $fileId, $caption, $keyboard);
                } else {
                    $this->api->sendDocument((int)$adminId, $fileId, $caption, $keyboard);
                }
            } catch (Throwable $e) {
                $this->logger->error('NOTIFY_ADMIN', "Failed to notify admin {$adminId}: " . $e->getMessage());
            }
        }
    }

    private function sendMyStatus(int $chatId, int $telegramId, array $user): void
    {
        $orders   = $this->orderModel->getByUserId($user['id'], 10);
        $pending  = 0; $approved = 0; $rejected = 0;

        foreach ($orders as $order) {
            match ($order['status']) {
                'pending'  => $pending++,
                'approved' => $approved++,
                'rejected' => $rejected++,
                default    => null,
            };
        }

        $total = count($orders);
        $text  = "📊 <b>Mening holatim</b>\n\n"
               . "📦 <b>Jami buyurtmalar:</b> {$total}\n"
               . "🕒 <b>Kutilmoqda:</b> {$pending}\n"
               . "✅ <b>Tasdiqlangan:</b> {$approved}\n"
               . "❌ <b>Bekor qilingan:</b> {$rejected}\n";

        if (!empty($orders)) {
            $text .= "\n<b>So'nggi buyurtmalar:</b>\n";
            foreach (array_slice($orders, 0, 5) as $order) {
                $icon = match ($order['status']) { 'approved' => '✅', 'rejected' => '❌', default => '🕒' };
                $date = date('d.m.Y', strtotime($order['created_at']));
                $text .= "\n{$icon} #{$order['id']} | {$order['nickname']} | {$date}";
            }
        }

        $this->api->sendMessage($chatId, $text, UserKeyboard::mainMenu());
    }

    private function sendInfo(int $chatId): void
    {
        $price = number_format((float)$this->settingModel->get('price', '50000'), 0, '.', ' ');
        $text  = "ℹ️ <b>Bot haqida ma'lumot</b>\n\n"
               . "🤖 <b>Verification Service Bot</b>\n\n"
               . "✅ Bu bot orqali rasmiy galochka (verificatsiya) xizmatini buyurtma qilishingiz mumkin.\n\n"
               . "💰 <b>Narx:</b> {$price} so'm\n\n"
               . "📋 <b>Jarayon:</b>\n"
               . "1. «✅ Galochka olish» tugmasini bosing\n"
               . "2. Username kiriting\n"
               . "3. To'lov qiling\n"
               . "4. Chek yuboring\n"
               . "5. Admin tasdiqlashini kuting\n\n"
               . "⏳ Tasdiqlanish vaqti: <b>1-24 soat</b>\n\n"
               . "❓ Savollar uchun admin bilan bog'laning.";
        $this->api->sendMessage($chatId, $text, UserKeyboard::mainMenu());
    }

    private function sendMainMenu(int $chatId): void
    {
        $this->api->sendMessage($chatId, "📌 Asosiy menyu:", UserKeyboard::mainMenu());
    }
}

// ══════════════════════════════════════════════════════════════
//  ADMIN HANDLER
// ══════════════════════════════════════════════════════════════
class AdminHandler
{
    private TelegramApi  $api;
    private UserModel    $userModel;
    private OrderModel   $orderModel;
    private AdminModel   $adminModel;
    private SettingModel $settingModel;
    private StateManager $state;
    private Logger       $logger;

    public function __construct()
    {
        $this->api          = TelegramApi::getInstance();
        $this->userModel    = new UserModel();
        $this->orderModel   = new OrderModel();
        $this->adminModel   = new AdminModel();
        $this->settingModel = new SettingModel();
        $this->state        = StateManager::getInstance();
        $this->logger       = Logger::getInstance();
    }

    public function handleMessage(array $update): void
    {
        $message    = $update['message'];
        $chatId     = $message['chat']['id'];
        $telegramId = $message['from']['id'];
        $text       = $message['text'] ?? '';

        if (!$this->adminModel->isAdmin($telegramId)) return;

        match ($this->state->getState($telegramId)) {
            STATE_ADMIN_SET_PRICE => $this->handleSetPrice($chatId, $telegramId, $text),
            STATE_ADMIN_SET_CARD  => $this->handleSetCard($chatId, $telegramId, $text),
            STATE_ADMIN_ADD_ADMIN => $this->handleAddAdmin($chatId, $telegramId, $text),
            STATE_ADMIN_BROADCAST => $this->handleBroadcastContent($chatId, $telegramId, $message),
            default               => $this->handleAdminMenu($chatId, $telegramId, $text),
        };
    }

    public function handleCallback(array $update): void
    {
        $cb         = $update['callback_query'];
        $chatId     = $cb['message']['chat']['id'];
        $messageId  = $cb['message']['message_id'];
        $telegramId = $cb['from']['id'];
        $data       = $cb['data'];
        $cbId       = $cb['id'];

        if (!$this->adminModel->isAdmin($telegramId)) {
            $this->api->answerCallbackQuery($cbId, '⛔ Ruxsat yo\'q', true);
            return;
        }

        $this->api->answerCallbackQuery($cbId);

        if (str_starts_with($data, 'approve_')) {
            $this->approveOrder($chatId, $messageId, $telegramId, (int)substr($data, 8));
        } elseif (str_starts_with($data, 'reject_')) {
            $this->rejectOrder($chatId, $messageId, $telegramId, (int)substr($data, 7));
        } elseif (str_starts_with($data, 'orders_')) {
            $this->showOrdersList($chatId, $messageId, substr($data, 7));
        } elseif (str_starts_with($data, 'rm_admin_')) {
            $this->removeAdmin($chatId, $messageId, $telegramId, (int)substr($data, 9));
        } else {
            match ($data) {
                'admin_stats'       => $this->showStats($chatId, $messageId),
                'admin_finance'     => $this->showFinance($chatId, $messageId),
                'admin_orders'      => $this->showOrdersMenu($chatId, $messageId),
                'admin_users'       => $this->showUsers($chatId, $messageId),
                'admin_settings'    => $this->showSettings($chatId, $messageId),
                'admin_broadcast'   => $this->startBroadcast($chatId, $telegramId),
                'admin_export'      => $this->exportData($chatId),
                'admin_back'        => $this->sendPanelEdit($chatId, $messageId),
                'set_price'         => $this->askSetPrice($chatId, $telegramId),
                'set_card'          => $this->askSetCard($chatId, $telegramId),
                'add_admin'         => $this->askAddAdmin($chatId, $telegramId),
                'remove_admin'      => $this->showRemoveAdmin($chatId, $messageId),
                'broadcast_confirm' => $this->executeBroadcast($chatId, $telegramId),
                'broadcast_cancel'  => $this->cancelBroadcast($chatId, $telegramId),
                default             => null,
            };
        }
    }

    private function handleAdminMenu(int $chatId, int $telegramId, string $text): void
    {
        if ($text === '/admin' || $text === '🔧 Admin Panel') $this->sendPanel($chatId, $telegramId);
    }

    public function sendPanel(int $chatId, int $telegramId): void
    {
        $this->api->sendMessage($chatId, "🔧 <b>Admin Panel</b>\n\nXush kelibsiz, Admin!", AdminKeyboard::panel());
    }

    private function sendPanelEdit(int $chatId, int $messageId): void
    {
        $this->api->editMessageText($chatId, $messageId, "🔧 <b>Admin Panel</b>\n\nXush kelibsiz, Admin!", AdminKeyboard::panel());
    }

    private function approveOrder(int $chatId, int $messageId, int $adminId, int $orderId): void
    {
        $order = $this->orderModel->findById($orderId);
        if (!$order) { $this->api->sendMessage($chatId, '❌ Buyurtma topilmadi.'); return; }
        if ($order['status'] !== 'pending') { $this->api->sendMessage($chatId, "⚠️ Buyurtma allaqachon {$order['status']} holatida."); return; }

        $this->orderModel->approve($orderId, $adminId);
        $this->api->editMessageReplyMarkup($chatId, $messageId, []);
        $this->api->sendMessage(
            (int)$order['telegram_id'],
            "✅ <b>To'lovingiz tasdiqlandi!</b>\n\n📋 Buyurtma #<code>{$orderId}</code> qabul qilindi.\n🎯 Nickname: <code>{$order['nickname']}</code>\n\n⏳ Tez orada yakunlanadi. Rahmat! 🙏"
        );
        $this->api->sendMessage($chatId, "✅ Buyurtma #{$orderId} tasdiqlandi va foydalanuvchiga xabar ketdi.");
        $this->logger->info('ORDER_APPROVED', "Order #{$orderId} approved by admin {$adminId}");
    }

    private function rejectOrder(int $chatId, int $messageId, int $adminId, int $orderId): void
    {
        $order = $this->orderModel->findById($orderId);
        if (!$order) { $this->api->sendMessage($chatId, '❌ Buyurtma topilmadi.'); return; }
        if ($order['status'] !== 'pending') { $this->api->sendMessage($chatId, "⚠️ Buyurtma allaqachon {$order['status']} holatida."); return; }

        $this->orderModel->reject($orderId, $adminId, 'Admin tomonidan bekor qilindi');
        $this->api->editMessageReplyMarkup($chatId, $messageId, []);
        $this->api->sendMessage(
            (int)$order['telegram_id'],
            "❌ <b>To'lov tasdiqlanmadi.</b>\n\n📋 Buyurtma #<code>{$orderId}</code>\n\nIltimos admin bilan bog'laning."
        );
        $this->api->sendMessage($chatId, "❌ Buyurtma #{$orderId} bekor qilindi va foydalanuvchiga xabar ketdi.");
        $this->logger->info('ORDER_REJECTED', "Order #{$orderId} rejected by admin {$adminId}");
    }

    private function showStats(int $chatId, int $messageId): void
    {
        $text = "📊 <b>Statistika</b>\n\n"
              . "👥 <b>Foydalanuvchilar:</b>\n"
              . "   Jami: <b>{$this->userModel->countAll()}</b>\n"
              . "   Bugun: <b>{$this->userModel->countToday()}</b>\n"
              . "   Shu oy: <b>{$this->userModel->countThisMonth()}</b>\n\n"
              . "📦 <b>Buyurtmalar:</b>\n"
              . "   Jami: <b>{$this->orderModel->countAll()}</b>\n"
              . "   ✅ Tasdiqlangan: <b>{$this->orderModel->countByStatus('approved')}</b>\n"
              . "   ❌ Bekor qilingan: <b>{$this->orderModel->countByStatus('rejected')}</b>\n"
              . "   🕒 Kutilmoqda: <b>{$this->orderModel->countByStatus('pending')}</b>\n"
              . "   📈 Bugun: <b>{$this->orderModel->countToday()}</b>\n"
              . "   📈 Shu oy: <b>{$this->orderModel->countThisMonth()}</b>";
        $this->api->editMessageText($chatId, $messageId, $text, AdminKeyboard::back());
    }

    private function showFinance(int $chatId, int $messageId): void
    {
        $fmt  = fn(float $v) => number_format($v, 0, '.', ' ');
        $text = "💰 <b>Moliya tizimi</b>\n\n"
              . "✅ <b>Tasdiqlangan buyurtmalar:</b> {$this->orderModel->countByStatus('approved')}\n"
              . "💵 <b>Hozirgi narx:</b> " . $fmt((float)$this->settingModel->get('price', '50000')) . " so'm\n\n"
              . "📊 <b>Tushum hisobi:</b>\n"
              . "   💰 Bugungi: <b>" . $fmt($this->orderModel->sumApprovedToday()) . "</b> so'm\n"
              . "   💰 Haftalik: <b>" . $fmt($this->orderModel->sumApprovedThisWeek()) . "</b> so'm\n"
              . "   💰 Oylik: <b>" . $fmt($this->orderModel->sumApprovedThisMonth()) . "</b> so'm\n"
              . "   💰 Umumiy: <b>" . $fmt($this->orderModel->sumApproved()) . "</b> so'm";
        $this->api->editMessageText($chatId, $messageId, $text, AdminKeyboard::back());
    }

    private function showOrdersMenu(int $chatId, int $messageId): void
    {
        $this->api->editMessageText($chatId, $messageId, "📦 <b>Buyurtmalar</b>\n\nQaysi buyurtmalarni ko'rmoqchisiz?", AdminKeyboard::ordersFilter());
    }

    private function showOrdersList(int $chatId, int $messageId, string $filter): void
    {
        $status = match ($filter) { 'pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected', default => null };
        $orders = $status ? $this->orderModel->getByStatus($status, 20) : $this->orderModel->getAll(20);
        $label  = match ($filter) { 'pending' => '🕒 Kutilmoqda', 'approved' => '✅ Tasdiqlangan', 'rejected' => '❌ Bekor qilingan', default => '📋 Barchasi' };

        if (empty($orders)) {
            $this->api->editMessageText($chatId, $messageId, "📦 <b>{$label}</b>\n\nBuyurtmalar yo'q.", AdminKeyboard::back('admin_orders'));
            return;
        }

        $text = "📦 <b>{$label}</b> (" . count($orders) . " ta)\n\n";
        foreach ($orders as $order) {
            $icon  = match ($order['status']) { 'approved' => '✅', 'rejected' => '❌', default => '🕒' };
            $date  = date('d.m.Y H:i', strtotime($order['created_at']));
            $uname = $order['username'] ? "@{$order['username']}" : $order['fullname'];
            $price = number_format((float)$order['price'], 0, '.', ' ');
            $text .= "{$icon} <b>#{$order['id']}</b> | {$order['nickname']} | {$uname} | {$price} | {$date}\n";
        }
        $this->api->editMessageText($chatId, $messageId, $text, AdminKeyboard::back('admin_orders'));
    }

    private function showUsers(int $chatId, int $messageId): void
    {
        $users = $this->userModel->getAll(20);
        $total = $this->userModel->countAll();
        $text  = "👥 <b>Foydalanuvchilar</b> (jami: {$total})\n\nSo'nggi 20 ta:\n\n";
        foreach ($users as $u) {
            $uname = $u['username'] ? "@{$u['username']}" : $u['fullname'];
            $date  = date('d.m.Y', strtotime($u['created_at']));
            $text .= "• <code>{$u['telegram_id']}</code> | {$uname} | {$date}\n";
        }
        $this->api->editMessageText($chatId, $messageId, $text, AdminKeyboard::back());
    }

    private function showSettings(int $chatId, int $messageId): void
    {
        $price  = number_format((float)$this->settingModel->get('price'), 0, '.', ' ');
        $card   = $this->settingModel->get('card_number');
        $owner  = $this->settingModel->get('card_owner');
        $admins = count($this->adminModel->getAll());
        $text   = "⚙️ <b>Sozlamalar</b>\n\n"
                . "💰 <b>Narx:</b> {$price} so'm\n"
                . "💳 <b>Karta:</b> <code>{$card}</code>\n"
                . "👤 <b>Egasi:</b> {$owner}\n"
                . "👮 <b>Adminlar soni:</b> {$admins}\n";
        $this->api->editMessageText($chatId, $messageId, $text, AdminKeyboard::settings());
    }

    private function askSetPrice(int $chatId, int $telegramId): void
    {
        $current = number_format((float)$this->settingModel->get('price'), 0, '.', ' ');
        $this->state->setState($telegramId, STATE_ADMIN_SET_PRICE);
        $this->api->sendMessage($chatId, "💰 <b>Yangi narxni kiriting (so'm):</b>\n\nHozirgi narx: <b>{$current}</b> so'm");
    }

    private function handleSetPrice(int $chatId, int $telegramId, string $text): void
    {
        $price = preg_replace('/\D/', '', $text);
        if (!$price || (int)$price < 1000) {
            $this->api->sendMessage($chatId, '⚠️ Noto\'g\'ri narx. Raqam kiriting (min: 1000 so\'m):');
            return;
        }
        $this->settingModel->set('price', $price);
        $this->state->reset($telegramId);
        $this->api->sendMessage($chatId, "✅ Narx yangilandi: <b>" . number_format((int)$price, 0, '.', ' ') . "</b> so'm", AdminKeyboard::back());
        $this->logger->info('SET_PRICE', "Price set to {$price} by admin {$telegramId}", $telegramId);
    }

    private function askSetCard(int $chatId, int $telegramId): void
    {
        $current = $this->settingModel->get('card_number');
        $this->state->setState($telegramId, STATE_ADMIN_SET_CARD);
        $this->api->sendMessage($chatId, "💳 <b>Yangi karta raqamini kiriting:</b>\n\nHozirgi: <code>{$current}</code>\n\nMisol: 8600 1234 5678 9012");
    }

    private function handleSetCard(int $chatId, int $telegramId, string $text): void
    {
        $card = preg_replace('/[^0-9 ]/', '', trim($text));
        if (strlen(preg_replace('/\s/', '', $card)) < 16) {
            $this->api->sendMessage($chatId, '⚠️ Karta raqami noto\'g\'ri. Qaytadan kiriting:');
            return;
        }
        $this->settingModel->set('card_number', $card);
        $this->state->reset($telegramId);
        $this->api->sendMessage($chatId, "✅ Karta yangilandi: <code>{$card}</code>", AdminKeyboard::back());
        $this->logger->info('SET_CARD', "Card updated by admin {$telegramId}", $telegramId);
    }

    private function askAddAdmin(int $chatId, int $telegramId): void
    {
        $this->state->setState($telegramId, STATE_ADMIN_ADD_ADMIN);
        $this->api->sendMessage($chatId, "➕ <b>Yangi admin qo'shish</b>\n\nAdmin Telegram ID sini kiriting:\n\n<i>Misol: 123456789</i>");
    }

    private function handleAddAdmin(int $chatId, int $telegramId, string $text): void
    {
        $newId = (int)trim($text);
        if ($newId < 10000) { $this->api->sendMessage($chatId, '⚠️ Noto\'g\'ri ID. Qaytadan kiriting:'); return; }
        if ($this->adminModel->isAdmin($newId)) {
            $this->state->reset($telegramId);
            $this->api->sendMessage($chatId, '⚠️ Bu foydalanuvchi allaqachon admin!');
            return;
        }
        $this->adminModel->add($newId, 'Admin', null, $telegramId);
        $this->state->reset($telegramId);
        $this->api->sendMessage($chatId, "✅ Admin qo'shildi: <code>{$newId}</code>");
        $this->logger->info('ADD_ADMIN', "Admin {$newId} added by {$telegramId}", $telegramId);
    }

    private function showRemoveAdmin(int $chatId, int $messageId): void
    {
        $admins = $this->adminModel->getAll();
        if (empty($admins)) {
            $this->api->editMessageText($chatId, $messageId, "👮 Adminlar ro'yxati bo'sh.", AdminKeyboard::back('admin_settings'));
            return;
        }
        $keyboard = [];
        foreach ($admins as $admin) {
            $name = $admin['username'] ? "@{$admin['username']}" : ($admin['fullname'] ?? $admin['telegram_id']);
            $keyboard[] = [['text' => "❌ {$name}", 'callback_data' => "rm_admin_{$admin['telegram_id']}"]];
        }
        $keyboard[] = [['text' => '🔙 Orqaga', 'callback_data' => 'admin_settings']];
        $this->api->editMessageText($chatId, $messageId, "👮 <b>Admin o'chirish</b>\n\nO'chirmoqchi bo'lgan adminni tanlang:", ['inline_keyboard' => $keyboard]);
    }

    private function removeAdmin(int $chatId, int $messageId, int $telegramId, int $targetId): void
    {
        if (in_array($targetId, SUPER_ADMINS, true)) {
            $this->api->sendMessage($chatId, '⛔ Super adminni o\'chirish mumkin emas!');
            return;
        }
        $this->adminModel->remove($targetId);
        $this->api->sendMessage($chatId, "✅ Admin <code>{$targetId}</code> o'chirildi.");
        $this->logger->info('REMOVE_ADMIN', "Admin {$targetId} removed by {$telegramId}", $telegramId);
    }

    private function startBroadcast(int $chatId, int $telegramId): void
    {
        $this->state->setState($telegramId, STATE_ADMIN_BROADCAST);
        $this->api->sendMessage($chatId, "📢 <b>Reklama yuborish</b>\n\nMatn, rasm yoki video yuboring.\n\nBarcha foydalanuvchilarga tarqatiladi.", UserKeyboard::cancel());
    }

    private function handleBroadcastContent(int $chatId, int $telegramId, array $message): void
    {
        $this->state->updateData($telegramId, [
            'broadcast_from_chat'   => $message['chat']['id'],
            'broadcast_message_id'  => $message['message_id'],
        ]);
        $this->state->setState($telegramId, STATE_ADMIN_BROADCAST);
        $userCount = $this->userModel->countAll();
        $this->api->sendMessage($chatId, "📢 <b>Reklama tasdiqlash</b>\n\n👥 Yuboriladi: <b>{$userCount}</b> ta foydalanuvchiga\n\nDavom etasizmi?", AdminKeyboard::broadcastConfirm());
    }

    private function executeBroadcast(int $chatId, int $telegramId): void
    {
        $data       = $this->state->getData($telegramId);
        $fromChatId = $data['broadcast_from_chat'] ?? null;
        $fromMsgId  = $data['broadcast_message_id'] ?? null;

        if (!$fromChatId || !$fromMsgId) {
            $this->api->sendMessage($chatId, '❌ Xabar topilmadi. Qaytadan boshlang.');
            $this->state->reset($telegramId);
            return;
        }

        $this->state->reset($telegramId);
        $users = $this->userModel->getAll(10000);
        $total = count($users);
        $sent  = 0; $failed = 0; $batch = 0;

        $progressMsg   = $this->api->sendMessage($chatId, "📤 Yuborilmoqda: 0/{$total}");
        $progressMsgId = $progressMsg['result']['message_id'] ?? null;

        foreach ($users as $user) {
            try {
                $result = $this->api->copyMessage((int)$user['telegram_id'], $fromChatId, (int)$fromMsgId);
                ($result['ok'] ?? false) ? $sent++ : $failed++;
            } catch (Throwable) { $failed++; }

            $batch++;
            if ($progressMsgId && $batch % 30 === 0) {
                $this->api->editMessageText($chatId, $progressMsgId, "📤 Yuborilmoqda: {$sent}/{$total} ✅ | {$failed} ❌");
                usleep(BROADCAST_SLEEP_MS);
            }
            usleep(35000);
        }

        if ($progressMsgId) {
            $this->api->editMessageText($chatId, $progressMsgId, "✅ <b>Reklama tugadi!</b>\n\n✅ Yuborildi: {$sent}\n❌ Xato: {$failed}\n📊 Jami: {$total}");
        }
        $this->logger->info('BROADCAST', "Sent={$sent} Failed={$failed} Total={$total}", $telegramId);
    }

    private function cancelBroadcast(int $chatId, int $telegramId): void
    {
        $this->state->reset($telegramId);
        $this->api->sendMessage($chatId, '❌ Reklama bekor qilindi.', UserKeyboard::mainMenu());
    }

    private function exportData(int $chatId): void
    {
        $users  = $this->userModel->getAll(10000);
        $orders = $this->orderModel->getAll(10000);

        $usersCsv = "ID,Telegram ID,Username,Fullname,Language,Banned,Created At\n";
        foreach ($users as $u) {
            $usersCsv .= implode(',', [$u['id'], $u['telegram_id'], '"' . ($u['username'] ?? '') . '"', '"' . ($u['fullname'] ?? '') . '"', $u['language'] ?? 'uz', $u['is_banned'] ?? 0, $u['created_at'] ?? '']) . "\n";
        }

        $ordersCsv = "ID,User ID,Nickname,Price,Status,Created At,Approved At\n";
        foreach ($orders as $o) {
            $ordersCsv .= implode(',', [$o['id'], $o['user_id'], '"' . ($o['nickname'] ?? '') . '"', $o['price'] ?? 0, $o['status'] ?? '', $o['created_at'] ?? '', $o['approved_at'] ?? '']) . "\n";
        }

        $tmpUsers  = sys_get_temp_dir() . '/users_'  . date('Ymd_His') . '.csv';
        $tmpOrders = sys_get_temp_dir() . '/orders_' . date('Ymd_His') . '.csv';
        file_put_contents($tmpUsers,  $usersCsv);
        file_put_contents($tmpOrders, $ordersCsv);

        $this->api->call('sendDocument', ['chat_id' => $chatId, 'document' => new CURLFile($tmpUsers,  'text/csv', 'users_'  . date('Ymd') . '.csv'), 'caption' => '👥 Foydalanuvchilar bazasi'], true);
        $this->api->call('sendDocument', ['chat_id' => $chatId, 'document' => new CURLFile($tmpOrders, 'text/csv', 'orders_' . date('Ymd') . '.csv'), 'caption' => '📦 Buyurtmalar bazasi'], true);

        @unlink($tmpUsers);
        @unlink($tmpOrders);
    }
}

// ══════════════════════════════════════════════════════════════
//  WEBHOOK ENTRY POINT
// ══════════════════════════════════════════════════════════════

// Security check
if (!empty(WEBHOOK_SECRET)) {
    $incomingSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($incomingSecret !== WEBHOOK_SECRET) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// Parse input
$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!is_array($update) || (!isset($update['message']) && !isset($update['callback_query']))) {
    http_response_code(200);
    exit('OK');
}

$logger = Logger::getInstance();

try {
    $adminModel = new AdminModel();

    if (isset($update['callback_query'])) {
        $telegramId = $update['callback_query']['from']['id'];
        if ($adminModel->isAdmin($telegramId)) {
            (new AdminHandler())->handleCallback($update);
        } else {
            TelegramApi::getInstance()->answerCallbackQuery($update['callback_query']['id'], '⛔ Ruxsat yo\'q', true);
        }
    } else {
        $telegramId   = $update['message']['from']['id'];
        $stateManager = StateManager::getInstance();
        $currentState = $stateManager->getState($telegramId);

        $adminStates = [STATE_ADMIN_SET_PRICE, STATE_ADMIN_SET_CARD, STATE_ADMIN_ADD_ADMIN, STATE_ADMIN_BROADCAST];

        if ($adminModel->isAdmin($telegramId) && in_array($currentState, $adminStates, true)) {
            (new AdminHandler())->handleMessage($update);
        } else {
            (new UserHandler())->handle($update);
        }
    }

} catch (Throwable $e) {
    $logger->error('FATAL', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    try {
        $api = TelegramApi::getInstance();
        foreach ((array)SUPER_ADMINS as $adminId) {
            $api->sendMessage($adminId, "⚠️ <b>Bot xatosi!</b>\n\n<code>" . htmlspecialchars($e->getMessage()) . "</code>");
        }
    } catch (Throwable) {}
}

http_response_code(200);
exit('OK');
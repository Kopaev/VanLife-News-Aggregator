<?php
/**
 * Simple migration runner for VanLife News Aggregator
 * Usage:
 *  php scripts/migrate.php         # apply pending migrations
 *  php scripts/migrate.php --seed  # apply migrations and load seed data
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root . '/config/config.php';

$options = getopt('', ['seed']);
$withSeeds = array_key_exists('seed', $options);

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['database']['host'],
    $config['database']['port'],
    $config['database']['name'],
    $config['database']['charset']
);

try {
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] Could not connect to database: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL UNIQUE,
            `checksum` CHAR(32) NOT NULL,
            `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
    );
}

function readAppliedMigrations(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT filename, checksum FROM migrations');
    $applied = [];
    foreach ($stmt->fetchAll() as $row) {
        $applied[$row['filename']] = $row['checksum'];
    }
    return $applied;
}

function applySqlFile(PDO $pdo, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("Cannot read SQL file: {$filePath}");
    }

    $pdo->exec($sql);
}

function registerMigration(PDO $pdo, string $filename, string $checksum): void
{
    $stmt = $pdo->prepare('INSERT INTO migrations (filename, checksum) VALUES (:filename, :checksum)');
    $stmt->execute([
        'filename' => $filename,
        'checksum' => $checksum,
    ]);
}

function applyMigrations(PDO $pdo, string $migrationsDir): void
{
    ensureMigrationsTable($pdo);
    $applied = readAppliedMigrations($pdo);

    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        $checksum = md5_file($file) ?: '';

        if (isset($applied[$filename])) {
            if ($applied[$filename] !== $checksum) {
                throw new RuntimeException("Checksum mismatch for migration {$filename}. Please investigate.");
            }
            echo "[SKIP] {$filename} already applied" . PHP_EOL;
            continue;
        }

        echo "[RUN ] Applying {$filename}..." . PHP_EOL;
        applySqlFile($pdo, $file);
        registerMigration($pdo, $filename, $checksum);
        echo "[DONE] {$filename}" . PHP_EOL;
    }

    if (empty($files)) {
        echo "[INFO] No migration files found." . PHP_EOL;
    }
}

function loadSeeds(PDO $pdo, string $seedsDir): void
{
    $files = glob($seedsDir . '/*.sql');
    sort($files);

    if (empty($files)) {
        echo "[INFO] No seed files found." . PHP_EOL;
        return;
    }

    echo "[INFO] Loading seed data..." . PHP_EOL;
    foreach ($files as $file) {
        $filename = basename($file);
        echo "[SEED] {$filename}" . PHP_EOL;
        applySqlFile($pdo, $file);
    }
    echo "[INFO] Seed data loaded." . PHP_EOL;
}

applyMigrations($pdo, $root . '/sql/migrations');

if ($withSeeds) {
    loadSeeds($pdo, $root . '/sql/seeds');
}

echo "[OK] Database is up to date." . PHP_EOL;

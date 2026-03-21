<?php
declare(strict_types=1);
/**
 * NexaMigrate - NexaUI Migration Runner
 * Dipanggil oleh migrate.bat
 */
$basePath = dirname(__DIR__, 2);

// Load .env
$envFile = $basePath . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
    }
}

$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_PORT'] = $_ENV['DB_PORT'] ?? '3306';
$_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE'] ?? 'nexa_db';
$_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'root';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';
$_ENV['DB_CHARSET'] = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

require_once $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\System\Database\NexaMigrator;

$migrator = new NexaMigrator();
$command = $argv[1] ?? 'run';

try {
    switch ($command) {
        case 'run':
        case 'migrate':
        case '1':
            echo "  Running migrations...\n";
            $executed = $migrator->run();
            if (empty($executed)) {
                echo "  [OK] Database is up to date.\n";
            } else {
                foreach ($executed as $m) {
                    echo "  [OK] {$m}\n";
                }
                echo "  Migrated " . count($executed) . " migration(s).\n";
            }
            break;

        case 'rollback':
        case '2':
            echo "  Rolling back last batch...\n";
            $rolled = $migrator->rollback();
            if (empty($rolled)) {
                echo "  [OK] Nothing to rollback.\n";
            } else {
                foreach ($rolled as $m) {
                    echo "  [OK] Rolled back: {$m}\n";
                }
                echo "  Rolled back " . count($rolled) . " migration(s).\n";
            }
            break;

        case 'status':
        case '3':
            echo "  Migration status:\n";
            $status = $migrator->status();
            if (empty($status)) {
                echo "  No migrations in database/migrations/\n";
            } else {
                foreach ($status as $s) {
                    $icon = $s['ran'] ? '[OK]' : '[--]';
                    $state = $s['ran'] ? 'Ran' : 'Pending';
                    echo "    {$icon} {$s['migration']} ({$state})\n";
                }
            }
            break;

        case 'create':
        case '4':
            $name = $argv[2] ?? null;
            if (!$name) {
                echo "  [ERROR] Usage: create MigrationName\n";
                echo "  Contoh: create CreateUsersTable\n";
                exit(1);
            }
            $path = $migrator->create($name);
            echo "  [OK] Created: " . basename($path) . "\n";
            echo "       Edit: {$path}\n";
            break;

        default:
            echo "  [ERROR] Perintah tidak dikenal: {$command}\n";
            echo "  Gunakan: run, rollback, status, create\n";
            exit(1);
    }
} catch (Throwable $e) {
    echo "  [ERROR] " . $e->getMessage() . "\n";
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo $e->getTraceAsString() . "\n";
    }
    exit(1);
}

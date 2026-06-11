<?php
/**
 * One-off local helper: apply 0007_return_reports_foundation.sql safely.
 * Usage: php tools/apply-0007-return-reports.php [--verify-only]
 * NOT for production. Manual owner approval only.
 */

declare(strict_types=1);

$verifyOnly = in_array('--verify-only', $argv ?? [], true);
$config = require __DIR__ . '/../config/database.php';
$dbName = (string) ($config['database'] ?? '');
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'] ?? '127.0.0.1',
    $config['port'] ?? 3306,
    $dbName,
    $config['charset'] ?? 'utf8mb4'
);

$pdo = new PDO($dsn, (string) ($config['username'] ?? 'root'), (string) ($config['password'] ?? ''), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function tableExists(PDO $pdo, string $schema, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name = :column'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $schema, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = :schema AND table_name = :table AND index_name = :index'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'index' => $index]);

    return (int) $stmt->fetchColumn() > 0;
}

function verify(PDO $pdo, string $dbName): array
{
    return [
        'ibs_return_reports' => tableExists($pdo, $dbName, 'ibs_return_reports'),
        'ibs_return_report_items' => tableExists($pdo, $dbName, 'ibs_return_report_items'),
        'ibs_return_receives.order_id' => columnExists($pdo, $dbName, 'ibs_return_receives', 'order_id'),
        'ibs_return_receives.return_reason' => columnExists($pdo, $dbName, 'ibs_return_receives', 'return_reason'),
    ];
}

function printVerify(array $results): void
{
    foreach ($results as $key => $ok) {
        echo ($ok ? '[OK]' : '[MISSING]') . ' ' . $key . PHP_EOL;
    }
}

$before = verify($pdo, $dbName);
echo "=== Before ===" . PHP_EOL;
printVerify($before);

if ($verifyOnly) {
    exit(array_reduce($before, static fn (bool $carry, bool $v): bool => $carry && $v, true) ? 0 : 1);
}

$allReady = array_reduce($before, static fn (bool $carry, bool $v): bool => $carry && $v, true);
if ($allReady) {
    echo PHP_EOL . 'All 0007 objects already present — no SQL executed.' . PHP_EOL;
    exit(0);
}

if (!tableExists($pdo, $dbName, 'ibs_return_receives')) {
    fwrite(STDERR, "ERROR: ibs_return_receives missing — apply 0006_dispatch_returns_payables.sql first.\n");
    exit(1);
}

$migrationPath = __DIR__ . '/../database/migrations/0007_return_reports_foundation.sql';
if (!is_readable($migrationPath)) {
    fwrite(STDERR, "ERROR: migration file not found.\n");
    exit(1);
}

$sql = file_get_contents($migrationPath);
if ($sql === false) {
    fwrite(STDERR, "ERROR: could not read migration file.\n");
    exit(1);
}

// Strip header comments; run CREATE blocks from file.
$statements = [];
$buffer = '';
foreach (preg_split('/\R/', $sql) as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) {
        continue;
    }
    $buffer .= $line . "\n";
    if (str_ends_with(rtrim($line), ';')) {
        $statements[] = trim($buffer);
        $buffer = '';
    }
}

echo PHP_EOL . '=== Applying 0007 (idempotent where possible) ===' . PHP_EOL;

foreach ($statements as $statement) {
    if (stripos($statement, 'ALTER TABLE ibs_return_receives ADD COLUMN order_id') === 0) {
        if (columnExists($pdo, $dbName, 'ibs_return_receives', 'order_id')) {
            echo 'SKIP order_id column (exists)' . PHP_EOL;
            continue;
        }
    }
    if (stripos($statement, 'ALTER TABLE ibs_return_receives ADD COLUMN return_reason') === 0) {
        if (columnExists($pdo, $dbName, 'ibs_return_receives', 'return_reason')) {
            echo 'SKIP return_reason column (exists)' . PHP_EOL;
            continue;
        }
    }
    if (stripos($statement, 'ALTER TABLE ibs_return_receives ADD KEY idx_return_receive_order') === 0) {
        if (indexExists($pdo, $dbName, 'ibs_return_receives', 'idx_return_receive_order')) {
            echo 'SKIP idx_return_receive_order (exists)' . PHP_EOL;
            continue;
        }
    }

    $preview = preg_replace('/\s+/', ' ', substr($statement, 0, 72));
    echo 'RUN ' . $preview . '...' . PHP_EOL;
    $pdo->exec($statement);
    echo 'OK' . PHP_EOL;
}

echo PHP_EOL . '=== After ===' . PHP_EOL;
$after = verify($pdo, $dbName);
printVerify($after);

$passed = array_reduce($after, static fn (bool $carry, bool $v): bool => $carry && $v, true);
exit($passed ? 0 : 1);

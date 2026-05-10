<?php

/**
 * Web-triggered migration runner.
 * Protected by X-Migrate-Secret header — must match MIGRATE_SECRET env var.
 * Called once by CI after each deploy via curl.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

$secret = $_ENV['MIGRATE_SECRET'] ?? '';
$given  = $_SERVER['HTTP_X_MIGRATE_SECRET'] ?? '';

if ($secret === '' || !hash_equals($secret, $given)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$migrationsDir = __DIR__ . '/../migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$results = [];
$db = FinanceDatabase::finance();

foreach ($files as $file) {
    $name = basename($file);
    $sql  = file_get_contents($file);
    if ($sql === false) {
        $results[] = ['file' => $name, 'status' => 'error', 'message' => 'Could not read file'];
        continue;
    }
    try {
        $db->exec($sql);
        $results[] = ['file' => $name, 'status' => 'ok'];
    } catch (PDOException $e) {
        if ($e->getCode() === '42000' && str_contains($e->getMessage(), 'Duplicate column')) {
            $results[] = ['file' => $name, 'status' => 'skip', 'message' => 'Already applied'];
        } else {
            $results[] = ['file' => $name, 'status' => 'fail', 'message' => $e->getMessage()];
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['migrations' => $results]);

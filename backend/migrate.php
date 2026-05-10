<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

if (empty($files)) {
    fwrite(STDOUT, "No migration files found.\n");
    exit(0);
}

$db = FinanceDatabase::finance();

foreach ($files as $file) {
    $name = basename($file);
    $sql  = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Could not read: $name\n");
        exit(1);
    }
    try {
        $db->exec($sql);
        fwrite(STDOUT, "OK  $name\n");
    } catch (PDOException $e) {
        // 1060 = Duplicate column — migration already applied, safe to skip
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'Column already exists')) {
            fwrite(STDOUT, "SKIP $name (already applied)\n");
        } else {
            fwrite(STDERR, "FAIL $name: " . $e->getMessage() . "\n");
            exit(1);
        }
    }
}

fwrite(STDOUT, "All migrations done.\n");

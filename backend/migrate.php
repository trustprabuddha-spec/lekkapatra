<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$sql = file_get_contents(__DIR__ . '/migrations/001_finance_core.sql');
if ($sql === false) {
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

$db = FinanceDatabase::finance();
$db->exec($sql);
fwrite(STDOUT, "Finance schema migration completed.\n");

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../services/StudentAggregatorService.php';

foreach (['1', '2'] as $schoolCode) {
    try {
        $rows = StudentAggregatorService::mergedStudents($schoolCode);
        echo "School {$schoolCode}: OK, merged rows = " . count($rows) . PHP_EOL;
    } catch (Throwable $e) {
        echo "School {$schoolCode}: FAILED - " . $e->getMessage() . PHP_EOL;
    }
}

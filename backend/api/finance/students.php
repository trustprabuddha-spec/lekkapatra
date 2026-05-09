<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/StudentAggregatorService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$schoolCode = financeSchoolCode();
$students = StudentAggregatorService::mergedStudents($schoolCode);
financeJson(['data' => $students]);

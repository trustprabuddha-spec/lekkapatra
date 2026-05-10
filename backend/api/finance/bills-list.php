<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$db = FinanceDatabase::finance();
$schoolCode = financeSchoolCode();
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));

$stmt = $db->prepare(
    'SELECT id, bill_no, student_name, total_amount, due_date, status, created_at
     FROM bills
     WHERE school_code = ?
     ORDER BY created_at DESC
     LIMIT ?'
);
$stmt->execute([$schoolCode, $limit]);
financeJson(['data' => $stmt->fetchAll()]);

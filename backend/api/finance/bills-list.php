<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$db         = FinanceDatabase::finance();
$schoolCode = financeSchoolCode();
$limit      = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $limit;
$search     = trim((string)($_GET['search'] ?? ''));

if ($search !== '') {
    $like = '%' . $search . '%';

    $countStmt = $db->prepare(
        'SELECT COUNT(*) FROM bills
         WHERE school_code = ?
           AND (student_name LIKE ? OR bill_no LIKE ?)'
    );
    $countStmt->execute([$schoolCode, $like, $like]);

    $dataStmt = $db->prepare(
        'SELECT id, bill_no, student_name, total_amount, due_date, status, created_at
         FROM bills
         WHERE school_code = ?
           AND (student_name LIKE ? OR bill_no LIKE ?)
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );
    $dataStmt->execute([$schoolCode, $like, $like, $limit, $offset]);
} else {
    $countStmt = $db->prepare('SELECT COUNT(*) FROM bills WHERE school_code = ?');
    $countStmt->execute([$schoolCode]);

    $dataStmt = $db->prepare(
        'SELECT id, bill_no, student_name, total_amount, due_date, status, created_at
         FROM bills
         WHERE school_code = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );
    $dataStmt->execute([$schoolCode, $limit, $offset]);
}

$total = (int)$countStmt->fetchColumn();
$data  = $dataStmt->fetchAll();

financeJson([
    'data'        => $data,
    'total'       => $total,
    'page'        => $page,
    'limit'       => $limit,
    'total_pages' => (int)ceil($total / $limit),
]);

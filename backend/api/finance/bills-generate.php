<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$input = financeInput();
foreach (['student_source', 'source_student_id', 'student_name', 'due_date', 'items'] as $key) {
    if (!isset($input[$key]) || $input[$key] === '') {
        financeJson(['error' => $key . ' is required'], 422);
    }
}
if (!is_array($input['items']) || count($input['items']) === 0) {
    financeJson(['error' => 'items must be a non-empty array'], 422);
}

$db = FinanceDatabase::finance();
$schoolCode = financeSchoolCode();
$billNo = 'FIN-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$total = 0.0;
foreach ($input['items'] as $item) {
    $total += (float)($item['amount'] ?? 0);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare('INSERT INTO bills (school_code, student_source, source_student_id, student_name, bill_no, due_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $schoolCode,
        trim((string)$input['student_source']),
        (int)$input['source_student_id'],
        trim((string)$input['student_name']),
        $billNo,
        trim((string)$input['due_date']),
        $total,
        'issued'
    ]);
    $billId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare('INSERT INTO bill_items (bill_id, fee_type_id, description, quantity, amount) VALUES (?, ?, ?, ?, ?)');
    foreach ($input['items'] as $item) {
        $itemStmt->execute([
            $billId,
            isset($item['fee_type_id']) ? (int)$item['fee_type_id'] : null,
            trim((string)($item['description'] ?? 'Fee')),
            (float)($item['quantity'] ?? 1),
            (float)($item['amount'] ?? 0),
        ]);
    }
    $db->commit();
    financeJson(['success' => true, 'bill_id' => $billId, 'bill_no' => $billNo], 201);
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

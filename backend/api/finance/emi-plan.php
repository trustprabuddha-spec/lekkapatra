<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$billId = (int)($_GET['bill_id'] ?? 0);
if ($billId < 1) {
    financeJson(['error' => 'bill_id is required'], 422);
}

$input = financeInput();
$installments = $input['installments'] ?? null;
if (!is_array($installments) || count($installments) === 0) {
    financeJson(['error' => 'installments must be a non-empty array'], 422);
}

$db = FinanceDatabase::finance();
$stmt = $db->prepare('SELECT total_amount FROM bills WHERE id = ?');
$stmt->execute([$billId]);
$bill = $stmt->fetch();
if (!$bill) {
    financeJson(['error' => 'Bill not found'], 404);
}

$sum = 0.0;
foreach ($installments as $row) {
    if (empty($row['due_date'])) {
        financeJson(['error' => 'Each installment requires due_date'], 422);
    }
    $sum += (float)($row['amount'] ?? 0);
}
$billTotal = (float)$bill['total_amount'];
if (abs($sum - $billTotal) > 0.01) {
    financeJson(['error' => 'Installment total must match bill total'], 422);
}

$db->beginTransaction();
try {
    $db->prepare('DELETE FROM emi_plans WHERE bill_id = ?')->execute([$billId]);
    $stmt = $db->prepare('INSERT INTO emi_plans (bill_id, installment_no, due_date, amount, status) VALUES (?, ?, ?, ?, ?)');
    foreach ($installments as $index => $row) {
        $stmt->execute([$billId, $index + 1, $row['due_date'], (float)$row['amount'], 'pending']);
    }
    $db->commit();
    financeJson(['success' => true]);
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

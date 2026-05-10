<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/InvoicePdfService.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    financeJson(['error' => 'Method not allowed'], 405);
}

$billId = (int)($_GET['bill_id'] ?? 0);
if ($billId < 1) {
    financeJson(['error' => 'bill_id is required'], 422);
}

$db = FinanceDatabase::finance();
$stmt = $db->prepare('SELECT * FROM bills WHERE id = ?');
$stmt->execute([$billId]);
$bill = $stmt->fetch();
if (!$bill) {
    financeJson(['error' => 'Bill not found'], 404);
}

$stmt = $db->prepare('SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id ASC');
$stmt->execute([$billId]);
$items = $stmt->fetchAll();

InvoicePdfService::stream($bill, $items);

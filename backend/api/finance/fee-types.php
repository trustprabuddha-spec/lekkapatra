<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';

$db = FinanceDatabase::finance();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$schoolCode = financeSchoolCode();

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM fee_types WHERE school_code = ? ORDER BY created_at DESC');
    $stmt->execute([$schoolCode]);
    financeJson(['data' => $stmt->fetchAll()]);
}

$input = financeInput();
if ($method === 'POST') {
    foreach (['name', 'category', 'default_amount'] as $key) {
        if (!isset($input[$key]) || $input[$key] === '') {
            financeJson(['error' => $key . ' is required'], 422);
        }
    }
    $stmt = $db->prepare('INSERT INTO fee_types (school_code, name, category, default_amount, recurrence, is_active) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $schoolCode,
        trim((string)$input['name']),
        trim((string)$input['category']),
        (float)$input['default_amount'],
        trim((string)($input['recurrence'] ?? 'one_time')),
        (int)($input['is_active'] ?? 1),
    ]);
    financeJson(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if ($id < 1) {
        financeJson(['error' => 'id is required'], 422);
    }
    $stmt = $db->prepare('UPDATE fee_types SET name = ?, category = ?, default_amount = ?, recurrence = ?, is_active = ? WHERE id = ? AND school_code = ?');
    $stmt->execute([
        trim((string)($input['name'] ?? '')),
        trim((string)($input['category'] ?? '')),
        (float)($input['default_amount'] ?? 0),
        trim((string)($input['recurrence'] ?? 'one_time')),
        (int)($input['is_active'] ?? 1),
        $id,
        $schoolCode
    ]);
    financeJson(['success' => true]);
}

financeJson(['error' => 'Method not allowed'], 405);

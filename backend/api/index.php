<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/helpers.php';

financeAllowCors();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/api/?#', '', $path);
$path = trim((string)$path, '/');

if (preg_match('#^auth/(login|logout|me)$#', $path) === 1) {
    require __DIR__ . '/finance/auth.php';
    exit;
}

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
AuthMiddleware::requireAuth();

if ($path === 'students') {
    require __DIR__ . '/finance/students.php';
    exit;
}
if ($path === 'fee-types') {
    require __DIR__ . '/finance/fee-types.php';
    exit;
}
if ($path === 'bills') {
    require __DIR__ . '/finance/bills-list.php';
    exit;
}
if ($path === 'bills/generate') {
    require __DIR__ . '/finance/bills-generate.php';
    exit;
}
if (preg_match('#^bills/([0-9]+)/emi-plan$#', $path, $m) === 1) {
    $_GET['bill_id'] = $m[1];
    require __DIR__ . '/finance/emi-plan.php';
    exit;
}
if (preg_match('#^invoices/([0-9]+)/pdf$#', $path, $m) === 1) {
    $_GET['bill_id'] = $m[1];
    require __DIR__ . '/finance/invoice-pdf.php';
    exit;
}

financeJson(['error' => 'Not found'], 404);

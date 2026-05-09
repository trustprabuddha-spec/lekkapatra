<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'POST' && preg_match('#/auth/login$#', $path) === 1) {
    $input = financeInput();
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $schoolCode = trim((string)($input['school_code'] ?? financeSchoolCode()));

    if ($username === '' || $password === '') {
        financeJson(['error' => 'Username and password are required'], 422);
    }

    $user = AuthMiddleware::login($username, $password, $schoolCode);
    financeJson(['success' => true, 'user' => $user]);
}

if ($method === 'POST' && preg_match('#/auth/logout$#', $path) === 1) {
    AuthMiddleware::logout();
    financeJson(['success' => true]);
}

if ($method === 'GET' && preg_match('#/auth/me$#', $path) === 1) {
    $user = AuthMiddleware::currentUser();
    if (!$user) {
        financeJson(['error' => 'Not authenticated'], 401);
    }
    financeJson(['success' => true, 'user' => $user]);
}

financeJson(['error' => 'Method not allowed'], 405);

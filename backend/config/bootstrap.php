<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
} catch (Throwable $e) {
    // Keep runtime working even if .env is absent.
}

date_default_timezone_set('Asia/Kolkata');

$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

set_exception_handler(function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $payload = ['error' => 'Internal server error'];
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        $payload['message'] = $e->getMessage();
    }
    echo json_encode($payload);
    exit;
});

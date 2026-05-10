<?php

declare(strict_types=1);

function financeJson(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function financeInput(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '{}', true);
    return is_array($decoded) ? $decoded : [];
}

function financeSchoolCode(): string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headers = array_change_key_case($headers, CASE_LOWER);
    return trim((string)($headers['x-school-code'] ?? $_GET['school_code'] ?? '1'));
}

function financeAllowCors(): void
{
    $corsByApache = $_SERVER['FINANCE_CORS_BY_APACHE']
        ?? $_SERVER['REDIRECT_FINANCE_CORS_BY_APACHE']
        ?? getenv('FINANCE_CORS_BY_APACHE')
        ?: '';

    if ($corsByApache === '1') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        return;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = array_filter(array_map('trim', explode(',', (string)($_ENV['ALLOWED_ORIGINS'] ?? ''))));
    if ($origin && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET,POST,PUT,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-School-Code');
    header('Vary: Origin');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

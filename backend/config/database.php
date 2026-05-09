<?php

declare(strict_types=1);

final class FinanceDatabase
{
    /** @var array<string, PDO> */
    private static array $pool = [];

    public static function finance(): PDO
    {
        return self::connect(
            'finance',
            (string)($_ENV['FIN_DB_HOST'] ?? 'localhost'),
            (string)($_ENV['FIN_DB_NAME'] ?? ''),
            (string)($_ENV['FIN_DB_USER'] ?? ''),
            (string)($_ENV['FIN_DB_PASS'] ?? '')
        );
    }

    public static function central(): PDO
    {
        return self::connect(
            'central',
            (string)($_ENV['CENTRAL_DB_HOST'] ?? 'localhost'),
            (string)($_ENV['CENTRAL_DB_NAME'] ?? ''),
            (string)($_ENV['CENTRAL_DB_USER'] ?? ''),
            (string)($_ENV['CENTRAL_DB_PASS'] ?? '')
        );
    }

    public static function anubhava(string $schoolCode): PDO
    {
        $isSchool2 = $schoolCode === '2';
        $suffix = $isSchool2 ? '2' : '1';
        return self::connect(
            'anubhava_' . $suffix,
            (string)($_ENV['ANU_DB' . $suffix . '_HOST'] ?? 'localhost'),
            (string)($_ENV['ANU_DB' . $suffix . '_NAME'] ?? ''),
            (string)($_ENV['ANU_DB' . $suffix . '_USER'] ?? ''),
            (string)($_ENV['ANU_DB' . $suffix . '_PASS'] ?? '')
        );
    }

    private static function connect(string $key, string $host, string $db, string $user, string $pass): PDO
    {
        if (isset(self::$pool[$key])) {
            return self::$pool[$key];
        }

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        self::$pool[$key] = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$pool[$key];
    }
}

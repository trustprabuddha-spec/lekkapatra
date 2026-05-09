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

        if ($db === '' || $user === '') {
            throw new RuntimeException("Missing database configuration for {$key}");
        }

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        self::$pool[$key] = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if ($key === 'finance') {
            self::ensureFinanceSchema(self::$pool[$key]);
        }
        return self::$pool[$key];
    }

    private static function ensureFinanceSchema(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS fee_types (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                school_code VARCHAR(10) NOT NULL,
                name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NOT NULL,
                default_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                recurrence ENUM('one_time','monthly','termly','yearly') NOT NULL DEFAULT 'one_time',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS bills (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                school_code VARCHAR(10) NOT NULL,
                student_source ENUM('anubhava','central') NOT NULL,
                source_student_id BIGINT UNSIGNED NOT NULL,
                student_name VARCHAR(200) NOT NULL,
                bill_no VARCHAR(80) NOT NULL UNIQUE,
                due_date DATE NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM('draft','issued','paid','partially_paid','cancelled') NOT NULL DEFAULT 'issued',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS bill_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bill_id BIGINT UNSIGNED NOT NULL,
                fee_type_id BIGINT UNSIGNED NULL,
                description VARCHAR(255) NOT NULL,
                quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS emi_plans (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bill_id BIGINT UNSIGNED NOT NULL,
                installment_no INT NOT NULL,
                due_date DATE NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bill_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                payment_mode VARCHAR(50) NOT NULL,
                reference_no VARCHAR(120) NULL,
                paid_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
            )
        ");
    }
}

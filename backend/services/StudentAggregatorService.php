<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class StudentAggregatorService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function mergedStudents(string $schoolCode): array
    {
        $anubhava = self::anubhavaStudents($schoolCode);
        $admissions = self::centralAdmissions($schoolCode);
        $all = array_merge($anubhava, $admissions);
        usort($all, static fn(array $a, array $b) => strcasecmp((string)$a['student_name'], (string)$b['student_name']));
        return $all;
    }

    private static function centralSchoolType(string $schoolCode): string
    {
        return $schoolCode === '2' ? 'jnanamandira' : 'pratishtana';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function anubhavaStudents(string $schoolCode): array
    {
        $db = FinanceDatabase::anubhava($schoolCode);
        $stmt = $db->query(
            "SELECT s.id, s.full_name, s.parent_contact, s.parent_email, s.class_name, s.section,
                    u.full_name AS parent_name
             FROM students s
             LEFT JOIN parent_students ps ON ps.student_id = s.id AND ps.is_primary = 1
             LEFT JOIN users u ON u.id = ps.parent_id AND u.role = 'parent'
             WHERE s.is_active = 1
             ORDER BY s.full_name"
        );
        $rows = $stmt->fetchAll();

        return array_map(static fn(array $row): array => [
            'source' => 'anubhava',
            'source_student_id' => (int)$row['id'],
            'student_name' => (string)$row['full_name'],
            'parent_name' => isset($row['parent_name']) && $row['parent_name'] !== '' ? (string)$row['parent_name'] : null,
            'parent_phone' => (string)($row['parent_contact'] ?? ''),
            'parent_email' => (string)($row['parent_email'] ?? ''),
            'class_name' => (string)($row['class_name'] ?? ''),
            'section' => (string)($row['section'] ?? ''),
            'lifecycle_status' => 'enrolled',
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function centralAdmissions(string $schoolCode): array
    {
        $db = FinanceDatabase::central();
        $schoolType = self::centralSchoolType($schoolCode);
        $stmt = $db->prepare("SELECT id, student_name, primary_parent_name, primary_parent_phone, status FROM admissions WHERE school_type = ? ORDER BY created_at DESC");
        $stmt->execute([$schoolType]);
        $rows = $stmt->fetchAll();

        return array_map(static fn(array $row): array => [
            'source' => 'central',
            'source_student_id' => (int)$row['id'],
            'student_name' => (string)$row['student_name'],
            'parent_name' => (string)($row['primary_parent_name'] ?? ''),
            'parent_phone' => (string)($row['primary_parent_phone'] ?? ''),
            'parent_email' => '',
            'class_name' => '',
            'section' => '',
            'lifecycle_status' => (string)($row['status'] ?? 'admitted'),
        ], $rows);
    }
}

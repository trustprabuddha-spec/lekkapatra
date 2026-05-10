<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

final class AuthMiddleware
{
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';
        $isCrossSiteRequest = self::isCrossSiteRequest();

        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', $isCrossSiteRequest ? 'None' : 'Lax');
        if ($isProduction || $isCrossSiteRequest) {
            ini_set('session.cookie_secure', '1');
        }
        session_name('lekka_finance_session');
        session_start();
    }

    public static function login(string $username, string $password, string $schoolCode): array
    {
        $db = FinanceDatabase::anubhava($schoolCode);
        $stmt = $db->prepare('SELECT id, username, password_hash, role, full_name, email FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            financeJson(['error' => 'Invalid username or password'], 401);
        }

        if (!self::hasLekkaAccess($db, $user)) {
            financeJson(['error' => 'You do not have access to Lekka. Contact admin.'], 403);
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION['finance_user_id'] = (int)$user['id'];
        $_SESSION['finance_school_code'] = $schoolCode;

        return self::publicUser($user, $schoolCode);
    }

    public static function currentUser(): ?array
    {
        self::startSession();
        $userId = (int)($_SESSION['finance_user_id'] ?? 0);
        $schoolCode = (string)($_SESSION['finance_school_code'] ?? '');
        if ($userId < 1 || $schoolCode === '') {
            return null;
        }

        $db = FinanceDatabase::anubhava($schoolCode);
        $stmt = $db->prepare('SELECT id, username, role, full_name, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? self::publicUser($user, $schoolCode) : null;
    }

    public static function requireAuth(): array
    {
        $user = self::currentUser();
        if (!$user) {
            financeJson(['error' => 'Not authenticated'], 401);
        }

        $requestedSchool = financeSchoolCode();
        if ($requestedSchool !== (string)$user['school_code']) {
            financeJson(['error' => 'Please log in to the selected school first.'], 403);
        }

        return $user;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }

    private static function hasLekkaAccess(PDO $db, array $user): bool
    {
        if (in_array((string)$user['role'], ['admin', 'superadmin'], true)) {
            return true;
        }

        try {
            $stmt = $db->prepare('SELECT can_access FROM lekka_user_access WHERE user_id = ?');
            $stmt->execute([(int)$user['id']]);
            $row = $stmt->fetch();
            return $row && (int)$row['can_access'] === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function isCrossSiteRequest(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return false;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $requestHost = $_SERVER['HTTP_HOST'] ?? '';
        $requestHost = explode(':', $requestHost)[0];

        return $originHost !== null && strcasecmp($originHost, $requestHost) !== 0;
    }

    private static function publicUser(array $user, string $schoolCode): array
    {
        return [
            'id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'role' => (string)$user['role'],
            'full_name' => (string)$user['full_name'],
            'email' => (string)($user['email'] ?? ''),
            'school_code' => $schoolCode,
        ];
    }
}

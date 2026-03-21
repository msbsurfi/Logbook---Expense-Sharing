<?php
class Security {
    public const AUTH_SESSION_LIFETIME = 2592000;
    private const SESSION_REGENERATE_INTERVAL = 900;

    public static function bootstrap(): void {
        self::applySecurityHeaders();
        self::ensureSession();
        self::enforceAuthenticatedSession();
    }

    public static function ensureSession(): void {
        self::configureSession();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function markAuthenticatedSession(): void {
        self::ensureSession();

        session_regenerate_id(true);

        $now = time();
        $_SESSION['auth_expires_at'] = $now + self::AUTH_SESSION_LIFETIME;
        $_SESSION['session_regenerated_at'] = $now;

        self::refreshSessionCookie((int)$_SESSION['auth_expires_at']);
    }

    public static function destroySession(): void {
        self::ensureSession();

        $_SESSION = [];
        session_unset();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => !empty($params['secure']),
                'httponly' => !empty($params['httponly']),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function csrfToken(){
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])){
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function validateCsrf($token){
        self::ensureSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    public static function csrfField(){
        return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(self::csrfToken()).'">';
    }

    private static function configureSession(): void {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isHttps = self::isHttps();

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string)self::AUTH_SESSION_LIFETIME);

        session_set_cookie_params([
            'lifetime' => self::AUTH_SESSION_LIFETIME,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function enforceAuthenticatedSession(): void {
        if (empty($_SESSION['user_id'])) {
            return;
        }

        $now = time();
        $expiresAt = (int)($_SESSION['auth_expires_at'] ?? 0);

        if ($expiresAt <= $now) {
            self::destroySession();
            self::ensureSession();
            $_SESSION['flash_error'] = 'Your session has expired. Please sign in again.';
            return;
        }

        $lastRegeneratedAt = (int)($_SESSION['session_regenerated_at'] ?? 0);
        if ($lastRegeneratedAt <= 0 || ($now - $lastRegeneratedAt) >= self::SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['session_regenerated_at'] = $now;
        }

        self::refreshSessionCookie($expiresAt);
    }

    private static function refreshSessionCookie(int $expiresAt): void {
        if (headers_sent()) {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => $expiresAt,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => !empty($params['secure']),
            'httponly' => !empty($params['httponly']),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    private static function applySecurityHeaders(): void {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), microphone=(), payment=(), usb=()');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' data: https://cdnjs.cloudflare.com; connect-src 'self';");
    }

    private static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $forwardedProto === 'https';
    }
}

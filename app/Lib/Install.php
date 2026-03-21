<?php
class Install {
    public const PLACEHOLDER = 'YOURDOMAIN';
    public const DEFAULT_DB_PORT = 3306;
    public const DEFAULT_SMTP_PORT = 465;

    public static function databaseConfigPath(): string {
        return __DIR__ . '/../../config/database.php';
    }

    public static function mailConfigPath(): string {
        return __DIR__ . '/../../config/mail.php';
    }

    public static function schemaPath(): string {
        return __DIR__ . '/../../database/install.sql';
    }

    public static function installScriptPath(): string {
        return __DIR__ . '/../../public/install.php';
    }

    public static function requiresInstallation(): bool {
        if (!is_file(self::databaseConfigPath())) {
            return true;
        }

        require_once self::databaseConfigPath();

        $requiredValues = [
            defined('DB_HOST') ? (string)DB_HOST : '',
            defined('DB_USER') ? (string)DB_USER : '',
            defined('DB_NAME') ? (string)DB_NAME : '',
        ];

        foreach ($requiredValues as $value) {
            if (self::isPlaceholderValue($value)) {
                return true;
            }
        }

        return false;
    }

    public static function isMailConfigured(): bool {
        if (!is_file(self::mailConfigPath())) {
            return false;
        }

        require_once self::mailConfigPath();

        $requiredValues = [
            defined('SMTP_HOST') ? (string)SMTP_HOST : '',
            defined('SMTP_USER') ? (string)SMTP_USER : '',
            defined('SMTP_PASS') ? (string)SMTP_PASS : '',
            defined('SMTP_FROM_EMAIL') ? (string)SMTP_FROM_EMAIL : '',
        ];

        foreach ($requiredValues as $value) {
            if (self::isPlaceholderValue($value)) {
                return false;
            }
        }

        return true;
    }

    public static function isPlaceholderValue(?string $value): bool {
        $normalized = trim((string)$value);

        if ($normalized === '') {
            return true;
        }

        return str_contains(strtoupper($normalized), self::PLACEHOLDER);
    }

    public static function installScriptExists(): bool {
        return is_file(self::installScriptPath());
    }

    public static function deleteInstallScript(): bool {
        return !self::installScriptExists() || @unlink(self::installScriptPath());
    }

    public static function placeholderMailConfig(): array {
        return [
            'host' => 'mail.' . self::PLACEHOLDER,
            'user' => 'noreply@' . self::PLACEHOLDER,
            'pass' => self::PLACEHOLDER,
            'port' => self::DEFAULT_SMTP_PORT,
            'from_email' => 'noreply@' . self::PLACEHOLDER,
            'from_name' => 'Logbook',
            'secure' => 'ssl',
        ];
    }

    public static function writeDatabaseConfig(array $config): bool {
        $content = "<?php\n"
            . "define('DB_HOST', " . var_export((string)$config['host'], true) . ");\n"
            . "define('DB_PORT', " . (int)($config['port'] ?? self::DEFAULT_DB_PORT) . ");\n"
            . "define('DB_USER', " . var_export((string)$config['user'], true) . ");\n"
            . "define('DB_PASS', " . var_export((string)$config['pass'], true) . ");\n"
            . "define('DB_NAME', " . var_export((string)$config['name'], true) . ");\n";

        return file_put_contents(self::databaseConfigPath(), $content) !== false;
    }

    public static function writeMailConfig(array $config): bool {
        $content = "<?php\n"
            . "define('SMTP_HOST', " . var_export((string)$config['host'], true) . ");\n"
            . "define('SMTP_USER', " . var_export((string)$config['user'], true) . ");\n"
            . "define('SMTP_PASS', " . var_export((string)$config['pass'], true) . ");\n"
            . "define('SMTP_PORT', " . (int)($config['port'] ?? self::DEFAULT_SMTP_PORT) . ");\n"
            . "define('SMTP_FROM_EMAIL', " . var_export((string)$config['from_email'], true) . ");\n"
            . "define('SMTP_FROM_NAME', " . var_export((string)($config['from_name'] ?? 'Logbook'), true) . ");\n"
            . "define('SMTP_SECURE', " . var_export((string)($config['secure'] ?? 'ssl'), true) . ");\n";

        return file_put_contents(self::mailConfigPath(), $content) !== false;
    }

    public static function connect(array $config, bool $withDatabase = true): PDO {
        $dsn = 'mysql:host=' . (string)$config['host']
            . ';port=' . (int)($config['port'] ?? self::DEFAULT_DB_PORT);

        if ($withDatabase) {
            $dsn .= ';dbname=' . (string)$config['name'];
        }

        $dsn .= ';charset=utf8mb4';

        return new PDO(
            $dsn,
            (string)$config['user'],
            (string)$config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function createDatabaseIfMissing(PDO $serverPdo, string $databaseName): void {
        $safeDatabaseName = str_replace('`', '``', $databaseName);
        $serverPdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$safeDatabaseName}` "
            . "CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
        );
    }

    public static function importSchema(PDO $pdo): void {
        $schema = @file_get_contents(self::schemaPath());
        if ($schema === false) {
            throw new RuntimeException('Installer schema file was not found.');
        }

        self::resetApplicationTables($pdo);

        foreach (self::splitStatements($schema) as $statement) {
            $pdo->exec($statement);
        }
    }

    public static function createInitialAdmin(PDO $pdo, array $admin): void {
        $profileCode = self::generateProfileCode($pdo, (string)$admin['name']);

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, phone, password, profile_code, status, email_verified, verification_token, role)
             VALUES (:name, :email, NULL, :password, :profile_code, "active", 1, NULL, "admin")'
        );
        $stmt->execute([
            ':name' => (string)$admin['name'],
            ':email' => (string)$admin['email'],
            ':password' => (string)$admin['password_hash'],
            ':profile_code' => $profileCode,
        ]);
    }

    private static function resetApplicationTables(PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::applicationTables() as $table) {
            $safeTable = str_replace('`', '``', $table);
            $pdo->exec("DROP TABLE IF EXISTS `{$safeTable}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private static function splitStatements(string $sql): array {
        $lines = preg_split('/\R/', $sql) ?: [];
        $filteredLines = [];

        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            $filteredLines[] = $line;
        }

        $statements = preg_split('/;\s*(?:\R|$)/', implode("\n", $filteredLines)) ?: [];
        $output = [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (
                $statement !== ''
                && strtoupper($statement) !== 'COMMIT'
                && strtoupper($statement) !== 'START TRANSACTION'
            ) {
                $output[] = $statement;
            }
        }

        return $output;
    }

    private static function generateProfileCode(PDO $pdo, string $name): string {
        $prefix = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $name), 0, 3));
        if ($prefix === '') {
            $prefix = 'ADM';
        }

        $lookup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE profile_code = :profile_code');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $profileCode = $prefix . '-' . random_int(100, 999);
            $lookup->execute([':profile_code' => $profileCode]);
            if ((int)$lookup->fetchColumn() === 0) {
                return $profileCode;
            }
        }

        return $prefix . '-' . substr((string)time(), -3);
    }

    private static function applicationTables(): array {
        return [
            'admin_action_logs',
            'audit_logs',
            'email_log',
            'expense_participants',
            'expenses',
            'friends',
            'impersonations',
            'notifications',
            'password_resets',
            'transactions',
            'users',
        ];
    }
}

<?php
require_once __DIR__ . '/../app/Lib/Security.php';
require_once __DIR__ . '/../app/Lib/Install.php';

Security::bootstrap();

$isLocked = !Install::requiresInstallation();
$errors = [];
$values = [
    'db_host' => trim((string)($_POST['db_host'] ?? 'localhost')),
    'db_port' => trim((string)($_POST['db_port'] ?? (string)Install::DEFAULT_DB_PORT)),
    'db_name' => trim((string)($_POST['db_name'] ?? '')),
    'db_user' => trim((string)($_POST['db_user'] ?? '')),
    'db_pass' => (string)($_POST['db_pass'] ?? ''),
    'skip_mail' => !empty($_POST['skip_mail']),
    'smtp_host' => trim((string)($_POST['smtp_host'] ?? 'mail.YOURDOMAIN')),
    'smtp_port' => trim((string)($_POST['smtp_port'] ?? (string)Install::DEFAULT_SMTP_PORT)),
    'smtp_secure' => in_array($_POST['smtp_secure'] ?? 'ssl', ['ssl', 'tls'], true) ? $_POST['smtp_secure'] : 'ssl',
    'smtp_user' => trim((string)($_POST['smtp_user'] ?? 'noreply@YOURDOMAIN')),
    'smtp_pass' => (string)($_POST['smtp_pass'] ?? ''),
    'smtp_from_email' => trim((string)($_POST['smtp_from_email'] ?? 'noreply@YOURDOMAIN')),
    'smtp_from_name' => trim((string)($_POST['smtp_from_name'] ?? 'Logbook')),
    'admin_name' => trim((string)($_POST['admin_name'] ?? '')),
    'admin_email' => trim((string)($_POST['admin_email'] ?? '')),
];

if (!$isLocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Refresh the page and try again.';
    }

    $dbConfig = [
        'host' => $values['db_host'],
        'port' => (int)$values['db_port'],
        'name' => $values['db_name'],
        'user' => $values['db_user'],
        'pass' => $values['db_pass'],
    ];

    $adminName = $values['admin_name'];
    $adminEmail = strtolower($values['admin_email']);
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string)($_POST['admin_password_confirm'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $dbConfig['host'])) {
        $errors[] = 'Database host must contain only letters, digits, dots, or hyphens.';
    }
    if ($dbConfig['port'] < 1 || $dbConfig['port'] > 65535) {
        $errors[] = 'Database port must be between 1 and 65535.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbConfig['name'])) {
        $errors[] = 'Database name may contain only letters, digits, and underscores.';
    }
    if ($dbConfig['user'] === '') {
        $errors[] = 'Database username is required.';
    }
    if ($adminName === '') {
        $errors[] = 'Admin name is required.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email must be valid.';
    }
    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters long.';
    }
    if ($adminPassword !== $adminPasswordConfirm) {
        $errors[] = 'Admin password confirmation does not match.';
    }

    if (!$values['skip_mail']) {
        if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $values['smtp_host'])) {
            $errors[] = 'SMTP host must contain only letters, digits, dots, or hyphens.';
        }
        if ((int)$values['smtp_port'] < 1 || (int)$values['smtp_port'] > 65535) {
            $errors[] = 'SMTP port must be between 1 and 65535.';
        }
        if (!filter_var($values['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'SMTP username must be a valid email address.';
        }
        if (!filter_var($values['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'From email must be a valid email address.';
        }
        if (trim($values['smtp_pass']) === '') {
            $errors[] = 'SMTP password is required unless you skip mail setup.';
        }
    }

    if (empty($errors)) {
        $mailConfig = $values['skip_mail']
            ? Install::placeholderMailConfig()
            : [
                'host' => $values['smtp_host'],
                'user' => strtolower($values['smtp_user']),
                'pass' => $values['smtp_pass'],
                'port' => (int)$values['smtp_port'],
                'from_email' => strtolower($values['smtp_from_email']),
                'from_name' => $values['smtp_from_name'] !== '' ? $values['smtp_from_name'] : 'Logbook',
                'secure' => $values['smtp_secure'],
            ];

        try {
            $serverPdo = Install::connect($dbConfig, false);
            Install::createDatabaseIfMissing($serverPdo, $dbConfig['name']);

            $databasePdo = Install::connect($dbConfig, true);
            Install::importSchema($databasePdo);
            Install::createInitialAdmin($databasePdo, [
                'name' => $adminName,
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            ]);

            if (!Install::writeMailConfig($mailConfig)) {
                throw new RuntimeException('Mail configuration could not be written. Check file permissions for config/mail.php.');
            }

            if (!Install::writeDatabaseConfig($dbConfig)) {
                throw new RuntimeException('Database configuration could not be written. Check file permissions for config/database.php.');
            }

            $_SESSION['installer_cleanup_token'] = bin2hex(random_bytes(32));
            $_SESSION['flash_success'] = $values['skip_mail']
                ? 'Installation completed. Sign in with the new admin account and add SMTP settings later from the admin panel.'
                : 'Installation completed. Sign in with the new admin account.';

            header('Location: /fix.php?cleanup=install&token=' . urlencode($_SESSION['installer_cleanup_token']));
            exit();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Logbook Installer</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        :root {
            color-scheme: light dark;
            --bg: 
            --panel: rgba(22, 21, 19, 0.94);
            --panel-soft: rgba(255, 255, 255, 0.05);
            --text: 
            --muted: 
            --brand: 
            --danger: 
            --danger-bg: rgba(255, 140, 125, 0.14);
            --border: rgba(214, 167, 41, 0.18);
            --input: rgba(255, 255, 255, 0.06);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Trebuchet MS", system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(214, 167, 41, 0.24), transparent 30%),
                linear-gradient(180deg, 
            min-height: 100vh;
        }
        a { color: inherit; }
        .shell {
            width: min(1100px, calc(100% - 32px));
            margin: 40px auto;
            display: grid;
            gap: 24px;
        }
        .hero, .panel {
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--panel);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }
        .hero {
            padding: 28px;
            display: grid;
            gap: 14px;
        }
        .hero h1, .hero p, .panel h2, .panel p { margin: 0; }
        .hero p, .panel p, .hint, li { color: var(--muted); }
        .hero-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 8px;
        }
        .hero-card {
            padding: 16px;
            border-radius: 18px;
            background: var(--panel-soft);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .panel { padding: 28px; }
        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255, 140, 125, 0.24);
            background: var(--danger-bg);
            color: var(--danger);
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            gap: 22px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .section {
            padding: 20px;
            border-radius: 20px;
            background: var(--panel-soft);
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: grid;
            gap: 14px;
        }
        .field { display: grid; gap: 8px; }
        .field label {
            font-size: 0.94rem;
            font-weight: 600;
        }
        .field input, .field select {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            background: var(--input);
            color: var(--text);
            padding: 12px 14px;
        }
        .field input:focus, .field select:focus {
            outline: none;
            border-color: rgba(214, 167, 41, 0.5);
            box-shadow: 0 0 0 4px rgba(214, 167, 41, 0.12);
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 14px;
            border-radius: 14px;
            background: rgba(214, 167, 41, 0.08);
            border: 1px solid rgba(214, 167, 41, 0.16);
        }
        .checkbox input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }
        .btn {
            border: none;
            border-radius: 999px;
            padding: 13px 22px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, 
            color: 
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .locked-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }
        ul {
            margin: 0;
            padding-left: 18px;
        }
        .hint {
            font-size: 0.9rem;
        }
        @media (max-width: 720px) {
            .shell { margin: 20px auto; }
            .hero, .panel { padding: 22px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <span class="hint">First-run setup</span>
            <h1>Install Logbook</h1>
            <p>Fill in the database details, optionally add SMTP now, and create the first admin account. The installer resets only Logbook tables inside the selected database before importing the schema.</p>
            <div class="hero-grid">
                <div class="hero-card">
                    <strong>Database</strong>
                    <p>Creates the database if needed and imports `database/install.sql`.</p>
                </div>
                <div class="hero-card">
                    <strong>Mail</strong>
                    <p>Skip SMTP now if you want and complete it later from the admin settings page.</p>
                </div>
                <div class="hero-card">
                    <strong>Cleanup</strong>
                    <p>After installation the flow hands off to `fix.php` so `install.php` can be removed cleanly.</p>
                </div>
            </div>
        </section>

        <section class="panel">
            <?php if ($isLocked): ?>
                <h2>Installer Locked</h2>
                <p>This application is already configured. Sign in to the app. If `install.php` still exists, remove it from the red warning banner in the admin panel.</p>
                <div class="locked-actions">
                    <a class="btn btn-primary" href="/login">Go to Login</a>
                </div>
            <?php else: ?>
                <h2>Configuration</h2>
                <p>Fields marked by workflow are required. SMTP can be skipped now and added later in the admin panel.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/install.php">
                    <?php echo Security::csrfField(); ?>
                    <div class="grid">
                        <section class="section">
                            <h3>Database</h3>
                            <div class="field">
                                <label for="db_host">Host</label>
                                <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($values['db_host']); ?>" placeholder="localhost" required>
                            </div>
                            <div class="field">
                                <label for="db_port">Port</label>
                                <input type="number" id="db_port" name="db_port" value="<?php echo htmlspecialchars($values['db_port']); ?>" min="1" max="65535" required>
                            </div>
                            <div class="field">
                                <label for="db_name">Database Name</label>
                                <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($values['db_name']); ?>" placeholder="logbook" required>
                            </div>
                            <div class="field">
                                <label for="db_user">Database User</label>
                                <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($values['db_user']); ?>" required>
                            </div>
                            <div class="field">
                                <label for="db_pass">Database Password</label>
                                <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($values['db_pass']); ?>" autocomplete="new-password">
                            </div>
                        </section>

                        <section class="section">
                            <h3>Mail</h3>
                            <label class="checkbox" for="skip_mail">
                                <input type="checkbox" id="skip_mail" name="skip_mail" value="1" <?php echo $values['skip_mail'] ? 'checked' : ''; ?>>
                                <span>Skip SMTP for now and configure it later from the admin panel.</span>
                            </label>
                            <div class="field">
                                <label for="smtp_host">SMTP Host</label>
                                <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($values['smtp_host']); ?>" placeholder="mail.YOURDOMAIN">
                            </div>
                            <div class="field">
                                <label for="smtp_port">SMTP Port</label>
                                <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($values['smtp_port']); ?>" min="1" max="65535">
                            </div>
                            <div class="field">
                                <label for="smtp_secure">Encryption</label>
                                <select id="smtp_secure" name="smtp_secure">
                                    <option value="ssl" <?php echo $values['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo $values['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="smtp_user">SMTP Username</label>
                                <input type="email" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($values['smtp_user']); ?>" placeholder="noreply@YOURDOMAIN">
                            </div>
                            <div class="field">
                                <label for="smtp_pass">SMTP Password</label>
                                <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars($values['smtp_pass']); ?>" autocomplete="new-password">
                            </div>
                            <div class="field">
                                <label for="smtp_from_email">From Email</label>
                                <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($values['smtp_from_email']); ?>" placeholder="noreply@YOURDOMAIN">
                            </div>
                            <div class="field">
                                <label for="smtp_from_name">From Name</label>
                                <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($values['smtp_from_name']); ?>">
                            </div>
                        </section>

                        <section class="section">
                            <h3>Administrator</h3>
                            <div class="field">
                                <label for="admin_name">Admin Name</label>
                                <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($values['admin_name']); ?>" required>
                            </div>
                            <div class="field">
                                <label for="admin_email">Admin Email</label>
                                <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($values['admin_email']); ?>" required>
                            </div>
                            <div class="field">
                                <label for="admin_password">Admin Password</label>
                                <input type="password" id="admin_password" name="admin_password" minlength="8" autocomplete="new-password" required>
                            </div>
                            <div class="field">
                                <label for="admin_password_confirm">Confirm Password</label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" minlength="8" autocomplete="new-password" required>
                            </div>
                            <ul>
                                <li>The first admin is created as active and email-verified.</li>
                                <li>If SMTP is skipped, user registration and password resets stay disabled until mail is configured.</li>
                            </ul>
                        </section>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Install Application</button>
                        <a href="/" class="btn btn-secondary">Back to Site</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>

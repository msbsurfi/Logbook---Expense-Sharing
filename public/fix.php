<?php
require_once __DIR__ . '/../app/Lib/Security.php';
require_once __DIR__ . '/../app/Lib/Install.php';

Security::bootstrap();

$isAdmin = !empty($_SESSION['user_id']) && (($_SESSION['user_role'] ?? '') === 'admin');
$installerToken = trim((string)($_GET['token'] ?? ''));
$hasInstallerAccess = $installerToken !== ''
    && !empty($_SESSION['installer_cleanup_token'])
    && hash_equals((string)$_SESSION['installer_cleanup_token'], $installerToken);

$redirectTarget = '/login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin || !Security::validateCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo 'Forbidden';
        exit();
    }

    if (($_POST['action'] ?? '') !== 'delete_install') {
        http_response_code(400);
        echo 'Invalid action';
        exit();
    }

    $redirectTarget = '/admin?tab=settings';
} elseif (!$hasInstallerAccess) {
    if ($isAdmin) {
        header('Location: /admin?tab=settings');
        exit();
    }

    http_response_code(403);
    echo 'Forbidden';
    exit();
}

$deleted = Install::deleteInstallScript();

if ($hasInstallerAccess) {
    unset($_SESSION['installer_cleanup_token']);
}

if ($deleted) {
    $message = 'Installer cleanup completed.';
    if (!empty($_SESSION['flash_success'])) {
        $_SESSION['flash_success'] .= ' ' . $message;
    } else {
        $_SESSION['flash_success'] = $message;
    }
} else {
    $message = 'Installer cleanup failed. Delete /public/install.php manually or use the admin banner later.';
    if (!empty($_SESSION['flash_error'])) {
        $_SESSION['flash_error'] .= ' ' . $message;
    } else {
        $_SESSION['flash_error'] = $message;
    }
}

header('Location: ' . $redirectTarget);
exit();

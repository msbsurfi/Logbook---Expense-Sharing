<?php
require_once __DIR__ . '/../../Lib/Security.php';

Security::ensureSession();

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAuthenticated = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Guest';
$userEmail = $_SESSION['user_email'] ?? '';
$userProfileCode = $_SESSION['user_profile_code'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr(preg_replace('/[^a-z]/i', '', $userName), 0, 2));
$userInitials = $userInitials !== '' ? $userInitials : 'LB';
$assetVersion = (string) (
    @filemtime(__DIR__ . '/../../../public/css/style.v3.css')
    ?: @filemtime(__DIR__ . '/../../../public/js/ui.v2.js')
    ?: time()
);
$logoVersion = (string) (
    @filemtime(__DIR__ . '/../../../public/logo.png')
    ?: $assetVersion
);

$primaryNav = [
    ['/dashboard', 'Dashboard', 'fa-solid fa-gauge-high'],
    ['/friends', 'Friends', 'fa-solid fa-user-group'],
    ['/transactions/history', 'History', 'fa-solid fa-clock-rotate-left'],
];

if ($isAuthenticated && $userRole === 'admin') {
    $primaryNav[] = ['/admin', 'Admin', 'fa-solid fa-shield-halved'];
}

$guestNav = [
    ['/login', 'Sign In', 'fa-solid fa-right-to-bracket'],
    ['/register', 'Register', 'fa-solid fa-user-plus'],
];

$shouldShowFab = $isAuthenticated && !str_starts_with($currentPath, '/expenses/create');

$isActive = static function (string $path) use ($currentPath): bool {
    if ($path === '/dashboard') {
        return $currentPath === '/' || $currentPath === '/dashboard';
    }
    return str_starts_with($currentPath, $path);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="
    <link rel="icon" type="image/png" href="/favicon.png?v=<?php echo rawurlencode($logoVersion); ?>">
    <link rel="shortcut icon" href="/favicon.png?v=<?php echo rawurlencode($logoVersion); ?>">
    <link rel="apple-touch-icon" href="/logo.png?v=<?php echo rawurlencode($logoVersion); ?>">
    <title><?php echo htmlspecialchars($title ?? 'Logbook'); ?></title>
    <link rel="stylesheet" href="https:
    <link rel="stylesheet" href="/css/style.v3.css?v=<?php echo rawurlencode($assetVersion); ?>">
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('logbook_theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if ((savedTheme || (prefersDark ? 'dark' : 'light')) === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (error) {}
        })();
    </script>
</head>
<body class="<?php echo $isAuthenticated ? 'is-authenticated' : 'is-guest'; ?>" data-csrf-token="<?php echo htmlspecialchars(Security::csrfToken(), ENT_QUOTES); ?>">

<?php if (!empty($_SESSION['impersonator_admin_state'])): ?>
    <div class="impersonation-banner">
        <div class="container impersonation-inner">
            <div>
                <strong>Impersonation active.</strong>
                <span>You are viewing the app as <?php echo htmlspecialchars($userName); ?>.</span>
            </div>
            <form action="/admin/stop-impersonation" method="post">
                <?php echo Security::csrfField(); ?>
                <button class="btn secondary" type="submit">Return to Admin</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?php echo $isAuthenticated ? '/dashboard' : '/login'; ?>" class="brand-link" aria-label="Logbook home">
            <span class="brand-mark" aria-hidden="true">
                <img src="/logo.png?v=<?php echo rawurlencode($logoVersion); ?>" alt="" width="44" height="44">
            </span>
            <span class="brand-copy">
                <strong>Logbook</strong>
                <small>Shared debt tracking</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Primary navigation">
            <?php foreach ($isAuthenticated ? $primaryNav : $guestNav as [$href, $label, $icon]): ?>
                <a href="<?php echo $href; ?>" class="<?php echo $isActive($href) ? 'active' : ''; ?>">
                    <i class="<?php echo $icon; ?>"></i>
                    <span><?php echo htmlspecialchars($label); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <button type="button" class="icon-button" data-theme-toggle aria-label="Toggle theme">
                <i class="fa-solid fa-moon"></i>
            </button>

            <?php if ($isAuthenticated): ?>
                <button type="button" class="icon-button notifications-trigger" id="notifications-btn" aria-label="Open notifications" aria-expanded="false" aria-controls="notifications-panel">
                    <i class="fa-regular fa-bell"></i>
                    <span class="icon-badge is-hidden" id="notifications-count">0</span>
                </button>

                <div class="profile-menu">
                    <button type="button" class="profile-trigger" id="profile-menu-button" aria-haspopup="true" aria-expanded="false">
                        <span class="profile-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
                        <span class="profile-meta">
                            <strong><?php echo htmlspecialchars($userName); ?></strong>
                            <small><?php echo htmlspecialchars($userProfileCode ?: 'Member'); ?></small>
                        </span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div class="profile-dropdown" id="profile-dropdown" hidden>
                        <div class="profile-dropdown-header">
                            <strong><?php echo htmlspecialchars($userName); ?></strong>
                            <span><?php echo htmlspecialchars($userEmail ?: 'No email on file'); ?></span>
                        </div>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="/admin">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>Admin Panel</span>
                            </a>
                        <?php endif; ?>
                        <a href="/friends">
                            <i class="fa-solid fa-address-book"></i>
                            <span>Manage Friends</span>
                        </a>
                        <form action="/logout" method="post" class="menu-action-form">
                            <?php echo Security::csrfField(); ?>
                            <button type="submit" class="menu-action danger-link">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="/register" class="btn btn-primary header-cta desktop-only">Create Account</a>
            <?php endif; ?>

            <button type="button" class="icon-button mobile-only" id="mobile-menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<?php if ($isAuthenticated): ?>
    <aside class="notifications-panel" id="notifications-panel" hidden aria-label="Notifications">
        <div class="notifications-panel-header">
            <div>
                <h2>Notifications</h2>
                <p>Recent activity across your account.</p>
            </div>
            <button type="button" class="icon-button" id="notifications-close" aria-label="Close notifications">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="notifications-list" id="notifications-list">
            <div class="notifications-empty">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet.</p>
            </div>
        </div>
    </aside>
    <div class="panel-scrim" id="notifications-scrim" hidden></div>
<?php endif; ?>

<div class="panel-scrim" id="mobile-nav-overlay" hidden></div>
<aside class="mobile-nav" id="mobile-nav" aria-hidden="true">
    <div class="mobile-nav-header">
        <div>
            <strong><?php echo htmlspecialchars($isAuthenticated ? $userName : 'Logbook'); ?></strong>
            <span><?php echo htmlspecialchars($isAuthenticated ? ($userEmail ?: 'Signed in') : 'Split bills without the spreadsheet'); ?></span>
        </div>
        <button type="button" class="icon-button" id="mobile-nav-close" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="mobile-nav-links" aria-label="Mobile navigation">
        <?php foreach ($isAuthenticated ? $primaryNav : $guestNav as [$href, $label, $icon]): ?>
            <a href="<?php echo $href; ?>" class="<?php echo $isActive($href) ? 'active' : ''; ?>">
                <i class="<?php echo $icon; ?>"></i>
                <span><?php echo htmlspecialchars($label); ?></span>
            </a>
        <?php endforeach; ?>
        <?php if ($isAuthenticated): ?>
            <a href="/expenses/create" class="<?php echo $isActive('/expenses') ? 'active' : ''; ?>">
                <i class="fa-solid fa-receipt"></i>
                <span>New Expense</span>
            </a>
        <?php else: ?>
            <a href="/register" class="<?php echo $isActive('/register') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-plus"></i>
                <span>Create Account</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="mobile-nav-footer">
        <button type="button" class="btn secondary full-width" data-theme-toggle>
            <i class="fa-solid fa-circle-half-stroke"></i>
            <span>Toggle Theme</span>
        </button>
        <?php if ($isAuthenticated): ?>
            <form action="/logout" method="post">
                <?php echo Security::csrfField(); ?>
                <button type="submit" class="btn btn-primary full-width">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</aside>

<main class="site-main">
    <?php if ($shouldShowFab): ?>
        <a href="/expenses/create" class="fab" title="Create expense">
            <i class="fa-solid fa-plus"></i>
            <span>Expense</span>
        </a>
    <?php endif; ?>

    <div class="container page-container">

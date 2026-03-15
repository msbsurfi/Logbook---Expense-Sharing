<?php
// --- PHP LOGIC FOR ACTIVE PAGE & USER INITIALS ---
$current_uri = $_SERVER['REQUEST_URI'];
function isActive($path) {
    global $current_uri;
    // Make dashboard active for both "/" and "/dashboard"
    if ($path === '/dashboard' && ($current_uri === '/dashboard' || $current_uri === '/')) return true;
    if ($path !== '/dashboard' && strpos($current_uri, $path) === 0) return true;
    return false;
}
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_initials = '';
if ($user_name !== 'Guest') {
    $parts = explode(' ', $user_name);
    $user_initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
} else {
    $user_initials = 'G';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <link rel="icon" type="image/x-icon" href="/logo.png">
    <title><?php echo $title ?? 'LogBook'; ?></title>

    <!-- External CSS Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Instant Theme-Applying Script -->
    <script>
        (function() {
            function applyTheme(theme) {
                if (theme === 'dark') document.documentElement.classList.add('dark-theme');
                else document.documentElement.classList.remove('dark-theme');
            }
            let savedTheme = localStorage.getItem('theme');
            if (savedTheme) applyTheme(savedTheme);
            else applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>

    <!-- GLOBAL STYLES (Your Application's Design System) -->
    <style>
        /* CSS Variables (Single Source of Truth) */
        :root {
            --brand-color: #007bff; --brand-hover: #0056b3; --brand-color-light: #cfe8ff;
            --header-bg: #ffffff; --header-border: #e5e7eb; --body-bg: #f4f7f6;
            --card-bg: #ffffff; --card-border: #e5e7eb; --input-bg: #f3f4f6;
            --text-primary: #1f2d37; --text-secondary: #6b7280; --text-light: #ffffff;
            --success-color: #16a34a; --success-bg: #dcfce7; --success-text: #15803d;
            --danger-color: #dc2626; --danger-bg: #fee2e2; --danger-text: #b91c1c;
            --warning-color: #facc15; --warning-bg: #fef9c3; --warning-text: #854d0e;
            --disabled-bg: #d1d5db; --disabled-text: #6b7280;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05); --shadow-md: 0 4px 12px rgba(0, 123, 255, 0.15);
        }
        .dark-theme {
            --brand-color-light: #1d4ed8;
            --header-bg: #1f2937; --header-border: #374151; --body-bg: #111827;
            --card-bg: #1f2937; --card-border: #374151; --input-bg: #374151;
            --text-primary: #f9fafb; --text-secondary: #d1d5db;
            --success-color: #22c55e; --success-bg: #164e32; --success-text: #6ee7b7;
            --danger-color: #ef4444; --danger-bg: #5f1818; --danger-text: #fca5a5;
            --warning-color: #facc15; --warning-bg: #42310b; --warning-text: #fef08a;
            --disabled-bg: #374151; --disabled-text: #9ca3af;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3); --shadow-md: 0 4px 12px rgba(0, 123, 255, 0.25);
        }

        /* Universal Box Sizing & Global Layout */
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--body-bg); color: var(--text-primary); margin: 0;
            display: flex; flex-direction: column; min-height: 100vh;
        }
        main { flex: 1 0 auto; padding: 24px 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { margin: 0 0 4px; color: var(--text-primary); }
        .page-header .subtitle { margin: 0; color: var(--text-secondary); }

        /* Global Components */
        .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; }
        .btn { padding: 8px 16px; border-radius: 6px; font-weight: 500; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background-color: var(--brand-color); color: white; border-color: var(--brand-color); }
        .btn-primary:hover { background-color: var(--brand-hover); }
        .btn.secondary { background-color: var(--input-bg); color: var(--text-secondary); border-color: var(--card-border); }
        .btn.secondary:hover { border-color: var(--text-primary); color: var(--text-primary); }
        .btn:disabled, .disable-on-click:disabled { background-color: var(--disabled-bg); color: var(--disabled-text); cursor: not-allowed; border-color: transparent; transform: none !important; box-shadow: none !important; }

        /* Header Styles */
        .header { background-color: var(--header-bg); border-bottom: 1px solid var(--header-border); height: 64px; display: flex; align-items: center; position: sticky; top: 0; z-index: 50; }
        .header-inner { width: 100%; display: flex; align-items: center; justify-content: space-between; }
        .header-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-primary); }
        .brand-logo { background-color: var(--brand-color); color: white; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 600; }
        .brand-name { font-size: 1.25rem; font-weight: 600; display: none; }
        @media (min-width: 640px) { .brand-name { display: block; } }
        .main-nav { display: none; }
        @media (min-width: 1024px) { .main-nav { display: flex; gap: 8px; margin: 0 auto; padding-left: 120px; } }
        .main-nav a { color: var(--text-secondary); text-decoration: none; font-weight: 500; padding: 8px 16px; border-radius: 6px; display: flex; align-items: center; gap: 8px; transition: color 0.2s, background-color 0.2s; }
        .main-nav a:hover { background-color: var(--input-bg); color: var(--text-primary); }
        .main-nav a.active { background-color: var(--brand-color-light); color: var(--brand-color); font-weight: 600; }
        .main-nav a .nav-text { display: none; }
        @media (min-width: 1024px) { .main-nav a .nav-text { display: inline; } }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .action-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.25rem; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s, color 0.2s; }
        .action-btn:hover { background-color: var(--input-bg); color: var(--text-primary); }
        #mobile-menu-btn { display: block; }
        @media (min-width: 1024px) { #mobile-menu-btn { display: none; } }
        .create-expense-btn { display: none; }
        @media (min-width: 768px) { .create-expense-btn { display: inline-flex; } }
        .profile-menu { position: relative; }
        .profile-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--brand-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; cursor: pointer; border: 2px solid transparent; }
        .profile-dropdown { position: absolute; top: calc(100% + 12px); right: 0; background-color: var(--card-bg); border-radius: 8px; border: 1px solid var(--card-border); box-shadow: var(--shadow-md); width: 220px; padding: 8px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s; z-index: 51; }
        .profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-user-info { padding: 8px 12px; border-bottom: 1px solid var(--card-border); margin-bottom: 8px; }
        .profile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 10px 12px; text-decoration: none; color: var(--text-primary); border-radius: 6px; font-size: 0.95rem; }
        .profile-dropdown a:hover { background-color: var(--input-bg); }
        .profile-dropdown a i { width: 20px; text-align: center; color: var(--text-secondary); }
        .profile-dropdown a.logout { color: var(--danger-text); }

        /* --- NEW & IMPROVED MOBILE NAVIGATION STYLES --- */
        .mobile-nav-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 90; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; }
        .mobile-nav-overlay.show { opacity: 1; visibility: visible; }
        .mobile-nav {
            position: fixed; top: 0; left: 0; width: 280px; height: 100%; background-color: var(--card-bg);
            z-index: 100; display: flex; flex-direction: column; transform: translateX(-100%);
            transition: transform 0.3s ease-in-out; box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .mobile-nav.show { transform: translateX(0); }
        .mobile-nav-header { display: flex; justify-content: space-between; align-items: center; padding: 12px; }
        .mobile-nav-user { display: flex; align-items: center; gap: 12px; padding: 16px; border-bottom: 1px solid var(--card-border); }
        .mobile-nav-user .profile-avatar { width: 44px; height: 44px; font-size: 1.2rem; }
        .mobile-nav-user .name { font-weight: 600; color: var(--text-primary); }
        .mobile-nav-user .email { font-size: 0.875rem; color: var(--text-secondary); }
        .mobile-nav nav { padding: 16px; display: flex; flex-direction: column; gap: 8px; }
        .mobile-nav nav a {
            display: flex; align-items: center; gap: 16px; padding: 12px 16px;
            text-decoration: none; color: var(--text-secondary); font-weight: 500; font-size: 1.05rem;
            border-radius: 8px; transition: all 0.2s;
        }
        .mobile-nav nav a i { width: 22px; text-align: center; }
        .mobile-nav nav a:hover { background-color: var(--input-bg); color: var(--text-primary); }
        .mobile-nav nav a.active { background-color: var(--brand-color-light); color: var(--brand-color); font-weight: 600; }
        .mobile-nav-footer { margin-top: auto; padding: 16px; border-top: 1px solid var(--card-border); }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-inner">
        <a href="/dashboard" class="header-brand">
            <div class="brand-logo"><img src="/logo.png" style="height: 40px;"></div>
            <span class="brand-name">LogBook</span>
        </a>

        <nav class="main-nav">
            <a href="/dashboard" class="<?php if(isActive('/dashboard')) echo 'active'; ?>"><i class="fa-solid fa-gauge"></i> <span class="nav-text">Dashboard</span></a>
            <a href="/friends" class="<?php if(isActive('/friends')) echo 'active'; ?>"><i class="fa-solid fa-user-group"></i> <span class="nav-text">Friends</span></a>
            <a href="/transactions/history" class="<?php if(isActive('/transactions')) echo 'active'; ?>"><i class="fa-solid fa-clock-rotate-left"></i> <span class="nav-text">History</span></a>
        </nav>

        <div class="header-actions">
            
            <div class="profile-menu">
                <div id="profile-avatar-btn" class="profile-avatar" title="My Account"><img class="rounded-circle border border-primary" src="/avator.png" style="height: 40px;"></div>
                <div id="profile-dropdown" class="profile-dropdown">
                    <div class="dropdown-user-info">
                        <div class="name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                        <div class="email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
                    </div>
                    <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role']==='admin'): ?>
                        <a href="/admin"><i class="fa-solid fa-shield-halved"></i> Admin Panel</a>
                    <?php endif; ?>
                    <a href="/logout" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
            <button id="mobile-menu-btn" class="action-btn" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
        </div>
    </div>
</header>

<!-- NEW & IMPROVED Mobile Off-Canvas Navigation -->
<div id="mobile-nav-overlay" class="mobile-nav-overlay"></div>
<div id="mobile-nav" class="mobile-nav">
    <div class="mobile-nav-header">
        <a href="/dashboard" class="header-brand">
            <div class="brand-logo"><img src="/logo.png" style="height: 40px;"></div>
            <span class="brand-name" style="display: block; font-size: 1.1rem;">LogBook</span>
        </a>
        <button id="mobile-nav-close-btn" class="action-btn"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="mobile-nav-user">
        <div class="profile-avatar"><img class="rounded-circle border border-primary" src="/avator.png" style="height: 40px;"></div>
        <div>
            <div class="name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
            <div class="email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
        </div>
    </div>

    <nav>
        <a href="/dashboard" class="<?php if(isActive('/dashboard')) echo 'active'; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="/friends" class="<?php if(isActive('/friends')) echo 'active'; ?>"><i class="fa-solid fa-user-group"></i> Friends</a>
        <a href="/transactions/history" class="<?php if(isActive('/transactions')) echo 'active'; ?>"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role']==='admin'): ?>
        <a href="/admin"><i class="fa-solid fa-shield-halved"></i> Admin Panel</a>
        <?php endif; ?>
    </nav>

    <div class="mobile-nav-footer">
        <a href="/logout" class="btn secondary" style="width: 100%;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const profileAvatarBtn = document.getElementById('profile-avatar-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    if (profileAvatarBtn && profileDropdown) {
        profileAvatarBtn.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
    }

    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileNavOverlay = document.getElementById('mobile-nav-overlay');
    const mobileNavCloseBtn = document.getElementById('mobile-nav-close-btn');
    const openMobileNav = () => { mobileNav?.classList.add('show'); mobileNavOverlay?.classList.add('show'); };
    const closeMobileNav = () => { mobileNav?.classList.remove('show'); mobileNavOverlay?.classList.remove('show'); };
    mobileMenuBtn?.addEventListener('click', openMobileNav);
    mobileNavCloseBtn?.addEventListener('click', closeMobileNav);
    mobileNavOverlay?.addEventListener('click', closeMobileNav);

    window.addEventListener('click', () => { profileDropdown?.classList.remove('show'); });
});
</script>

<main>
    <a href="/expenses/create" class="fab" title="New Expense">
        <i class="fa-solid fa-plus"></i>
    </a>
    <style>
        .fab {
            display: flex; /* Changed from none to flex by default for mobile-first */
            position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px;
            border-radius: 50%; background-color: var(--brand-color); color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 40;
            align-items: center; justify-content: center; font-size: 1.5rem; text-decoration: none;
            transition: transform 0.2s, background-color 0.2s;
        }
        .fab:hover { background-color: var(--brand-hover); transform: scale(1.05); }
        @media (max-width: 100px) {
            .fab { display: none; /* Hide FAB on desktop where header button is visible */ }
        }
    </style>

    <div class="container">
<?php
$scriptVersion = (string) (
    @filemtime(__DIR__ . '/../../../public/js/ui.v2.js')
    ?: @filemtime(__DIR__ . '/../../../public/css/style.v3.css')
    ?: time()
);
$logoVersion = (string) (
    @filemtime(__DIR__ . '/../../../public/logo.png')
    ?: $scriptVersion
);

$serverToasts = [];
$flashMap = [
    'flash_error' => ['error', 'Error'],
    'flash_success' => ['success', 'Success'],
];

foreach ($flashMap as $sessionKey => [$type, $title]) {
    if (!empty($_SESSION[$sessionKey])) {
        $messages = is_array($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : [$_SESSION[$sessionKey]];
        foreach ($messages as $message) {
            $serverToasts[] = [
                'type' => $type,
                'title' => $title,
                'message' => (string)$message,
            ];
        }
        unset($_SESSION[$sessionKey]);
    }
}

$toastMap = [
    'toast_errors' => ['error', 'Error'],
    'toast_success' => ['success', 'Success'],
];

foreach ($toastMap as $sessionKey => [$type, $title]) {
    if (!empty($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey])) {
        foreach ($_SESSION[$sessionKey] as $message) {
            $serverToasts[] = [
                'type' => $type,
                'title' => $title,
                'message' => (string)$message,
            ];
        }
        unset($_SESSION[$sessionKey]);
    }
}
?>
    </div>
</main>

<div id="global-loading-overlay" class="loading-overlay" hidden>
    <div class="loading-box">
        <span class="btn-spinner"></span>
        <div>
            <strong>Working</strong>
            <span>Please wait while Logbook finishes the request.</span>
        </div>
    </div>
</div>

<div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <img src="/logo.png?v=<?php echo rawurlencode($logoVersion); ?>" alt="Logbook logo" class="footer-logo" width="52" height="52">
            <div class="footer-copy">
                <strong>Logbook</strong>
                <p>Clear balances, shared expenses, and faster settlements for small groups.</p>
            </div>
        </div>
        <div class="footer-links">
            <a href="mailto:info@logbook.msbsu.com">Support</a>
            <a href="mailto:abuse@logbook.msbsu.com">Report Abuse</a>
            <?php if (empty($_SESSION['user_id'])): ?>
                <a href="/login">Sign In</a>
                <a href="/register">Register</a>
            <?php else: ?>
                <a href="/friends">Friends</a>
                <a href="/transactions/history">History</a>
            <?php endif; ?>
        </div>
        <small>&copy; <?php echo date('Y'); ?> Logbook.</small>
    </div>
</footer>

<script>
    window.__LOGBOOK_SERVER_TOASTS = <?php echo json_encode($serverToasts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="/js/ui.v2.js?v=<?php echo rawurlencode($scriptVersion); ?>"></script>

<?php if (isset($page_scripts)) { echo $page_scripts; } ?>

</body>
</html>

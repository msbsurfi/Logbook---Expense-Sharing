</div> <!-- This closes the .container div opened in header.php -->
</main>

<!-- Global HTML Elements -->
<div id="global-loading-overlay" class="loading-overlay">
    <div class="loading-box">
        <div class="spinner"></div>
        <div class="loading-text">Loading...</div>
    </div>
</div>
<div class="toast-stack"></div>

<!-- Site Footer HTML -->
<footer class="site-footer">
    <div class="container">
        &copy; <?php echo date('Y'); ?> LogBook. All rights reserved.
    </div>
</footer>

<div style="text-align: center; color: #333; line-height: 1; max-width: 800px; margin: 15px auto; border: 1px solid #a0a0a0; padding: 10px; border-radius: 6px; font-size: 10px;">

    <p style="margin: 0 0 10px 0; font-weight: bold; font-size: 12px; color: #1a1a1a;">Account & Support</p>

    <hr style="border: 0; height: 1px; background-color: #e0e0e0; margin: 10px 0;">

    <p style="margin-bottom: 8px;">
        <strong style="color: #d9534f;">To Delete Your Account:</strong> Please settle up all your transactions and then email
        <a href="mailto:info@logbook.msbsu.com" style="color: #007bff; text-decoration: none;">
            info@logbook.msbsu.com
        </a>
    </p>

    <p style="margin: 0;">
        <strong style="color: #f0ad4e;">To Report Abuse:</strong> Please email (with screenshots, if available)
        <a href="mailto:abuse@logbook.msbsu.com" style="color: #007bff; text-decoration: none;">
            abuse@logbook.msbsu.com
        </a>
    </p>

</div>

<!-- Self-Contained Styles for Footer Elements -->
<style>
    .site-footer { background-color: var(--header-bg); padding: 24px 0; border-top: 1px solid var(--header-border); text-align: center; color: var(--text-secondary); flex-shrink: 0; }
    .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; }
    .dark-theme .loading-overlay { background-color: rgba(17, 24, 39, 0.8); }
    .loading-overlay.show { opacity: 1; visibility: visible; }
    .loading-box { display: flex; flex-direction: column; align-items: center; gap: 16px; color: var(--text-primary); }
    .spinner { width: 48px; height: 48px; border: 4px solid var(--header-border); border-top-color: var(--brand-color); border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .toast-stack { position: fixed; top: 80px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 12px; }
    .toast { display: flex; align-items: flex-start; padding: 16px; border-radius: 8px; background-color: var(--header-bg); box-shadow: var(--shadow-md); width: 350px; max-width: 90vw; border-left: 4px solid; animation: slideIn 0.3s ease-out, fadeOut 0.5s ease-in 4.5s forwards; }
    .toast.success { border-left-color: var(--success-color); } .toast.error { border-left-color: var(--danger-color); }
    .toast-icon { font-size: 1.25rem; margin-right: 12px; }
    .toast.success .toast-icon { color: var(--success-color); } .toast.error .toast-icon { color: var(--danger-color); }
    .toast-content { flex-grow: 1; }
    .toast-title { font-weight: 600; color: var(--text-primary); margin: 0 0 4px; }
    .toast-message { font-size: 0.9rem; color: var(--text-secondary); margin: 0; }
    .toast-close-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.25rem; cursor: pointer; line-height: 1; padding: 0 0 0 16px; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; transform: translateX(20px); } }
</style>


<!-- ======================================================= -->
<!--                  SCRIPT LOADING AREA                    -->
<!-- ======================================================= -->

<!-- VENDOR SCRIPTS (Libraries are loaded FIRST) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.min.js" integrity="sha512-nKXmKvJyiGQy343jatQlzDprflyB5c+tKCzGP3Uq67v+lmzfnZUi/ZT+fc6ITZfSC5HhaBKUIvr/nTLCV+7F+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- GLOBAL UI SCRIPT (Loaded SECOND) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Define the global UI object
    window.HalkhataUI = {
        showLoader: function() {
            document.getElementById('global-loading-overlay')?.classList.add('show');
        },
        hideLoader: function() {
            document.getElementById('global-loading-overlay')?.classList.remove('show');
        },
        showToast: function(title, message, type = 'info') {
            const stack = document.querySelector('.toast-stack');
            if (!stack) return;
            const iconClass = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<div class="toast-icon"><i class="${iconClass}"></i></div><div class="toast-content"><p class="toast-title">${title}</p><p class="toast-message">${message}</p></div><button class="toast-close-btn">&times;</button>`;
            stack.appendChild(toast);
            const removeToast = () => {
                toast.style.animation = 'fadeOut 0.5s ease-in forwards';
                toast.addEventListener('animationend', () => toast.remove(), { once: true });
            };
            const timeoutId = setTimeout(removeToast, 5000);
            toast.querySelector('.toast-close-btn').addEventListener('click', () => { clearTimeout(timeoutId); removeToast(); });
        }
    };

    // Process server-side toasts passed from PHP
    <?php
        $all_toasts = [];
        if (!empty($_SESSION['toast_errors'])) { $all_toasts = array_merge($all_toasts, array_map(fn($m) => ['type' => 'error', 'title' => 'Error', 'message' => $m], $_SESSION['toast_errors'])); unset($_SESSION['toast_errors']); }
        if (!empty($_SESSION['toast_success'])) { $all_toasts = array_merge($all_toasts, array_map(fn($m) => ['type' => 'success', 'title' => 'Success', 'message' => $m], $_SESSION['toast_success'])); unset($_SESSION['toast_success']); }
        if (!empty($all_toasts)) { echo "const serverToasts = " . json_encode($all_toasts) . "; serverToasts.forEach(t => HalkhataUI.showToast(t.title, t.message, t.type));"; }
    ?>

    // Automatically hide the loader on every page load
    setTimeout(() => { window.HalkhataUI?.hideLoader(); }, 100);

    // Global handler for disabling buttons on form submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('.disable-on-click');
            if (submitButton) { setTimeout(() => { submitButton.disabled = true; }, 10); }
        });
    });
});
</script>

<!-- PAGE-SPECIFIC SCRIPT STACK (Loaded LAST) -->
<?php
// This will render scripts defined in files like history.php and admin.php
if (isset($page_scripts)) {
    echo $page_scripts;
}
?>

</body>
</html>
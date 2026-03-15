</div> <?php /* closes .container from header.php */ ?>
</main>

<div id="global-loading-overlay" class="loading-overlay">
    <div class="loading-box">
        <div class="spinner"></div>
        <div class="loading-text">Loading...</div>
    </div>
</div>
<div class="toast-stack"></div>

<footer class="site-footer">
    <div class="container">
        <div class="footer-brand">
            <span class="footer-logo">L</span>
            <strong>Logbook</strong>
        </div>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> Logbook. All rights reserved.</p>
        <div class="footer-links">
            <span>Account &amp; Support:
                <a href="mailto:info@logbook.msbsu.com">info@logbook.msbsu.com</a>
            </span>
            <span class="footer-sep">&bull;</span>
            <span>Report Abuse:
                <a href="mailto:abuse@logbook.msbsu.com">abuse@logbook.msbsu.com</a>
            </span>
        </div>
        <p class="footer-delete-note">
            To delete your account, please settle all transactions then email us.
        </p>
    </div>
</footer>

<style>
    .site-footer {
        background-color: var(--header-bg);
        border-top: 3px solid var(--brand-color);
        padding: 32px 0 24px;
        text-align: center;
        color: var(--text-secondary);
        flex-shrink: 0;
        margin-top: 24px;
    }
    .footer-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .footer-logo {
        background-color: var(--brand-color);
        color: #fff;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .footer-brand strong {
        font-size: 1rem;
        color: var(--text-primary);
    }
    .footer-copy {
        margin: 0 0 10px;
        font-size: 0.85rem;
    }
    .footer-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px 16px;
        font-size: 0.8rem;
        margin-bottom: 8px;
    }
    .footer-links a {
        color: var(--brand-color);
        text-decoration: none;
    }
    .footer-links a:hover {
        text-decoration: underline;
    }
    .footer-sep {
        color: var(--card-border);
    }
    .footer-delete-note {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin: 0;
        opacity: 0.7;
    }
    .loading-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(255,255,255,0.85); z-index: 9999;
        display: flex; justify-content: center; align-items: center;
        opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s;
    }
    .dark-theme .loading-overlay { background-color: rgba(17,24,39,0.85); }
    .loading-overlay.show { opacity: 1; visibility: visible; }
    .loading-box { display: flex; flex-direction: column; align-items: center; gap: 16px; color: var(--text-primary); }
    .spinner {
        width: 48px; height: 48px; border: 4px solid var(--header-border);
        border-top-color: var(--brand-color); border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .toast-stack { position: fixed; top: 80px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 12px; }
    .toast {
        display: flex; align-items: flex-start; padding: 16px; border-radius: 8px;
        background-color: var(--header-bg); box-shadow: var(--shadow-md);
        width: 350px; max-width: 90vw; border-left: 4px solid;
        animation: slideIn 0.3s ease-out, fadeOut 0.5s ease-in 4.5s forwards;
    }
    .toast.success { border-left-color: var(--success-color); }
    .toast.error { border-left-color: var(--danger-color); }
    .toast-icon { font-size: 1.25rem; margin-right: 12px; }
    .toast.success .toast-icon { color: var(--success-color); }
    .toast.error .toast-icon { color: var(--danger-color); }
    .toast-content { flex-grow: 1; }
    .toast-title { font-weight: 600; color: var(--text-primary); margin: 0 0 4px; }
    .toast-message { font-size: 0.9rem; color: var(--text-secondary); margin: 0; }
    .toast-close-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.25rem; cursor: pointer; line-height: 1; padding: 0 0 0 16px; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; transform: translateX(20px); } }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.min.js" integrity="sha512-nKXmKvJyiGQy343jatQlzDprflyB5c+tKCzGP3Uq67v+lmzfnZUi/ZT+fc6ITZfSC5HhaBKUIvr/nTLCV+7F+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
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

    <?php
        $all_toasts = [];
        if (!empty($_SESSION['toast_errors'])) {
            $all_toasts = array_merge($all_toasts, array_map(fn($m) => ['type' => 'error', 'title' => 'Error', 'message' => $m], $_SESSION['toast_errors']));
            unset($_SESSION['toast_errors']);
        }
        if (!empty($_SESSION['toast_success'])) {
            $all_toasts = array_merge($all_toasts, array_map(fn($m) => ['type' => 'success', 'title' => 'Success', 'message' => $m], $_SESSION['toast_success']));
            unset($_SESSION['toast_success']);
        }
        if (!empty($all_toasts)) {
            echo "const serverToasts = " . json_encode($all_toasts) . "; serverToasts.forEach(t => HalkhataUI.showToast(t.title, t.message, t.type));";
        }
    ?>

    setTimeout(() => { window.HalkhataUI?.hideLoader(); }, 100);

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('.disable-on-click');
            if (submitButton) { setTimeout(() => { submitButton.disabled = true; }, 10); }
        });
    });
});
</script>

<?php if (isset($page_scripts)) { echo $page_scripts; } ?>

</body>
</html>

<?php
$title = 'Forgot Password';
require_once __DIR__ . '/../layouts/header.php'; // Your modern, theme-aware header
require_once __DIR__ . '/../../Lib/Security.php';
?>

<div class="auth-container">
    <div class="auth-card">

        <?php // --- STATE 2: SUCCESS MESSAGE --- ?>
        <?php if (isset($_SESSION['flash_fp_success'])) : ?>
            
            <div class="auth-icon success">
                <i class="fa-solid fa-envelope-circle-check"></i>
            </div>
            
            <h1>Check your inbox</h1>
            <p class="subtitle">
                We've sent a password reset link to the email address:
                <strong><?php echo htmlspecialchars($_SESSION['flash_fp_email'] ?? 'your email'); ?></strong>
            </p>
            
            <div class="resend-info">
                <p>Didn't receive the email? Check your spam folder, or you can <a href="/forgot-password">try again</a>.</p>
            </div>

            <?php
                unset($_SESSION['flash_fp_success']);
                unset($_SESSION['flash_fp_email']);
            ?>

        <?php // --- STATE 1: INITIAL FORM --- ?>
        <?php else : ?>

            <div class="auth-icon">
                <i class="fa-solid fa-key"></i>
            </div>

            <h1>Forgot your password?</h1>
            <p class="subtitle">No problem. Enter your email below and we'll send you a reset link.</p>
            
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert error-box" style="text-align: left; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>

            <form action="/forgot-password" method="post" class="auth-form">
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                </div>
                <button class="btn btn-primary disable-on-click" type="submit">
                    <i class="fa-solid fa-paper-plane"></i> Send Reset Link
                </button>
            </form>

        <?php endif; ?>

        <p class="footer-link">
            <a href="/login"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </p>
    </div>
</div>

<style>
    /* Re-using auth styles for consistency with Login/Register pages */
    .auth-container {
        max-width: 450px;
        width: 100%;
        margin: 2rem auto;
        padding: 20px;
    }
    .auth-card {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: var(--shadow-md);
        text-align: center;
    }
    .auth-icon {
        background-color: var(--brand-color-light);
        color: var(--brand-color);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 20px;
    }
    .auth-icon.success {
        background-color: var(--success-bg);
        color: var(--success-color);
    }
    h1 {
        font-size: 2rem;
        font-weight: 600;
        margin: 0 0 10px;
        color: var(--text-primary);
    }
    .subtitle {
        color: var(--text-secondary);
        margin: 0 0 30px;
        line-height: 1.6;
    }
    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .btn-primary {
        padding: 14px;
        font-size: 1.05rem;
        margin-top: 10px;
    }
    .footer-link {
        margin-top: 30px;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    .footer-link a {
        color: var(--brand-color);
        text-decoration: none;
        font-weight: 500;
    }
    .footer-link a:hover {
        text-decoration: underline;
    }
    .resend-info {
        margin-top: 24px;
        padding: 12px;
        background-color: var(--input-bg);
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
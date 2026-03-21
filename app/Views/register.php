<?php
require_once __DIR__ . '/../Lib/Security.php';
$logoVersion = (string) (
  @filemtime(__DIR__ . '/../../public/logo.png')
  ?: time()
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#b8860b">
  <link rel="icon" type="image/png" href="/favicon.png?v=<?php echo rawurlencode($logoVersion); ?>">
  <link rel="shortcut icon" href="/favicon.png?v=<?php echo rawurlencode($logoVersion); ?>">
  <link rel="apple-touch-icon" href="/logo.png?v=<?php echo rawurlencode($logoVersion); ?>">
  <title>Register - Logbook</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">

  <style>
    /* --- CSS STYLES --- */

    /* --- Global Styles & Variables (Consistent with Login Page) --- */
    :root {
      --primary-color: #b8860b;
      --primary-hover: #906707;
      --background-color: #f6f1e6;
      --card-background: rgba(255, 251, 245, 0.98);
      --text-color: #1f1b16;
      --subtext-color: #6b5f51;
      --border-color: rgba(97, 74, 28, 0.16);
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(184, 134, 11, 0.12), transparent 30%),
        var(--background-color);
      color: var(--text-color);
      margin: 0;
      padding: 20px 0; /* Add padding for scroll on small screens */
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    /* --- Main Container & Card --- */
    .register-container {
      max-width: 480px;
      width: 100%;
      padding: 20px;
    }

    .register-card {
      background-color: var(--card-background);
      border: 1px solid var(--border-color);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 24px 50px rgba(35, 27, 15, 0.12);
      text-align: center;
    }

    .brand-lockup {
      display: grid;
      justify-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .auth-logo {
      width: 72px;
      height: 72px;
      object-fit: contain;
      border-radius: 20px;
      padding: 8px;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(184, 134, 11, 0.18);
      box-shadow: 0 18px 28px rgba(184, 134, 11, 0.16);
    }

    .brand-name {
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--subtext-color);
    }

    h1 {
      font-size: 2rem;
      font-weight: 600;
      margin: 0 0 10px;
    }

    .subtitle {
      color: var(--subtext-color);
      margin: 0 0 30px;
    }

    /* --- Form Elements --- */
    .register-form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .form-group {
      position: relative;
    }

    .input-icon {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      color: #aaa;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
      width: 100%;
      padding: 14px 14px 14px 45px; /* Left padding for icon */
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box; /* Important for padding */
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }
    
    /* --- Checkbox Group --- */
    .form-group-checkbox {
      display: flex;
      align-items: center;
      gap: 10px;
      text-align: left;
      margin-top: 5px;
    }
    
    .form-group-checkbox input[type="checkbox"] {
      width: 1.1em;
      height: 1.1em;
      cursor: pointer;
    }
    
    .form-group-checkbox label {
      font-size: 0.875rem;
      color: var(--subtext-color);
    }

    /* --- Button --- */
    .btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 15px;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
      margin-top: 10px;
    }

    .btn:hover {
      background-color: var(--primary-hover);
    }
    
    .btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }
    
    /* --- Honeypot Field (Hidden from users) --- */
    .honeypot {
        position: absolute;
        left: -5000px;
        top: -5000px;
    }

    /* --- Links --- */
    a {
      color: var(--primary-color);
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    .footer-link {
      margin-top: 30px;
      color: var(--subtext-color);
      font-size: 0.9rem;
    }
    
    /* --- Modal Styles --- */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s, visibility 0.3s;
    }
    
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 12px;
      max-width: 600px;
      width: 90%;
      position: relative;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transform: scale(0.9);
      transition: transform 0.3s;
      text-align: left;
    }
    
    .modal-overlay.active .modal-content {
        transform: scale(1);
    }

    .close-modal {
      position: absolute;
      top: 10px;
      right: 15px;
      background: none;
      border: none;
      font-size: 2rem;
      color: #aaa;
      cursor: pointer;
    }
    
    .modal-body {
        max-height: 60vh;
        overflow-y: auto;
        margin-top: 20px;
        font-size: 0.9rem;
        line-height: 1.6;
        color: var(--subtext-color);
    }
  </style>
</head>
<body>

<div class="register-container">
  <div class="register-card">
    <div class="brand-lockup">
      <img src="/logo.png?v=<?php echo rawurlencode($logoVersion); ?>" class="auth-logo" alt="Logbook logo" width="72" height="72">
      <span class="brand-name">Logbook</span>
    </div>
    
    <h1>Create Your Account</h1>
    <p class="subtitle">Join Logbook today. It's free and always will be.</p>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px 15px;border-radius:8px;margin-bottom:18px;background:#fdecea;color:#d93025;text-align:left;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px 15px;border-radius:8px;margin-bottom:18px;background:#e9f7ef;color:#1e7e34;text-align:left;">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></span>
      </div>
    <?php endif; ?>
    
    <form action="/register" method="post" class="register-form">
      <?php echo Security::csrfField(); ?>
      
      <!-- Honeypot Field for Spam Protection -->
      <div class="honeypot">
          <label for="website_url">Do not fill this out</label>
          <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-group">
        <i class="fa-solid fa-user input-icon"></i>
        <input type="text" name="name" placeholder="Full Name" autocomplete="name" required>
      </div>

      <div class="form-group">
        <i class="fa-solid fa-envelope input-icon"></i>
        <input type="email" name="email" placeholder="Email Address" autocomplete="email" required>
      </div>
      
      <div class="form-group">
        <i class="fa-solid fa-phone input-icon"></i>
        <input type="text" name="phone" placeholder="Phone (Optional)" autocomplete="tel">
      </div>

      <div class="form-group">
        <i class="fa-solid fa-lock input-icon"></i>
        <input type="password" name="password" placeholder="Password" autocomplete="new-password" minlength="8" required>
      </div>
      
      <div class="form-group-checkbox">
          <input type="checkbox" id="terms" name="terms" required>
          <label for="terms">I agree to the <a href="#" id="termsLink">Terms and Conditions</a></label>
      </div>

      <button class="btn disable-on-click" type="submit">Create Account</button>
    </form>

    <p class="footer-link">
      Already have an account? <a href="/login">Sign In</a>
    </p>
  </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal-overlay" id="termsModal">
  <div class="modal-content">
    <button class="close-modal" id="closeTermsModal">&times;</button>
    <h2>Terms and Conditions</h2>
    <div class="modal-body">
        <p><strong>Last Updated: 15-Nov-2025</strong></p>
        <p>Welcome to LogBook! These terms and conditions outline the rules and regulations for the use of our website and services.</p>
        
        <h3>1. Acceptance of Terms</h3>
        <p>By accessing this website, we assume you accept these terms and conditions. Do not continue to use LogBook if you do not agree to all of the terms and conditions stated on this page.</p>

        <h3>2. License to Use</h3>
        <p>Unless otherwise stated, LogBook and its licensor own the intellectual property rights for all material on LogBook. All intellectual property rights are reserved. You may access this from Halkhata for your own personal use subjected to restrictions set in these terms and conditions.</p>
        <p>You must not:</p>
        <ul>
            <li>Republish material from LogBook</li>
            <li>Sell, rent or sub-license material from LogBook</li>
            <li>Reproduce, duplicate or copy material from LogBook</li>
            <li>Redistribute content from LogBook</li>
        </ul>

        <h3>3. User Accounts</h3>
        <p>You are responsible for safeguarding the password that you use to access the service and for any activities or actions under your password. You agree not to disclose your password to any third party. You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.</p>
        
        <h3>4. Limitation of Liability</h3>
        <p>In no event shall LogBook, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses...</p>
        
        <p><em>To delete your account from LogBook, request a deletion by mailing support@YOURDOMAIN and report abuse at abuse@YOURDOMAIN.</em></p>
    </div>
  </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', () => {

    const registerForm = document.querySelector('.register-form');
    const honeypotInput = document.getElementById('website_url');

    // --- Honeypot Spam Prevention ---
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            // Check if the honeypot field is filled out
            if (honeypotInput.value.length > 0) {
                // It's likely a bot, so we block the submission silently
                console.log('Honeypot triggered. Form submission blocked.');
                e.preventDefault(); 
                return false;
            }
            
            // --- Disable Button on Valid Submit ---
            const submitButton = registerForm.querySelector('.disable-on-click');
            if (submitButton) {
                // Small delay to ensure form data is captured before disabling
                setTimeout(() => {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Creating Account...';
                }, 10);
            }
        });
    }

    // --- Terms and Conditions Modal Functionality ---
    const termsModal = document.getElementById('termsModal');
    const termsLink = document.getElementById('termsLink');
    const closeTermsModal = document.getElementById('closeTermsModal');

    if (termsModal && termsLink && closeTermsModal) {
      const openModal = (e) => {
        e.preventDefault();
        termsModal.classList.add('active');
      };

      const closeModal = () => {
        termsModal.classList.remove('active');
      };

      termsLink.addEventListener('click', openModal);
      closeTermsModal.addEventListener('click', closeModal);

      // Close modal by clicking outside
      termsModal.addEventListener('click', (e) => {
        if (e.target === termsModal) {
          closeModal();
        }
      });

      // Close modal with Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && termsModal.classList.contains('active')) {
          closeModal();
        }
      });
    }
  });
</script>

</body>
</html>

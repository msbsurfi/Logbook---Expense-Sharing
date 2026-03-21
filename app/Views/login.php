<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="/logo.png">
  <title>Login - LogBook</title>
  
  <!-- Font Awesome for icons (This is the only external link, as embedding an icon library is impractical) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* --- CSS STYLES --- */

    /* --- Global Styles & Variables --- */
    :root {
      --primary-color: #b8860b;
      --primary-hover: #906707;
      --background-color: #f6f1e6;
      --card-background: rgba(255, 251, 245, 0.98);
      --text-color: #1f1b16;
      --subtext-color: #6b5f51;
      --border-color: rgba(97, 74, 28, 0.16);
      --success-bg: rgba(20, 125, 82, 0.12);
      --success-color: #0c5c3b;
      --error-bg: rgba(194, 65, 53, 0.12);
      --error-color: #8d2d24;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background:
        radial-gradient(circle at top left, rgba(184, 134, 11, 0.12), transparent 30%),
        var(--background-color);
      color: var(--text-color);
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    /* --- Main Container & Card --- */
    .login-container {
      max-width: 420px;
      width: 100%;
      padding: 20px;
    }

    .login-card {
      background-color: var(--card-background);
      border: 1px solid var(--border-color);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 24px 50px rgba(35, 27, 15, 0.12);
      text-align: center;
    }

    .logo {
      max-width: 150px;
      margin-bottom: 20px;
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

    /* --- Alerts --- */
    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: left;
      font-size: 0.9rem;
    }

    .error-box {
      background-color: var(--error-bg);
      color: var(--error-color);
    }

    .info-box {
      background-color: var(--success-bg);
      color: var(--success-color);
    }

    /* --- Form Elements --- */
    .login-form {
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

    .form-group input {
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

    .toggle-password {
      position: absolute;
      top: 50%;
      right: 15px;
      transform: translateY(-50%);
      color: #aaa;
      cursor: pointer;
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
    
    .btn.disable-on-click:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    /* --- Links --- */
    .links-container {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      font-size: 0.875rem;
    }

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
      max-width: 400px;
      width: 90%;
      position: relative;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transform: scale(0.9);
      transition: transform 0.3s;
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

    .modal-content h2 {
      margin-top: 0;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <img src="/logo.png" style="height: 40px;">
    
    <h1>Welcome Back</h1>
    <p class="subtitle">Sign in to continue to LogBook.</p>

    <?php require_once __DIR__ . '/../Lib/Security.php'; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert error-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="alert info-box">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
      </div>
    <?php endif; ?>

    <form action="/login" method="post" class="login-form">
      <?php echo Security::csrfField(); ?>
      
      <div class="form-group">
        <i class="fa-solid fa-envelope input-icon"></i>
        <input type="email" name="email" placeholder="Email Address" required>
      </div>

      <div class="form-group">
        <i class="fa-solid fa-lock input-icon"></i>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword"></i>
      </div>

      <button class="btn disable-on-click" type="submit">Sign In</button>
    </form>

    <div class="links-container">
      <a href="/forgot-password">Forgot Password?</a>
      <a href="#" id="resendVerificationLink">Resend Verification</a>
    </div>

    <p class="footer-link">
      Don't have an account? <a href="/register">Sign Up</a>
    </p>
  </div>
</div>

<!-- Resend Verification Modal -->
<div class="modal-overlay" id="resendModal">
  <div class="modal-content">
    <button class="close-modal" id="closeModal">&times;</button>
    <h2>Resend Verification Email</h2>
    <p>Enter your email address and we'll send you a new verification link.</p>
    <form action="/resend-verification" method="post">
      <?php echo Security::csrfField(); ?>
      <div class="form-group">
        <i class="fa-solid fa-envelope input-icon"></i>
        <input type="email" name="email" placeholder="Email Address" required>
      </div>
      <button type="submit" class="btn disable-on-click">Send Verification Link</button>
    </form>
  </div>
</div>

<script>
  // --- JAVASCRIPT LOGIC ---
  document.addEventListener('DOMContentLoaded', () => {

    // --- Password Toggle Functionality ---
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    if (togglePassword && password) {
      togglePassword.addEventListener('click', function () {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        // Toggle the icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
      });
    }

    // --- Disable Button on Form Submit ---
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('.disable-on-click');
            if (submitButton) {
                // Small delay to ensure form data is captured before disabling
                setTimeout(() => {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Processing...';
                }, 10);
            }
        });
    });

    // --- Resend Verification Modal Functionality ---
    const resendModal = document.getElementById('resendModal');
    const resendLink = document.getElementById('resendVerificationLink');
    const closeModal = document.getElementById('closeModal');
    const modalEmailInput = resendModal.querySelector('input[name="email"]');

    if (resendModal && resendLink && closeModal) {
      // Function to open the modal
      const openModal = (e) => {
        e.preventDefault();
        
        // Pre-fill email from main form if it exists
        const mainEmailValue = document.querySelector('.login-form input[name="email"]').value;
        if (mainEmailValue) {
            modalEmailInput.value = mainEmailValue;
        }
        
        resendModal.classList.add('active');
        modalEmailInput.focus();
      };

      // Function to close the modal
      const closeModalFunc = () => {
        resendModal.classList.remove('active');
      };

      // Event listeners
      resendLink.addEventListener('click', openModal);
      closeModal.addEventListener('click', closeModalFunc);

      // Close modal if user clicks outside of the modal content
      resendModal.addEventListener('click', (e) => {
        if (e.target === resendModal) {
          closeModalFunc();
        }
      });

      // Close modal with the Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && resendModal.classList.contains('active')) {
          closeModalFunc();
        }
      });
    }
  });
</script>

</body>
</html>

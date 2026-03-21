<?php require_once __DIR__ . '/../Lib/Security.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="/logo.png">
  <title>Reset Password - Logbook</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer">
  <style>
    :root {
      --brand: #b8860b;
      --brand-dark: #906707;
      --bg: #f6f1e6;
      --card: rgba(255, 251, 245, 0.98);
      --text: #1f1b16;
      --muted: #6b5f51;
      --border: rgba(97, 74, 28, 0.16);
      --input: #fff9ef;
      --success-bg: rgba(20, 125, 82, 0.12);
      --success-text: #0c5c3b;
      --danger-bg: rgba(194, 65, 53, 0.12);
      --danger-text: #8d2d24;
      --shadow: 0 24px 50px rgba(35, 27, 15, 0.12);
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      background:
        radial-gradient(circle at top left, rgba(184, 134, 11, 0.12), transparent 30%),
        var(--bg);
      color: var(--text);
      font-family: "Segoe UI", "Trebuchet MS", system-ui, sans-serif;
    }

    .auth-shell { width: min(100%, 440px); }
    .auth-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 36px;
      box-shadow: var(--shadow);
    }

    .logo {
      display: inline-grid;
      place-items: center;
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: linear-gradient(135deg, var(--brand), #d2a53c);
      color: #fff;
      font-size: 1.2rem;
      font-weight: 800;
      margin-bottom: 20px;
    }

    h1 { margin: 0 0 10px; font-size: 2rem; }
    .subtitle { margin: 0 0 24px; color: var(--muted); line-height: 1.55; }

    .alert {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      padding: 14px 16px;
      border-radius: 16px;
      margin-bottom: 16px;
      line-height: 1.45;
    }

    .alert.error { background: var(--danger-bg); color: var(--danger-text); }

    .field { margin-bottom: 16px; }
    label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.92rem;
      font-weight: 600;
      color: var(--text);
    }

    .input-wrap { position: relative; }
    .input-wrap i {
      position: absolute;
      top: 50%;
      left: 14px;
      transform: translateY(-50%);
      color: var(--muted);
    }

    input {
      width: 100%;
      padding: 14px 14px 14px 44px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--input);
      font: inherit;
      color: var(--text);
    }

    input:focus {
      outline: none;
      border-color: rgba(184, 134, 11, 0.55);
      box-shadow: 0 0 0 4px rgba(184, 134, 11, 0.12);
    }

    .btn {
      width: 100%;
      border: none;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--brand), var(--brand-dark));
      color: #fff;
      padding: 14px 18px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
    }

    .btn:disabled { opacity: 0.7; cursor: not-allowed; }

    .helper {
      margin-top: 18px;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .helper a {
      color: var(--brand-dark);
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="logo">L</div>
      <h1>Create a new password</h1>
      <p class="subtitle">Set a new password for <?php echo htmlspecialchars($data['name'] ?? 'your account'); ?>.</p>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></span>
        </div>
      <?php endif; ?>

      <form action="/reset-password" method="post">
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token'] ?? '', ENT_QUOTES); ?>">

        <div class="field">
          <label for="password">New password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
          </div>
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-check-double"></i>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
          </div>
        </div>

        <button class="btn disable-on-click" type="submit">Update Password</button>
      </form>

      <p class="helper">Remembered it? <a href="/login">Back to login</a></p>
    </div>
  </div>

  <script>
    document.addEventListener('submit', function (event) {
      const form = event.target;
      const button = form.querySelector('.disable-on-click');
      if (!button) return;
      setTimeout(() => {
        button.disabled = true;
        button.textContent = 'Updating password...';
      }, 10);
    }, true);
  </script>
</body>
</html>

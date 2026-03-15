<?php
$title='Resend Verification';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/../Lib/Security.php';
?>
<h1>Resend Verification Email</h1>
<p class="text-muted">Enter your registered email to receive a fresh verification link (limited to 5/day, 5-minute intervals).</p>
<form action="/resend-verification" method="post" class="card">
  <?php echo Security::csrfField(); ?>
  <input type="email" name="email" placeholder="Your registered email" required>
  <button class="btn disable-on-click" type="submit"><i class="fa-solid fa-envelope"></i> Resend Email</button>
</form>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
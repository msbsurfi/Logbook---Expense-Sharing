<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';
require_once __DIR__ . '/../Lib/EmailTemplate.php';

class UserController {
    private User $userModel;

    public function __construct(){
        Security::ensureSession();
        $this->userModel = new User();
    }

    public function showRegistrationForm(){
        require_once __DIR__ . '/../Views/register.php';
    }

    public function showLoginForm(){
        require_once __DIR__ . '/../Views/login.php';
    }

    public function showForgotPasswordForm(){
        require_once __DIR__ . '/../Views/forget-password.php';
    }

    public function showResetPasswordForm(){
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            $_SESSION['flash_error'] = 'The password reset link is invalid or has expired.';
            header('Location:/forgot-password');
            return;
        }

        $tokenHash = hash('sha256', $token);
        $resetRequest = $this->userModel->findPasswordResetByTokenHash($tokenHash);
        if (!$resetRequest) {
            $_SESSION['flash_error'] = 'The password reset link is invalid or has expired.';
            header('Location:/forgot-password');
            return;
        }

        $data = [
            'token' => $token,
            'name' => $resetRequest->name ?? 'User',
        ];
        require_once __DIR__ . '/../Views/reset-password.php';
    }

    public function register(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/register'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/register'); return;
        }

        $name     = trim($_POST['name'] ?? '');
        $emailRaw = trim($_POST['email'] ?? '');
        $email    = strtolower($emailRaw);
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $honeypot = trim($_POST['website_url'] ?? '');
        $acceptedTerms = !empty($_POST['terms']);

        if ($honeypot !== '') {
            header('Location:/register');
            return;
        }

        if (!$name || !$email || !$password){
            $_SESSION['flash_error'] = 'All required fields must be filled.';
            header('Location:/register'); return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            header('Location:/register'); return;
        }

        if (!$acceptedTerms) {
            $_SESSION['flash_error'] = 'You must accept the terms and conditions to register.';
            header('Location:/register'); return;
        }

        if (!Mailer::isConfigured()) {
            $_SESSION['flash_error'] = 'Registration is temporarily unavailable until an administrator configures email delivery.';
            header('Location:/register'); return;
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Password must be at least 8 characters long.';
            header('Location:/register'); return;
        }

        if ($this->userModel->findUserByEmail($email)){
            $_SESSION['flash_error'] = 'Email already registered. Please login or use password reset.';
            header('Location:/login'); return;
        }

        $profileCode        = $this->userModel->generateUniqueProfileCode($name);
        $verificationToken  = bin2hex(random_bytes(16));
        $hashed             = password_hash($password, PASSWORD_DEFAULT);

        $data = [
            'name'              => $name,
            'email'             => $email,
            'phone'             => $phone,
            'password'          => $hashed,
            'profile_code'      => $profileCode,
            'verification_token'=> $verificationToken,
        ];

        if (!$this->userModel->createUserWithVerification($data)){
            $_SESSION['flash_error'] = 'Registration failed (database error). Please try again.';
            header('Location:/register'); return;
        }

        $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                   . ':

        $verifyBtn = "<div style='text-align:center;margin:24px 0;'><a href='{$verifyUrl}' style='background-color:
        $html = EmailTemplate::generate(
            'Verify Your Logbook Account',
            $name,
            'Welcome to Logbook! Please verify your email address to activate your account.',
            $verifyBtn . 'After verification, an admin will review and approve your account before you can log in.',
            [
                'Name' => htmlspecialchars($name),
                'Email' => htmlspecialchars($email),
                'Link Valid' => 'Single use only'
            ]
        );

        $mailer = new Mailer();
        $mailer->send($email,$name,'Verify Your Logbook Account',$html);
        $this->userModel->logEmail(null,$email,'Verify Your Logbook Account');

        $_SESSION['flash_success'] = 'Registration successful. Check your email for verification link.';
        header('Location:/login');
    }

    public function verifyEmail(){
        $token = $_GET['token'] ?? '';
        if (!$token){
            echo 'Invalid verification token.'; return;
        }
        $user = $this->userModel->findByVerificationToken($token);
        if (!$user){
            echo 'Token invalid or already used.'; return;
        }
        if ($this->userModel->markEmailVerified($user->id)){
            $_SESSION['flash_success'] = 'Email verified. Await admin approval.';
            header('Location:/login');
        } else {
            echo 'Could not verify email.';
        }
    }

    public function resendVerification(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/login'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/login'); return;
        }

        $emailRaw = trim($_POST['email'] ?? '');
        $email    = strtolower($emailRaw);

        if (!$email){
            $_SESSION['flash_error'] = 'Email is required.';
            header('Location:/login'); return;
        }

        $user = $this->userModel->findUserByEmail($email);
        if (!$user){
            $_SESSION['flash_error'] = 'Email not found.';
            header('Location:/login'); return;
        }
        if ($user->email_verified){
            $_SESSION['flash_error'] = 'Email already verified. Please login.';
            header('Location:/login'); return;
        }
        if (!$this->userModel->updateResendVerificationStats($user->id)){
            $_SESSION['flash_error'] = 'Rate limit exceeded. Try again later.';
            header('Location:/login'); return;
        }

        if (!Mailer::isConfigured()) {
            $_SESSION['flash_error'] = 'Email delivery is not configured yet. Please contact an administrator.';
            header('Location:/login'); return;
        }

        $newToken = bin2hex(random_bytes(16));
        $db = new Database();
        $db->query("UPDATE users SET verification_token=:t WHERE id=:id");
        $db->bind(':t',$newToken);
        $db->bind(':id',$user->id);
        if (!$db->execute()){
            $_SESSION['flash_error'] = 'Could not generate new token.';
            header('Location:/login'); return;
        }

        $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                   . ':

        $verifyBtn = "<div style='text-align:center;margin:24px 0;'><a href='{$verifyUrl}' style='background-color:
        $html = EmailTemplate::generate(
            'Email Verification Link',
            $user->name,
            'You requested a new verification link for your Logbook account.',
            $verifyBtn . 'If you did not request this, please ignore this email.',
            [
                'Email' => htmlspecialchars($user->email),
                'Link Valid' => 'Single use only'
            ]
        );

        $mailer = new Mailer();
        $mailer->send($user->email,$user->name,'Email Verification Link',$html);
        $this->userModel->logEmail($user->id,$user->email,'Email Verification Link');

        $_SESSION['flash_success'] = 'Verification email resent.';
        header('Location:/login');
    }

    public function login(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/login'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/login'); return;
        }
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error']='Invalid credentials.'; header('Location:/login'); return;
        }

        $user = $this->userModel->findUserByEmail($email);
        if (!$user){
            $_SESSION['flash_error']='Invalid credentials.'; header('Location:/login'); return;
        }
        if (!$user->email_verified){
            $_SESSION['flash_error']='Please verify your email first.'; header('Location:/login'); return;
        }
        if ($user->banned_at){
            $_SESSION['flash_error']='Account banned: '.$user->ban_reason; header('Location:/login'); return;
        }
        if ($user->rejected_at){
            $_SESSION['flash_error']='Account rejected: '.$user->rejection_reason; header('Location:/login'); return;
        }
        if ($user->status !== 'active'){
            $_SESSION['flash_error']='Account not approved yet.'; header('Location:/login'); return;
        }
        if (!password_verify($password,$user->password)){
            $_SESSION['flash_error']='Invalid credentials.'; header('Location:/login'); return;
        }

        $this->setAuthenticatedSession($user);
        Security::markAuthenticatedSession();
        $_SESSION['flash_success']    = 'Login successful.';
        header('Location:/dashboard');
    }

    public function sendPasswordReset(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/forgot-password'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/forgot-password'); return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            header('Location:/forgot-password'); return;
        }

        if (!Mailer::isConfigured()) {
            $_SESSION['flash_error'] = 'Password reset is unavailable until email delivery is configured.';
            header('Location:/forgot-password'); return;
        }

        $user = $this->userModel->findUserByEmail($email);
        if ($user && !$user->banned_at && !$user->rejected_at) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            if (!$this->userModel->createPasswordReset($user->email, $tokenHash, $expiresAt)) {
                $_SESSION['flash_error'] = 'Unable to create a password reset request right now.';
                header('Location:/forgot-password'); return;
            }

            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
                . ':

            $resetBtn = "<div style='text-align:center;margin:24px 0;'><a href='{$resetUrl}' style='background-color:
            $html = EmailTemplate::generate(
                'Reset Your Logbook Password',
                $user->name,
                'We received a request to reset your Logbook password.',
                $resetBtn . 'This link expires in 1 hour. If you did not request a password reset, you can ignore this email.',
                [
                    'Email' => htmlspecialchars($user->email),
                    'Expires In' => '1 hour',
                ]
            );

            $mailer = new Mailer();
            $mailer->send($user->email, $user->name, 'Reset Your Logbook Password', $html);
            $this->userModel->logEmail($user->id, $user->email, 'Reset Your Logbook Password');
        }

        $_SESSION['flash_success'] = 'If that email exists in our system, a reset link has been sent.';
        header('Location:/forgot-password');
    }

    public function resetPassword(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/forgot-password'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/forgot-password'); return;
        }

        $token = trim($_POST['token'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['password_confirmation'] ?? '');

        if ($token === '') {
            $_SESSION['flash_error'] = 'The password reset link is invalid or has expired.';
            header('Location:/forgot-password'); return;
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Password must be at least 8 characters long.';
            header('Location:/reset-password?token=' . urlencode($token)); return;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            header('Location:/reset-password?token=' . urlencode($token)); return;
        }

        $tokenHash = hash('sha256', $token);
        $resetRequest = $this->userModel->findPasswordResetByTokenHash($tokenHash);
        if (!$resetRequest) {
            $_SESSION['flash_error'] = 'The password reset link is invalid or has expired.';
            header('Location:/forgot-password'); return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (
            !$this->userModel->updatePasswordByEmail($resetRequest->email, $passwordHash) ||
            !$this->userModel->deletePasswordReset($tokenHash)
        ) {
            $_SESSION['flash_error'] = 'Unable to reset your password right now.';
            header('Location:/reset-password?token=' . urlencode($token)); return;
        }

        $_SESSION['flash_success'] = 'Your password has been updated. You can sign in now.';
        header('Location:/login');
    }

    public function logout(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::validateCsrf($_POST['csrf_token'] ?? '')){
            header('Location:' . (!empty($_SESSION['user_id']) ? '/dashboard' : '/login'));
            return;
        }

        $impersonator = $_SESSION['impersonator_admin_id'] ?? null;
        $impersonated = $_SESSION['impersonated_user_id'] ?? null;
        Security::destroySession();
        Security::ensureSession();

        if ($impersonator && $impersonated){
            $_SESSION['flash_success']='Impersonation session ended.';
        } else {
            $_SESSION['flash_success']='Signed out successfully.';
        }
        header('Location:/login');
    }

    public function showDashboard(){
        if (!isset($_SESSION['user_id'])){ header('Location:/login'); return; }
        $friendModel      = new Friend();
        $transactionModel = new Transaction();
        $userId           = $_SESSION['user_id'];

        $balances = $transactionModel->getNetBalances($userId);
        $totalOwedByYou=0; $totalOwedToYou=0;
        foreach($balances as $b){
            if ($b['balance']<0) $totalOwedByYou += abs($b['balance']);
            else $totalOwedToYou += $b['balance'];
        }
        $data = [
            'friends'=>$friendModel->getAcceptedFriends($userId),
            'balances'=>$balances,
            'totalOwedByYou'=>$totalOwedByYou,
            'totalOwedToYou'=>$totalOwedToYou
        ];
        require_once __DIR__ . '/../Views/dashboard.php';
    }

    private function setAuthenticatedSession(object $user): void {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_email'] = $user->email ?? '';
        $_SESSION['user_profile_code'] = $user->profile_code ?? '';
        $_SESSION['user_role'] = $user->role ?? 'user';
    }
}

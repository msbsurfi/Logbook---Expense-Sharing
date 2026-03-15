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

    public function register(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location:/register'); return;
        }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location:/register'); return;
        }

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $name     = trim($_POST['name'] ?? '');
        $emailRaw = trim($_POST['email'] ?? '');
        $email    = strtolower($emailRaw);
        $phone    = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$name || !$email || !$password){
            $_SESSION['flash_error'] = 'All required fields must be filled.';
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
                   . '://'.$_SERVER['HTTP_HOST'].'/verify?token='.$verificationToken;

        $verifyBtn = "<div style='text-align:center;margin:24px 0;'><a href='{$verifyUrl}' style='background-color:#C9A227;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block;'>Verify My Email</a></div>";
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
                   . '://'.$_SERVER['HTTP_HOST'].'/verify?token='.$newToken;

        $verifyBtn = "<div style='text-align:center;margin:24px 0;'><a href='{$verifyUrl}' style='background-color:#C9A227;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block;'>Verify My Email</a></div>";
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
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

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

        $_SESSION['user_id']          = $user->id;
        $_SESSION['user_name']        = $user->name;
        $_SESSION['user_profile_code']= $user->profile_code;
        $_SESSION['user_role']        = $user->role;
        $_SESSION['flash_success']    = 'Login successful.';
        header('Location:/dashboard');
    }

    public function logout(){
        $impersonator = $_SESSION['impersonator_admin_id'] ?? null;
        $impersonated = $_SESSION['impersonated_user_id'] ?? null;
        session_unset();
        session_destroy();
        session_start();
        if ($impersonator && $impersonated){
            $_SESSION['flash_success']='Impersonation session ended.';
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
}
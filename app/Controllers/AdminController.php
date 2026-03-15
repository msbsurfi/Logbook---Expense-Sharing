<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';
require_once __DIR__ . '/../Lib/Logger.php';
require_once __DIR__ . '/../Lib/EmailTemplate.php';

class AdminController {
    private $userModel;
    private $transactionModel;
    private $notificationModel;
    private $logger;

    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin'){
            $_SESSION['flash_error'] = "Admin access required.";
            header('Location:/dashboard');
            exit();
        }
        $this->userModel = new User();
        $this->transactionModel = new Transaction();
        $this->notificationModel = new Notification();
        $this->logger = new Logger();
    }

    public function index() {
        $tab = $_GET['tab'] ?? 'dashboard';
        $validTabs = ['dashboard', 'users', 'logs', 'settings'];
        if (!in_array($tab, $validTabs)) $tab = 'dashboard';
    
        $data = ['tab' => $tab];
        $page = max(1, (int)($_GET['page'] ?? 1));
    
        switch ($tab) {
            case 'dashboard':
                $data['pending'] = $this->userModel->getPendingUsers();
                $data['stats'] = $this->collectStats(); 
                break;
    
            case 'users':
                $perPage = 25;
                $filters = [
                    'role'   => $this->sanitizeFilter($_GET['role'] ?? ''),
                    'status' => $this->sanitizeFilter($_GET['status'] ?? ''),
                    'search' => trim($_GET['search'] ?? '')
                ];
                
                $totalUsers = $this->userModel->countFilteredUsers($filters);
                $data['users'] = $this->userModel->listUsersPaginated($filters, $page, $perPage);
                $data['totalPages'] = (int)ceil($totalUsers / $perPage);
                $data['page'] = $page;
                $data['filters'] = $filters;
                break;
    
            case 'logs':
                $perPage = 50;
                $offset = ($page - 1) * $perPage;
                
                $db = new Database();
                $db->query("SELECT COUNT(*) AS c FROM admin_action_logs");
                $totalActions = (int)$db->fetchOne()->c;
    
                $db->query("SELECT l.id, l.admin_id, u.name AS admin_name, l.action, l.meta, l.created_at
                            FROM admin_action_logs l
                            LEFT JOIN users u ON l.admin_id = u.id
                            ORDER BY l.created_at DESC
                            LIMIT :offset, :limit");
                $db->bind(':offset', $offset); 
                $db->bind(':limit', $perPage);
                $data['actions'] = $db->fetchAll();
                $data['totalPages'] = (int)ceil($totalActions / $perPage);
                $data['totalActions'] = $totalActions;
                $data['page'] = $page;
                break;

            case 'settings':
                $data['mailConfig'] = [
                    'host'       => SMTP_HOST,
                    'user'       => SMTP_USER,
                    'port'       => SMTP_PORT,
                    'from_email' => SMTP_FROM_EMAIL,
                    'from_name'  => SMTP_FROM_NAME,
                    'secure'     => SMTP_SECURE ?? 'ssl',
                ];
                break;
        }
    
        require_once __DIR__ . '/../Views/admin/index.php';
    }

    private function collectStats(){
        $db = new Database();
        
        $db->query("SELECT COUNT(*) AS total_tx, SUM(amount) AS sum_amt FROM transactions");
        $all = $db->fetchOne();

        $db->query("SELECT COUNT(*) AS weekly_tx, SUM(amount) AS weekly_sum FROM transactions WHERE created_at >= (NOW() - INTERVAL 7 DAY)");
        $wk = $db->fetchOne();

        $db->query("SELECT COUNT(*) AS monthly_tx, SUM(amount) AS monthly_sum FROM transactions WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
        $mo = $db->fetchOne();

        $db->query("SELECT COUNT(*) AS weekly_emails FROM email_log WHERE sent_at >= (NOW() - INTERVAL 7 DAY)");
        $we = $db->fetchOne();
        $db->query("SELECT COUNT(*) AS monthly_emails FROM email_log WHERE sent_at >= (NOW() - INTERVAL 30 DAY)");
        $me = $db->fetchOne();

        return [
            'total_tx'      => (int)($all->total_tx ?? 0),
            'total_sum'     => (float)($all->sum_amt ?? 0),
            'weekly_tx'     => (int)($wk->weekly_tx ?? 0),
            'weekly_sum'    => (float)($wk->weekly_sum ?? 0),
            'monthly_tx'    => (int)($mo->monthly_tx ?? 0),
            'monthly_sum'   => (float)($mo->monthly_sum ?? 0),
            'weekly_emails' => (int)($we->weekly_emails ?? 0),
            'monthly_emails'=> (int)($me->monthly_emails ?? 0)
        ];
    }

    private function sanitizeFilter($value){
        return preg_replace('/[^a-zA-Z0-9_]/', '', trim($value));
    }

    private function checkCsrf() {
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Security token expired.';
            $this->redirectBack();
            exit();
        }
    }

    private function redirectBack() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
        if (strpos($referer, $_SERVER['HTTP_HOST']) === false) {
             $referer = '/admin';
        }
        header("Location: $referer");
        exit();
    }

    public function approve($id){
        $this->checkCsrf();
        if ($this->userModel->approveUser($id)){
            $this->logger->logAdminAction($_SESSION['user_id'], 'approve_user', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User approved.";
        } else {
            $_SESSION['flash_error'] = "Action failed.";
        }
        $this->redirectBack();
    }

    public function reject($id){
        $this->checkCsrf();
        if ($this->userModel->rejectUser($id, "Admin rejected")){
            $this->logger->logAdminAction($_SESSION['user_id'], 'reject_user', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User rejected.";
        }
        $this->redirectBack();
    }

    public function ban($id){
        $this->checkCsrf();
        if ($this->userModel->banUser($id, "Admin ban")){
            $this->logger->logAdminAction($_SESSION['user_id'], 'ban_user', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User banned.";
        }
        $this->redirectBack();
    }

    public function unban($id){
        $this->checkCsrf();
        if ($this->userModel->unbanUser($id)){
            $this->logger->logAdminAction($_SESSION['user_id'], 'unban_user', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User unbanned.";
        }
        $this->redirectBack();
    }

    public function promote($id){
        $this->checkCsrf();
        if ($this->userModel->promoteToAdmin($id)){
            $this->logger->logAdminAction($_SESSION['user_id'], 'promote_admin', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User is now an Admin.";
        }
        $this->redirectBack();
    }

    public function demote($id){
        $this->checkCsrf();
        if ($this->userModel->demoteFromAdmin($id)){
            $this->logger->logAdminAction($_SESSION['user_id'], 'demote_admin', ['target_id' => $id]);
            $_SESSION['flash_success'] = "User demoted to standard role.";
        }
        $this->redirectBack();
    }

    public function impersonate($id){
        $this->checkCsrf();
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "Cannot impersonate yourself.";
            $this->redirectBack();
        }

        if (empty($_SESSION['impersonator_admin_id'])) {
            $_SESSION['impersonator_admin_id'] = $_SESSION['user_id'];
        }

        $_SESSION['user_id'] = $id;
        $this->logger->logAdminAction($_SESSION['impersonator_admin_id'], 'start_impersonation', ['target_id' => $id]);
        $_SESSION['flash_success'] = "Now viewing as user ID: $id";
        header('Location: /dashboard');
        exit();
    }

    public function stopImpersonation(){
        if (!empty($_SESSION['impersonator_admin_id'])) {
            $adminId = $_SESSION['impersonator_admin_id'];

            $_SESSION['user_id'] = $adminId;
            $_SESSION['user_role'] = 'admin';

            $this->logger->logAdminAction($adminId, 'stop_impersonation', ['target_id' => $_SESSION['user_id']]);

            unset($_SESSION['impersonator_admin_id']);

            $_SESSION['flash_success'] = "Welcome back, Admin.";
            header('Location: /admin');
            exit();
        }
        header('Location: /dashboard');
    }

    public function analyticsData(){
        header('Content-Type: application/json');
        $db = new Database();
        $db->query("
            SELECT DATE(created_at) AS d, COUNT(*) AS c, SUM(amount) AS s
            FROM transactions
            WHERE created_at >= (NOW() - INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        $rows = $db->fetchAll();
        $labels=[]; $counts=[]; $sums=[];
        foreach($rows as $r){
            $labels[]=$r->d; $counts[]=(int)$r->c; $sums[]=(float)$r->s;
        }
        echo json_encode(['labels'=>$labels,'counts'=>$counts,'sums'=>$sums]);
        exit();
    }

    public function exportUsersCsv(){
        $filters = [
            'role'   => $this->sanitizeFilter($_GET['role'] ?? ''),
            'status' => $this->sanitizeFilter($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? '')
        ];
        
        $users = $this->userModel->listUsersPaginated($filters, 1, 100000); 

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_'.date('Y-m-d').'.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Email', 'Role', 'Status', 'Registered At']);
        
        foreach($users as $u){
            fputcsv($out, [
                $u->id, 
                $u->name, 
                $u->email, 
                $u->role, 
                $u->status, 
                $u->created_at
            ]);
        }
        fclose($out);
        $this->logger->logAdminAction($_SESSION['user_id'], 'export_users_csv', ['count' => count($users)]);
        exit();
    }

    public function exportTransactionsCsv(){
        $db = new Database();
        $sql = "SELECT t.id, 
                       l.name as lender, 
                       b.name as borrower, 
                       t.amount, 
                       t.description, 
                       t.status, 
                       t.created_at
                FROM transactions t
                JOIN users l ON t.lender_id = l.id
                JOIN users b ON t.borrower_id = b.id
                ORDER BY t.created_at DESC
                LIMIT 10000";
        
        $db->query($sql);
        $rows = $db->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_export_'.date('Y-m-d').'.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Lender', 'Borrower', 'Amount', 'Description', 'Status', 'Date']);
        
        foreach($rows as $r){
            fputcsv($out, [
                $r->id, $r->lender, $r->borrower, $r->amount, $r->description, $r->status, $r->created_at
            ]);
        }
        fclose($out);
        $this->logger->logAdminAction($_SESSION['user_id'], 'export_transactions_csv', ['count' => count($rows)]);
        exit();
    }
    
    public function exportExpensesCsv(){
        $db = new Database();
        $sql = "SELECT e.id, e.description, e.total_amount, u.name as creator, e.created_at
                FROM expenses e
                JOIN users u ON e.created_by_user_id = u.id
                ORDER BY e.created_at DESC
                LIMIT 5000";
        
        $db->query($sql);
        $rows = $db->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expenses_export_'.date('Y-m-d').'.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Description', 'Total Amount', 'Creator', 'Date']);
        
        foreach($rows as $r){
            fputcsv($out, [
                $r->id, $r->description, $r->total_amount, $r->creator, $r->created_at
            ]);
        }
        fclose($out);
        $this->logger->logAdminAction($_SESSION['user_id'], 'export_expenses_csv', ['count' => count($rows)]);
        exit();
    }

    public function saveSettings(){
        $this->checkCsrf();
        $host      = trim($_POST['smtp_host'] ?? '');
        $user      = filter_var(trim($_POST['smtp_user'] ?? ''), FILTER_VALIDATE_EMAIL);
        $pass      = $_POST['smtp_pass'] ?? '';
        $port      = (int)($_POST['smtp_port'] ?? 465);
        $fromEmail = filter_var(trim($_POST['smtp_from_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $fromName  = preg_replace('/[^a-zA-Z0-9 _\-]/', '', trim($_POST['smtp_from_name'] ?? 'Logbook'));
        $secure    = in_array($_POST['smtp_secure'] ?? 'ssl', ['ssl', 'tls']) ? $_POST['smtp_secure'] : 'ssl';

        if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $host)) {
            $_SESSION['flash_error'] = 'SMTP host must be a valid hostname (letters, digits, dots, hyphens only).';
            header('Location:/admin?tab=settings');
            exit();
        }

        if ($port < 1 || $port > 65535) {
            $_SESSION['flash_error'] = 'SMTP port must be between 1 and 65535.';
            header('Location:/admin?tab=settings');
            exit();
        }

        if (!$host || !$user || !$fromEmail){
            $_SESSION['flash_error'] = 'Host, a valid SMTP username email, and a valid from-email are required.';
            header('Location:/admin?tab=settings');
            exit();
        }

        if (empty($pass)){
            $pass = SMTP_PASS;
        }

        $configPath = __DIR__ . '/../../config/mail.php';
        $content = "<?php\n"
            . "define('SMTP_HOST', " . var_export($host, true) . ");\n"
            . "define('SMTP_USER', " . var_export($user, true) . ");\n"
            . "define('SMTP_PASS', " . var_export($pass, true) . ");\n"
            . "define('SMTP_PORT', " . $port . ");\n"
            . "define('SMTP_FROM_EMAIL', " . var_export($fromEmail, true) . ");\n"
            . "define('SMTP_FROM_NAME', " . var_export($fromName, true) . ");\n"
            . "define('SMTP_SECURE', " . var_export($secure, true) . ");\n";

        if (file_put_contents($configPath, $content) !== false){
            $this->logger->logAdminAction($_SESSION['user_id'], 'update_mail_settings', [
                'host' => $host, 'user' => $user, 'port' => $port
            ]);
            $_SESSION['flash_success'] = 'Mail settings updated successfully.';
        } else {
            $_SESSION['flash_error'] = 'Could not write configuration file. Check file permissions.';
        }
        header('Location:/admin?tab=settings');
        exit();
    }
}
?>
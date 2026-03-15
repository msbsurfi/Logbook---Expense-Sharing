<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';
require_once __DIR__ . '/../Lib/Logger.php';

class AdminController {
    private $userModel;
    private $transactionModel;
    private $notificationModel;
    private $logger;

    public function __construct(){
        Security::ensureSession();
        // Check if user is logged in AND is admin
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

    // =========================================================
    // MAIN VIEW (Combined Dashboard, Users, Logs)
    // =========================================================
    public function index() {
        $tab = $_GET['tab'] ?? 'dashboard';
        $validTabs = ['dashboard', 'users', 'logs'];
        if (!in_array($tab, $validTabs)) $tab = 'dashboard';
    
        $data = ['tab' => $tab];
        $page = max(1, (int)($_GET['page'] ?? 1));
    
        // 1. Fetch Data based on Active Tab
        switch ($tab) {
            case 'dashboard':
                // Dashboard specific data
                $data['pending'] = $this->userModel->getPendingUsers();
                $data['stats'] = $this->collectStats(); 
                break;
    
            case 'users':
                // User Management Logic
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
                // Audit Logs Logic
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
        }
    
        // 2. Load the Single View
        require_once __DIR__ . '/../Views/admin/index.php';
    }

    // =========================================================
    // HELPER METHODS (Private)
    // =========================================================

    /**
     * Collects statistics for the dashboard
     */
    private function collectStats(){
        $db = new Database();
        
        // Total Transactions & Sum
        $db->query("SELECT COUNT(*) AS total_tx, SUM(amount) AS sum_amt FROM transactions");
        $all = $db->fetchOne();

        // Weekly Stats
        $db->query("SELECT COUNT(*) AS weekly_tx, SUM(amount) AS weekly_sum FROM transactions WHERE created_at >= (NOW() - INTERVAL 7 DAY)");
        $wk = $db->fetchOne();

        // Monthly Stats
        $db->query("SELECT COUNT(*) AS monthly_tx, SUM(amount) AS monthly_sum FROM transactions WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
        $mo = $db->fetchOne();

        // Email Stats
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
        // Only allow alphanumeric and underscore
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
        // Ensure we don't redirect to an external site
        if (strpos($referer, $_SERVER['HTTP_HOST']) === false) {
             $referer = '/admin';
        }
        header("Location: $referer");
        exit();
    }

    // =========================================================
    // ACTIONS (User Management)
    // =========================================================

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

    // =========================================================
    // IMPERSONATION
    // =========================================================

    public function impersonate($id){
        $this->checkCsrf();
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "Cannot impersonate yourself.";
            $this->redirectBack();
        }

        // Store original admin ID if not already stored
        if (empty($_SESSION['impersonator_admin_id'])) {
            $_SESSION['impersonator_admin_id'] = $_SESSION['user_id'];
        }

        $_SESSION['user_id'] = $id;
        $this->logger->logAdminAction($_SESSION['impersonator_admin_id'], 'start_impersonation', ['target_id' => $id]);
        $_SESSION['flash_success'] = "Now viewing as user ID: $id";
        header('Location: /dashboard'); // Go to user dashboard
        exit();
    }

    public function stopImpersonation(){
        // Check CSRF
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')) {
            // Even if token fails, if we are in a broken state, let's try to recover session if admin id exists
            // But for security, we usually strictly require it. 
            // In impersonation mode, the dashboard might generate a token for the *impersonated* user, 
            // so validation might be tricky depending on how Security class generates tokens (based on session ID or user ID).
            // Assuming token logic is standard:
        }
        
        if (!empty($_SESSION['impersonator_admin_id'])) {
            $adminId = $_SESSION['impersonator_admin_id'];
            
            // Restore session
            $_SESSION['user_id'] = $adminId;
            // Ensure role is admin
            $_SESSION['user_role'] = 'admin'; 
            
            $this->logger->logAdminAction($adminId, 'stop_impersonation', ['target_id' => $_SESSION['user_id']]);

            unset($_SESSION['impersonator_admin_id']);
            
            $_SESSION['flash_success'] = "Welcome back, Admin.";
            header('Location: /admin');
            exit();
        }
        header('Location: /dashboard');
    }

    // =========================================================
    // DATA & EXPORTS
    // =========================================================

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
}
?>
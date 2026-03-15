<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../app/Models/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Friend.php';
require_once __DIR__ . '/../app/Models/Transaction.php';
require_once __DIR__ . '/../app/Models/Expense.php';
require_once __DIR__ . '/../app/Models/Notification.php';

require_once __DIR__ . '/../app/Lib/Security.php';
require_once __DIR__ . '/../app/Lib/Logger.php';

require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/FriendController.php';
require_once __DIR__ . '/../app/Controllers/TransactionController.php';
require_once __DIR__ . '/../app/Controllers/ExpenseController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';
require_once __DIR__ . '/../app/Controllers/NotificationController.php';

$url = isset($_GET['url']) ? trim($_GET['url'],'/') : '';
$url = $url === '' ? 'home' : $url;
$parts = explode('/', $url);

switch($parts[0]){
    case 'register':
        $c = new UserController();
        if ($_SERVER['REQUEST_METHOD']==='POST') $c->register(); else $c->showRegistrationForm();
        break;
        
    case 'verify':
        (new UserController())->verifyEmail();
        break;
        
    case 'resend-verification':
        $c = new UserController();
        if ($_SERVER['REQUEST_METHOD']==='POST') $c->resendVerification(); else require_once __DIR__ . '/../app/Views/resend_verification.php';
        break;
        
    case 'login':
        $c = new UserController();
        if ($_SERVER['REQUEST_METHOD']==='POST') $c->login(); else $c->showLoginForm();
        break;
        
    case 'logout':
        (new UserController())->logout();
        break;
        
    case 'dashboard':
        (new UserController())->showDashboard();
        break;
        
    case 'friends':
        $fc = new FriendController();
        // Handle Send
        if (isset($parts[1]) && $parts[1]==='send') $fc->sendRequest();
        // Handle Response (Accept/Decline)
        elseif (isset($parts[1]) && $parts[1]==='respond' && isset($parts[2]) && isset($parts[3])) $fc->respond($parts[2], $parts[3]);
        // Handle Unfriend
        elseif (isset($parts[1]) && $parts[1]==='unfriend' && isset($parts[2])) $fc->unfriend($parts[2]);
        // Handle Cancel Request (Added this for the Sent Requests feature)
        elseif (isset($parts[1]) && $parts[1]==='cancel' && isset($parts[2])) $fc->cancelRequest($parts[2]);
        // Default View
        else $fc->index();
        break;
        
    case 'transactions':
        $tc = new TransactionController();
        if (isset($parts[1]) && $parts[1]==='create') $tc->create();
        elseif (isset($parts[1]) && $parts[1]==='settle' && isset($parts[2])) $tc->showSettlePage($parts[2]);
        elseif (isset($parts[1]) && $parts[1]==='settle' && $_SERVER['REQUEST_METHOD']==='POST') $tc->settleUp();
        elseif (isset($parts[1]) && $parts[1]==='history') $tc->showHistory();
        else header('Location:/dashboard');
        break;
        
    case 'expenses':
        $ec = new ExpenseController();
        if (isset($parts[1]) && $parts[1]==='create'){
            if ($_SERVER['REQUEST_METHOD']==='POST') $ec->create(); else $ec->showCreateForm();
        } else header('Location:/dashboard');
        break;
        
    case 'admin':
        $ac = new AdminController();
        
        // If just '/admin', show the main combined index view
        if (!isset($parts[1])) {
            $ac->index();
        } else {
            switch ($parts[1]) {
                // Actions (POST)
                case 'approve':
                    if (isset($parts[2])) $ac->approve($parts[2]);
                    break;
                case 'reject':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->reject($parts[2]);
                    break;
                case 'ban':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->ban($parts[2]);
                    break;
                case 'unban':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->unban($parts[2]);
                    break;
                case 'promote':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->promote($parts[2]);
                    break;
                case 'demote':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->demote($parts[2]);
                    break;
                case 'impersonate':
                    if (isset($parts[2]) && $_SERVER['REQUEST_METHOD']==='POST') $ac->impersonate($parts[2]);
                    break;
                case 'stop-impersonation':
                    if ($_SERVER['REQUEST_METHOD']==='POST') $ac->stopImpersonation();
                    break;
                    
                // Data & Exports
                case 'analytics-data':
                    $ac->analyticsData();
                    break;
                case 'export-users-csv':
                    $ac->exportUsersCsv();
                    break;
                case 'export-transactions-csv':
                    $ac->exportTransactionsCsv();
                    break;
                case 'export-expenses-csv':
                    $ac->exportExpensesCsv();
                    break;
                    
                // Default fallback (e.g. /admin/logs -> redirects or handles via index if needed)
                default:
                    // If a specific legacy URL like /admin/logs is hit, we can either
                    // redirect to /admin?tab=logs or handle it here. 
                    // Since the controller expects $_GET['tab'], the clean way is to rely on /admin?tab=...
                    $ac->index(); 
                    break;
            }
        }
        break;
        
    case 'notifications':
        $nc = new NotificationController();
        if (isset($parts[1]) && $parts[1]==='unread-count') $nc->unreadCount();
        elseif (isset($parts[1]) && $parts[1]==='list') $nc->list();
        elseif (isset($parts[1]) && $parts[1]==='mark-read' && $_SERVER['REQUEST_METHOD']==='POST') $nc->markRead();
        else { header('HTTP/1.1 404 Not Found'); echo json_encode(['error'=>'Invalid endpoint']); }
        break;
        
    case 'home':
    default:
        header('Location:/dashboard'); exit();
        break;
}
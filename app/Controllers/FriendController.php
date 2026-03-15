<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';

class FriendController {
    private $friendModel;
    private $userModel;
    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id'])){ header('Location:/login'); exit(); }
        $this->friendModel = new Friend();
        $this->userModel   = new User();
    }

    public function index(){
        $userId = $_SESSION['user_id'];
        $data = [
            'friends'=>$this->friendModel->getAcceptedFriends($userId),
            'requests'=>$this->friendModel->getPendingRequests($userId)
        ];
        require_once __DIR__ . '/../Views/friends/index.php';
    }

    public function sendRequest(){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/friends'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Security token invalid.'; header('Location:/friends'); return;
        }
        $profileCode = trim($_POST['profile_code'] ?? '');
        $senderId = $_SESSION['user_id'];
        $receiver = $this->userModel->findUserByProfileCode($profileCode);
        if (!$receiver){ $_SESSION['flash_error']='User not found.'; header('Location:/friends'); return; }
        if ($receiver->id == $senderId){ $_SESSION['flash_error']='Cannot add yourself.'; header('Location:/friends'); return; }
        if ($this->friendModel->sendRequest($senderId,$receiver->id)){
            $_SESSION['flash_success']='Friend request sent.';
        } else {
            $_SESSION['flash_error']='Friendship already exists or pending.';
        }
        header('Location:/friends');
    }

    public function respond($id,$action){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/friends'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Security token invalid.'; header('Location:/friends'); return;
        }
        $currentUserId = $_SESSION['user_id'];
        if ($this->friendModel->respondRequest($id,$action,$currentUserId)){
            $_SESSION['flash_success']=ucfirst($action).'ed request.';
        } else {
            $_SESSION['flash_error']='Could not process.';
        }
        header('Location:/friends');
    }

    public function unfriend($friendId){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/friends'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Security token invalid.'; header('Location:/friends'); return;
        }
        $currentUserId = $_SESSION['user_id'];
        if ($this->friendModel->unfriend($currentUserId,$friendId,$currentUserId)){
            $_SESSION['flash_success']='Unfriended.';
        } else {
            $_SESSION['flash_error']='Unable to unfriend.';
        }
        header('Location:/friends');
    }
}
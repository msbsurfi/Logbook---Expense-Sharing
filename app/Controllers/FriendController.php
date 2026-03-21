<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';

class FriendController {
    private $friendModel;
    private $userModel;
    private $notificationModel;
    private $transactionModel;
    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id'])){ header('Location:/login'); exit(); }
        $this->friendModel = new Friend();
        $this->userModel   = new User();
        $this->notificationModel = new Notification();
        $this->transactionModel = new Transaction();
    }

    public function index(){
        $userId = $_SESSION['user_id'];
        $data = [
            'friends'=>$this->friendModel->getAcceptedFriends($userId),
            'requests'=>$this->friendModel->getPendingRequests($userId),
            'sent_requests'=>$this->friendModel->getSentRequests($userId)
        ];
        require_once __DIR__ . '/../Views/friends/index.php';
    }

    public function sendRequest(){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/friends'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Security token invalid.'; header('Location:/friends'); return;
        }
        $profileCode = strtoupper(trim($_POST['profile_code'] ?? ''));
        $senderId = $_SESSION['user_id'];
        $receiver = $this->userModel->findUserByProfileCode($profileCode);
        if (!$receiver){ $_SESSION['flash_error']='User not found.'; header('Location:/friends'); return; }
        if ($receiver->id == $senderId){ $_SESSION['flash_error']='Cannot add yourself.'; header('Location:/friends'); return; }
        if (($receiver->status ?? '') !== 'active' || empty($receiver->email_verified) || !empty($receiver->banned_at) || !empty($receiver->rejected_at)) {
            $_SESSION['flash_error'] = 'This user cannot receive friend requests right now.';
            header('Location:/friends');
            return;
        }
        if ($this->friendModel->sendRequest($senderId,$receiver->id)){
            $_SESSION['flash_success']='Friend request sent.';
            $sender = $this->userModel->findUserById($senderId);
            if ($sender) {
                $this->notificationModel->send(
                    $receiver->id,
                    'Friend Request',
                    $sender->name . ' sent you a friend request.'
                );
            }
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
        $friendship = $this->friendModel->getFriendshipById((int)$id);
        if (
            !$friendship ||
            (int)$friendship->user_id_2 !== (int)$currentUserId ||
            $friendship->status !== 'pending'
        ) {
            $_SESSION['flash_error'] = 'Could not process.';
            header('Location:/friends');
            return;
        }

        if ($this->friendModel->respondRequest($id,$action,$currentUserId)){
            $messages = [
                'accept' => 'Friend request accepted.',
                'decline' => 'Friend request declined.',
            ];
            $_SESSION['flash_success'] = $messages[$action] ?? 'Request updated.';

            $requesterId = (int)$friendship->requested_by;
            $currentUser = $this->userModel->findUserById($currentUserId);
            if ($requesterId > 0 && $currentUser) {
                $verb = $action === 'accept' ? 'accepted' : 'declined';
                $this->notificationModel->send(
                    $requesterId,
                    'Friend Request Update',
                    $currentUser->name . ' ' . $verb . ' your friend request.'
                );
            }
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
        if (!empty($this->transactionModel->getUnpaidTransactionsWithFriend($currentUserId, (int)$friendId))) {
            $_SESSION['flash_error'] = 'Settle all outstanding balances before removing this friend.';
            header('Location:/friends');
            return;
        }
        if ($this->friendModel->unfriend($currentUserId,$friendId,$currentUserId)){
            $_SESSION['flash_success']='Unfriended.';
            $currentUser = $this->userModel->findUserById($currentUserId);
            if ($currentUser) {
                $this->notificationModel->send(
                    (int)$friendId,
                    'Friend Removed',
                    $currentUser->name . ' removed you from their friends list.'
                );
            }
        } else {
            $_SESSION['flash_error']='Unable to unfriend.';
        }
        header('Location:/friends');
    }

    public function cancelRequest($friendshipId){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/friends'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Security token invalid.'; header('Location:/friends'); return;
        }
        $currentUserId = $_SESSION['user_id'];
        if ($this->friendModel->cancelRequest((int)$friendshipId, $currentUserId)){
            $_SESSION['flash_success']='Friend request cancelled.';
        } else {
            $_SESSION['flash_error']='Unable to cancel request.';
        }
        header('Location:/friends');
    }
}

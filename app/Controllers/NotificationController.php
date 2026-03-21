<?php
class NotificationController {
    private $notificationModel;
    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id'])){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error'=>'Not logged in']);
            exit();
        }
        $this->notificationModel = new Notification();
        header('Content-Type: application/json');
    }

    public function unreadCount(){
        $count = $this->notificationModel->unreadCount($_SESSION['user_id']);
        echo json_encode(['unread'=>$count]);
    }

    public function list(){
        $list = $this->notificationModel->listLatest($_SESSION['user_id'], 25);
        echo json_encode(['notifications'=>$list]);
    }

    public function markRead(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::validateCsrf($_POST['csrf_token'] ?? '')) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->notificationModel->markRead($_SESSION['user_id'],$id);
        echo json_encode(['status'=>'ok']);
    }
}

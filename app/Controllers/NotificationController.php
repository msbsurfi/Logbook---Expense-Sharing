<?php
class NotificationController {
    private $notificationModel;
    public function __construct(){
        session_start();
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
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->notificationModel->markRead($_SESSION['user_id'],$id);
        echo json_encode(['status'=>'ok']);
    }
}
<?php
class Notification {
    private $db;
    public function __construct(){ $this->db = new Database; }

    public function send($userId,$title,$message){
        $this->db->query("INSERT INTO notifications (user_id,title,message) VALUES (:uid,:t,:m)");
        $this->db->bind(':uid',$userId);
        $this->db->bind(':t',$title);
        $this->db->bind(':m',$message);
        return $this->db->execute();
    }

    public function unreadCount($userId){
        $this->db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=:uid AND is_read=0");
        $this->db->bind(':uid',$userId);
        $row = $this->db->fetchOne();
        return $row ? (int)$row->c : 0;
    }

    public function listLatest($userId,$limit=25){
        $this->db->query("SELECT id,title,message,is_read,created_at FROM notifications WHERE user_id=:uid ORDER BY id DESC LIMIT :lim");
        $this->db->bind(':uid',$userId);
        $this->db->bind(':lim',(int)$limit, PDO::PARAM_INT);
        return $this->db->fetchAll();
    }

    public function markRead($userId,$notificationId){
        $this->db->query("UPDATE notifications SET is_read=1 WHERE id=:nid AND user_id=:uid");
        $this->db->bind(':nid',$notificationId);
        $this->db->bind(':uid',$userId);
        return $this->db->execute();
    }
}
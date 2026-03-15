<?php
class Friend {
    private $db;
    public function __construct(){ $this->db = new Database; }

    public function friendshipExists($userId1,$userId2){
        $this->db->query('SELECT id FROM friends WHERE (user_id_1=:u1 AND user_id_2=:u2) OR (user_id_1=:u2 AND user_id_2=:u1)');
        $this->db->bind(':u1',$userId1);
        $this->db->bind(':u2',$userId2);
        return $this->db->fetchOne() ? true : false;
    }

    public function sendRequest($senderId,$receiverId){
        if ($this->friendshipExists($senderId,$receiverId)) return false;
        $this->db->query('INSERT INTO friends (user_id_1,user_id_2,requested_by,status) VALUES (:u1,:u2,:req,"pending")');
        $this->db->bind(':u1',$senderId);
        $this->db->bind(':u2',$receiverId);
        $this->db->bind(':req',$senderId);
        return $this->db->execute();
    }

    public function respondRequest($friendshipId,$action,$currentUserId){
        if(!in_array($action,['accept','decline'])) return false;
        $newStatus = $action==='accept' ? 'accepted':'declined';
        $this->db->query('UPDATE friends SET status=:s WHERE id=:id AND user_id_2=:uid');
        $this->db->bind(':s',$newStatus);
        $this->db->bind(':id',$friendshipId);
        $this->db->bind(':uid',$currentUserId);
        return $this->db->execute();
    }

    public function unfriend($userId,$friendId,$removedBy){
        $this->db->query('UPDATE friends SET status="declined", removed_by=:r WHERE ((user_id_1=:u AND user_id_2=:f) OR (user_id_1=:f AND user_id_2=:u)) AND status="accepted"');
        $this->db->bind(':r',$removedBy);
        $this->db->bind(':u',$userId);
        $this->db->bind(':f',$friendId);
        return $this->db->execute();
    }

    public function getPendingRequests($userId){
        $this->db->query('SELECT friends.id, users.name, users.profile_code, users.email FROM friends JOIN users ON friends.user_id_1=users.id WHERE friends.user_id_2=:uid AND friends.status="pending"');
        $this->db->bind(':uid',$userId);
        return $this->db->fetchAll();
    }

    public function getAcceptedFriends($userId){
        $this->db->query("SELECT u.id,u.name,u.profile_code,u.email
                          FROM users u JOIN friends f ON (u.id=f.user_id_1 OR u.id=f.user_id_2)
                          WHERE (f.user_id_1=:uid OR f.user_id_2=:uid) AND u.id!=:uid AND f.status='accepted'");
        $this->db->bind(':uid',$userId);
        return $this->db->fetchAll();
    }
}
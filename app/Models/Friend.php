<?php
class Friend {
    private Database $db;

    public function __construct(?Database $db = null){
        $this->db = $db ?? new Database;
    }

    public function friendshipExists($userId1,$userId2){
        $relationship = $this->findRelationship($userId1, $userId2);
        if (!$relationship) {
            return false;
        }
        return in_array($relationship->status, ['pending', 'accepted'], true);
    }

    public function findRelationship($userId1,$userId2){
        $this->db->query('SELECT id,status,requested_by FROM friends WHERE (user_id_1=:u1 AND user_id_2=:u2) OR (user_id_1=:u2 AND user_id_2=:u1) LIMIT 1');
        $this->db->bind(':u1',$userId1);
        $this->db->bind(':u2',$userId2);
        return $this->db->fetchOne();
    }

    public function sendRequest($senderId,$receiverId){
        $existing = $this->findRelationship($senderId, $receiverId);
        if ($existing) {
            if (in_array($existing->status, ['pending', 'accepted'], true)) {
                return false;
            }

            $this->db->query('UPDATE friends SET user_id_1=:u1, user_id_2=:u2, requested_by=:req, status="pending", removed_by=NULL WHERE id=:id');
            $this->db->bind(':u1', $senderId);
            $this->db->bind(':u2', $receiverId);
            $this->db->bind(':req', $senderId);
            $this->db->bind(':id', $existing->id);
            return $this->db->execute();
        }

        $this->db->query('INSERT INTO friends (user_id_1,user_id_2,requested_by,status) VALUES (:u1,:u2,:req,"pending")');
        $this->db->bind(':u1',$senderId);
        $this->db->bind(':u2',$receiverId);
        $this->db->bind(':req',$senderId);
        return $this->db->execute();
    }

    public function respondRequest($friendshipId,$action,$currentUserId){
        if(!in_array($action,['accept','decline'])) return false;
        $newStatus = $action==='accept' ? 'accepted':'declined';
        $this->db->query('UPDATE friends SET status=:s WHERE id=:id AND user_id_2=:uid AND status="pending"');
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

    public function getFriendshipById($friendshipId){
        $this->db->query('SELECT * FROM friends WHERE id=:id LIMIT 1');
        $this->db->bind(':id', $friendshipId);
        return $this->db->fetchOne();
    }

    public function getSentRequests($userId){
        $this->db->query('
            SELECT f.id, u.name, u.profile_code, u.email
            FROM friends f
            JOIN users u ON u.id = CASE WHEN f.user_id_1 = :uid THEN f.user_id_2 ELSE f.user_id_1 END
            WHERE f.requested_by = :uid AND f.status = "pending"
            ORDER BY f.id DESC
        ');
        $this->db->bind(':uid', $userId);
        return $this->db->fetchAll();
    }

    public function cancelRequest($friendshipId,$currentUserId){
        $this->db->query('UPDATE friends SET status="declined", removed_by=:uid WHERE id=:id AND requested_by=:uid AND status="pending"');
        $this->db->bind(':id', $friendshipId);
        $this->db->bind(':uid', $currentUserId);
        return $this->db->execute();
    }

    public function getAcceptedFriends($userId){
        $this->db->query("SELECT u.id,u.name,u.profile_code,u.email
                          FROM users u JOIN friends f ON (u.id=f.user_id_1 OR u.id=f.user_id_2)
                          WHERE (f.user_id_1=:uid OR f.user_id_2=:uid) AND u.id!=:uid AND f.status='accepted'");
        $this->db->bind(':uid',$userId);
        return $this->db->fetchAll();
    }

    public function getAcceptedFriendIds($userId): array {
        return array_map(
            static fn($friend) => (int)$friend->id,
            $this->getAcceptedFriends($userId)
        );
    }

    public function areFriends($userId1, $userId2): bool {
        $this->db->query('
            SELECT id
            FROM friends
            WHERE ((user_id_1=:u1 AND user_id_2=:u2) OR (user_id_1=:u2 AND user_id_2=:u1))
              AND status="accepted"
            LIMIT 1
        ');
        $this->db->bind(':u1', $userId1);
        $this->db->bind(':u2', $userId2);
        return $this->db->fetchOne() ? true : false;
    }

    public function ensureAcceptedFriendship(int $userId1, int $userId2, ?int $requestedBy = null): bool {
        if ($userId1 <= 0 || $userId2 <= 0 || $userId1 === $userId2) {
            return false;
        }

        $requestedBy ??= $userId1;
        $existing = $this->findRelationship($userId1, $userId2);

        if ($existing) {
            if (($existing->status ?? null) === 'accepted') {
                return true;
            }

            $this->db->query('UPDATE friends SET status="accepted", requested_by=:req, removed_by=NULL WHERE id=:id');
            $this->db->bind(':req', $requestedBy);
            $this->db->bind(':id', $existing->id);
            return $this->db->execute();
        }

        $this->db->query('INSERT INTO friends (user_id_1,user_id_2,requested_by,status,removed_by) VALUES (:u1,:u2,:req,"accepted",NULL)');
        $this->db->bind(':u1', $userId1);
        $this->db->bind(':u2', $userId2);
        $this->db->bind(':req', $requestedBy);
        return $this->db->execute();
    }
}

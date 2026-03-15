<?php
class Logger {
    private $db;
    public function __construct() { $this->db = new Database(); }

    public function logAdminAction($adminId, $action, array $meta = []) {
        $this->db->query("INSERT INTO admin_action_logs (admin_id, action, meta) VALUES (:aid,:action,:meta)");
        $this->db->bind(':aid',$adminId);
        $this->db->bind(':action',$action);
        $this->db->bind(':meta',$meta ? json_encode($meta) : null);
        $this->db->execute();
    }

    public function logImpersonationStart($adminId,$targetUserId) {
        $this->db->query("INSERT INTO impersonations (admin_id,target_user_id) VALUES (:aid,:tid)");
        $this->db->bind(':aid',$adminId);
        $this->db->bind(':tid',$targetUserId);
        $this->db->execute();
        $this->logAdminAction($adminId,'impersonation_start',['target_user_id'=>$targetUserId]);
    }

    public function logImpersonationEnd($adminId,$targetUserId) {
        $this->db->query("UPDATE impersonations SET ended_at=NOW() WHERE admin_id=:aid AND target_user_id=:tid AND ended_at IS NULL");
        $this->db->bind(':aid',$adminId);
        $this->db->bind(':tid',$targetUserId);
        $this->db->execute();
        $this->logAdminAction($adminId,'impersonation_end',['target_user_id'=>$targetUserId]);
    }
}
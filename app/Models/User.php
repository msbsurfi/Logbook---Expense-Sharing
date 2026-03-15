<?php
class User {
    private Database $db;
    public function __construct(){ $this->db = new Database; }

    /* =========================
       Retrieval / Lookup
       ========================= */
    public function findUserByEmail(string $email){
        $this->db->query('SELECT * FROM users WHERE email = :email LIMIT 1');
        $this->db->bind(':email',$email);
        return $this->db->fetchOne();
    }

    public function findUserById(int $id){
        $this->db->query('SELECT * FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id',$id);
        return $this->db->fetchOne();
    }

    public function findUserByProfileCode(string $profileCode){
        $this->db->query('SELECT id,name,email,profile_code FROM users WHERE profile_code=:c LIMIT 1');
        $this->db->bind(':c',$profileCode);
        return $this->db->fetchOne();
    }

    public function findByVerificationToken(string $token){
        $this->db->query('SELECT * FROM users WHERE verification_token=:t LIMIT 1');
        $this->db->bind(':t',$token);
        return $this->db->fetchOne();
    }

    /* =========================
       Creation / Updates
       ========================= */
    public function createUserWithVerification(array $data): bool {
        $this->db->query('INSERT INTO users (name,email,phone,password,profile_code,verification_token,email_verified,status,role)
                          VALUES (:name,:email,:phone,:password,:profile_code,:token,0,"pending_approval","user")');
        $this->db->bind(':name',$data['name']);
        $this->db->bind(':email',$data['email']);
        $this->db->bind(':phone',$data['phone']);
        $this->db->bind(':password',$data['password']);
        $this->db->bind(':profile_code',$data['profile_code']);
        $this->db->bind(':token',$data['verification_token']);
        return $this->db->execute();
    }

    public function markEmailVerified(int $userId): bool {
        $this->db->query('UPDATE users SET email_verified=1, verification_token=NULL WHERE id=:id');
        $this->db->bind(':id',$userId);
        return $this->db->execute();
    }

    /* =========================
       Rate Limiting Resend Verification
       Columns required:
         verification_resend_count INT NOT NULL DEFAULT 0
         last_verification_resend_at TIMESTAMP NULL
       ========================= */
    public function updateResendVerificationStats(int $userId): bool {
        // Fetch current count and timestamp
        $this->db->query("SELECT verification_resend_count,last_verification_resend_at FROM users WHERE id=:id LIMIT 1");
        $this->db->bind(':id',$userId);
        $row = $this->db->fetchOne();

        // If columns missing (older schema), skip limiting to avoid fatal error.
        if (!$row || !property_exists($row,'verification_resend_count')) {
            return true; // allow until schema fixed
        }

        $count = (int)$row->verification_resend_count;
        $last  = $row->last_verification_resend_at;

        $now = time();
        $minIntervalSeconds = 5 * 60; // 5 minutes
        $maxPerDay          = 5;

        // Reset daily count if last resend older than 24h
        if ($last && ($now - strtotime($last) > 86400)) {
            $count = 0;
        }

        // Too many overall today?
        if ($count >= $maxPerDay) {
            return false;
        }

        // Too soon since last?
        if ($last && ($now - strtotime($last) < $minIntervalSeconds)) {
            return false;
        }

        // Increment & store
        $count++;
        $this->db->query("UPDATE users SET verification_resend_count=:c, last_verification_resend_at=NOW() WHERE id=:id");
        $this->db->bind(':c',$count);
        $this->db->bind(':id',$userId);
        return $this->db->execute();
    }

    /* =========================
       Profile Code Generation
       ========================= */
    public function generateUniqueProfileCode(string $baseName, int $maxAttempts=20): string {
        $prefix = strtoupper(substr(preg_replace('/\s+/','', $baseName),0,3));
        if ($prefix === '') $prefix = 'USR';
        for ($i=0; $i<$maxAttempts; $i++){
            $code = $prefix . '-' . random_int(100,999);
            if (!$this->findUserByProfileCode($code)) return $code;
        }
        return $prefix . '-' . (time() % 1000);
    }

    /* =========================
       Email Logging
       ========================= */
    public function logEmail($userId,$email,$subject): void {
        $this->db->query("INSERT INTO email_log (user_id,email,subject) VALUES (:uid,:email,:sub)");
        $this->db->bind(':uid',$userId);
        $this->db->bind(':email',$email);
        $this->db->bind(':sub',$subject);
        $this->db->execute();
    }

    /* =========================
       Admin Workflow Helpers
       ========================= */
    public function getPendingUsers(){
        $this->db->query("SELECT id,name,email,created_at FROM users WHERE status='pending_approval' AND rejected_at IS NULL AND banned_at IS NULL ORDER BY created_at DESC");
        return $this->db->fetchAll();
    }

    public function approveUser(int $id): bool {
        $this->db->query("UPDATE users SET status='active', rejected_at=NULL, rejection_reason=NULL WHERE id=:id");
        $this->db->bind(':id',$id);
        return $this->db->execute();
    }

    public function rejectUser(int $id,string $reason): bool {
        $this->db->query("UPDATE users SET rejected_at=NOW(), rejection_reason=:r WHERE id=:id AND status='pending_approval'");
        $this->db->bind(':id',$id);
        $this->db->bind(':r',$reason);
        return $this->db->execute();
    }

    public function banUser(int $id,string $reason): bool {
        $this->db->query("UPDATE users SET banned_at=NOW(), ban_reason=:r WHERE id=:id AND status='active'");
        $this->db->bind(':id',$id);
        $this->db->bind(':r',$reason);
        return $this->db->execute();
    }

    public function unbanUser(int $id): bool {
        $this->db->query("UPDATE users SET banned_at=NULL, ban_reason=NULL WHERE id=:id");
        $this->db->bind(':id',$id);
        return $this->db->execute();
    }

    public function promoteToAdmin(int $id): bool {
        $this->db->query("UPDATE users SET role='admin' WHERE id=:id AND role='user'");
        $this->db->bind(':id',$id);
        return $this->db->execute();
    }

    public function demoteFromAdmin(int $id): bool {
        $this->db->query("UPDATE users SET role='user' WHERE id=:id AND role='admin'");
        $this->db->bind(':id',$id);
        return $this->db->execute();
    }

    /* =========================
       Pagination & Filters (if needed by Admin)
       ========================= */
    public function countFilteredUsers(array $filters): int {
        $sql = "SELECT COUNT(*) AS c FROM users WHERE 1=1";
        if ($filters['role'])   $sql .= " AND role = :role";
        if ($filters['status']) $sql .= " AND status = :status";
        if ($filters['search']) $sql .= " AND (email LIKE :search OR name LIKE :search)";
        $this->db->query($sql);
        if ($filters['role'])   $this->db->bind(':role',$filters['role']);
        if ($filters['status']) $this->db->bind(':status',$filters['status']);
        if ($filters['search']) $this->db->bind(':search','%'.$filters['search'].'%');
        $row = $this->db->fetchOne();
        return $row ? (int)$row->c : 0;
    }

    public function listUsersPaginated(array $filters,int $page,int $perPage){
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id,name,email,role,status,email_verified,banned_at,ban_reason,
                       rejected_at,rejection_reason,created_at,verification_resend_count,last_verification_resend_at
                FROM users WHERE 1=1";
        if ($filters['role'])   $sql .= " AND role = :role";
        if ($filters['status']) $sql .= " AND status = :status";
        if ($filters['search']) $sql .= " AND (email LIKE :search OR name LIKE :search)";
        $sql .= " ORDER BY created_at DESC LIMIT :offset,:limit";
        $this->db->query($sql);
        if ($filters['role'])   $this->db->bind(':role',$filters['role']);
        if ($filters['status']) $this->db->bind(':status',$filters['status']);
        if ($filters['search']) $this->db->bind(':search','%'.$filters['search'].'%');
        $this->db->bind(':offset',(int)$offset, PDO::PARAM_INT);
        $this->db->bind(':limit',(int)$perPage, PDO::PARAM_INT);
        return $this->db->fetchAll();
    }
}
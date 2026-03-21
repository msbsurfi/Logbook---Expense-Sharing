<?php
class User {
    private Database $db;
    public function __construct(){ $this->db = new Database; }

    
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
        $this->db->query('SELECT id,name,email,profile_code,status,email_verified,banned_at,rejected_at FROM users WHERE profile_code=:c LIMIT 1');
        $this->db->bind(':c',$profileCode);
        return $this->db->fetchOne();
    }

    public function findByVerificationToken(string $token){
        $this->db->query('SELECT * FROM users WHERE verification_token=:t LIMIT 1');
        $this->db->bind(':t',$token);
        return $this->db->fetchOne();
    }

    public function findPasswordResetByTokenHash(string $tokenHash){
        if (!$this->ensurePasswordResetTable()) {
            return null;
        }

        $this->db->query('
            SELECT pr.email, pr.expires_at, u.id, u.name
            FROM password_resets pr
            JOIN users u ON u.email = pr.email
            WHERE pr.token_hash = :token AND pr.expires_at >= NOW()
            LIMIT 1
        ');
        $this->db->bind(':token', $tokenHash);
        return $this->db->fetchOne();
    }

    
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

    
    public function updateResendVerificationStats(int $userId): bool {
        
        $this->db->query("SELECT verification_resend_count,last_verification_resend_at FROM users WHERE id=:id LIMIT 1");
        $this->db->bind(':id',$userId);
        $row = $this->db->fetchOne();

        
        if (!$row || !property_exists($row,'verification_resend_count')) {
            return true; 
        }

        $count = (int)$row->verification_resend_count;
        $last  = $row->last_verification_resend_at;

        $now = time();
        $minIntervalSeconds = 5 * 60; 
        $maxPerDay          = 5;

        
        if ($last && ($now - strtotime($last) > 86400)) {
            $count = 0;
        }

        
        if ($count >= $maxPerDay) {
            return false;
        }

        
        if ($last && ($now - strtotime($last) < $minIntervalSeconds)) {
            return false;
        }

        
        $count++;
        $this->db->query("UPDATE users SET verification_resend_count=:c, last_verification_resend_at=NOW() WHERE id=:id");
        $this->db->bind(':c',$count);
        $this->db->bind(':id',$userId);
        return $this->db->execute();
    }

    
    public function generateUniqueProfileCode(string $baseName, int $maxAttempts=20): string {
        $prefix = strtoupper(substr(preg_replace('/\s+/','', $baseName),0,3));
        if ($prefix === '') $prefix = 'USR';
        for ($i=0; $i<$maxAttempts; $i++){
            $code = $prefix . '-' . random_int(100,999);
            if (!$this->findUserByProfileCode($code)) return $code;
        }
        return $prefix . '-' . (time() % 1000);
    }

    
    public function logEmail($userId,$email,$subject): void {
        $this->db->query("INSERT INTO email_log (user_id,email,subject) VALUES (:uid,:email,:sub)");
        $this->db->bind(':uid',$userId);
        $this->db->bind(':email',$email);
        $this->db->bind(':sub',$subject);
        $this->db->execute();
    }

    
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

    
    public function countFilteredUsers(array $filters): int {
        $sql = "SELECT COUNT(*) AS c FROM users WHERE 1=1";
        if ($filters['role'])   $sql .= " AND role = :role";
        if ($filters['status'] === 'suspended') {
            $sql .= " AND banned_at IS NOT NULL";
        } elseif ($filters['status']) {
            $sql .= " AND status = :status";
        }
        if ($filters['search']) $sql .= " AND (email LIKE :search OR name LIKE :search)";
        $this->db->query($sql);
        if ($filters['role'])   $this->db->bind(':role',$filters['role']);
        if ($filters['status'] && $filters['status'] !== 'suspended') {
            $this->db->bind(':status',$filters['status']);
        }
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
        if ($filters['status'] === 'suspended') {
            $sql .= " AND banned_at IS NOT NULL";
        } elseif ($filters['status']) {
            $sql .= " AND status = :status";
        }
        if ($filters['search']) $sql .= " AND (email LIKE :search OR name LIKE :search)";
        $sql .= " ORDER BY created_at DESC LIMIT :offset,:limit";
        $this->db->query($sql);
        if ($filters['role'])   $this->db->bind(':role',$filters['role']);
        if ($filters['status'] && $filters['status'] !== 'suspended') {
            $this->db->bind(':status',$filters['status']);
        }
        if ($filters['search']) $this->db->bind(':search','%'.$filters['search'].'%');
        $this->db->bind(':offset',(int)$offset, PDO::PARAM_INT);
        $this->db->bind(':limit',(int)$perPage, PDO::PARAM_INT);
        return $this->db->fetchAll();
    }

    public function createPasswordReset(string $email, string $tokenHash, string $expiresAt): bool {
        if (!$this->ensurePasswordResetTable()) {
            return false;
        }

        $this->db->query('DELETE FROM password_resets WHERE email = :email');
        $this->db->bind(':email', $email);
        $this->db->execute();

        $this->db->query('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (:email, :token, :expires_at)');
        $this->db->bind(':email', $email);
        $this->db->bind(':token', $tokenHash);
        $this->db->bind(':expires_at', $expiresAt);
        return $this->db->execute();
    }

    public function updatePasswordByEmail(string $email, string $passwordHash): bool {
        $this->db->query('UPDATE users SET password = :password WHERE email = :email');
        $this->db->bind(':password', $passwordHash);
        $this->db->bind(':email', $email);
        return $this->db->execute();
    }

    public function deletePasswordReset(string $tokenHash): bool {
        if (!$this->ensurePasswordResetTable()) {
            return false;
        }

        $this->db->query('DELETE FROM password_resets WHERE token_hash = :token');
        $this->db->bind(':token', $tokenHash);
        return $this->db->execute();
    }

    private function ensurePasswordResetTable(): bool {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_password_reset_email (email),
                UNIQUE KEY uniq_password_reset_token (token_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        return $this->db->execute();
    }
}

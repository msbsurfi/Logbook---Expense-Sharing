<?php
class Transaction {
    private $db;
    public function __construct(){ $this->db = new Database(); }

    public function createTransaction($lenderId,$borrowerId,$amount,$description,$expenseId=null,$createdBy=0): int|false {
        $this->db->query("INSERT INTO transactions (created_by,lender_id,borrower_id,amount,description,expense_id) VALUES (:cb,:l,:b,:amt,:d,:eid)");
        $this->db->bind(':cb',$createdBy);
        $this->db->bind(':l',$lenderId);
        $this->db->bind(':b',$borrowerId);
        $this->db->bind(':amt',$amount);
        $this->db->bind(':d',$description);
        $this->db->bind(':eid',$expenseId);
        if ($this->db->execute()) {
            $id = (int)$this->db->lastInsertId();
            return $id > 0 ? $id : false;
        }
        return false;
    }

    public function markTransactionAsPaid($txnId,$actorId){
        $this->db->query("UPDATE transactions t
            JOIN (SELECT 1 FROM transactions WHERE id=:id AND (lender_id=:uid OR borrower_id=:uid)) x
            SET t.status='paid', t.paid_at=NOW(), t.settled_by=:actor, t.settled_at=NOW()
            WHERE t.id=:id");
        $this->db->bind(':id',$txnId);
        $this->db->bind(':uid',$actorId);
        $this->db->bind(':actor',$actorId);
        return $this->db->execute();
    }

    public function getTransactionById($id){
        $this->db->query("SELECT * FROM transactions WHERE id=:id");
        $this->db->bind(':id',$id);
        return $this->db->fetchOne();
    }

    public function getNetBalances($userId){
        $this->db->query("SELECT lender_id AS friend_id, SUM(amount) AS total_owed FROM transactions WHERE borrower_id=:uid AND status='unpaid' GROUP BY lender_id");
        $this->db->bind(':uid',$userId);
        $debts = $this->db->fetchAll();

        $this->db->query("SELECT borrower_id AS friend_id, SUM(amount) AS total_lent FROM transactions WHERE lender_id=:uid AND status='unpaid' GROUP BY borrower_id");
        $this->db->bind(':uid',$userId);
        $credits = $this->db->fetchAll();

        $balances = [];
        foreach($debts as $d){
            $balances[$d->friend_id]['balance'] = ($balances[$d->friend_id]['balance'] ?? 0) - $d->total_owed;
        }
        foreach($credits as $c){
            $balances[$c->friend_id]['balance'] = ($balances[$c->friend_id]['balance'] ?? 0) + $c->total_lent;
        }

        foreach($balances as $fid => &$b){
            $this->db->query("SELECT name FROM users WHERE id=:i");
            $this->db->bind(':i',$fid);
            $u = $this->db->fetchOne();
            $b['name'] = $u ? $u->name : 'Unknown';
        }
        return $balances;
    }

    public function getUnpaidTransactionsWithFriend($u1,$u2){
        $this->db->query("SELECT * FROM transactions WHERE status='unpaid' AND ((lender_id=:u1 AND borrower_id=:u2) OR (lender_id=:u2 AND borrower_id=:u1)) ORDER BY created_at ASC");
        $this->db->bind(':u1',$u1);
        $this->db->bind(':u2',$u2);
        return $this->db->fetchAll();
    }

    public function getTransactionHistoryForUser($userId){
        $this->db->query("SELECT t.*, cu.name AS created_by_name, su.name AS settled_by_name, e.description AS expense_description
            FROM transactions t
            LEFT JOIN users cu ON t.created_by = cu.id
            LEFT JOIN users su ON t.settled_by = su.id
            LEFT JOIN expenses e ON t.expense_id = e.id
            WHERE t.lender_id=:uid OR t.borrower_id=:uid
            ORDER BY t.created_at DESC");
        $this->db->bind(':uid',$userId);
        return $this->db->fetchAll();
    }

    public function getExpenseParticipants($expenseId){
        $this->db->query("SELECT ep.user_id,u.email,u.name FROM expense_participants ep JOIN users u ON ep.user_id=u.id WHERE ep.expense_id=:eid");
        $this->db->bind(':eid',$expenseId);
        return $this->db->fetchAll();
    }
}
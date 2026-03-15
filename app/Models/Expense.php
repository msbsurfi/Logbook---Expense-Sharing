<?php
class Expense {
    private $db;
    public function __construct(){ $this->db = new Database; }

    public function createExpense($description,$totalAmount,$creatorId){
        $this->db->query('INSERT INTO expenses (description,total_amount,created_by_user_id) VALUES (:d,:t,:c)');
        $this->db->bind(':d',$description);
        $this->db->bind(':t',$totalAmount);
        $this->db->bind(':c',$creatorId);
        if ($this->db->execute()) return $this->db->lastInsertId();
        return false;
    }
}
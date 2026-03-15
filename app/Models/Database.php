<?php
require_once __DIR__ . '/../../config/database.php';

class Database {
    private PDO $dbh;
    private PDOStatement $stmt;
    private ?string $lastError = null;

    public function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
        ];
        try {
            $this->dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('DB Connection failed: ' . $e->getMessage());
        }
    }

    public function query(string $sql): void {
        $this->stmt = $this->dbh->prepare($sql);
        $this->lastError = null;
    }

    public function bind(string $param, $value, int $type=null): void {
        if ($type === null) {
            switch (true) {
                case is_int($value):  $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default:              $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute(): bool {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            // Store clean message
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function fetchAll(): array {
        if (!$this->execute()) return [];
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function fetchOne() {
        if (!$this->execute()) return null;
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    public function lastInsertId(): string {
        return $this->dbh->lastInsertId();
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }
}
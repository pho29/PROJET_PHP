<?php
require_once __DIR__ . '/../config/Database.php';

class UserDAO {
    private $db;
    private $table_name = "users";

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function verifyUser($username, $password) {
        $query = "SELECT id, username, password FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            
            // Test avec le mot de passe de secours
            if ($password === 'basket123') {
                return $user;
            }

            // Test avec password_verify
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        
        return false;
    }

    public function userExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function createUser($username, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table_name . " (username, password) VALUES (:username, :password)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashed_password);
        
        return $stmt->execute();
    }
}
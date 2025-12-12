<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $dsn = "mysql:host=" . DatabaseConfig::HOST . 
               ";dbname=" . DatabaseConfig::DB_NAME . 
               ";charset=" . DatabaseConfig::CHARSET;
        
        try {
            $this->connection = new PDO($dsn, DatabaseConfig::USERNAME, DatabaseConfig::PASSWORD);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Erreur de connexion: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
?>
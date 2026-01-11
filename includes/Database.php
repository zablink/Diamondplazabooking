<?php
// includes/Database.php
// Database Connection Class with Singleton Pattern

class Database {
    private static $instance = null;
    private $conn;
    
    // Database configuration
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    
    // Private constructor to prevent direct instantiation
    private function __construct() {
        // Load config if available
        if (defined('DB_HOST')) {
            $this->host = DB_HOST;
            $this->dbname = DB_NAME;
            $this->username = DB_USER;
            $this->password = DB_PASS;
        } else {
            // Default values (should be overridden by config.php)
            $this->host = 'localhost';
            $this->dbname = 'booking_db';
            $this->username = 'root';
            $this->password = '';
        }
        
        $this->connect();
    }
    
    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Connect to database
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Get connection
    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }
    
    // Alias method for backward compatibility
    public function getConn() {
        return $this->getConnection();
    }
    
    // Prepare statement
    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }
    
    // Execute query and return results
    public function query($sql) {
        return $this->getConnection()->query($sql);
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Close connection
    public function close() {
        $this->conn = null;
    }
    
    // Destructor
    public function __destruct() {
        $this->close();
    }
}

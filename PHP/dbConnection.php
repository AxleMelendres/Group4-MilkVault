<?php
class Database {
    private $host = "sql108.infinityfree.com";    
    private $user = "if0_40432921";          
    private $pass = "CHZSi8FDvB";             
    private $dbname = "if0_40432921_milkvaultdb";
    private $conn;


    public function __construct() {
        $this->connect();
    }

    // Create the database connection
    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }

    // Return the connection when needed
    public function getConnection() {
        return $this->conn;
    }

    // Close the connection
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}


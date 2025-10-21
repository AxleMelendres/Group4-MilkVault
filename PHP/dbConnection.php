<?php
class Database {
    private $host = "localhost";    
    private $user = "root";          
    private $pass = "";             
    private $dbname = "milkvaultdb";
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
?>

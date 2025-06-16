<?php
require_once '../dbinfo.php';

class Database
{
    private $host;
    private $username;
    private $password;
    private $database;

    private $port;

    private $conn;

    public function __construct()
    {
        global $servername, $username, $password, $dbname, $port;

        $this->host = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->database = $dbname;
        $this->port = $port;
    }

    public function connect()
    {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->database}",
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            return null;
        }
    }
}
?>
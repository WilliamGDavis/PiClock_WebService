<?php

class DBConnect {

    private $db_host;
    private $db_port;
    private $db_name;
    private $db_username;
    private $db_password;
    private $timezone;
    public $DBObject;

    public function __construct() {
        //Retrieve ENV Variables (.htaccess)
        $this->db_host = getenv('DB_HOST');
        $this->db_port = getenv('DB_PORT');
        $this->db_name = getenv('DB_NAME');
        $this->db_username = getenv('DB_USERNAME');
        $this->db_password = getenv('DB_PASSWORD');
        $this->timezone = 'America/Detroit';
        try {
            date_default_timezone_set($this->timezone);
            $this->TryConnect();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /*
     * Function: Try to connect to the MySQL Database, and if successful set the DBObject
     * Return: DBObject or error message
     */
    private function TryConnect() {
        //Data Source Name = Mysql
        $dsn = "mysql:host=$this->db_host;port=$this->db_port;dbname=$this->db_name";

        //Try to connect to the database and return the $db connection object.  
        //If not, stop processing all scripts and return a generalized message
        try {
            $this->DBObject = new PDO($dsn, $this->db_username, $this->db_password); //Connect to DB
            $this->DBObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->DBObject->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //Creates TRUE prepared statements
        } catch (PDOException $e) {
            $this->DBObject = null;
            die("Cannot connect to database");
        }
    }
    
        /*
     * Function: Try to connect to the MySQL Database, and if successful return a message to the user
     * Return: DBObject or error message
     */
    public function CheckConnection() {
        //Data Source Name = Mysql
        $dsn = "mysql:host=$this->db_host;port=$this->db_port;dbname=$this->db_name";

        //Try to connect to the database  
        //If not, stop processing all scripts and return a generalized message
        try {
            $this->DBObject = new PDO($dsn, $this->db_username, $this->db_password); //Connect to DB
            return "true";
        } catch (PDOException $e) {
            $this->DBObject = null;
            return "false";
        }
    }

    /*
     * Function: Return any database errors to the 'error_log' table within the database
     * Location: N/A
     * Return: The error number, string, file, and location to the database
     */

    static function db_errorHandler($db, $errno, $errstr, $errfile, $errline) {
        $query = "INSERT INTO `error_log` "
                . "VALUES(:id,:error_time,:errno,:errstr,:errfile,:errline)";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':id' => NULL,
            ':error_time' => NULL,
            ':errno' => $errno,
            ':errstr' => $errstr,
            ':errfile' => $errfile,
            ':errline' => $errline
        ));
        return true; //Don't execute PHP error handler
    }

}

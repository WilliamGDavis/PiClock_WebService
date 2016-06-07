<?php

/**
 * Description of Authentication
 *
 * @author William G Davis
 */
require_once 'DBConnect.php';

class Authentication_Api {

    private $Username;
    private $Password;

    function __construct() {
        $this->Username = (isset($_SERVER['PHP_AUTH_USER'])) ?
                filter_var($_SERVER['PHP_AUTH_USER']) : null;
        $this->Password = (isset($_SERVER['PHP_AUTH_PW'])) ?
                filter_var($_SERVER['PHP_AUTH_PW']) : null;
    }

    /**
     * Authenticate against the RPC server using HTTP Basic Authentication
     * TODO: Tighten up the security using SSL
     * @return bool
     */
    function TryHttpBasicAuthentication() {
        //Check for null values
        if (null === $this->Username || null === $this->Password) {
            return false;
        }

        //Ensure PHP can read the Environment variables
        if (false === getenv("API_USERNAME") || false === getenv("API_PASSWORD")) {
            return false;
        }

        //Match the username:password against the Environment variables
        if (getenv("API_USERNAME") != $this->Username || getenv("API_PASSWORD") != $this->Password) {
            return false;
        }
    }

}

class Authentication {

    /**
     * Helper function for Query_PinLogin
     * @param string $pin
     * @return array $employee
     */
    public static function PinLogin($pin) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $employee = self::Query_PinLogin($db, $pin);
        $db = null;
        return $employee;
    }

    /**
     * Return an employee from the database based on their PIN
     * 
     * @param   object $db
     * @param   string $pin
     * @return  array $employee or empty array      
     */
    private static function Query_PinLogin($db, $pin) {
        $employee = [];
        $query = "SELECT 
                    users.id, 
                    users.fname, 
                    users.mname, 
                    users.lname
                  FROM 
                    users
                  WHERE 
                    users.PIN = :pin
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":pin" => $pin
        ));
        while ($row = $stmt->fetchObject()) {
            $employee['id'] = $row->id;
            $employee['fname'] = $row->fname;
            $employee['mname'] = $row->mname;
            $employee['lname'] = $row->lname;
        }
        return $employee;
    }

}

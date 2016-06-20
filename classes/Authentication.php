<?php

/**
 * Description of Authentication
 * Handles very basic PIN authentication
 *
 * @author William G Davis
 */
require_once 'DBConnect.php';

class Authentication {

    /**
     * Return the employee info based on the PIN
     * @param string $pin
     * @return array
     */
    public static function PinLogin($pin) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $employee = self::Query_PinLogin($db, $pin);
        $db = null;
        return $employee;
    }

    //=============== Database Queries ===============
    /**
     * Return an employee from the database based on their PIN
     * 
     * @param DBConnect $db
     * @param string $pin
     * @return array   
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

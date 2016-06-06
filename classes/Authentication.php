<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Authentication
 *
 * @author Owner
 */
require_once 'DBConnect.php';

class Authentication {

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
     * @return  array (Single Employee|Empty array)      
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

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
        $employee_array = self::Query_PinLogin($db, $pin);
        $db = null;
        return $employee_array;
    }
    
    private static function Query_PinLogin($db, $pin) {
        $employee = [];
        $query = "SELECT id, fname, mname, lname "
                . "FROM users "
                . "WHERE PIN = :pin "
                . "LIMIT 1";
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

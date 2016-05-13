<?php

require_once 'DBConnect.php';

/**
 * Description of Employee
 *
 * @author Owner
 */
class Employee {

//    public static function GetAllEmployees() {
//        return self::ReturnAllEmployees();
//    }

    public static function ReturnAllEmployees() {
        return self::ReturnAllEmployeesFromDB();
    }

    public static function Login_PIN($pin) {
        return self::LoginViaPIN($pin);
    }
    
    public static function ReturnCurrentJobByEmployeeId($id){
        return self::Get_Current_Job_By_Employee_Id($id);
    }

    /*
     * Function: Return the list of employees
     * Return: Array of assigned appointments
     */

    private static function ReturnAllEmployeesFromDB() {
        $db = new DBConnect();
        $db = $db->DBObject;

        $query = "SELECT * "
                . "FROM `users` "
                . "WHERE active = 1 "
                . "ORDER BY users.fname ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $employee_array = [];


        while ($row = $stmt->fetchObject()) {
            array_push($employee_array, array(
                'id' => $row->id,
                'fname' => $row->fname,
                'mname' => $row->mname,
                'lname' => $row->lname
            ));
        }

        return $employee_array;
    }

    private static function LoginViaPIN($pin) {
        $db = new DBConnect();
        $db = $db->DBObject;

        //Counting Query
        //Used to return valid data to the user
        //Count Query
        $query = "SELECT COUNT(*) "
                . "FROM `users` "
                . "WHERE PIN = :pin";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":pin" => $pin
        ));
        $count = $stmt->fetchColumn();

        if (0 == $count) {
            return "";
        }

        //Actual Query
        $query = "SELECT * "
                . "FROM `users` "
                . "WHERE PIN = :pin "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":pin" => $pin
        ));

        $employee = [];
        while ($row = $stmt->fetchObject()) {
            $employee['id'] = $row->id;
            $employee['fname'] = $row->fname;
            $employee['mname'] = $row->mname;
            $employee['lname'] = $row->lname;
        }
        return $employee;
    }
    
    

    private static function Get_Current_Job_By_Employee_Id($id) {
        $db = new DBConnect();
        $db = $db->DBObject;

        //Actual Query
        $query = "SELECT * "
                . "FROM `punches` "
                . "WHERE id_users = :id "
                . "AND type = 1 "
                . "AND open_status = 1 "
                . "ORDER BY timestamp DESC "
                . "LIMIT 1";
//        $query = "SELECT * "
//                . "FROM `punches_open` "
//                . "WHERE id_users = :id "
//                . "AND type = 1 "
//                . "ORDER BY timestamp ASC "
//                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id" => $id
        ));

        $punch = [];
        while ($row = $stmt->fetchObject()) {
            $punch['id'] = $row->id;
            $punch['id_users'] = $row->id_users;
            $punch['id_jobs'] = self::ConvertJobIdToJobNumber($row->id_jobs);
            $punch['timestamp'] = $row->timestamp;
            $punch['type'] = $row->type;
        }
        return $punch;
    }
    
    private static function ConvertJobIdToJobNumber($id){
        $db = new DBConnect();
        $db = $db->DBObject;

        //Actual Query
        $query = "SELECT description "
                . "FROM `jobs` "
                . "WHERE id = :id "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id" => $id
        ));

        $punch = "";
        while ($row = $stmt->fetchObject()) {
            $punch = $row->description;
        }
        return $punch;
    }
}

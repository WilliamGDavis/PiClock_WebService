<?php

require_once 'DBConnect.php';

/**
 * Description of Employee
 *
 * @author Owner
 */
class Employee {

    /**
     * Return an Array of all employees in the database
     * @return array
     */
    public static function GetEmployeeList() {
        $db = new DBConnect();
        $db = $db->DBObject;
        $all_employees_array = self::Query_GetEmployeeList($db);
        $db = null;
        return $all_employees_array;
    }

    private static function Query_GetEmployeeList($db) {
        $employee_array = [];
        $query = "SELECT * "
                . "FROM `users` "
                . "WHERE active = 1 "
                . "ORDER BY users.fname ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
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

    /**
     * Check the database to see if an employee is currenly punched into a job
     * @param string $employeeId
     * @return string true or false
     */
    public static function CheckLoginStatus($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $result = self::Query_CheckLoginStatus($db, $employeeId);
        $db = null;
        return $result;
    }

    private static function Query_CheckLoginStatus($db, $employeeId) {
        $query = "SELECT 
                    COUNT(*) 
                  FROM 
                    punches
                  WHERE 
                    punches.id_users = :id_users 
                    AND punches.open_status = 1 
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_users" => $employeeId
        ));

        $count = $stmt->fetchColumn();
        if (1 == $count) {
            return "true";
        } else {
            return "false";
        }
    }

    public static function GetCurrentJob($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $result = self::Query_GetCurrentJob($db, $employeeId);
        $db = null;
        return $result;
    }

    public static function ReturnCurrentJobByEmployeeId($id) {
        return self::Get_Current_Job_By_Employee_Id($id);
    }

    

    

    public static function JobPunch($employeeId, $newJobId) {
        return self::JobPunchToDb($employeeId, $newJobId);
    }

    /**
     * Return an array of job information, if the employee is currently punched into one
     * @param DBConnect $db
     * @param string $employeeId
     * @return array
     */
    private static function Query_GetCurrentJob($db, $employeeId) {
        $currentJobArray = [];
        $query = "SELECT 
                    jobs.id,
                    jobs.description,
                    jobs.code,
                    jobs.active
                  FROM 
                    jobs
                  INNER JOIN 
                    punches_jobs
                  ON 
                    jobs.id = punches_jobs.id_jobs
                  WHERE 
                    punches_jobs.id_users = :id_users
                    AND punches_jobs.open_status = 1
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_users" => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            $currentJobArray["id"] = $row->id;
            $currentJobArray["description"] = $row->description;
            $currentJobArray["code"] = $row->code;
            $currentJobArray["active"] = $row->active;
        }

        return $currentJobArray;
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
                . "ORDER BY datetime DESC "
                . "LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id" => $id
        ));

        $punch = [];
        while ($row = $stmt->fetchObject()) {
            $punch['id'] = $row->id;
            $punch['id_users'] = $row->id_users;
            $punch['id_jobs'] = self::ConvertJobIdToJobNumber($row->id_jobs);
            $punch['datetime'] = $row->datetime;
            $punch['type'] = $row->type;
        }
        return $punch;
    }

    private static function ConvertJobIdToJobNumber($id) {
        $db = new DBConnect();
        $db = $db->DBObject;

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

    private function displayPage($array) {
        header('Location: index.php?' . http_build_query($array));
        exit();
    }

}

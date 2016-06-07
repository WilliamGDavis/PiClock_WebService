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
        $query = "SELECT COUNT(*) "
                . "FROM `punches_open` "
                . "WHERE id_users = :id_users";
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
        $query = "SELECT jobs.id,
                             jobs.description,
                             jobs.code,
                             jobs.active
                        FROM punches_jobs_open
                        INNER JOIN punches_jobs
                        ON punches_jobs.id = punches_jobs_open.id_punches_jobs
                        INNER JOIN jobs
                        ON jobs.id = punches_jobs.id_jobs
                        WHERE punches_jobs_open.id_users = :id_users
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

    private static function JobPunchToDb($employeeId, $newJobId) {
        try {
            $db = new DBConnect();
            $db = $db->DBObject;

            //Add the new punch to the Database
            $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, datetime, type, open_status)"
                    . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, NOW(), :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_jobs" => $newJobId,
                ":id_parent_punch_jobs" => 0,
                ":id_users" => $employeeId,
                ":type" => 1,
                ":open_status" => 1
            ));
            $last_insert_id = $db->lastInsertId();

            //Create an "Open" punch for the user
            $query = "INSERT INTO punches_jobs_open (id, id_punches_jobs, id_users)"
                    . "VALUES (:id, :id_punches_jobs, :id_users)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_punches_jobs" => $last_insert_id,
                ":id_users" => $employeeId
            ));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function displayPage($array) {
        header('Location: index.php?' . http_build_query($array));
        exit();
    }

}

<?php

/**
 * Description of Employee
 *
 * @author William G Davis
 * @copyright (c) 2016, William G Davis
 */
require_once 'DBConnect.php';

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

    /**
     * Check the database to see if an employee is currenly punched into the system
     * @param string $employeeId
     * @return string "true" or "false"
     */
    public static function CheckLoginStatus($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $result = self::Query_CheckLoginStatus($db, $employeeId);
        $db = null;
        return $result;
    }

    /**
     * Return the job information for the job an employee is currently punched into
     * @param string $employeeId
     * @return array
     */
    public static function GetCurrentJob($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $result = self::Query_GetCurrentJob($db, $employeeId);
        $db = null;
        return $result;
    }

    //=============== Database Queries ===============
    private static function Query_GetEmployeeList($db) {
        $employee_array = [];
        $query = "SELECT
                    users.id,
                    users.fname,
                    users.mname,
                    users.lname
                  FROM
                    users
                  WHERE
                    users.active = 1
                  ORDER BY
                    users.fname ASC";
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

}

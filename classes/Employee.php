<?php

require_once 'DBConnect.php';

/**
 * Description of Employee
 *
 * @author Owner
 */
class Employee {

    public static function GetEmployeeList() {
        $db = new DBConnect();
        $db = $db->DBObject;
        $all_employees_array = self::Query_GetEmployeeList($db);
        $db = null;
        return $all_employees_array;
    }

    public static function ReturnCurrentJobByEmployeeId($id) {
        return self::Get_Current_Job_By_Employee_Id($id);
    }

    public static function CheckLoginStatus($employeeId) {
        return self::CheckForLoginStatus($employeeId);
    }

    public static function CheckCurrentJob($employeeId) {
        return self::CheckForCurrentJob($employeeId);
    }

    public static function ChangeJob($employeeId, $jobId, $newJobId) {
        return self::ChangeJobInDb($employeeId, $jobId, $newJobId);
    }

    public static function GetJobIdByJobDescription($jobDescription) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $jobId = self::Query_GetJobIdByJobDescription($db, $jobDescription);
        $db = null;
        return $jobId;
    }
    private static function Query_GetJobIdByJobDescription($db, $jobDescription) {
        $query = "SELECT jobs.id 
                  FROM jobs 
                  WHERE jobs.description = :jobDescription 
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":jobDescription" => $jobDescription
        ));
        
        return $stmt->fetchColumn();
    }

    public static function JobPunch($employeeId, $newJobId) {
        return self::JobPunchToDb($employeeId, $newJobId);
    }

    /*
     * Function: Return the list of employees
     * Return: Array of all employees
     * @param: $db: MySql database object
     */

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

//TODO: Create a function to check for an "open" punch to marry a PunchIn to a PunchOut

    private static function ChangeJobInDb($employeeId, $jobId, $newJobId) {
        try {
            $db = new DBConnect();
            $db = $db->DBObject;
            $jobPunch = [];
            //TODO: Use a transaction
            //Find the Parent Punch for Open Job Punch for the user
            $query = "SELECT id, id_punches_jobs "
                    . "FROM punches_jobs_open "
                    . "WHERE id_users = :id_users "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ':id_users' => $employeeId
            ));

            while ($row = $stmt->fetchObject()) {
                $jobPunch['id'] = $row->id;
                $jobPunch['id_punches_jobs'] = $row->id_punches_jobs;
            }

            //Delete the currently open punch
            $query = "DELETE FROM punches_jobs_open "
                    . "WHERE id = :id "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ':id' => $jobPunch['id']
            ));

            //"Close" out the original parent job punch
            $query = "UPDATE punches_jobs "
                    . "SET open_status = :open_status "
                    . "WHERE id = :id_punches_jobs "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":open_status" => 0,
                ":id_punches_jobs" => $jobPunch['id_punches_jobs']
            ));

            //Add a PunchOut to the Database
            $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, datetime, type, open_status)"
                    . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, NOW(), :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_jobs" => $jobId,
                ":id_parent_punch_jobs" => $jobPunch['id_punches_jobs'],
                ":id_users" => $employeeId,
                ":type" => 0,
                ":open_status" => 0
            ));

            //This is where it seems to break on the ARM MySql server
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
            $last_insert_id = $db->lastInsertId(); //TODO: This is likely where the bug with the job id not being passed into the db properly

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

    private static function CheckForLoginStatus($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;

//Counting Query
        $query = "SELECT COUNT(*) "
                . "FROM `punches_open` "
                . "WHERE id_users = :id_users";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_users" => $employeeId
        ));

        $count = $stmt->fetchColumn();
        if (1 == $count) {
            return true;
        } elseif (0 == $count) {
            return false;
        } else {
            return null;
        }
    }

    private static function CheckForCurrentJob($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $currentJobArray = [];

        //Counting Query
        $query = "SELECT COUNT(*) "
                . "FROM `punches_jobs_open` "
                . "WHERE id_users = :id_users";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_users" => $employeeId
        ));

        $count = $stmt->fetchColumn();
        
        if ($count >= 1) {
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
        } else {
            return null;
        }
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

    private function displayPage($array){
        header('Location: index.php?' . http_build_query($array));
        exit();
    }
}

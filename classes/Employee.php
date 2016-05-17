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

    public static function ReturnCurrentJobByEmployeeId($id) {
        return self::Get_Current_Job_By_Employee_Id($id);
    }

    public static function PunchIn($params) {
        return self::PunchInClock($params);
    }

    public static function PunchOut($params) {
        return self::PunchOutClock($params);
    }

    public static function CheckLoginStatus($employeeId) {
        return self::CheckForLoginStatus($employeeId);
    }

    public static function CheckCurrentJob($employeeId) {
        return self::CheckForCurrentJob($employeeId);
    }

    public static function GetSettings() {
        return self::ReturnAllSettings();
    }

    public static function ChangeJob($employeeId, $jobId, $newJobId) {
        return self::ChangeJobInDb($employeeId, $jobId, $newJobId);
    }

    public static function JobLookup($jobDescription) {
        return self::JobLookupFromDb($jobDescription);
    }

    public static function JobPunch($employeeId, $newJobId) {
        return self::JobPunchToDb($employeeId, $newJobId);
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

    private static function ReturnAllSettings() {
        $db = new DBConnect();
        $db = $db->DBObject;

        $query = "SELECT id, name, value "
                . "FROM settings";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $settings_array = [];
        while ($row = $stmt->fetchObject()) {
            array_push($settings_array, array(
                'id' => $row->id,
                'name' => $row->name,
                'value' => $row->value
            ));
        }
        return $settings_array;
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

    //TODO: Create a function to check for an "open" punch to marry a PunchIn to a PunchOut
    /*
     * Function: Add a PunchIn stamp to the database
     * It also adds a punch to the punches_open table in order to keep track of any open punches
     *
     */
    private static function PunchInClock($params) {
        //Temp
        if ($params["type"] == "in") {
            $params["type"] = 1;
            $params["open_status"] = 1;
        }
        try {
            $db = new DBConnect();
            $db = $db->DBObject;
            $query = "INSERT INTO punches (id, id_users, id_jobs, id_parentPunch, type, open_status)"
                    . "VALUES (:id, :id_users, :id_jobs, :id_parentPunch, :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_users" => $params["employeeId"],
                ":id_jobs" => 0,
                ":id_parentPunch" => 0,
                ":type" => $params["type"], //TODO: Call a function to convert 'in' to 1
                ":open_status" => $params["open_status"] //TODO: Call a function to convert $params["open_status"]
            ));
            $last_insert_id = $db->lastInsertId();
            $query = "INSERT INTO punches_open (id, id_punches, id_users)"
                    . "VALUES (:id, :id_punches, :id_users)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_punches" => $last_insert_id,
                ":id_users" => $params['employeeId']
            ));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private static function PunchOutClock($params) {
        try {
            $db = new DBConnect();
            $db = $db->DBObject;
            $punch = [];

            //TODO: Use a transaction
            //Find the Open Punch for the user
            $query = "SELECT id, id_punches "
                    . "FROM punches_open "
                    . "WHERE id_users = :id_users "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ':id_users' => $params['employeeId']
            ));

            while ($row = $stmt->fetchObject()) {
                $punch['id'] = $row->id;
                $punch['id_punches'] = $row->id_punches;
            }

            //Delete the Open Punch in the table
            $query = "DELETE FROM punches_open "
                    . "WHERE id = :id "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ':id' => $punch['id']
            ));

            //Insert the PunchOut
            $query = "INSERT INTO punches (id, id_users, id_jobs, id_parentPunch, type, open_status)"
                    . "VALUES (:id, :id_users, :id_jobs, :id_parentPunch, :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_users" => $params["employeeId"],
                ":id_jobs" => 0,
                ':id_parentPunch' => $punch['id_punches'],
                ":type" => $params["type"], //TODO: Call a function to convert 'in' to 1
                ":open_status" => $params["open_status"] //TODO: Call a function to convert $params["open_status"]
            ));

            //UPDATE the original "parent" punch
            $query = "UPDATE punches "
                    . "SET open_status = :open_status "
                    . "WHERE id = :id_punches "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":open_status" => $params['open_status'],
                ":id_punches" => $punch['id_punches']
            ));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

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
            $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, type, open_status)"
                    . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_jobs" => $jobId,
                ":id_parent_punch_jobs" => $jobPunch['id_punches_jobs'],
                ":id_users" => $employeeId,
                ":type" => 0,
                ":open_status" => 0
            ));

            //Add the new punch to the Database
            $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, type, open_status)"
                    . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, :type, :open_status)";
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
        $currentJobId = null;
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

        if (1 <= $count) {
            $query = "SELECT id_punches_jobs  "
                    . "FROM `punches_jobs_open` "
                    . "WHERE id_users = :id_users "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id_users" => $employeeId
            ));

            while ($row = $stmt->fetchObject()) {
                $currentJobId = $row->id_punches_jobs;
            }

            //Find the job id
            $query = "SELECT id_jobs  "
                    . "FROM `punches_jobs` "
                    . "WHERE id = :id_currentJob "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id_currentJob" => $currentJobId
            ));

            while ($row = $stmt->fetchObject()) {
                $jobId = $row->id_jobs;
            }

            //Find the job data
            $query = "SELECT id, description, code, active  "
                    . "FROM `jobs` "
                    . "WHERE id = :id_jobs "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id_jobs" => $jobId
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

    private static function ConvertJobIdToJobNumber($id) {
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

    private static function JobLookupFromDb($jobDescription) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $jobNumber = null;

        //Actual Query
        $query = "SELECT id "
                . "FROM `jobs` "
                . "WHERE description = :jobDescription "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":jobDescription" => $jobDescription
        ));

        while ($row = $stmt->fetchObject()) {
            $jobNumber = $row->id;
        }

        return $jobNumber;
    }

    private static function JobPunchToDb($employeeId, $newJobId) {
        try {
            $db = new DBConnect();
            $db = $db->DBObject;
            
            //Add the new punch to the Database
            $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, type, open_status)"
                    . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, :type, :open_status)";
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

}

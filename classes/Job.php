<?php

/**
 * Description of Job
 *
 * @author William G Davis
 */
require_once 'DBConnect.php';

class Job {

    /**
     * Change a job in the database (Multiple Queries)
     * @param string $employeeId
     * @param string $jobId
     * @param string $newJobId
     * @return n/a
     */
    public static function ChangeJob($employeeId, $jobId, $newJobId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $result = self::Query_ChangeJob($db, $employeeId, $jobId, $newJobId);
        $db = null;
        return $result;
    }

    private static function Query_ChangeJob($db, $employeeId, $jobId, $newJobId) {
        $jobPunch = [];
        //Find the Parent Punch for Open Job Punch for the user
        try {
            $db->beginTransaction();
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
            $db->commit();
        } catch (PDOException $ex) {
            $db->rollback();
            return $ex->getMessage();
        }
    }

    /**
     * Return a job Id from the database based on a job description
     * @param string $jobDescription
     * @return string job Id
     */
    public static function GetJobIdByJobDescription($jobDescription) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $jobId = self::Query_GetJobIdByJobDescription($db, $jobDescription);
        $db = null;
        return $jobId;
    }

    private static function Query_GetJobIdByJobDescription($db, $jobDescription) {
        $query = "SELECT 
                    jobs.id
                  FROM 
                    jobs
                  WHERE 
                    jobs.description = :jobDescription
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":jobDescription" => $jobDescription
        ));

        return $stmt->fetchColumn();
    }

}

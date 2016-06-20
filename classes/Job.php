<?php

/**
 * Description of Job
 *
 * @author William G Davis
 */
require_once 'DBConnect.php';

class Job {

//    /**
//     * Change a job in the database (Multiple Queries)
//     * @param string $employeeId
//     * @param string $jobId
//     * @param string $newJobId
//     * @return n/a
//     */
//    public static function ChangeJob($employeeId, $jobId, $newJobId) {
//        $db = new DBConnect();
//        $db = $db->DBObject;
//        $result = self::Query_ChangeJob($db, $employeeId, $jobId, $newJobId);
//        $db = null;
//        return $result;
//    }
//
//    private static function Query_ChangeJob($db, $employeeId, $jobId, $newJobId) {
//        $jobPunch = [];
//        try {
//            //Retrieve the Open punch info for the employee
//            $jobPunch = self::Query_GetOpenJob($db, $employeeId);
//
//            //Return an empty array if the job number is not in the database
//            //(Usually if the employee is not punched in, or not punched in to a job
//            if (!isset($jobPunch['id']) || !isset($jobPunch['id_punches_jobs'])) {
//                return [];
//            }
//
//            //Begin the transaction
//            $db->beginTransaction();
//            //Delete the currently open punch
//            self::Query_DeleteOpenJobById($db, $jobPunch['id']);
//            //"Close" out the original parent job punch by updating it's open_status to 0
//            self::Query_UpdateJobParentPunch($db, $jobPunch['id_punches_jobs']);
//            //Add a "Closed" job punch to the Database
//            self::Query_InsertClosedJobPunch($db, $jobId, $jobPunch['id_punches_jobs'], $employeeId);
//            //Add the new job punch to the Database
//            self::Query_InsertOpenJobPunch($db, $newJobId, $employeeId);
//            $db->commit();
//        } catch (PDOException $ex) {
//            $db->rollback();
//            return $ex->getMessage();
//        }
//    }

//    /**
//     * Retrieve the open job (id, id_punches_jobs) from the database
//     * @param DBConnect $db
//     * @param string $employeeId
//     * @return array
//     */
//    private static function Query_GetOpenJob($db, $employeeId) {
//        $openJob = [];
//        $query = "SELECT
//                    punches_jobs_open.id,
//                    punches_jobs_open.id_punches_jobs
//                  FROM
//                    punches_jobs_open
//                  WHERE
//                    punches_jobs_open.id_users = :id_users
//                  LIMIT 1";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ':id_users' => $employeeId
//        ));
//
//        while ($row = $stmt->fetchObject()) {
//            $openJob['id'] = $row->id;
//            $openJob['id_punches_jobs'] = $row->id_punches_jobs;
//        }
//        return $openJob;
//    }
//
//    /**
//     * Delete an open job from the database
//     * @param DBConnect $db
//     * @param string $jobId
//     */
//    private static function Query_DeleteOpenJobById($db, $jobId) {
//        $query = "DELETE FROM punches_jobs_open "
//                . "WHERE id = :id "
//                . "LIMIT 1";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ':id' => $jobId
//        ));
//    }
//
//    /**
//     * Update the "Parent" punch of a job to close it out (To show that the punch is not currently open)
//     * @param DBConnect $db
//     * @param string $id_punches_jobs
//     */
//    private static function Query_UpdateJobParentPunch($db, $id_punches_jobs) {
//        $query = "UPDATE 
//                    punches_jobs
//                  SET 
//                    punches_jobs.open_status = :open_status
//                  WHERE 
//                    punches_jobs.id = :id_punches_jobs
//                  LIMIT 1";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ":open_status" => 0,
//            ":id_punches_jobs" => $id_punches_jobs
//        ));
//    }

//    /**
//     * Create a "Closed" job punch in the database
//     * @param DBConnect $db
//     * @param string $jobId
//     * @param string $id_punches_jobs
//     * @param string $employeeId
//     */
//    private static function Query_InsertClosedJobPunch($db, $jobId, $id_punches_jobs, $employeeId) {
//        $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, datetime, type, open_status)"
//                . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, NOW(), :type, :open_status)";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ":id" => null,
//            ":id_jobs" => $jobId,
//            ":id_parent_punch_jobs" => $id_punches_jobs,
//            ":id_users" => $employeeId,
//            ":type" => 0,
//            ":open_status" => 0
//        ));
//    }
//
//    /**
//     * Insert an "Open" job punch to the database
//     * @param DBConnect $db
//     * @param string $newJobId
//     * @param string $employeeId
//     */
//    private static function Query_InsertOpenJobPunch($db, $newJobId, $employeeId) {
//        $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, datetime, type, open_status)"
//                . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, NOW(), :type, :open_status)";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ":id" => null,
//            ":id_jobs" => $newJobId,
//            ":id_parent_punch_jobs" => 0,
//            ":id_users" => $employeeId,
//            ":type" => 1,
//            ":open_status" => 1
//        ));
//
//        //Use the Id of the last inserted row in the database
//        $last_insert_id = $db->lastInsertId();
//
//        $query = "INSERT INTO punches_jobs_open (id, id_punches_jobs, id_users)"
//                . "VALUES (:id, :id_punches_jobs, :id_users)";
//        $stmt = $db->prepare($query);
//        $stmt->execute(array(
//            ":id" => null,
//            ":id_punches_jobs" => $last_insert_id,
//            ":id_users" => $employeeId
//        ));
//    }

    /**
     * Return a job Id from the database based on a job description
     * @param string $jobDescription
     * @return string JobId (Successful)
     * @return string "" (No results from Database)
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

        return (string)$stmt->fetchColumn();
    }

}

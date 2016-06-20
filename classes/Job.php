<?php

/**
 * Description of Job
 *
 * @author William G Davis
 */
require_once 'DBConnect.php';

class Job {

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

    
    //======================== Database Queries ========================
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

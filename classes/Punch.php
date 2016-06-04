<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Punch
 *
 * @author Owner
 */
require_once './classes/DBConnect.php';

class Punch {

    public static function PunchIn($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $punchResult = self::Query_PunchIn($db, $employeeId); //Should return TRUE or an error message
        $db = null;
        return $punchResult;
    }

    public static function PunchOut($employeeId, $currentJobId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $punchResult = self::Query_PunchOut($db, $employeeId, $currentJobId);
        $db = null;
        return $punchResult;
    }

    public static function PunchIntoJob($employeeId, $currentJobId, $newJobId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $punchResult = self::Query_PunchIntoJob($db, $employeeId, $currentJobId, $newJobId);
        $db = null;
        return $punchResult;
    }

    public static function GetSingleDayPunchesByEmployeeId($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $todaysPunches = self::Query_GetSingleDayPunchesByEmployeeId($db, $employeeId);
        $db = null;
        return $todaysPunches;
    }

    public static function GetThisWeeksPunchesByEmployeeId($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $todaysPunches = self::Query_GetThisWeeksPunchesByEmployeeId($db, $employeeId);
        $db = null;
        return $todaysPunches;
    }

    /*
     * Function: Add a PunchIn stamp to the database
     * It also adds a punch to the punches_open table in order to keep track of any open punches
     *
     */

    //Database Queries
    private static function Query_PunchIn($db, $employeeId) {
        //TODO: Check if a user is already punched in and return an error if ther are
        $currentlyLoggedIn = self::Query_CheckIfPunchedIn($db, $employeeId);
        if (true === $currentlyLoggedIn) {
            return false;
        } else {
            try {
                $db->beginTransaction();
                $query = "INSERT INTO punches (id, id_users, id_jobs, id_parentPunch, datetime, type, open_status)"
                        . "VALUES (:id, :id_users, :id_jobs, :id_parentPunch, NOW(), :type, :open_status)";
                $stmt = $db->prepare($query);
                $stmt->execute(array(
                    ":id" => null,
                    ":id_users" => $employeeId,
                    ":id_jobs" => 0,
                    ":id_parentPunch" => 0,
                    ":type" => 1,
                    ":open_status" => 1
                ));
                $last_insert_id = $db->lastInsertId();
                $query = "INSERT INTO punches_open (id, id_punches, id_users)"
                        . "VALUES (:id, :id_punches, :id_users)";
                $stmt = $db->prepare($query);
                $stmt->execute(array(
                    ":id" => null,
                    ":id_punches" => $last_insert_id,
                    ":id_users" => $employeeId
                ));
                $db->commit();
                return true;
            } catch (Exception $ex) {
                $db->rollback();
                return $ex->getMessage();
            }
        }
    }

    private static function Query_CheckIfPunchedIn($db, $employeeId) {
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

    private static function Query_PunchOut($db, $employeeId, $currentJobId) {
        $punch = [];
        try {
            $db->beginTransaction();
            //Find the Open Punch for the user
            $query = "SELECT id, id_punches "
                    . "FROM punches_open "
                    . "WHERE id_users = :id_users "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ':id_users' => $employeeId
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
            $query = "INSERT INTO punches (id, id_users, id_jobs, id_parentPunch, datetime, type, open_status)"
                    . "VALUES (:id, :id_users, :id_jobs, :id_parentPunch, NOW(), :type, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_users" => $employeeId,
                ":id_jobs" => 0,
                ':id_parentPunch' => $punch['id_punches'],
                ":type" => 0,
                ":open_status" => 0
            ));

            //UPDATE the original "parent" punch
            $query = "UPDATE punches "
                    . "SET open_status = :open_status "
                    . "WHERE id = :id_punches "
                    . "LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":open_status" => 0,
                ":id_punches" => $punch['id_punches']
            ));

            //Punch Out of the Open job if a user is currently logged into one
            if ("null" !== $currentJobId) {
                self::Query_PunchOutOfJob($db, $employeeId, $currentJobId);
            }
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            return $ex->getMessage();
        }
    }

    private static function Query_PunchOutOfJob($db, $employeeId, $currentJobId) {
        $query = "SELECT id, id_punches_jobs "
                . "FROM punches_jobs_open "
                . "WHERE id_users = :id_users "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':id_users' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            $currentJobInfo['id'] = $row->id;
            $currentJobInfo['id_punches_jobs'] = $row->id_punches_jobs;
        }

        //Delete the Open Jobs Punch in the table
        $query = "DELETE FROM punches_jobs_open "
                . "WHERE id = :id "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':id' => $currentJobInfo['id']
        ));

        //Insert the PunchOut
        $query = "INSERT INTO punches_jobs (id, id_jobs, id_parent_punch_jobs, id_users, datetime, type, open_status)"
                . "VALUES (:id, :id_jobs, :id_parent_punch_jobs, :id_users, NOW(), :type, :open_status)";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id" => null,
            ":id_jobs" => $currentJobId,
            ":id_parent_punch_jobs" => $currentJobInfo['id_punches_jobs'],
            ':id_users' => $employeeId,
            ":type" => 0,
            ":open_status" => 0
        ));

        //UPDATE the original "parent" punch
        $query = "UPDATE punches_jobs "
                . "SET open_status = :open_status "
                . "WHERE id = :id_punches_jobs "
                . "LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":open_status" => 0,
            ":id_punches_jobs" => $currentJobInfo['id_punches_jobs']
        ));
    }

    private static function Query_PunchIntoJob($db, $employeeId, $currentJobId, $newJobId) {
        try {
            $db->beginTransaction();

            if ("null" !== $currentJobId) {
                self::Query_PunchOutOfJob($db, $employeeId, $currentJobId);
            }
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
        } catch (Exception $ex) {
            $db->rollback();
            return $ex->getMessage();
        }
    }

    private static function Query_GetSingleDayPunchesByEmployeeId($db, $employeeId) {
        $singleDayPunches = [];
        $regularPunchesOpen = [];
        $jobPunchesOpen = [];
        $regularPunchesPaired = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        $jobPunchesPaired = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );

        $date = date('Y-m-d', strtotime("today")); //TODO: Fix this to enable a user to pass in a date
        //2016-06-02

        $regularPunchesPaired = self::Query_GetRegularPunchesPairedByEmployeeId($db, $employeeId, $regularPunchesPaired, $date);
        $regularPunchesOpen = self::Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen);
        $jobPunchesPaired = self::Query_GetJobPunchesPairedByEmployeeId($db, $employeeId, $jobPunchesPaired, $date);
        $jobPunchesOpen = self::Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen);

        $singleDayPunches["RegularPunchesPaired"] = $regularPunchesPaired;
        $singleDayPunches["JobPunchesPaired"] = $jobPunchesPaired;
        $singleDayPunches["RegularPunchesOpen"] = $regularPunchesOpen;
        $singleDayPunches["JobPunchesOpen"] = $jobPunchesOpen;
        return $singleDayPunches;
    }

    private static function Query_GetRegularPunchesPairedByEmployeeId($db, $employeeId, $regularPunchesPaired, $date) {
        $query = "SELECT
                    p.id_parentPunch as ParentId,
                    p.id as ChildId,
                    punches.datetime as PunchIn,
                    p.datetime as PunchOut,
                    SUM(UNIX_TIMESTAMP(p.datetime) - UNIX_TIMESTAMP(punches.datetime)) as DurationInSeconds
                  FROM
                    (
                        SELECT
                          punches.id,
                          punches.id_users,
                          punches.id_parentPunch,
                          punches.datetime
                        FROM
                          punches
                        WHERE
                          punches.id_users = :employeeId
                          AND punches.open_status = 0
                          AND DATE(punches.datetime) = :date
                      ) AS p
                    INNER JOIN
                      punches ON punches.id = p.id_parentPunch
                    GROUP BY ChildId
                    ORDER BY PunchIn DESC";

        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId,
            ':date' => $date
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($regularPunchesPaired['Punches'], array(
                'ParentId' => strval($row->ParentId),
                'ChildId' => strval($row->ChildId),
                'PunchIn' => strval($row->PunchIn),
                'PunchOut' => strval($row->PunchOut),
                'DurationInSeconds' => strval($row->DurationInSeconds)
            ));
            $regularPunchesPaired['TotalDurationInSeconds'] += $row->DurationInSeconds;
        }

        $regularPunchesPaired['TotalDurationInSeconds'] = strval(($regularPunchesPaired['TotalDurationInSeconds']));
        return $regularPunchesPaired;
    }

    private static function Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen) {
        $regularPunchesOpen['OpenId'] = "";
        $regularPunchesOpen['ParentId'] = "";
        $regularPunchesOpen['PunchIn'] = "";

        $query = "SELECT
                    punches_open.id AS OpenId,
                    punches_open.id_punches AS ParentId,
                    punches.datetime AS PunchIn
                  FROM
                    punches_open
                  INNER JOIN
                    punches ON punches.id = punches_open.id_punches
                  WHERE
                    punches.id_users = :employeeId
                    AND punches.open_status = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            $regularPunchesOpen['OpenId'] = strval($row->OpenId);
            $regularPunchesOpen['ParentId'] = strval($row->ParentId);
            $regularPunchesOpen['PunchIn'] = strval($row->PunchIn);
        }
        return $regularPunchesOpen;
    }

    private static function Query_GetJobPunchesPairedByEmployeeId($db, $employeeId, $jobPunchesPaired, $date) {
        $query = "SELECT
                    alias.id AS ParentId,
                    punches_jobs.id AS ChildId,
                    alias.datetime AS PunchIn,
                    punches_jobs.datetime AS PunchOut,
                    alias.jobId as JobId,
                    alias.description as JobDescription,
                    SUM(UNIX_TIMESTAMP(punches_jobs.datetime) - UNIX_TIMESTAMP(alias.datetime)) AS DurationInSeconds
                  FROM (
                        SELECT
                          punches_jobs.id,
                          punches_jobs.id_parent_punch_jobs,
                          punches_jobs.datetime,
                          jobs.id as jobId,
                          jobs.description,
                          jobs.code
                        FROM
                          punches_jobs
                        INNER JOIN
                          jobs ON punches_jobs.id_jobs = jobs.id
                        WHERE
                          punches_jobs.id_users = :employeeId
                          AND punches_jobs.open_status = 0
                          AND DATE(punches_jobs.datetime) = :date
                    ) AS alias
                    INNER JOIN
                      punches_jobs ON alias.id = punches_jobs.id_parent_punch_jobs
                    GROUP BY alias.id
                    ORDER BY
                      alias.datetime DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId,
            ':date' => $date
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($jobPunchesPaired['Punches'], array(
                'ParentId' => strval($row->ParentId),
                'ChildId' => strval($row->ChildId),
                'PunchIn' => strval($row->PunchIn),
                'PunchOut' => strval($row->PunchOut),
                'DurationInSeconds' => strval($row->DurationInSeconds),
//                'JobId' => strval($row->JobId),
//                'JobDescription' => strval($row->JobDescription)
                'JobInformation' => array(
                    'Id' => strval($row->JobId),
                    'Description' => strval($row->JobDescription)
                )
            ));
            $jobPunchesPaired['TotalDurationInSeconds'] += $row->DurationInSeconds;
        }
        $jobPunchesPaired['TotalDurationInSeconds'] = strval($jobPunchesPaired['TotalDurationInSeconds']);
        return $jobPunchesPaired;
    }

    private static function Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen) {
        $jobPunchesOpen['OpenId'] = "";
        $jobPunchesOpen['ParentId'] = "";
        $jobPunchesOpen['PunchIn'] = "";

        $query = "SELECT
                    punches_jobs_open.id AS OpenId,
                    punches_jobs_open.id_punches_jobs AS ParentId,
                    punches_jobs.datetime AS PunchIn,
                    jobs.id AS JobId,
                    jobs.description AS JobDescription
                  FROM
                    punches_jobs_open
                  INNER JOIN punches_jobs
                    ON punches_jobs_open.id_punches_jobs = punches_jobs.id
                  INNER JOIN jobs
                    ON punches_jobs.id_jobs = jobs.id
                  WHERE
                    punches_jobs.id_users = :employeeId
                    AND punches_jobs.open_status = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            $jobPunchesOpen['OpenId'] = strval($row->OpenId);
            $jobPunchesOpen['ParentId'] = strval($row->ParentId);
            $jobPunchesOpen['PunchIn'] = strval($row->PunchIn);
//                'JobId' => strval($row->JobId),
//                'JobDescription' => strval($row->JobDescription)
            $jobPunchesOpen['JobInformation'] = array(
                'Id' => strval($row->JobId),
                'Description' => strval($row->JobDescription)
            );
        }
        return $jobPunchesOpen;
    }

    //Calculate by duration of the punches for each dat as opposed to first punch and last punch, just in case someone punches out for a period of time
    //TODO: Make this work
    private static function Query_GetThisWeeksPunchesByEmployeeId($db, $employeeId) {
        $thisWeeksPunches['DayOfWeekPunch'] = [];
        $regularPunchesOpen = [];
        $jobPunchesOpen = [];
        $regularPunchesPaired = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        $jobPunchesPaired = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );

        $query = "SELECT
                    days.dayDesc AS DayName,
                    days.date AS Date
                  FROM(
                        SELECT 
                          'Monday' AS dayDesc, 0 AS dayOrder, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Monday'), '%X%V %W') AS Date
                        UNION SELECT
                          'Tuesday', 1, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Tuesday'), '%X%V %W')
                        UNION SELECT
                          'Wednesday', 2, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Wednesday'), '%X%V %W')
                        UNION SELECT
                          'Thursday', 3, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Thursday'), '%X%V %W')
                        UNION SELECT
                          'Friday', 4, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Friday'), '%X%V %W')
                        UNION SELECT
                          'Saturday', 5, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Saturday'), '%X%V %W')
                        UNION SELECT
                          'Sunday', 6, STR_TO_DATE(CONCAT(YEAR(NOW()), (WEEKOFYEAR(NOW()) + 1), ' ', 'Sunday'), '%X%V %W')
                          ) AS days
                    LEFT JOIN
                      (
                      SELECT SUM(UNIX_TIMESTAMP(punches.datetime) - UNIX_TIMESTAMP(parentPunches.datetime)) AS duration,
                      parentPunches.datetime
                      FROM
                        (
                          SELECT
                            punches.id,
                            punches.datetime AS DATETIME
                          FROM
                            punches
                          WHERE
                            punches.id_users = :employeeId 
                            AND punches.id_parentPunch = 0 
                            AND punches.open_status = 0 
                            AND WEEKOFYEAR(punches.datetime) = WEEKOFYEAR(NOW())
                        ) AS parentPunches
                        LEFT JOIN punches 
                        ON parentPunches.id = punches.id_parentPunch
                        GROUP BY WEEKDAY(punches.datetime)
                      ) AS punches 
                    ON days.dayOrder = WEEKDAY(punches.datetime)
                    ORDER BY
                      days.dayOrder ASC";

        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            $date = date('Y-m-d', strtotime($row->Date));
            array_push($thisWeeksPunches['DayOfWeekPunch'], array(
                'Date' => $date,
                'DayName' => $row->DayName,
                'RegularPunchesPaired' => array(
                    self::Query_GetRegularPunchesPairedByEmployeeId($db, $employeeId, $regularPunchesPaired, $date)
                ),
                'JobPunchesPaired' => array(
                    self::Query_GetJobPunchesPairedByEmployeeId($db, $employeeId, $jobPunchesPaired, $date)
                ),
                'RegularPunchesOpen' => array(
                    self::Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen)
                ),
                'JobPunchesOpen' => array(
                    self::Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen)
                )
            ));
        }
        return $thisWeeksPunches;
    }

}

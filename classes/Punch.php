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

    public static function GetTodaysPunchesByEmployeeId($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $todaysPunches = self::Query_GetTodaysPunchesByEmployeeId($db, $employeeId);
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

    //Seperate "job" punches from regular punches (seperate keys in the array)
    private static function Query_GetTodaysPunchesByEmployeeId($db, $employeeId) {
        $todaysPunches = [];
        $regularPunchesPaired = [];
        $regularPunchesOpen = [];
        $jobPunchesPaired = [];
        $jobPunchesOpen = [];

        //Paired (Closed) Regular Punches
        $query = "SELECT alias.id AS id_punchesParent, 
                         punches.id AS id_punchesChild, 
                         alias.datetime AS dateTimeParent, 
                         punches.datetime AS dateTimeChild 
                  FROM (
                    SELECT punches.id, 
                           punches.id_users, 
                           punches.id_parentPunch, 
                           punches.datetime 
                    FROM punches 
                    WHERE punches.id_users = :employeeId 
                    AND punches.open_status = 0 
                    AND DATE(punches.datetime) = CURDATE()
                  ) as alias 
                  INNER JOIN punches 
                  ON alias.id = punches.id_parentPunch 
                  ORDER BY alias.datetime DESC";

        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($regularPunchesPaired, array(
                'Id_ParentPunch' => $row->id_punchesParent,
                'Id_ChildPunch' => $row->id_punchesChild,
                'DateTimeIn' => $row->dateTimeParent,
                'DateTimeOut' => $row->dateTimeChild
            ));
        }

        //Open Regular Punches
        $query = "SELECT punches.id, 
                         punches.id_parentPunch, 
                         punches.datetime, 
                         punches.type 
                  FROM punches 
                  WHERE punches.id_users = :employeeId 
                  AND punches.open_status = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($regularPunchesOpen, array(
                'id' => $row->id,
                'Id_ParentPunch' => $row->id_parentPunch,
                'DateTime' => $row->datetime,
                'Type' => $row->type
            ));
        }

        //Paired (Closed) Job Punches
        $query = "SELECT alias.id AS id_punchesParent, 
                         punches_jobs.id AS id_punchesChild, 
                         alias.datetime AS dateTimeParent, 
                         punches_jobs.datetime AS dateTimeChild, 
                         alias.description 
                  FROM (
                    SELECT punches_jobs.id, 
                           punches_jobs.id_parent_punch_jobs, 
                           punches_jobs.datetime, 
                           jobs.description 
                    FROM punches_jobs 
                    INNER JOIN jobs 
                    ON punches_jobs.id_jobs = jobs.id 
                    WHERE punches_jobs.id_users = :employeeId 
                    AND punches_jobs.open_status = 0 
                    AND DATE(punches_jobs.datetime) = CURDATE()
                  ) as alias 
                  INNER JOIN punches_jobs 
                  ON alias.id = punches_jobs.id_parent_punch_jobs 
                  ORDER BY alias.datetime DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($jobPunchesPaired, array(
                'Id_ParentPunch' => $row->id_punchesParent,
                'Id_ChildPunch' => $row->id_punchesChild,
                'JobDescription' => $row->description,
                'DateTimeIn' => $row->dateTimeParent,
                'DateTimeOut' => $row->dateTimeChild
            ));
        }

        //Open Job Punches
        $query = "SELECT punches_jobs.id, 
                         punches_jobs.id_jobs, 
                         punches_jobs.id_parent_punch_jobs, 
                         punches_jobs.datetime, 
                         punches_jobs.type, 
                         jobs.description 
                  FROM punches_jobs 
                  INNER JOIN jobs 
                  ON punches_jobs.id_jobs = jobs.id 
                  WHERE punches_jobs.id_users = :employeeId 
                  AND punches_jobs.open_status = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($jobPunchesOpen, array(
                'Id' => $row->id,
                'Id_Jobs' => $row->id_jobs,
                'JobDescription' => $row->description,
                'Id_ParentPunch' => $row->id_parent_punch_jobs,
                'DateTime' => $row->datetime,
                'Type' => $row->type
            ));
        }

        $todaysPunches["RegularPunchesPaired"] = $regularPunchesPaired;
        $todaysPunches["RegularPunchesOpen"] = $regularPunchesOpen;
        $todaysPunches["JobPunchesPaired"] = $jobPunchesPaired;
        $todaysPunches["JobPunchesOpen"] = $jobPunchesOpen;
        return $todaysPunches;
    }

    //Calculate by duration of the punches for each dat as opposed to first punch and last punch, just in case someone punches out for a period of time
    private static function Query_GetThisWeeksPunchesByEmployeeId($db, $employeeId) {
        $thisWeeksPunches = [];
        $query = "SELECT days.dayDesc as DayName, punches.duration
                FROM (
                    SELECT 'Monday' as dayDesc, 0 as dayOrder 
                    UNION SELECT 'Tuesday', 1 
                    UNION SELECT 'Wednesday', 2 
                    UNION SELECT 'Thursday', 3 
                    UNION SELECT 'Friday', 4 
                    UNION SELECT 'Saturday', 5 
                    UNION SELECT 'Sunday', 6
                ) as days 
                LEFT JOIN (
                    SELECT SUM((UNIX_TIMESTAMP(punches.datetime) - UNIX_TIMESTAMP(parentPunches.datetime))) as duration, parentPunches.datetime 
                    FROM (
                        SELECT punches.id, punches.datetime AS datetime 
                        FROM punches 
                        WHERE punches.id_users = :employeeId 
                        AND punches.id_parentPunch = 0 
                        AND punches.open_status = 0 
                        AND WEEKOFYEAR(punches.datetime) = WEEKOFYEAR(NOW())
                    ) as parentPunches 
                    LEFT JOIN punches 
                    ON parentPunches.id = punches.id_parentPunch 
                    GROUP BY WEEKDAY(punches.datetime)
                ) as punches 
                ON days.dayOrder = WEEKDAY(punches.datetime) 
                ORDER BY days.dayOrder ASC";

        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($thisWeeksPunches, array(
                'DayName' => $row->DayName,
                'DurationInSeconds' => $row->duration
            ));
        }
        return $thisWeeksPunches;
    }

}

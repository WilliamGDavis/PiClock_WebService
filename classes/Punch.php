<?php

/**
 * Description of Punch
 *
 * @author William G Davis
 */
require_once './classes/DBConnect.php';

class Punch {

    /**
     * Punch an employee into the database (Regular Punch)
     * @param string $employeeId
     * @return string "true" (Successful)
     * #return string "false" (Unsuccessful)
     */
    public static function PunchIn($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $punchResult = self::Query_PunchIn($db, $employeeId);
        $db = null;
        return $punchResult;
    }

    private static function Query_PunchIn($db, $employeeId) {
        //Check to see if a user is currently punched in to the database
        $currentlyLoggedIn = self::Query_CheckIfPunchedIn($db, $employeeId);
        if (true === $currentlyLoggedIn) {
            return "false";
        }

        //Punch into the database if $currentlyLoggedIn = false
        try {
            $query = "INSERT INTO
                        punches
                            (id, id_users, datetime_in, datetime_out, open_status)
                      VALUES
                            (:id, :id_users, NOW(), null, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_users" => $employeeId,
                ":open_status" => 1
            ));
            return "true";
        } catch (PDOException $ex) {
            //TODO: Write $ex->error messages to database using the custom error_handler method in DBConnect
            return "false";
        }
    }
    
    public static function GetRangePunchesByEmployeeId($employeeId){
        $db = new DBConnect();
        $db = $db->DBObject;
        $punches = self::Query_GetRangePunchesByEmployeeId($db, $employeeId);
        $db = null;
        return $punches;
    }

    /**
     * Punch an employee out of the database (Regular Punch)
     * @param string $employeeId
     * @param string $currentJobId
     * @return string "true" (Successful)
     * #return string "false" (Unsuccessful)
     */
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

    /**
     * Return an array of punches for the day based on employeeId
     * @param DBConnect $db
     * @param string $employeeId
     * @return array
     */
    public static function GetSingleDayPunchesByEmployeeId($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $todaysPunches = self::Query_GetSingleDayPunchesByEmployeeId($db, $employeeId);
        $db = null;
        return $todaysPunches;
    }

    private static function Query_GetSingleDayPunchesByEmployeeId($db, $employeeId) {
        $singleDayPunches = [];
//        $regularPunchesOpen = [];
//        $jobPunchesOpen = [];
        $regularPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        $jobPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        
        $date = date('Y-m-d', strtotime('today'));
//        $day = date('w');
//        $startDate = date('Y-m-d', strtotime('-' . $day . ' days')); //TODO: Fix this to enable a user to pass in a date
//        $endDate = date('Y-m-d', strtotime('+' . (6 - $day) . ' days'));
        //2016-06-02

        $regularPunches = self::Query_GetRegularPunchesByEmployeeId($db, $employeeId, $regularPunches, $date);
//        $regularPunchesOpen = self::Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen, $date);
        $jobPunches = self::Query_GetJobPunchesByEmployeeId($db, $employeeId, $jobPunches, $date);
//        $jobPunchesOpen = self::Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen);
//        $singleDayPunches["RegularPunchesOpen"] = $regularPunchesOpen;
//        $singleDayPunches["JobPunchesOpen"] = $jobPunchesOpen;
        $singleDayPunches["RegularPunches"] = $regularPunches;
        $singleDayPunches["JobPunches"] = $jobPunches;
        return $singleDayPunches;
    }
    
    private static function Query_GetRangePunchesByEmployeeId($db, $employeeId) {
        $singleDayPunches = [];
//        $regularPunchesOpen = [];
//        $jobPunchesOpen = [];
        $regularPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        $jobPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        
        $day = date('w');
        $weekStart = date('Y-m-d', strtotime('-' . $day . ' days')); //TODO: Fix this to enable a user to pass in a date
        $weekEnd = date('Y-m-d', strtotime('+' . (6 - $day) . ' days'));
        $date = date('Y-m-d', strtotime('today'));
        //2016-06-02

        $regularPunches = self::Query_GetRangeRegularPunchesByEmployeeId($db, $employeeId, $regularPunches, $weekStart, $weekEnd);
//        $regularPunchesOpen = self::Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen, $date);
        $jobPunches = self::Query_GetRangeJobPunchesByEmployeeId($db, $employeeId, $jobPunches, $weekStart, $weekEnd);
//        $jobPunchesOpen = self::Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen);
//        $singleDayPunches["RegularPunchesOpen"] = $regularPunchesOpen;
//        $singleDayPunches["JobPunchesOpen"] = $jobPunchesOpen;
        $singleDayPunches["RegularPunches"] = $regularPunches;
        $singleDayPunches["JobPunches"] = $jobPunches;
        return $singleDayPunches;
    }

    /**
     * Return an array of punches for the week based on employeeId
     * @param DBConnect $db
     * @param string $employeeId
     * @return array
     */
    public static function GetThisWeeksPunchesByEmployeeId($employeeId) {
        $db = new DBConnect();
        $db = $db->DBObject;
        $weeklyPunches = self::Query_GetThisWeeksPunchesByEmployeeId($db, $employeeId);
        $db = null;
        return $weeklyPunches;
    }

    private static function Query_GetThisWeeksPunchesByEmployeeId($db, $employeeId) {
//        $thisWeeksPunches['PairedPunches'] = [];
//        $regularPunchesOpen = [];
//        $jobPunchesOpen = [];
        $regularPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );
        $jobPunches = array(
            'TotalDurationInSeconds' => null,
            'Punches' => []
        );

//        $query = "SELECT
//                    days.dayDesc AS DayName,
//                    days.date AS Date
//                  FROM(
//                        SELECT
//                          'Monday' AS dayDesc, 0 AS dayOrder, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Monday'), '%X%V %W') AS Date
//                        UNION SELECT
//                          'Tuesday', 1, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Tuesday'), '%X%V %W')
//                        UNION SELECT
//                          'Wednesday', 2, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Wednesday'), '%X%V %W')
//                        UNION SELECT
//                          'Thursday', 3, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Thursday'), '%X%V %W')
//                        UNION SELECT
//                          'Friday', 4, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Friday'), '%X%V %W')
//                        UNION SELECT
//                          'Saturday', 5, STR_TO_DATE(CONCAT(YEAR(NOW()), WEEKOFYEAR(NOW()), ' ', 'Saturday'), '%X%V %W')
//                        UNION SELECT
//                          'Sunday', 6, STR_TO_DATE(CONCAT(YEAR(NOW()), (WEEKOFYEAR(NOW()) + 1), ' ', 'Sunday'), '%X%V %W')
//                          ) AS days
//                    LEFT JOIN
//                      (
//                      SELECT SUM(UNIX_TIMESTAMP(punches.datetime) - UNIX_TIMESTAMP(parentPunches.datetime)) AS duration,
//                      parentPunches.datetime
//                      FROM
//                        (
//                          SELECT
//                            punches.id,
//                            punches.datetime AS DATETIME
//                          FROM
//                            punches
//                          WHERE
//                            punches.id_users = :employeeId
//                            AND punches.id_parentPunch = 0
//                            AND punches.open_status = 0
//                            AND WEEKOFYEAR(punches.datetime) = WEEKOFYEAR(NOW())
//                        ) AS parentPunches
//                        LEFT JOIN punches
//                        ON parentPunches.id = punches.id_parentPunch
//                        GROUP BY WEEKDAY(punches.datetime)
//                      ) AS punches
//                    ON days.dayOrder = WEEKDAY(punches.datetime)
//                    ORDER BY
//                      days.dayOrder ASC";
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
                  ORDER BY days.dayOrder ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $punches = [];
        while ($row = $stmt->fetchObject()) {
            $date = date('Y-m-d', strtotime($row->Date));
//            $thisWeeksPunches['OpenPunches'] = array(
//                "RegularPunchesOpen" => self::Query_GetRegularPunchesOpenByEmployeeId($db, $employeeId, $regularPunchesOpen),
//                "JobPunchesOpen" => self::Query_GetJobPunchesOpenByEmployeeId($db, $employeeId, $jobPunchesOpen)
//            );
            array_push($punches, array(
                'Date' => $date,
                'DayName' => $row->DayName,
                'RegularPunches' => array(
                    self::Query_GetRegularPunchesByEmployeeId($db, $employeeId, $regularPunches, $date)
                ),
                'JobPunches' => array(
                    self::Query_GetJobPunchesByEmployeeId($db, $employeeId, $jobPunches, $date)
                )
                    )
            );
            $thisWeeksPunches['WeekdayPunches'] = $punches;
        }
        return $thisWeeksPunches;
    }

    //Database Queries
    private static function Query_CheckIfPunchedIn($db, $employeeId) {
        $query = "SELECT
                    COUNT(*)
                  FROM
                    punches
                  WHERE
                    punches.id_users = :id_users
                    AND punches.open_status = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_users" => $employeeId
        ));

        $count = $stmt->fetchColumn();
        if (1 == $count) {
            return true;
        } else {
            return false;
        }
    }

    private static function Query_PunchOut($db, $employeeId, $currentJobId) {
        $punch = [];
        try {
            $db->beginTransaction();
            //Update the original punch (Regular Punch)
            $query = "UPDATE 
                        punches
                      SET 
                        punches.open_status = 0,
                        punches.datetime_out = NOW()
                      WHERE 
                        punches.id_users = :id_users 
                        AND punches.open_status = 1
                      LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id_users" => $employeeId
            ));

            //Punch Out of the Open job if a user is currently logged into one
            if ("null" !== $currentJobId) {
                self::Query_PunchOutOfJob($db, $employeeId, $currentJobId);
            }
            $db->commit();
            return "true";
        } catch (Exception $ex) {
            $db->rollback();
            //TODO: Write $ex->error messages to database using the custom error_handler method in DBConnect
            return "false";
        }
    }

    private static function Query_PunchOutOfJob($db, $employeeId, $currentJobId) {
        //Update the original Job Punch
        $query = "UPDATE 
                    punches_jobs
                  SET 
                    punches_jobs.open_status = 0, 
                    punches_jobs.datetime_out = NOW()
                  WHERE 
                    punches_jobs.id_jobs = :id_jobs
                    AND punches_jobs.id_users = :id_users
                    AND punches_jobs.open_status = 1 
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ":id_jobs" => $currentJobId,
            ":id_users" => $employeeId,
        ));
    }

    private static function Query_PunchIntoJob($db, $employeeId, $currentJobId, $newJobId) {
        try {
            $db->beginTransaction();

            //Punch an employee out of a job if they are currently punched in
            if ("null" !== $currentJobId) {
                self::Query_PunchOutOfJob($db, $employeeId, $currentJobId);
            }

            //Add the new punch to the Database
            $query = "INSERT INTO 
                        punches_jobs 
                            (id, id_jobs, id_users, datetime_in, datetime_out, open_status)
                      VALUES 
                            (:id, :id_jobs, :id_users, NOW(), null, :open_status)";
            $stmt = $db->prepare($query);
            $stmt->execute(array(
                ":id" => null,
                ":id_jobs" => $newJobId,
                ":id_users" => $employeeId,
                ":open_status" => 1
            ));
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            return $ex->getMessage();
        }
    }

    private static function Query_GetRegularPunchesByEmployeeId($db, $employeeId, $regularPunchesPaired, $date) {
        $query = "SELECT
                    punches.id as Id,
                    punches.datetime_in as PunchIn,
                    punches.datetime_out as PunchOut,
                    SUM(UNIX_TIMESTAMP(punches.datetime_out) - UNIX_TIMESTAMP(punches.datetime_in)) as DurationInSeconds
                  FROM
                    punches
                  WHERE
                    punches.id_users = :employeeId
                    AND(
                        DATE(punches.datetime_in) = :date_in
                        OR DATE(punches.datetime_out) = :date_out
                        OR punches.datetime_out = NULL
                        )
                  GROUP BY
                    punches.id
                  ORDER BY 
                    punches.datetime_in DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId,
            ':date_in' => $date,
            ':date_out' => $date
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($regularPunchesPaired['Punches'], array(
                'Id' => strval($row->Id),
                'PunchIn' => strval($row->PunchIn),
                'PunchOut' => strval($row->PunchOut),
                'DurationInSeconds' => strval($row->DurationInSeconds)
            ));
            $regularPunchesPaired['TotalDurationInSeconds'] += $row->DurationInSeconds;
        }

        $regularPunchesPaired['TotalDurationInSeconds'] = strval(($regularPunchesPaired['TotalDurationInSeconds']));
        return $regularPunchesPaired;
    }
    private static function Query_GetRangeRegularPunchesByEmployeeId($db, $employeeId, $regularPunchesPaired, $weekStart, $weekEnd) {
        //Set MySQL variables
        $varQuery = "SET @weekStart = :weekStart";
        $stmt = $db->prepare($varQuery);
        $stmt->execute(array(
            ':weekStart' => $weekStart
        ));
        $varQuery = "SET @weekEnd = :weekEnd";
        $stmt = $db->prepare($varQuery);
        $stmt->execute(array(
            ':weekEnd' => $weekEnd
        ));
        
        //Query
        $query = "SELECT
                    punches.id as Id,
                    punches.datetime_in as PunchIn,
                    punches.datetime_out as PunchOut,
                    SUM(UNIX_TIMESTAMP(punches.datetime_out) - UNIX_TIMESTAMP(punches.datetime_in)) as DurationInSeconds
                  FROM
                    punches
                  WHERE
                    punches.id_users = :employeeId
                    AND(
                        DATE(punches.datetime_in) BETWEEN @weekStart AND @weekEnd
                        OR DATE(punches.datetime_out) BETWEEN @weekStart AND @weekEnd
                        OR punches.datetime_out = NULL
                        )
                  GROUP BY
                    punches.id
                  ORDER BY 
                    punches.datetime_in DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($regularPunchesPaired['Punches'], array(
                'Id' => strval($row->Id),
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

    private static function Query_GetJobPunchesByEmployeeId($db, $employeeId, $jobPunchesPaired, $date) {
//        $query = "SELECT
//                    alias.id AS ParentId,
//                    punches_jobs.id AS ChildId,
//                    alias.datetime AS PunchIn,
//                    punches_jobs.datetime AS PunchOut,
//                    alias.jobId as JobId,
//                    alias.description as JobDescription,
//                    SUM(UNIX_TIMESTAMP(punches_jobs.datetime) - UNIX_TIMESTAMP(alias.datetime)) AS DurationInSeconds
//                  FROM (
//                        SELECT
//                          punches_jobs.id,
//                          punches_jobs.id_parent_punch_jobs,
//                          punches_jobs.datetime,
//                          jobs.id as jobId,
//                          jobs.description,
//                          jobs.code
//                        FROM
//                          punches_jobs
//                        INNER JOIN
//                          jobs ON punches_jobs.id_jobs = jobs.id
//                        WHERE
//                          punches_jobs.id_users = :employeeId
//                          AND punches_jobs.open_status = 0
//                          AND DATE(punches_jobs.datetime) = :date
//                    ) AS alias
//                    INNER JOIN
//                      punches_jobs ON alias.id = punches_jobs.id_parent_punch_jobs
//                    GROUP BY alias.id
//                    ORDER BY
//                      alias.datetime DESC";
        $query = "SELECT
                      punches_jobs.id AS Id,
                      punches_jobs.datetime_in AS PunchIn,
                      punches_jobs.datetime_out AS PunchOut,
                      SUM(UNIX_TIMESTAMP(punches_jobs.datetime_out) - UNIX_TIMESTAMP(punches_jobs.datetime_in)) AS DurationInSeconds,
                      jobs.id AS JobId,
                      jobs.description as JobDescription,
                      jobs.code AS JobCode
                  FROM
                      punches_jobs
                  INNER JOIN
                      jobs ON punches_jobs.id_jobs = jobs.id
                  WHERE
                      punches_jobs.id_users = :employeeId
                      AND (
                          DATE(punches_jobs.datetime_in) = :date_in
                          OR DATE(punches_jobs.datetime_out) = :date_out
                          OR DATE(punches_jobs.datetime_out) = NULL
                          )
                  GROUP BY
                        punches_jobs.id
                  ORDER BY
                        punches_jobs.datetime_in DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId,
            ':date_in' => $date,
            ':date_out' => $date
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($jobPunchesPaired['Punches'], array(
                'Id' => strval($row->Id),
                'PunchIn' => strval($row->PunchIn),
                'PunchOut' => strval($row->PunchOut),
                'DurationInSeconds' => strval($row->DurationInSeconds),
                'JobInformation' => array(
                    'Id' => strval($row->JobId),
                    'Description' => strval($row->JobDescription),
                    'Code' => strval($row->JobCode)
                )
            ));
            $jobPunchesPaired['TotalDurationInSeconds'] += $row->DurationInSeconds;
        }
        $jobPunchesPaired['TotalDurationInSeconds'] = strval($jobPunchesPaired['TotalDurationInSeconds']);
        return $jobPunchesPaired;
    }
    
    private static function Query_GetRangeJobPunchesByEmployeeId($db, $employeeId, $jobPunchesPaired, $weekStart, $weekEnd) {
//        $query = "SELECT
//                    alias.id AS ParentId,
//                    punches_jobs.id AS ChildId,
//                    alias.datetime AS PunchIn,
//                    punches_jobs.datetime AS PunchOut,
//                    alias.jobId as JobId,
//                    alias.description as JobDescription,
//                    SUM(UNIX_TIMESTAMP(punches_jobs.datetime) - UNIX_TIMESTAMP(alias.datetime)) AS DurationInSeconds
//                  FROM (
//                        SELECT
//                          punches_jobs.id,
//                          punches_jobs.id_parent_punch_jobs,
//                          punches_jobs.datetime,
//                          jobs.id as jobId,
//                          jobs.description,
//                          jobs.code
//                        FROM
//                          punches_jobs
//                        INNER JOIN
//                          jobs ON punches_jobs.id_jobs = jobs.id
//                        WHERE
//                          punches_jobs.id_users = :employeeId
//                          AND punches_jobs.open_status = 0
//                          AND DATE(punches_jobs.datetime) = :date
//                    ) AS alias
//                    INNER JOIN
//                      punches_jobs ON alias.id = punches_jobs.id_parent_punch_jobs
//                    GROUP BY alias.id
//                    ORDER BY
//                      alias.datetime DESC";
        $varQuery = "SET @weekStart = :weekStart";
        $stmt = $db->prepare($varQuery);
        $stmt->execute(array(
            ':weekStart' => $weekStart
        ));
        $varQuery = "SET @weekEnd = :weekEnd";
         $stmt = $db->prepare($varQuery);
        $stmt->execute(array(
            ':weekEnd' => $weekEnd
        ));
        $query = "SELECT
                      punches_jobs.id AS Id,
                      punches_jobs.datetime_in AS PunchIn,
                      punches_jobs.datetime_out AS PunchOut,
                      SUM(UNIX_TIMESTAMP(punches_jobs.datetime_out) - UNIX_TIMESTAMP(punches_jobs.datetime_in)) AS DurationInSeconds,
                      jobs.id AS JobId,
                      jobs.description as JobDescription,
                      jobs.code AS JobCode
                  FROM
                      punches_jobs
                  INNER JOIN
                      jobs ON punches_jobs.id_jobs = jobs.id
                  WHERE
                      punches_jobs.id_users = :employeeId
                      AND (
                          DATE(punches_jobs.datetime_in) BETWEEN @weekStart AND @weekEnd
                          OR DATE(punches_jobs.datetime_out) BETWEEN @weekStart AND @weekEnd
                          OR DATE(punches_jobs.datetime_out) = NULL
                          )
                  GROUP BY
                        punches_jobs.id
                  ORDER BY
                        punches_jobs.datetime_in DESC";
        $stmt = $db->prepare($query);
        $stmt->execute(array(
            ':employeeId' => $employeeId
        ));

        while ($row = $stmt->fetchObject()) {
            array_push($jobPunchesPaired['Punches'], array(
                'Id' => strval($row->Id),
                'PunchIn' => strval($row->PunchIn),
                'PunchOut' => strval($row->PunchOut),
                'DurationInSeconds' => strval($row->DurationInSeconds),
                'JobInformation' => array(
                    'Id' => strval($row->JobId),
                    'Description' => strval($row->JobDescription),
                    'Code' => strval($row->JobCode)
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
            $jobPunchesOpen['JobInformation'] = array(
                'Id' => strval($row->JobId),
                'Description' => strval($row->JobDescription)
            );
        }
        return $jobPunchesOpen;
    }

}

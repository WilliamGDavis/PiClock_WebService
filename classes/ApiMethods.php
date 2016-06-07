<?php

/**
 * Helper functions to call methods from classes
 *
 * @author William G Davis
 */
require_once './classes/Authentication.php';
require_once './classes/Punch.php';
require_once './classes/Employee.php';
require_once './classes/Job.php';

class ApiMethods {

}

class ApiMethods_Authentication {

    public static function PinLogin($pin) {
        try {
            return Authentication::PinLogin($pin);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

class ApiMethods_Punch {

    public static function GetSingleDayPunchesByEmployeeId($employeeId) {
        try {
            return Punch::GetSingleDayPunchesByEmployeeId($employeeId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    function PunchIn($employeeId) {
        try {
            return Punch::PunchIn($employeeId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    function PunchOut($employeeId, $currentJobId) {
        try {
            return Punch::PunchOut($employeeId, $currentJobId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

class ApiMethods_Employee {

    public static function GetEmployeeList() {
        try {
            return Employee::GetEmployeeList();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public static function CheckLoginStatus($employeeId) {
        try {
            return Employee::CheckLoginStatus($employeeId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public static function GetCurrentJob($employeeId) {
        try {
            return Employee::GetCurrentJob($employeeId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

class ApiMethods_Database {

    public static function TestDBConnection() {
        $ConnectionStatus = new DBConnect();
        return $ConnectionStatus->CheckConnection();
    }

}

class ApiMethods_Job {

    public static function ChangeJob($employeeId, $jobId, $newJobId) {
        try {
            return Job::ChangeJob($employeeId, $jobId, $newJobId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public static function GetJobIdByJobDescription($jobDescription) {
        try {
            return Job::GetJobIdByJobDescription($jobDescription);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

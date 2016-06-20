<?php

/**
 * Helper functions to call methods from classes
 *
 * @author William G Davis
 */
require_once './classes/Authentication.php';
require_once './classes/Punch.php';
require_once './classes/Employee.php';
require_once './classes/Database.php';
require_once './classes/Job.php';
require_once './classes/Settings.php';

class ApiMethods {
    
}

class ApiMethods_Authentication {

    public static function PinLogin($pin) {
        try {
            return Authentication::PinLogin($pin);
        } catch (PDOException $ex) {
            return $ex->getMessage();
        }
    }

}

class ApiMethods_Punch {

    function PunchIn($employeeId) {
        return Punch::PunchIn($employeeId);
    }

    function PunchOut($employeeId, $currentJobId) {
        return Punch::PunchOut($employeeId, $currentJobId);
    }

    public static function PunchIntoJob($employeeId, $currentJobId, $newJobId) {
        try {
            return Punch::PunchIntoJob($employeeId, $currentJobId, $newJobId);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public static function GetSingleDayPunchesByEmployeeId($employeeId) {
        return Punch::GetSingleDayPunchesByEmployeeId($employeeId);
    }
    
    public static function GetRangePunchesByEmployeeId($employeeId){
        return Punch::GetRegularPunchesBetweenDates($employeeId);
    }

    public static function GetThisWeeksPunchesByEmployeeId($employeeId) {
        return Punch::GetThisWeeksPunchesByEmployeeId($employeeId);
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

    public static function GetJobIdByJobDescription($jobDescription) {
        return Job::GetJobIdByJobDescription($jobDescription);
    }

}

class ApiMethods_Settings {

    public static function GetSettings() {
        try {
            return Settings::GetSettings();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

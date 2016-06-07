<?php

require_once './classes/DBConnect.php';
require_once './classes/Employee.php';
require_once './classes/Punch.php';
require_once './classes/Settings.php';
require_once './classes/ApiMethods.php';

//Basic HTTP Authentication
$auth = new Authentication_Api();
if (false === $auth->TryHttpBasicAuthentication()) {
    exit(json_encode("An error has occured"));
}

//Decode the POST array to a JSON object
$postData = json_decode(file_get_contents('php://input'));

function JobPunch($employeeId, $newJobId) {
    try {
        return Employee::JobPunch($employeeId, $newJobId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function PunchIntoJob($employeeId, $currentJobId, $newJobId) {
    try {
        return Punch::PunchIntoJob($employeeId, $currentJobId, $newJobId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function GetThisWeeksPunchesByEmployeeId($employeeId) {
    try {
        return Punch::GetThisWeeksPunchesByEmployeeId($employeeId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function GetSettings() {
    return Settings::GetSettings();
}

function get_current_job_by_employee_id($id) {
    $job = Employee::ReturnCurrentJobByEmployeeId($id);

    if (!empty($job)) {
        return $job;
    } else {
        return NULL;
    }
}

$possible_actions = array(
    "GetEmployeeList",
    "PinLogin",
    "test_connection",
    "get_current_job_number",
    "add_user",
    "PunchIn",
    "PunchOut",
    "CheckLoginStatus",
    "GetSettings",
    "GetCurrentJob",
    "ChangeJob",
    "GetJobIdByJobDescription",
    "JobPunch",
    "PunchIntoJob",
    "GetSingleDayPunchesByEmployeeId",
    "GetThisWeeksPunchesByEmployeeId"
);

$value = "An error has occured";



if (isset($_GET["action"]) && in_array($_GET["action"], $possible_actions)) {
    switch ($_GET["action"]) {
        default:
            break;
    }
} elseif (isset($postData->action) && in_array($postData->action, $possible_actions)) {
    switch ($postData->action) {
        /**
         * Check for a valid connection to the database
         * @return string true or false
         */
        case "test_connection":
            $value = ApiMethods_Database::TestDBConnection();
            break;
        /**
         * Return a list of all employees from the database
         * @return array
         * @return string $ex->message
         */
        case 'GetEmployeeList':
            $value = ApiMethods_Employee::GetEmployeeList();
            break;
        /**
         * Return a user from the database based on a PIN
         * @param string $pin
         * @return array
         * @return string $ex->message
         */
        case 'PinLogin':
            $pin = (isset($postData->pin)) ? $postData->pin : null;
            if (null !== $pin) {
                $value = ApiMethods_Authentication::PinLogin($pin);
            }
            break;
        case 'PunchIn':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Punch::PunchIn($employeeId);
            }
            break;
        case 'PunchOut':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $currentJobId = (isset($postData->currentJobId)) ? $postData->currentJobId : null;
            if (null !== $employeeId && null !== $currentJobId) {
                $value = ApiMethods_Punch::PunchOut($employeeId, $currentJobId);
            }
            break;
        /**
         * Check to see if a user is currently punched into the system
         * @param string $employeeId
         * @return bool
         */
        case 'CheckLoginStatus':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Employee::CheckLoginStatus($employeeId);
            }
            break;
        /**
         * Returns the current job an employee is punched into
         * @param string $employeeId
         * @return array job information
         * @return string $ex->message
         */
        case 'GetCurrentJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Employee::GetCurrentJob($employeeId);
            }
            break;
        /**
         * Change the job an employee is punched into
         * @param string $employeeId
         * @param string $jobId
         * @param string $newJobId
         */
        case 'ChangeJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $jobId = (isset($postData->jobId)) ? $postData->jobId : null;
            $newJobId = (isset($postData->newJobId)) ? $postData->newJobId : null;
            if (null !== $employeeId && null !== $jobId && null !== $newJobId) {
                $value = ApiMethods_Job::ChangeJob($employeeId, $jobId, $newJobId);
            }
            break;
        /**
         * Return the Job Id from the database, based on the Job Description
         * @param string $jobDescription
         * @return Int
         * TODO: Should this be returning an INT???
         */
        case 'GetJobIdByJobDescription':
            $jobDescription = (isset($postData->jobDescription)) ? $postData->jobDescription : null;
            if (null !== $jobDescription) {
                $value = (int) ApiMethods_Job::GetJobIdByJobDescription($jobDescription);
            }
            break;
        case 'JobPunch':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $newJobId = (isset($postData->newJobId)) ? $postData->newJobId : null;
            if (null !== $employeeId && null !== $newJobId) {
                $value = JobPunch($employeeId, $newJobId);
            }
            break;
        case 'PunchIntoJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $currentJobId = (isset($postData->currentJobId)) ? $postData->currentJobId : null;
            $newJobId = (isset($postData->newJobId)) ? $postData->newJobId : null;
            if (null !== $employeeId && null !== $currentJobId && null !== $newJobId) {
                $value = PunchIntoJob($employeeId, $currentJobId, $newJobId);
            }
            break;
        case 'GetSettings':
            $value = GetSettings();
            break;
        /**
         * Return an array of an employee's punches for the day
         * @param string $employeeId
         * @return array
         * @return string $ex->message
         */
        case "GetSingleDayPunchesByEmployeeId":
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Punch::GetSingleDayPunchesByEmployeeId($employeeId);
            }
            break;
        case "GetThisWeeksPunchesByEmployeeId":
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = GetThisWeeksPunchesByEmployeeId($employeeId);
            }
            break;
        default:
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    
}

//return JSON string
header('Content-type: application/json');
exit(json_encode($value));

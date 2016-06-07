<?php

require_once './classes/DBConnect.php';
require_once './classes/Employee.php';
require_once './classes/Punch.php';
//require_once './classes/Authentication.php';
require_once './classes/Settings.php';
require_once './classes/ApiMethods.php';

//Basic HTTP Authentication
//TODO: Ensure the RPC calls are passed over SSL
if (false === basicAuthenticationOverSsl()) {
    exit();
}

//Decode the POST array to a JSON object
$postData = json_decode(file_get_contents('php://input'));


/*
 * Return the entire list of employees from the DB
 */

function GetEmployeeList() {
    try {
        return Employee::GetEmployeeList();
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function GetSingleDayPunchesByEmployeeId($employeeId) {
    try {
        return Punch::GetSingleDayPunchesByEmployeeId($employeeId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function CheckLoginStatus($employeeId) {
    try {
        return Employee::CheckLoginStatus($employeeId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function test_connection() {
    $ConnectionStatus = new DBConnect();
    return $ConnectionStatus->CheckConnection();
}

function CheckCurrentJob($employeeId) {
    try {
        return Employee::CheckCurrentJob($employeeId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function ChangeJob($employeeId, $jobId, $newJobId) {
    try {
        return Employee::ChangeJob($employeeId, $jobId, $newJobId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function GetJobIdByJobDescription($jobDescription) {
    try {
        return Employee::GetJobIdByJobDescription($jobDescription);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

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

/**
 * Authenticate an application using Basic Authentication
 * TODO: Tighten up the security
 * @return bool
 */
function basicAuthenticationOverSsl() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        return false;
    }

    $username = filter_var($_SERVER['PHP_AUTH_USER']);
    $password = filter_var($_SERVER['PHP_AUTH_PW']);

    if ('user' != $username || 'pass' != $password) {
        return false;
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
    "CheckCurrentJob",
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
        case "test_connection":
            $value = test_connection();
            break;
        case "get_current_job_number":
            if (isset($_GET["id"])) {
                $value = get_current_job_by_employee_id($_GET["id"]);
            } else {
                $value = "Missing Argument";
            }
            break;
        default:
            break;
    }
} elseif (isset($postData->action) && in_array($postData->action, $possible_actions)) {
    switch ($postData->action) {
        case "test_connection":
            $value = test_connection();
            break;
        case 'GetEmployeeList':
            $value = GetEmployeeList();
            break;
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
        case 'CheckLoginStatus':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = CheckLoginStatus($employeeId);
            }
            break;
        case 'CheckCurrentJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = CheckCurrentJob($employeeId);
            }
            break;
        case 'ChangeJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $jobId = (isset($postData->jobId)) ? $postData->jobId : null;
            $newJobId = (isset($postData->newJobId)) ? $postData->newJobId : null;
            if (null !== $employeeId && null !== $jobId && null !== $newJobId) {
                $value = ChangeJob($employeeId, $jobId, $newJobId);
            }
            break;
        case 'GetJobIdByJobDescription':
            $jobDescription = (isset($postData->jobDescription)) ? $postData->jobDescription : null;
            if (null !== $jobDescription) {
                $value = (int) GetJobIdByJobDescription($jobDescription);
            } else {
                $value = "";
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
        case "GetSingleDayPunchesByEmployeeId":
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = GetSingleDayPunchesByEmployeeId($employeeId);
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

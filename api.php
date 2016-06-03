<?php

require_once './classes/DBConnect.php';
require_once './classes/Employee.php';
require_once './classes/Punch.php';
require_once './classes/Authentication.php';
require_once './classes/Settings.php';


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

/*
 * Function: Try logging in using a PIN
 * Expected Result: An employee array with their data
 */

function PinLogin($pin) {
    try {
        return Authentication::PinLogin($pin);
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

function JobLookup($jobDescription) {
    try {
        return Employee::JobLookup($jobDescription);
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

$possible_url = array(
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
    "JobLookup",
    "JobPunch",
    "PunchIntoJob",
    "GetSingleDayPunchesByEmployeeId",
    "GetThisWeeksPunchesByEmployeeId"
);
$value = "An error has occured";

if (isset($_GET["action"]) && in_array($_GET["action"], $possible_url)) {
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
} elseif (isset($_POST['action']) && in_array($_POST["action"], $possible_url)) {
    switch ($_POST['action']) {
        case 'add_user':
            break;
        case 'GetEmployeeList':
            $value = GetEmployeeList();
            break;
        case 'PinLogin':
            if (isset($_POST["pin"])) {
                $value = PinLogin($_POST["pin"]);
            } else {
                $value = null;
            }
            break;
        case 'PunchIn':
            if (isset($_POST["employeeId"])) {
                $value = PunchIn($_POST['employeeId']);
            } else {
                $value = null;
            }
            break;
        case 'PunchOut':
            if (isset($_POST["employeeId"]) && isset($_POST['currentJobId'])) {
                $value = PunchOut($_POST['employeeId'], $_POST['currentJobId']);
            } else {
                $value = null;
            }
            break;
        case 'CheckLoginStatus':
            if (isset($_POST['employeeId'])) {
                $value = CheckLoginStatus($_POST['employeeId']);
            } else {
                $value = null;
            }
            break;
        case 'CheckCurrentJob':
            if (isset($_POST['employeeId'])) {
                $value = CheckCurrentJob($_POST['employeeId']);
            } else {
                $value = null;
            }
            break;
        case 'ChangeJob':
            if (isset($_POST['employeeId']) && isset($_POST['jobId']) && isset($_POST['newJobId'])) {
                $value = ChangeJob($_POST['employeeId'], $_POST['jobId'], $_POST['newJobId']);
            } else {
                $value = null;
            }
            break;
        case 'JobLookup':
            if (isset($_POST['jobDescription'])) {
                $value = JobLookup($_POST['jobDescription']);
            } else {
                $value = null;
            }
            break;
        case 'JobPunch':
            if (isset($_POST['employeeId']) && isset($_POST['newJobId'])) {
                $value = JobPunch($_POST['employeeId'], $_POST['newJobId']);
            } else {
                $value = null;
            }
            break;
        case 'PunchIntoJob':
            if (isset($_POST['employeeId']) && isset($_POST['currentJobId']) && isset($_POST['newJobId'])) {
                $value = PunchIntoJob($_POST['employeeId'], $_POST['currentJobId'], $_POST['newJobId']);
            } else {
                $value = null;
            }
            break;
        case 'GetSettings':
            $value = GetSettings();
            break;
        case "GetSingleDayPunchesByEmployeeId":
            if (isset($_POST['employeeId'])) {
                $value = GetSingleDayPunchesByEmployeeId($_POST['employeeId']);
            } else {
                $value = null;
            }
            break;
        case "GetThisWeeksPunchesByEmployeeId":
            if (isset($_POST['employeeId'])) {
                $value = GetThisWeeksPunchesByEmployeeId($_POST['employeeId']);
            } else {
                $value = null;
            }
            break;
        default:
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $value = parse_str(file_get_contents("php://input"));
//    switch ($putArray['action']){
//        case 'PunchOut':
//            $value = "Got here";
//        default:
//            break;
//    }
}

//return JSON array
exit(json_encode($value));

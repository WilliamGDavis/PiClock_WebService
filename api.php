<?php

require_once './classes/Employee.php';
require_once './classes/DBConnect.php';

function get_employee_by_id($id) {
    $employee_info = array();

    //Pull employee info and build a JSON array
    switch ($id) {
        case 1:
            $employee_info = array("fname" => "Will", "mname" => "G", "lname" => "Davis");
            break;
        case 2:
            $employee_info = array("fname" => "Henry", "mname" => "R", "lname" => "Jedynak");
            break;
        default:
            break;
    }

    return $employee_info;
}

/*
 * Return the entire list of employees from the DB
 */

function get_all_employees() {
    try {
        return Employee::ReturnAllEmployees();
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function login_PIN($pin) {
    try {
        return Employee::Login_PIN($pin);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function PunchIn($employeeId, $type, $open_status) {
    $params = [
        "employeeId" => $employeeId,
        "type" => $type,
        "open_status" => $open_status
    ];

    try {
        return Employee::PunchIn($params);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function PunchOut($employeeId, $type, $open_status) {
    $params = [
        "employeeId" => $employeeId,
        "type" => $type,
        "open_status" => $open_status
    ];

    try {
        return Employee::PunchOut($params);
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

function JobLookup($jobDescription){
    try {
        return Employee::JobLookup($jobDescription);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function JobPunch($employeeId, $newJobId){
    try {
        return Employee::JobPunch($employeeId, $newJobId);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function get_settings() {
    return Employee::GetSettings();
}

function get_current_job_by_employee_id($id) {
    $job = Employee::ReturnCurrentJobByEmployeeId($id);

    if (!empty($job)) {
        return $job;
    } else {
        return NULL;
    }
}

$possible_url = array("get_employee",
    "get_all_employees",
    "Pin_Login",
    "test_connection",
    "get_current_job_number",
    "add_user",
    "PunchIn",
    "PunchOut",
    "CheckLoginStatus",
    "RetrieveSettings",
    "CheckCurrentJob",
    "ChangeJob",
    "JobLookup",
    "JobPunch"
);
$value = "An error has occured";

if (isset($_GET["action"]) && in_array($_GET["action"], $possible_url)) {
    switch ($_GET["action"]) {
        case "get_employee":
            if (isset($_GET["id"])) {
                $value = get_employee_by_id($_GET["id"]);
            } else {
                $value = "Missing Argument";
            }
            break;
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
        case 'get_all_employees':
            $value = get_all_employees();
            break;
        case 'Pin_Login':
            if (isset($_POST["pin"])) {
                $value = login_pin($_POST["pin"]);
            } else {
                $value = null;
            }
            break;
        case 'PunchIn':
            if (isset($_POST["employeeId"])) {
                $value = PunchIn($_POST['employeeId'], $_POST['type'], $_POST['open_status']);
            } else {
                $value = null;
            }
            break;
        case 'PunchOut':
            if (isset($_POST["employeeId"])) {
                $value = PunchOut($_POST['employeeId'], $_POST['type'], $_POST['open_status']);
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
        case 'RetrieveSettings':
            $value = get_settings();
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

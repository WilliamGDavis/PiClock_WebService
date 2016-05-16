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

function test_connection() {
    $ConnectionStatus = new DBConnect();
    return $ConnectionStatus->CheckConnection();
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
    "PunchIn"
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
        default:
            break;
    }
}

//return JSON array
exit(json_encode($value));

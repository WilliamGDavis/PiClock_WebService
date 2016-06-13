<?php
//TODO: Force an SSL connection and die if there isn't one available

require_once './classes/Authentication.php';
require_once './classes/ApiMethods.php';

//Basic HTTP Authentication
$auth = new Authentication_Api();
if (false === $auth->TryHttpBasicAuthentication()) {
    exit(json_encode("An error has occured"));
}

//Decode the POST array to a JSON object
$postData = json_decode(file_get_contents('php://input'));

$possible_actions = array(
    "GetEmployeeList",
    "PinLogin",
    "test_connection",
    "get_current_job_number",
    "add_user",
    "PunchIn",
    "PunchIntoJob",
    "PunchOut",
    "CheckLoginStatus",
    "GetSettings",
    "GetCurrentJob",
    "ChangeJob",
    "GetJobIdByJobDescription",
    "GetSingleDayPunchesByEmployeeId",
    "GetThisWeeksPunchesByEmployeeId"
);

$value = "An error has occured";


//RPC Actions
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
        /**
         * Allow a user to Punch In to the database (Regular Punch)
         * @param string $employeeId
         * @return string "true" (Successful)
         * @return string "false" (Unsuccessful or Employee already punched in)
         */
        case 'PunchIn':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Punch::PunchIn($employeeId);
            }
            break;
        /**
         * Allow a user to Punch Out to the database (Regular Punch)
         * @param string $employeeId
         */
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
         * @return string jobId
         * @return string "" if non-existing jobId
         */
        case 'GetJobIdByJobDescription':
            $jobDescription = (isset($postData->jobDescription)) ? $postData->jobDescription : null;
            if (null !== $jobDescription) {
                $value = ApiMethods_Job::GetJobIdByJobDescription($jobDescription);
            }
            break;
        /**
         * Punch an employee into a job
         * @param string $employeeId
         * @param string $currentJobId
         * @param string $newJobid
         */
        case 'PunchIntoJob':
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            $currentJobId = (isset($postData->currentJobId)) ? $postData->currentJobId : null;
            $newJobId = (isset($postData->newJobId)) ? $postData->newJobId : null;
            if (null !== $employeeId && null !== $currentJobId && null !== $newJobId) {
                $value = ApiMethods_Punch::PunchIntoJob($employeeId, $currentJobId, $newJobId);
            }
            break;
        /**
         * Return an array of settings saved in the database
         * @return array
         */
        case 'GetSettings':
            $value = ApiMethods_Settings::GetSettings();
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
        /**
         * Return an array of an employee's punches for the week
         * @param string $employeeId
         * @return array
         * @return string $ex->message
         */
        case "GetThisWeeksPunchesByEmployeeId":
            $employeeId = (isset($postData->employeeId)) ? $postData->employeeId : null;
            if (null !== $employeeId) {
                $value = ApiMethods_Punch::GetThisWeeksPunchesByEmployeeId($employeeId);
            }
            break;
        default:
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {

}

//Return JSON-Encoded string
header('Content-type: application/json');
exit(json_encode($value));

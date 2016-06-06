<?php
/**
 * Description of ApiMethods
 *
 * @author William G Davis
 */
require_once './classes/Authentication.php';
require_once './classes/Punch.php';

class ApiMethods {
    
}

class ApiMethods_Authentication {

    /**
     * Function: Try logging in using a PIN
     * @param string $pin
     * @return array
     */
    public static function PinLogin($pin) {
        try {
            return Authentication::PinLogin($pin);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

}

class ApiMethods_Punch {

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

<?php
/**
 * Description of Database
 *
 * @author William G Davis
 * @copyright (c) 2016, William G Davis
 */
require_once './classes/DBConnect.php';
class Database {
    
    /**
     * Check for a valid connection to the database
     * @return string true or false
     */
    public static function TestDBConnection() {
        $ConnectionStatus = new DBConnect();
        return $ConnectionStatus->CheckConnection();
    }

}

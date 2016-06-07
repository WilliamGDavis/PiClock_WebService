<?php
/**
 * Description of Database
 *
 * @author Owner
 */
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

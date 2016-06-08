<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Settings
 *
 * @author Owner
 */
require_once 'DBConnect.php';

class Settings {
    /**
     * Collect any settings saved in the database
     * @return array
     */
    public static function GetSettings() {
        $db = new DBConnect();
        $db = $db->DBObject;
        $settings_array = self::Query_GetSettings($db);
        $db = null;
        return $settings_array;
    }
    
    private static function Query_GetSettings($db) {
        $settings_array = [];
        
        $query = "SELECT 
                    settings.id, 
                    settings.name, 
                    settings.value
                  FROM 
                    settings";
        $stmt = $db->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetchObject()) {
            array_push($settings_array, array(
                'id' => $row->id,
                'name' => $row->name,
                'value' => $row->value
            ));
        }
        return $settings_array;
    }
}

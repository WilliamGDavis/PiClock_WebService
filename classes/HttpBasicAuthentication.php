<?php

/**
 * Description of HttpBasicAuthentication
 * Handles HTTP Basic Authentication (over SSL)
 *
 * @author William G Davis
 * @copyright (c) 2016, William G Davis
 */
class HttpBasicAuthentication {

    private $Username;
    private $Password;

    function __construct() {
        $this->Username = (isset($_SERVER['PHP_AUTH_USER'])) ?
                filter_var($_SERVER['PHP_AUTH_USER']) : null;
        $this->Password = (isset($_SERVER['PHP_AUTH_PW'])) ?
                filter_var($_SERVER['PHP_AUTH_PW']) : null;
    }

    /**
     * Authenticate against the RPC server using HTTP Basic Authentication
     * TODO: Tighten up the security using SSL and encryption
     * @return bool
     */
    function TryHttpBasicAuthentication() {
        //Check for null values
        if (null === $this->Username || null === $this->Password) {
            return false;
        }

        //Ensure PHP can read the Environment variables
        if (false === getenv("API_USERNAME") || false === getenv("API_PASSWORD")) {
            return false;
        }

        //Match the username:password against the Environment variables
        if (getenv("API_USERNAME") != $this->Username || getenv("API_PASSWORD") != $this->Password) {
            return false;
        }
    }

}

<?php
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        $username = "CLECKHUDDERS_MARKET"; 
        $password = "Nepal#$987";  
        $connection_string = "//localhost/xe"; 
        $conn = oci_connect($username, $password, $connection_string);

        if (!$conn) {
            $e = oci_error();
            die ("Connection Error: " . $e['message']);
        }

        return $conn;
    }
}


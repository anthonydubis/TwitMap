<?php

require("db_info.php");

function db_connect() {
	$conn = new mysqli($servername, $username, $password, $dbname, $port);
   	if (!$conn) {
    	throw new Exception('Sorry, we could not connect to the database at this time. Please try again.');
   	} else {
    	return $conn;
    }
}

?>
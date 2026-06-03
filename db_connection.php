<?php
// db_connection.php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "motify_db"; 

// Create the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
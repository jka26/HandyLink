<?php
    $servername = "localhost";
$username = "jemima.arhin";
$password = "SjQeLm1#";
$dbname = "webtech_fall2024_jemima_arhin";
    // Database connection (adjust with your credentials)
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check for connection error
    if ($conn->connect_error) {
        die("Database connection failed: ".$conn->connect_error);
    }
    else {
    //    echo "Connected Successfully";
    }
?>
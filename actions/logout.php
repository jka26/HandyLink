<?php
    //Start the session to have access to session data
    session_start();

    //Remove all session variables the log the user out
    session_unset(); //Clears all session data

    //Destroy the session completely
    session_destroy();

    //Redirect the user back to the login page
    header('Location: ../view/login.php');
    exit(); // Stop further script execution after redirect
?>
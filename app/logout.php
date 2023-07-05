<?php
include "login_database.php";
 
    unset($_SESSION["logged"]);
    header("Location: index.php"); 
	exit;
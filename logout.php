<?php
require_once 'config.php';

// Destroy session to logout user
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();

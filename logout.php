<?php
// Made by Hitansh Parikh - 23CS054
session_start();
require_once 'classes/Auth.php';

$auth = new Auth();
$result = $auth->logout();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit;
?>
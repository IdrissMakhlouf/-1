<?php
/**
 * User Logout Script
 * Heritage Platform - Algeria Cultural Heritage
 */

session_start();
session_destroy();
header('Location: index.php');
exit();
?>
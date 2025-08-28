<?php
session_start();
require_once '../../include/config.php';
require_once '../../include/database.php';

// Redirect to search page
header("Location: search.php");
exit();
?>

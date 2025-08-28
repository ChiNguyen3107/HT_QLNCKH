<?php
session_start();
require_once '../../include/config.php';

// Redirect to search page
header("Location: search.php");
exit();
?>

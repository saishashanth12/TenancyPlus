<?php
// logout.php
// This script destroys the user's session and logs them out.

// 1. Access the current session
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session itself
session_destroy();

// 4. THE FIX IS HERE: Redirect the user to 'login.php' (our renamed file)
header("Location: login.php");
exit;
?>


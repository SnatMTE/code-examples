<?php
//Simpleish forum written by Snat.
//Do as you wish with it, I was bored.

// Database host
// Database name
$dbname = '';
$username = ''; // Database username
$password = ''; // Database password

//Below is general config details. 

$site_name = "";
// Contact email for the site
$site_email = "";

// Application init marker
if (!defined('APP_INIT')) define('APP_INIT', true);
// Include security first
require_once(__DIR__ . "/includes/security.php");

// Now include other helpers
require_once(__DIR__ . "/includes/avatar.php");
require_once(__DIR__ . "/includes/db.php");

?>
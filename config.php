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

// If local config doesn't exist, redirect to installer (unless we are the installer)
if (!file_exists(__DIR__ . '/config.local.php') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// Load local configuration if available
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once(__DIR__ . '/config.local.php');
    // Include DB helper only when local config exists (avoid connecting during installer)
    require_once(__DIR__ . "/includes/db.php");
    require_once(__DIR__ . "/includes/avatar.php");
} else {
    // Installer mode: only include helpers that don't require DB
    require_once(__DIR__ . "/includes/avatar.php");
    // Ensure minimal variables exist to avoid notices
    $db_type = $db_type ?? null;
    $site_name = $site_name ?? '';
    $site_email = $site_email ?? '';
}

// If installer left behind but site not marked installed, you may need to set $installed in config.local.php
if (empty($installed) && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    // Let the installer handle it
}


?>
<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo function_exists('e') ? e(($page_name ?? '') . " - " . ($site_name ?? '')) : htmlspecialchars(($page_name ?? '') . " - " . ($site_name ?? ''), ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" type="text/css" href="style.css" />
<script src="https://cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
</head>
<body>
    <div id="wrapper">
        <div id="header">
          <h1><a href="./"><?php echo function_exists('e') ? e($site_name ?? '') : htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?></a></h1>
        </div>
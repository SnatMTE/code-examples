<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function getGravatarImageUrl($email, $size = 80) {
    $hash = md5(strtolower(trim($email)));
    $url = "https://www.gravatar.com/avatar/$hash?s=$size&d=identicon";
    return "<img src='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "' alt='avatar' />";
}

?>
<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
</div>
<div id="footer">
<br />
<hr />
<p><?php echo function_exists('e') ? e($site_name ?? '') . " &copy; " . date('Y') : htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8') . " &copy; " . date('Y'); ?></p>
<p><a href="https://github.com/SnatMTE/PHPSimpleForum">Snat's Simple Forum</a> v0.04.</p>
</div>
</div>
</body>
</html>
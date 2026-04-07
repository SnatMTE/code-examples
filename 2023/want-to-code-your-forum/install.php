<?php
require_once 'config.php';

// If already installed, prevent re-running
if (file_exists(__DIR__ . '/config.local.php')) {
    include 'template/header.php';
    echo '<h2>Already installed</h2><p>The application appears to be installed. If you want to re-run the installer, remove <code>config.local.php</code> and <code>data/</code> (or the DB) first.</p>';
    include 'template/footer.php';
    exit;
}

$errors = [];
success:;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $site_name = trim($_POST['site_name'] ?? '');
        $site_email = trim($_POST['site_email'] ?? '');
        $db_type = ($_POST['db_type'] ?? 'sqlite');

        // DB params
        $mysql_host = trim($_POST['mysql_host'] ?? '127.0.0.1');
        $mysql_db = trim($_POST['mysql_db'] ?? 'forum');
        $mysql_user = trim($_POST['mysql_user'] ?? '');
        $mysql_pass = trim($_POST['mysql_pass'] ?? '');

        $sqlite_path = trim($_POST['sqlite_path'] ?? __DIR__ . '/data/forum.sqlite');

        $admin_user = sanitize_username($_POST['admin_user'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_pass2 = $_POST['admin_pass2'] ?? '';

        if (empty($site_name)) $errors[] = 'Site name is required.';
        if (!filter_var($site_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Site email is invalid.';
        if (empty($admin_user) || strlen($admin_user) < 3) $errors[] = 'Admin username is required (min 3 chars).';
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin email is invalid.';
        if (strlen($admin_pass) < 8) $errors[] = 'Admin password must be at least 8 characters.';
        if ($admin_pass !== $admin_pass2) $errors[] = 'Password confirmation does not match.';

        // Validate DB connection
        try {
            if ($db_type === 'mysql') {
                $dsn = "mysql:host={$mysql_host};charset=utf8mb4";
                $pdo_test = new PDO($dsn, $mysql_user, $mysql_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                // Create DB if not exists and select it
                $pdo_test->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','', $mysql_db) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo_test = null;
                $dsn_db = "mysql:host={$mysql_host};dbname={$mysql_db};charset=utf8mb4";
                $pdo = new PDO($dsn_db, $mysql_user, $mysql_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            } else {
                // SQLite
                $dir = dirname($sqlite_path);
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $dsn = "sqlite:" . $sqlite_path;
                $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                $pdo->exec('PRAGMA foreign_keys = ON');
            }

            // Run schema
            if ($db_type === 'mysql') {
                $sqls = [
                    "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255) NOT NULL UNIQUE, email VARCHAR(255), password VARCHAR(255) NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_expires DATETIME DEFAULT NULL)",
                    "CREATE TABLE IF NOT EXISTS topics (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), body TEXT, user_id INT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL)",
                    "CREATE TABLE IF NOT EXISTS replies (id INT AUTO_INCREMENT PRIMARY KEY, body TEXT, user_id INT, topic_id INT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL, FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE)"
                ];
            } else {
                $sqls = [
                    "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, email TEXT, password TEXT NOT NULL, reset_token TEXT, reset_expires DATETIME)",
                    "CREATE TABLE IF NOT EXISTS topics (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT, user_id INTEGER, created_at DATETIME DEFAULT (datetime('now')), FOREIGN KEY (user_id) REFERENCES users(id))",
                    "CREATE TABLE IF NOT EXISTS replies (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT, user_id INTEGER, topic_id INTEGER, created_at DATETIME DEFAULT (datetime('now')), FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (topic_id) REFERENCES topics(id))"
                ];
            }
            foreach ($sqls as $s) { $pdo->exec($s); }

            // Insert admin
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute(['username' => $admin_user, 'email' => $admin_email, 'password' => $hash]);

            // Write config.local.php
            $config_content = "<?php\n// Generated by installer. Do not commit this file.\n$site_name_line = '$site_name';\n";
            // Build config content
            $cfg = "<?php\n// Generated by installer. Do not commit this file.\n";
            $cfg .= "\$site_name = '" . addslashes($site_name) . "';\n";
            $cfg .= "\$site_email = '" . addslashes($site_email) . "';\n";
            $cfg .= "\$db_type = '" . ($db_type === 'mysql' ? 'mysql' : 'sqlite') . "';\n";
            if ($db_type === 'mysql') {
                $cfg .= "\$host = '" . addslashes($mysql_host) . "';\n";
                $cfg .= "\$dbname = '" . addslashes($mysql_db) . "';\n";
                $cfg .= "\$username = '" . addslashes($mysql_user) . "';\n";
                $cfg .= "\$password = '" . addslashes($mysql_pass) . "';\n";
            } else {
                $cfg .= "\$sqlite_path = '" . addslashes($sqlite_path) . "';\n";
            }
            $cfg .= "\$installed = true;\n";
            $cfg .= "?>\n";

            file_put_contents(__DIR__ . '/config.local.php', $cfg);
            @chmod(__DIR__ . '/config.local.php', 0600);

            // Success - redirect
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            $errors[] = 'Error during setup: ' . $e->getMessage();
            error_log('Installer error: ' . $e->getMessage());
        }
    }
}

$page_name = 'Installer';
include 'template/header.php';
include 'template/left.php';
?>

<h2>Install Forum</h2>
<?php if (!empty($errors)) { echo '<div style="color:red"><ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $errors)) . '</li></ul></div>'; } ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

    <h3>Site</h3>
    <p><label>Site name: <input type="text" name="site_name" value="<?php echo e($_POST['site_name'] ?? ''); ?>"></label></p>
    <p><label>Site contact email: <input type="email" name="site_email" value="<?php echo e($_POST['site_email'] ?? ''); ?>"></label></p>

    <h3>Database</h3>
    <p>
        <label><input type="radio" name="db_type" value="sqlite" <?php if(($_POST['db_type'] ?? 'sqlite') === 'sqlite') echo 'checked'; ?>> SQLite (file)</label>
        &nbsp;
        <label><input type="radio" name="db_type" value="mysql" <?php if(($_POST['db_type'] ?? '') === 'mysql') echo 'checked'; ?>> MySQL</label>
    </p>

    <div id="sqlite_opts">
        <p><label>SQLite path: <input type="text" name="sqlite_path" value="<?php echo e($_POST['sqlite_path'] ?? __DIR__ . '/data/forum.sqlite'); ?>" style="width:400px"></label></p>
    </div>

    <div id="mysql_opts">
        <p><label>MySQL host: <input type="text" name="mysql_host" value="<?php echo e($_POST['mysql_host'] ?? '127.0.0.1'); ?>"></label></p>
        <p><label>Database name: <input type="text" name="mysql_db" value="<?php echo e($_POST['mysql_db'] ?? 'forum'); ?>"></label></p>
        <p><label>DB user: <input type="text" name="mysql_user" value="<?php echo e($_POST['mysql_user'] ?? ''); ?>"></label></p>
        <p><label>DB password: <input type="password" name="mysql_pass" value="<?php echo e($_POST['mysql_pass'] ?? ''); ?>"></label></p>
    </div>

    <h3>Admin account</h3>
    <p><label>Admin username: <input type="text" name="admin_user" value="<?php echo e($_POST['admin_user'] ?? 'admin'); ?>"></label></p>
    <p><label>Admin email: <input type="email" name="admin_email" value="<?php echo e($_POST['admin_email'] ?? ''); ?>"></label></p>
    <p><label>Password: <input type="password" name="admin_pass"></label></p>
    <p><label>Confirm password: <input type="password" name="admin_pass2"></label></p>

    <p><input type="submit" value="Install"></p>
</form>

<script>
(function(){
    function toggle(){
        var db = document.querySelector('input[name="db_type"]:checked').value;
        document.getElementById('sqlite_opts').style.display = db === 'sqlite' ? 'block' : 'none';
        document.getElementById('mysql_opts').style.display = db === 'mysql' ? 'block' : 'none';
    }
    var radios = document.querySelectorAll('input[name="db_type"]');
    for (var i=0;i<radios.length;i++) radios[i].addEventListener('change', toggle);
    toggle();
})();
</script>

<?php include 'template/footer.php'; ?>
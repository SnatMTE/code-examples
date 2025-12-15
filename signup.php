<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $username = sanitize_username($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 30) {
            $error = 'Username must be 3-30 characters.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->execute([
                    'username' => $username
                ]);
                $existingUser = $stmt->fetch();

                if ($existingUser) {
                    $error = "Username already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                    $stmt->execute([
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT)
                    ]);

                    header('Location: login.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Signup error: ' . $e->getMessage());
                $error = 'Error signing up.';
            }
        }
    }
}

$page_name = "Sign Up";
include("template/header.php");
include("template/left.php");


?>
    <h1>Sign Up</h1>
    <hr>
    <?php if (isset($error)) { ?>
        <p style="color: red;"><?php echo function_exists('e') ? e($error) : htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <p>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo isset($username) ? e($username) : ''; ?>">
        </p>
        <p>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password">
        </p>
        <p>
            <input type="submit" value="Submit">
        </p>
    </form>

    <?php include("template/footer.php"); ?>
<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $username = sanitize_username($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([
                'username' => $username
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Only store minimal data in session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username']
                ];
                regenerate_session();
                header('Location: index.php');
                exit;
            } else {
                $error = "Incorrect username or password. <a href='forgotten_password.php'>Forgotten password?</a>";
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Error logging in.';
        }
    }
}

$page_name = "Login";
include("template/header.php");
include("template/left.php");

?>

<h2>Login</h2>
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
    <p>
        Don't have an account? <a href="signup.php">Sign up here</a>.
    </p>
<?php include("template/footer.php") ?>
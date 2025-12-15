<?php
include 'config.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires >= NOW()");
                $stmt->execute(['token' => $token]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Invalid or expired token.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id");
                    $stmt->execute([
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $user['id']
                    ]);

                    echo '<p>Your password has been reset. <a href="login.php">Login</a>.</p>';
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Reset password error: ' . $e->getMessage());
                $error = 'Error processing request.';
            }
        }
    }
}

$page_name = "Reset Password";
include('template/header.php');
?>
<h2>Reset password</h2>
<hr>
<?php if (isset($error)) { echo '<p style="color:red;">' . e($error) . '</p>'; } ?>
<form action="reset.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="token" value="<?php echo e($token); ?>">
    <p>
        <label for="password">New password:</label>
        <input type="password" name="password" id="password">
    </p>
    <p>
        <input type="submit" value="Reset password">
    </p>
</form>

<?php include('template/footer.php');
?>
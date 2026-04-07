<?php
include 'config.php';

$page_name = "Forgot Password";
include("template/header.php");
?>
<h2>Forgotten password</h2>
<hr>
<form action="forgotten_password.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <p>
        <label for="email">Email address:</label>
        <input type="email" name="email" id="email">
    </p>
    <p>
        <input type="submit" name="submit" value="Submit">
    </p>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        echo '<p style="color:red;">Invalid CSRF token.</p>';
    } else {
        $email = trim($_POST['email'] ?? '');

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([
                'email' => $email
            ]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                try {
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email");
                    $stmt->execute([
                        'token' => $token,
                        'expires' => $expires,
                        'email' => $email
                    ]);
                } catch (PDOException $e) {
                    error_log('Error saving reset token: ' . $e->getMessage());
                    echo '<p style="color:red;">Error processing request.</p>';
                    exit;
                }

                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['PHP_SELF']), '\\/');
                $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $host . $path . "/reset.php?token=" . urlencode($token);

                $to = $email;
                $subject = "Password reset request";
                $message = "To reset your password, visit the following link (valid for 1 hour): " . $link;
                $headers = 'From: ' . ($site_email ?: 'noreply@' . $host);
                @mail($to, $subject, $message, $headers);

                echo '<p>A password reset link has been sent to your email address.</p>';
            } else {
                echo '<p style="color:red;">No user found with that email address.</p>';
            }
        } catch (PDOException $e) {
            error_log('Forgotten password error: ' . $e->getMessage());
            echo '<p style="color:red;">Error processing request.</p>';
        }
    }
}
include("template/footer.php");
?>
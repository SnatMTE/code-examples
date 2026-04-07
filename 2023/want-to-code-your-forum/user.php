<?php
require_once 'config.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $new_password = $_POST['password'] ?? '';
        $new_email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$new_email) {
            $error = 'Invalid email address.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = :password, email = :email WHERE id = :id");
                $stmt->execute([
                    'password' => password_hash($new_password, PASSWORD_DEFAULT),
                    'email' => $new_email,
                    'id' => $_SESSION['user']['id']
                ]);
                $success = true;
            } catch (PDOException $e) {
                error_log('User update error: ' . $e->getMessage());
                $error = 'Error updating profile.';
            }
        }
    }
}

$page_name = "User Profile";
include("template/header.php");
include("template/left.php");

?>

    <h2>Change Password and Email</h2>
    <?php if (isset($error)) echo '<p style="color:red;">' . e($error) . '</p>'; ?>
    <?php if (isset($success) && $success) echo '<p style="color:green;">Profile updated.</p>'; ?>
    <form action="user.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <label for="password">New Password:</label>
        <input type="password" name="password" id="password" required>
        <br><br>
        <label for="email">New Email:</label>
        <?php
$profile_email = '';
try {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user']['id']]);
    $row = $stmt->fetch();
    $profile_email = $row['email'] ?? '';
} catch (Exception $e) {
}
?>
        <input type="email" name="email" id="email" required value="<?php echo e($profile_email); ?>">
        <br><br>
        <input type="submit" name="submit" value="Submit">
    </form>

    <?php include("template/footer.php"); ?>
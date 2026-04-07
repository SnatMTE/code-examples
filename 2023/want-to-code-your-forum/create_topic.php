<?php
require_once 'config.php';

require_login();

try {
    if (isset($_POST['submit'])) {
        // Validate CSRF token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token.');
        }

        // Ensure DB is configured
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            error_log('Create topic: PDO not configured or missing.');
            throw new Exception('Database not configured.');
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if (empty($title) || empty($body)) {
            throw new Exception('Title and body are required.');
        }

        $user_id = $_SESSION['user']['id'] ?? null;
        if (empty($user_id)) {
            error_log('Create topic: user id missing from session.');
            header('Location: login.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO topics (title, body, created_at, user_id) VALUES (:title, :body, CURRENT_TIMESTAMP, :user_id)");
            $stmt->execute([
                ':title' => $title,
                ':body' => $body,
                ':user_id' => $user_id
            ]);
        } catch (PDOException $e) {
            error_log('Create topic DB error: ' . $e->getMessage() . ' (SQLSTATE ' . $e->getCode() . ')');
            // If table missing or schema issue, include hint
            if (stripos($e->getMessage(), 'no such table') !== false || stripos($e->getMessage(), 'doesn\'t exist') !== false) {
                error_log('Create topic: topics table missing or DB schema not initialized.');
            }
            throw new Exception('Database error.');
        }

        // Regenerate CSRF token after successful form submission to prevent replay
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    error_log('Create topic error: ' . $e->getMessage());
    $error_message = 'An error occurred while creating the topic.';
}

$page_name = "Create new topic";
include("template/header.php");
include("template/left.php");
?>

<main>
  <h2>Create a new topic as <?php echo function_exists('e') ? e($_SESSION['user']['username']) : htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?>.</h2>
  <hr />

  <?php
  if (!empty($error_message)) {
      echo '<p style="color:red;">Error: ' . $error_message . '</p>';
  }
  ?>

  <form action="" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <div>
      <label for="title">Title</label><br />
      <input type="text" name="title" id="title" value="<?php echo isset($title) ? e($title) : ''; ?>" required>
    </div><br />
    <div>
      <label for="body">Body</label>
      <textarea name="body" id="body" required><?php echo isset($body) ? e($body) : ''; ?></textarea>
    </div>
    <div><br />
      <input type="submit" name="submit" value="Submit">
    </div>
  </form>

  <script>
    CKEDITOR.replace('body');
  </script>
</main>

<?php include("template/footer.php"); ?>

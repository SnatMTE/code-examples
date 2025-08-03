<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Generate a CSRF token if it does not exist yet
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    if (isset($_POST['submit'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token.');
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if (empty($title) || empty($body)) {
            throw new Exception('Title and body are required.');
        }

        $user_id = $_SESSION['user']['id'];

        $stmt = $pdo->prepare("INSERT INTO topics (title, body, created_at, user_id) VALUES (:title, :body, NOW(), :user_id)");
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':body', $body, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Regenerate CSRF token after successful form submission to prevent replay
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = htmlspecialchars($e->getMessage());
}

$page_name = "Create new topic";
include("template/header.php");
include("template/left.php");
?>

<main>
  <h2>Create a new topic as <?php echo htmlspecialchars($_SESSION['user']['username']); ?>.</h2>
  <hr />

  <?php
  if (!empty($error_message)) {
      echo '<p style="color:red;">Error: ' . $error_message . '</p>';
  }
  ?>

  <form action="" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div>
      <label for="title">Title</label><br />
      <input type="text" name="title" id="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
    </div><br />
    <div>
      <label for="body">Body</label>
      <textarea name="body" id="body" required><?php echo isset($body) ? htmlspecialchars($body) : ''; ?></textarea>
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

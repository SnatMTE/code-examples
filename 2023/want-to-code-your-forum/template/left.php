<?php if (!defined('APP_INIT')) { http_response_code(403); exit; } ?>
<div id="leftcolumn">
<header>
    <nav>
      <ul>
        <?php if(isset($_SESSION['user'])) { ?>
            <?php
              // Try to fetch email for gravatar if not in session
              $avatar_email = '';
              if (!empty($_SESSION['user']['email'])) {
                  $avatar_email = $_SESSION['user']['email'];
              } else {
                  try {
                      $stmt = $pdo->prepare('SELECT email FROM users WHERE id = :id');
                      $stmt->execute(['id' => $_SESSION['user']['id']]);
                      $row = $stmt->fetch();
                      $avatar_email = $row['email'] ?? '';
                  } catch (Exception $e) {
                      $avatar_email = '';
                  }
              }
              if ($avatar_email) echo getGravatarImageUrl($avatar_email, 80);
            ?>
          <li>Hello, <a href="user.php"><?php echo function_exists('e') ? e($_SESSION['user']['username']) : htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li><a href="logout.php">Logout</a></li>
        <?php } else { ?>
          <li><a href="login.php">Login</a></li>
          <li><a href="signup.php">Sign Up</a></li>
        <?php } ?>
      </ul>
    </nav>
  </header>
        </div>
        <div id="content">
<?php
require_once 'config.php';

$topic_id = (int)($_GET['id'] ?? 0);
$topic = fetchOne("SELECT t.*, u.username FROM topics t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = :topic_id", $pdo, ['topic_id' => $topic_id]);
if (!$topic) {
    header('Location: index.php');
    exit;
}

$replies = fetchData("SELECT * FROM replies WHERE topic_id = :topic_id", $pdo, ['topic_id' => $topic_id]);

$page_name = $topic['title'];
include("template/header.php");
include("template/left.php");

?>
<h2><?php echo function_exists('e') ? e($topic['title']) : htmlspecialchars($topic['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p>
    Created by: <?php echo function_exists('e') ? e($topic['username']) : htmlspecialchars($topic['username'], ENT_QUOTES, 'UTF-8'); ?>
</p>
<p>
    <?php echo nl2br(function_exists('e') ? e($topic['body']) : htmlspecialchars($topic['body'], ENT_QUOTES, 'UTF-8')); ?>
</p>

<p>
    Created on: <?php echo function_exists('e') ? e($topic['created_at']) : htmlspecialchars($topic['created_at'], ENT_QUOTES, 'UTF-8'); ?>
</p>
<hr>

<?php foreach ($replies as $reply) { 
    try {
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :user_id");
        $stmt->execute([
            'user_id' => $reply['user_id']
        ]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error loading user: ' . $e->getMessage());
        continue;
    }
    ?>
    <section class="layout">  
        <div class="userdetail"><?php echo function_exists('e') ? e($user['username']) : htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
    
        <p>
        <?php
            echo getGravatarImageUrl($user['email'], 80);
        ?>
        </p>
    </div>  
        <div class="post">
            <p><?php echo function_exists('e') ? e(date("F jS, Y", strtotime($reply['created_at']))) : htmlspecialchars(date("F jS, Y", strtotime($reply['created_at'])), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php echo nl2br(function_exists('e') ? e($reply['body']) : htmlspecialchars($reply['body'], ENT_QUOTES, 'UTF-8')); ?>
        </div>

    <?php if (isset($_SESSION['user']) && (($_SESSION['user']['id'] === 1) || ($_SESSION['user']['id'] === $reply['user_id']))) { ?>
        <form action="delete_reply.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo e($reply['id']); ?>">
            <input type="hidden" name="topic_id" value="<?php echo e($topic_id); ?>">
            <input type="submit" value="Delete">
        </form>
    <?php } ?>
        </section>
    <hr>
<?php } ?>
<?php if (isset($_SESSION['user'])) { ?>
    <h2>Post a Reply</h2>
<form action="post_reply.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="topic_id" value="<?php echo e($topic_id); ?>">
    
    <p>
        <textarea name="body" id="body" rows="5" cols="80"></textarea>
    </p>
    <p>
        <input type="submit" name="submit" value="Submit">
    </p>
</form>
<script>
    CKEDITOR.replace( 'body' );
</script>
<?php } else { ?>
    <p>
        Please <a href="login.php">login</a> to post a reply.
    </p>
<?php } 

include("template/footer.php");
?>
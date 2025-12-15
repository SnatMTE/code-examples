<?php
require_once('config.php');

//Future change - functions!
$topics = fetchData("SELECT * FROM topics ORDER BY created_at DESC", $pdo);
$page_name = "Forum";

include("template/header.php");
include("template/left.php");

?>

<h2>Topics</h2><hr />

<main>

        <?php foreach($topics as $topic) { ?>
          <section class="index">
          <div class="index_body"><a href="view_topic.php?id=<?php echo e($topic['id']); ?>"><?php echo e($topic['title']); ?></a></div>          

            <?php 
            $user_id = $topic['user_id'];
            $user_stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
            $user_stmt->execute([
                'id' => $user_id
            ]);
            $user = $user_stmt->fetch();
            echo "<div class=\"index_sidebar\">";
            echo e($user['username']);
            $email = $user['email'];
            echo "</div>"; 

            echo getGravatarImageUrl($email, 80);

            ?>
 
           <div class="grow1"><?php echo e(date("F jS, Y", strtotime($topic['created_at']))); ?></div>
           
          </section>
          <hr />
          <?php } ?>
    <?php if(isset($_SESSION['user'])) { ?>
      <a href="create_topic.php">Create a new topic</a>
    <?php } ?>
  </main>

<?php include("template/footer.php") ?>
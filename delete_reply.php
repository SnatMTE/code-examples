<?php
require_once 'config.php';

require_login();

$reply_id = (int)($_POST['id'] ?? 0);
$topic_id = (int)($_POST['topic_id'] ?? 0);

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: view_topic.php?id=' . $topic_id);
    exit;
}

try {
    // Fetch reply owner
    $stmt = $pdo->prepare("SELECT user_id FROM replies WHERE id = :reply_id");
    $stmt->execute(['reply_id' => $reply_id]);
    $reply = $stmt->fetch();

    if (!$reply) {
        header('Location: view_topic.php?id=' . $topic_id);
        exit;
    }

    $current_user_id = (int)$_SESSION['user']['id'];
    // Allow deletion if admin (user id 1) or the owner of the reply
    if ($current_user_id !== 1 && $current_user_id !== (int)$reply['user_id']) {
        header('Location: view_topic.php?id=' . $topic_id);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM replies WHERE id = :reply_id");
    $stmt->execute([
        'reply_id' => $reply_id
    ]);
} catch (PDOException $e) {
    error_log('Error deleting reply: ' . $e->getMessage());
    exit;
}

header('Location: view_topic.php?id=' . $topic_id);
exit;
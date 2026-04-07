<?php
require_once 'config.php';

require_login();

try {

    if (!isset($_POST['submit'])) {
        header('Location: index.php');
        exit;
    }

    // CSRF check
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        error_log('Post reply: invalid CSRF token for user ' . ($_SESSION['user']['id'] ?? 'anon'));
        $tid = (int)($_POST['topic_id'] ?? 0);
        header('Location: view_topic.php?id=' . $tid . '&err=csrf');
        exit;
    }

    $body = trim($_POST['body'] ?? '');
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    $user_id = (int)$_SESSION['user']['id'];

    if (empty($body)) {
        header('Location: view_topic.php?id=' . $topic_id . '&err=empty');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO replies (body, created_at, user_id, topic_id) VALUES (:body, CURRENT_TIMESTAMP, :user_id, :topic_id)");
        $stmt->execute([
            ':body' => $body,
            ':user_id' => $user_id,
            ':topic_id' => $topic_id
        ]);
    } catch (PDOException $e) {
        error_log('Post reply DB error: ' . $e->getMessage());
        header('Location: view_topic.php?id=' . $topic_id . '&err=db');
        exit;
    }

    header('Location: view_topic.php?id=' . $topic_id);
    exit;

} catch (Exception $e) {
    error_log('Post reply error: ' . $e->getMessage());
    header('Location: view_topic.php?id=' . ($topic_id ?? '0') . '&err=unknown');
    exit;
}
?>
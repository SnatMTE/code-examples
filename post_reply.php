<?php
require_once 'config.php';

require_login();

try {

    if (isset($_POST['submit'])) {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token.');
        }

        $body = trim($_POST['body'] ?? '');
        $topic_id = (int)($_POST['topic_id'] ?? 0);
        $user_id = (int)$_SESSION['user']['id'];

        $stmt = $pdo->prepare("INSERT INTO replies (body, created_at, user_id, topic_id) VALUES (:body, NOW(), :user_id, :topic_id)");

        $stmt->bindParam(':body', $body);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: view_topic.php?id=' . $topic_id);
        exit;
    }

} catch (Exception $e) {
    error_log('Post reply error: ' . $e->getMessage());
    // Redirect back with an error (do not expose DB errors)
    header('Location: view_topic.php?id=' . ($topic_id ?? '0'));
    exit;
}
?>
<?php

if (!defined('APP_INIT')) { http_response_code(403); exit; }

try {
    if (!empty($db_type) && $db_type === 'sqlite') {
        // SQLite - ensure directory exists
        $sqlite_path = $sqlite_path ?? __DIR__ . '/../data/forum.sqlite';
        $dir = dirname($sqlite_path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $dsn = "sqlite:" . $sqlite_path;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, null, null, $options);
        // Enable foreign keys for SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        // Default to MySQL
        $host = $host ?? '127.0.0.1';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $username, $password, $options);
    }
} catch (PDOException $e) {
    // Do not leak DB details in production
    error_log('DB connection error: ' . $e->getMessage());
    die('Error connecting to database.');
}

function fetchData($query, $pdo, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    return $data;
}

function fetchOne($query, $pdo, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch();
}

function executeQuery($query, $pdo, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
}

?>
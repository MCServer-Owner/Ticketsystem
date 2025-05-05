<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);

    if ($userId !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: manage_users.php");
exit;

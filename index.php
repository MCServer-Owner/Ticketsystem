<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
?>

<?php include 'navigation.php'; ?>

<div class="container mt-5">
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->bind_result($username);
            $stmt->fetch();
            $stmt->close();
        ?>
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>
        <p>Glad, to see you again in our Ticketsystem.</p>
        <p>Please select an action above.</p>
    <?php else: ?>
        <h2>Welcome in our Ticketsystem!</h2>
        <p>If you want to go back the Status, press <a href="http://status.myts3server.at/status/index.php">here</a>.</p>
    <?php endif; ?>
</div>


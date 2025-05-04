<?php
session_start();
include 'config.php';
include 'admin_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !isset($_GET['ticket_id'])) {
    header("Location: login.php");
    exit();
}

$comment_id = $_GET['id'];
$ticket_id = $_GET['ticket_id'];

$stmt = $conn->prepare("SELECT * FROM ticket_comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

if (!$comment) {
    die("Kommentar nicht gefunden.");
}

if ($comment['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    die("Keine Berechtigung.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['comment'];
    $stmt = $conn->prepare("UPDATE ticket_comments SET comment = ?, created_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $text, $comment_id);
    $stmt->execute();
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}
?>

<h2>Kommentar bearbeiten</h2>
<form method="post">
    <textarea name="comment" rows="5" cols="50"><?= htmlspecialchars($comment['comment']) ?></textarea><br>
    <button type="submit">Speichern</button>
</form>
<a href="edit_ticket.php?id=<?= $ticket_id ?>">Zurück</a>


<?php
session_start();
require_once 'config.php';
require_once 'send_email.php';
include 'navigation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO tickets (title, description, status, user_id) VALUES (?, ?, 'open', ?)");
    $stmt->bind_param("ssi", $title, $description, $user_id);
    $stmt->execute();
    $ticket_id = $stmt->insert_id;
    $stmt->close();

    // E-Mail an Ersteller
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();

    $ticketLink = "http://support.myts3server.at/support/edit_ticket.php?id=" . $ticket_id;
    $subject = "Ticket #$ticket_id wurde erstellt";
    $message = "Ihr Ticket wurde erfolgreich erstellt.<br><br><a href='$ticketLink'>Zum Ticket</a>";
    send_email($email, $subject, $message);

    header("Location: list_tickets.php");
    exit;
}
?>

<div class="container mt-4">
    <h2>Create New Ticket</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" name="title" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create Ticket</button>
    </form>
</div>


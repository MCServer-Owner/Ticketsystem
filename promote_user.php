<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';

// Überprüfen, ob der Benutzer angemeldet ist und ob er ein Admin ist
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'], $conn)) {
    header("Location: login.php");
    exit();
}

// Benutzer befördern
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer befördern - Ticketsystem</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
        }

        .nav a {
            margin: 5px 10px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .nav a:hover {
            background-color: #0056b3;
        }

        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Benutzer befördern</h1>

    <div class="nav">
        <a href="create_ticket.php">Neues Ticket</a>
        <a href="list_tickets.php">Alle Tickets</a>
        <a href="admin_dashboard.php">Adminbereich</a>
        <a href="profile.php">Profil</a>
        <a href="logout.php">Logout</a>
    </div>

    <h2>Benutzer befördern</h2>
    <p>Der Benutzer wurde erfolgreich befördert. Der neue Admin hat jetzt erhöhte Berechtigungen.</p>
</body>
</html>


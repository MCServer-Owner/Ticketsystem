<?php
// config.php

// Setze die Datenbank-Verbindungsdetails
$servername = "localhost";  // oder die IP-Adresse deines DB-Servers
$username = "youruser";
$password = "yourpassword";
$dbname = "yourdbname";  // Deine Ticket-Datenbank

// Erstelle eine Verbindung zur Datenbank
$conn = new mysqli($servername, $username, $password, $dbname);

// Prüfe die Verbindung
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>


<?php
// config.php

// Setze die Datenbank-Verbindungsdetails
$servername = "10.0.0.128";  // oder die IP-Adresse deines DB-Servers
$username = "znuny";
$password = "Adminer123456789";
$dbname = "ticketsystem";  // Deine Ticket-Datenbank

// Erstelle eine Verbindung zur Datenbank
$conn = new mysqli($servername, $username, $password, $dbname);

// Prüfe die Verbindung
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>


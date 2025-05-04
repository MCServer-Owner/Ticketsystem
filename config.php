<?php
// config.php

// Setze die Datenbank-Verbindungsdetails
$host = 'localhost';
$db = 'your_database';
$user = 'your_db_user';
$pass = 'your_db_password';

// Erstelle eine Verbindung zur Datenbank
$conn = new mysqli($servername, $username, $password, $dbname);

// Prüfe die Verbindung
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>


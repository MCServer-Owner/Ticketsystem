<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // DB-Daten
    $db_servername = $_POST['db_servername'];
    $db_username   = $_POST['db_username'];
    $db_password   = $_POST['db_password'];
    $db_name       = $_POST['db_name'];

    // SMTP
    $smtp_host     = $_POST['smtp_host'];
    $smtp_user     = $_POST['smtp_user'];
    $smtp_pass     = $_POST['smtp_pass'];
    $smtp_port     = $_POST['smtp_port'];
    $smtp_from     = $_POST['smtp_from'];
    $smtp_replyto  = $_POST['smtp_replyto'];

    // Admin-Nutzer
    $admin_username = $_POST['admin_username'];
    $admin_email    = $_POST['admin_email'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);

    // Schritt 1: config.php schreiben
    $configContent = <<<PHP
<?php
// config.php

\$servername = "$db_servername";
\$username = "$db_username";
\$password = "$db_password";
\$dbname = "$db_name";

\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);
if (\$conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . \$conn->connect_error);
}

\$smtp_config = [
    'host' => "$smtp_host",
    'username' => "$smtp_user",
    'password' => "$smtp_pass",
    'port' => $smtp_port,
    'from' => "$smtp_from",
    'reply_to' => "$smtp_replyto"
];
?>
PHP;

    file_put_contents("config.php", $configContent);

    // Schritt 2: Verbindung aufbauen
    $conn = new mysqli($db_servername, $db_username, $db_password);
    if ($conn->connect_error) {
        die("Datenbankverbindung fehlgeschlagen: " . $conn->connect_error);
    }

    // Schritt 3: Datenbank erstellen, falls nicht vorhanden
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $conn->select_db($db_name);

    // Schritt 4: Tabellen anlegen
    $schemaSQL = <<<SQL

 -- Tabelle für Benutzer
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    email VARCHAR(255) NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'active'
);

-- Tabelle für Tickets
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'offen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Tabelle für Kommentare
CREATE TABLE IF NOT EXISTS ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabelle für Statusverlauf und Zuweisungshistorie
CREATE TABLE IF NOT EXISTS ticket_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (changed_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Tabelle für Passwort-Zurücksetzen-Tokens
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);              
SQL;

    if (!$conn->multi_query($schemaSQL)) {
        die("Fehler beim Erstellen der Tabellen: " . $conn->error);
    }

    // Warten bis alle Queries ausgeführt wurden
    while ($conn->more_results() && $conn->next_result()) {}

    // Schritt 5: Admin-Benutzer anlegen
    $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin, email, status) VALUES (?, ?, 1, ?, 'active')");
    $stmt->bind_param("sss", $admin_username, $admin_password, $admin_email);
    $stmt->execute();
    $stmt->close();

    echo "<h2>Installation erfolgreich!</h2><p>config.php erstellt, Datenbank eingerichtet, Admin-User angelegt.</p><a href='login.php'>Zum Login</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ticketsystem installieren</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 600px; margin: auto; }
        input[type=text], input[type=password], input[type=email], input[type=number] {
            width: 100%; padding: 10px; margin: 6px 0 12px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        input[type=submit] {
            background-color: #007bff; color: white;
            padding: 12px; border: none; border-radius: 4px;
            cursor: pointer; width: 100%;
        }
        input[type=submit]:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Installation Ticketsystem</h2>
    <form method="post">
        <h3>Datenbank</h3>
        <label>Servername:</label>
        <input type="text" name="db_servername" value="10.0.0.128" required>
        <label>Benutzer:</label>
        <input type="text" name="db_username" value="znuny" required>
        <label>Passwort:</label>
        <input type="password" name="db_password" value="Adminer123456789" required>
        <label>Datenbank:</label>
        <input type="text" name="db_name" value="ticketsystem" required>

        <h3>SMTP</h3>
        <label>SMTP-Host:</label>
        <input type="text" name="smtp_host" value="myts3server.at" required>
        <label>SMTP-Benutzer (E-Mail):</label>
        <input type="email" name="smtp_user" value="noreply@myts3server.at" required>
        <label>SMTP-Passwort:</label>
        <input type="password" name="smtp_pass" value="Adminer123456" required>
        <label>SMTP-Port:</label>
        <input type="number" name="smtp_port" value="587" required>
        <label>Absenderadresse:</label>
        <input type="email" name="smtp_from" value="noreply@myts3server.at" required>
        <label>Antwortadresse:</label>
        <input type="email" name="smtp_replyto" value="support@myts3server.at" required>

        <h3>Admin-Konto</h3>
        <label>Benutzername:</label>
        <input type="text" name="admin_username" value="admin" required>
        <label>E-Mail:</label>
        <input type="email" name="admin_email" value="admin@example.com" required>
        <label>Passwort:</label>
        <input type="password" name="admin_password" required>

        <input type="submit" value="Installation starten">
    </form>
</body>
</html>

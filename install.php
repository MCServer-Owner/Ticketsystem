<?php
// install.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db_servername = $_POST['db_servername'] ?? '';
    $db_username   = $_POST['db_username'] ?? '';
    $db_password   = $_POST['db_password'] ?? '';
    $db_name       = $_POST['db_name'] ?? '';

    $smtp_host     = $_POST['smtp_host'] ?? '';
    $smtp_user     = $_POST['smtp_user'] ?? '';
    $smtp_pass     = $_POST['smtp_pass'] ?? '';
    $smtp_port     = $_POST['smtp_port'] ?? '';
    $smtp_from     = $_POST['smtp_from'] ?? '';
    $smtp_replyto  = $_POST['smtp_replyto'] ?? '';

    $configContent = <<<PHP
<?php
// config.php

// Setze die Datenbank-Verbindungsdetails
\$servername = "$db_servername";
\$username = "$db_username";
\$password = "$db_password";
\$dbname = "$db_name";

// Erstelle eine Verbindung zur Datenbank
\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);

// Prüfe die Verbindung
if (\$conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . \$conn->connect_error);
}

// SMTP-Konfiguration für send_email.php
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
    echo "<h2>config.php erfolgreich erstellt.</h2><p><a href='index.php'>Zum Ticketsystem</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Install Ticketsystem</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 600px; margin: auto; }
        input[type=text], input[type=password], input[type=number], input[type=email] {
            width: 100%;
            padding: 10px;
            margin: 6px 0 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type=submit] {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        input[type=submit]:hover {
            background-color: #0056b3;
        }
        @media screen and (max-width: 600px) {
            body { padding: 10px; }
        }
    </style>
</head>
<body>
    <h2>Ticketsystem Installation</h2>
    <form method="post">
        <h3>Datenbank-Verbindung</h3>
        <label>Servername (z. B. dbservername)</label>
        <input type="text" name="db_servername" required value="dbserver">

        <label>Datenbank-Benutzer</label>
        <input type="text" name="db_username" required value="dbuser">

        <label>Datenbank-Passwort</label>
        <input type="password" name="db_password" required value="dbpassword">

        <label>Datenbank-Name</label>
        <input type="text" name="db_name" required value="dbname">

        <h3>SMTP-Konfiguration</h3>
        <label>SMTP-Host</label>
        <input type="text" name="smtp_host" required value="yoursmtphost">

        <label>SMTP-Benutzer</label>
        <input type="email" name="smtp_user" required value="yourmail">

        <label>SMTP-Passwort</label>
        <input type="password" name="smtp_pass" required value="yourpassword">

        <label>SMTP-Port</label>
        <input type="number" name="smtp_port" required value="587">

        <label>Absenderadresse</label>
        <input type="email" name="smtp_from" required value="yourmail">

        <label>Antwortadresse (Reply-To)</label>
        <input type="email" name="smtp_replyto" required value="yourmail">

        <input type="submit" value="config.php erstellen">
    </form>
</body>
</html>

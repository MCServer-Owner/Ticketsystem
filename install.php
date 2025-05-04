<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    $admin_user = $_POST['admin_user'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
    $admin_email = $_POST['admin_email'];
    $mail_host = $_POST['mail_host'];
    $mail_port = $_POST['mail_port'];
    $mail_user = $_POST['mail_user'];
    $mail_pass = $_POST['mail_pass'];
    $mail_from = $_POST['mail_from'];

    // Verbindung zur Datenbank
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("Verbindung fehlgeschlagen: " . $conn->connect_error);
    }

    // Datenbank erstellen
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
    $conn->select_db($db_name);

    // SQL-Schema laden
    $schema = file_get_contents('schema.sql');
    if (!$conn->multi_query($schema)) {
        die("Fehler beim Ausführen von schema.sql: " . $conn->error);
    }

    // Admin-Benutzer anlegen
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin, status) VALUES (?, ?, ?, 1, 'active')");
    $stmt->bind_param("sss", $admin_user, $admin_pass, $admin_email);
    $stmt->execute();

    // config.php schreiben
    $configContent = "<?php
\$host = '$db_host';
\$user = '$db_user';
\$password = '$db_pass';
\$dbname = '$db_name';
\$conn = new mysqli(\$host, \$user, \$password, \$dbname);
if (\$conn->connect_error) {
    die('Verbindung fehlgeschlagen: ' . \$conn->connect_error);
}

// Mail-Konfiguration
\$mail_config = [
    'host' => '$mail_host',
    'port' => $mail_port,
    'username' => '$mail_user',
    'password' => '$mail_pass',
    'from' => '$mail_from'
];
?>";
    file_put_contents("config.php", $configContent);

    echo "<p style='color:green;'>Installation abgeschlossen! Du kannst dich jetzt als <strong>$admin_user</strong> einloggen.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticketsystem Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        form {
            background: white;
            max-width: 600px;
            margin: auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }

        h2 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type=submit] {
            background-color: #4CAF50;
            color: white;
            border: none;
            margin-top: 20px;
            cursor: pointer;
        }

        input[type=submit]:hover {
            background-color: #45a049;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            form {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Ticketsystem Installation</h2>

        <label>Datenbank Host</label>
        <input type="text" name="db_host" required placeholder="z. B. localhost">

        <label>Datenbank Benutzer</label>
        <input type="text" name="db_user" required>

        <label>Datenbank Passwort</label>
        <input type="password" name="db_pass">

        <label>Datenbank Name</label>
        <input type="text" name="db_name" required>

        <label>Admin Benutzername</label>
        <input type="text" name="admin_user" required>

        <label>Admin Passwort</label>
        <input type="password" name="admin_pass" required>

        <label>Admin E-Mail</label>
        <input type="email" name="admin_email" required>

        <label>Mailserver Host</label>
        <input type="text" name="mail_host" required placeholder="z. B. smtp.example.com">

        <label>Mailserver Port</label>
        <input type="number" name="mail_port" required value="587">

        <label>Mail Benutzername</label>
        <input type="text" name="mail_user" required>

        <label>Mail Passwort</label>
        <input type="password" name="mail_pass" required>

        <label>Absender-E-Mail</label>
        <input type="email" name="mail_from" required>

        <input type="submit" value="Installation starten">
    </form>
</body>
</html>

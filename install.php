<?php
// install.php – Interaktive Installer-Datei für das Ticketsystem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = $_POST['db_servername'];
    $username = $_POST['db_username'];
    $password = $_POST['db_password'];
    $dbname = $_POST['db_name'];

    $smtp_host = $_POST['smtp_host'];
    $smtp_user = $_POST['smtp_user'];
    $smtp_pass = $_POST['smtp_pass'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_from = $_POST['smtp_from'];
    $smtp_replyto = $_POST['smtp_replyto'];

    $admin_user = $_POST['admin_user'];
    $admin_email = $_POST['admin_email'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    // === Schritt 1: config.php erstellen ===
    $configContent = <<<PHP
<?php
// config.php – Datenbankverbindung und E-Mail-Funktion

// Datenbank
\$servername = "$db_servername";
\$username = "$db_username";
\$password = "$db_password";
\$dbname = "$db_name";

\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);
if (\$conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . \$conn->connect_error);
}

// SMTP-Konfiguration
\$smtp_config = [
    'host' => "$smtp_host",
    'username' => "$smtp_user",
    'password' => "$smtp_pass",
    'port' => $smtp_port,
    'from' => "$smtp_from",
    'reply_to' => "$smtp_replyto"
];

// PHPMailer
use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception;
require_once __DIR__ . '/vendor/autoload.php';

// E-Mail-Versandfunktion
function send_email(\$to, \$subject, \$message) {
    global \$smtp_config;

    \$mail = new PHPMailer(true);
    try {
        \$mail->isSMTP();
        \$mail->Host = \$smtp_config['host'];
        \$mail->SMTPAuth = true;
        \$mail->Username = \$smtp_config['username'];
        \$mail->Password = \$smtp_config['password'];
        \$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        \$mail->Port = \$smtp_config['port'];

        \$mail->setFrom(\$smtp_config['from'], 'Ticketsystem');
        \$mail->addAddress(\$to);
        \$mail->addReplyTo(\$smtp_config['reply_to'], 'Support');

        \$mail->isHTML(true);
        \$mail->Subject = \$subject;
        \$mail->Body = \$message;

        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log("Mailer Error: " . \$mail->ErrorInfo);
        return false;
    }
}
?>
PHP;

    file_put_contents('config.php', $configContent);

    // === Schritt 2: Verbindung testen ===
    require_once 'config.php';

    // === Schritt 3: schema.sql ausführen ===
    $schema = file_get_contents('schema.sql');
    if ($conn->multi_query($schema)) {
        while ($conn->next_result()) { /* flush multi_query */ }
    }

    // === Schritt 4: Admin-Benutzer einfügen ===
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin, status) VALUES (?, ?, ?, 1, 'active')");
    $stmt->bind_param("sss", $admin_user, $admin_pass, $admin_email);
    $stmt->execute();
    $stmt->close();

    echo "<p style='font-family: Arial'>✅ Installation erfolgreich abgeschlossen. Du kannst dich nun als Admin einloggen.</p>";
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
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }

        input, label {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            width: 100%;
            background-color: #4CAF50;
            color: white;
            border: none;
            font-size: 16px;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Install Ticketsystem</h2>
    <form method="POST">
        <h3>Datenbank</h3>
        <label>Datenbank-Host</label>
        <input type="text" name="servername" value="yourhost" required>

        <label>Datenbank-Benutzer</label>
        <input type="text" name="username" value="yourdbuser" required>

        <label>Datenbank-Passwort</label>
        <input type="password" name="password" value="yourdbpassword" required>

        <label>Datenbank-Name</label>
        <input type="text" name="dbname" value="yourdbname" required>

        <h3>SMTP Einstellungen</h3>
        <label>SMTP-Host</label>
        <input type="text" name="smtp_host" value="yoursmtphost" required>

        <label>SMTP-Benutzer (E-Mail)</label>
        <input type="email" name="smtp_user" value="yourmailaddress" required>

        <label>SMTP-Passwort</label>
        <input type="password" name="smtp_pass" value="yourstmppassword" required>

        <label>SMTP-Port</label>
        <input type="number" name="smtp_port" value="587" required>

        <label>Absender-E-Mail (From)</label>
        <input type="email" name="smtp_from" value="yourmailaddress" required>

        <label>Antwort-E-Mail (Reply-To)</label>
        <input type="email" name="smtp_replyto" value="yourmailaddress" required>

        <h3>Admin Benutzer</h3>
        <label>Benutzername</label>
        <input type="text" name="admin_user" value="admin" required>

        <label>E-Mail</label>
        <input type="email" name="admin_email" value="admin@example.com" required>

        <label>Passwort</label>
        <input type="password" name="admin_pass" required>

        <button type="submit">Installieren</button>
    </form>
</body>
</html>

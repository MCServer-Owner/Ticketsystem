<?php
session_start();

// Wenn config.php schon existiert, Installation verhindern
if (file_exists(__DIR__ . '/config.php')) {
    die("Das System wurde bereits installiert. Bitte lösche die Datei config.php, um die Installation erneut durchzuführen.");
}

$currentStep = isset($_SESSION['step']) ? $_SESSION['step'] : 1;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($currentStep == 1) {
        // Schritt 1: Eingaben speichern
        $_SESSION['dbHost'] = $_POST['dbHost'];
        $_SESSION['dbUser'] = $_POST['dbUser'];
        $_SESSION['dbPass'] = $_POST['dbPass'];
        $_SESSION['dbName'] = $_POST['dbName'];

        $_SESSION['smtpHost'] = $_POST['smtpHost'];
        $_SESSION['smtpPort'] = $_POST['smtpPort'];
        $_SESSION['smtpEncryption'] = $_POST['smtpEncryption'];
        $_SESSION['smtpUser'] = $_POST['smtpUser'];
        $_SESSION['smtpPass'] = $_POST['smtpPass'];
        $_SESSION['smtpFrom'] = $_POST['smtpFrom'];
        $_SESSION['smtpReplyTo'] = $_POST['smtpReplyTo'];

        $_SESSION['step'] = 2;
        header("Location: install.php");
        exit;
    } elseif ($currentStep == 2) {
        // Schritt 2: Admin-Zugangsdaten erfassen
        $_SESSION['adminEmail'] = $_POST['adminEmail'];
        $_SESSION['adminPassword'] = password_hash($_POST['adminPassword'], PASSWORD_BCRYPT);
        $_SESSION['step'] = 3;
        header("Location: install.php");
        exit;
    }
}

if ($currentStep == 3) {
    // Verbindung zur Datenbank herstellen
    $dbHost = $_SESSION['dbHost'];
    $dbUser = $_SESSION['dbUser'];
    $dbPass = $_SESSION['dbPass'];
    $dbName = $_SESSION['dbName'];

    $conn = new mysqli($dbHost, $dbUser, $dbPass);

    if ($conn->connect_error) {
        die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
    }

    // Datenbank erstellen, falls nicht vorhanden
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbName);

    // Tabellen anlegen
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if (!$conn->multi_query($sql)) {
        die("Fehler beim Importieren der Datenbankstruktur: " . $conn->error);
    }

    // Warten, bis alle Queries abgeschlossen sind
    while ($conn->more_results() && $conn->next_result()) {;}

    // Admin-Benutzer anlegen
    $adminEmail = $_SESSION['adminEmail'];
    $adminPassword = $_SESSION['adminPassword'];

    $stmt = $conn->prepare("INSERT INTO users (email, password, is_admin) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $adminEmail, $adminPassword);
    $stmt->execute();
    $stmt->close();

    // Konfigurationsdatei schreiben
    $smtpHost = $_SESSION['smtpHost'];
    $smtpPort = $_SESSION['smtpPort'];
    $smtpEncryption = $_SESSION['smtpEncryption'];
    $smtpUser = $_SESSION['smtpUser'];
    $smtpPass = $_SESSION['smtpPass'];
    $smtpFrom = $_SESSION['smtpFrom'];
    $smtpReplyTo = $_SESSION['smtpReplyTo'];

    $configContent = <<<PHP
<?php
\$db_host = '$dbHost';
\$db_user = '$dbUser';
\$db_pass = '$dbPass';
\$db_name = '$dbName';

\$smtp_host = '$smtpHost';
\$smtp_port = $smtpPort;
\$smtp_encryption = '$smtpEncryption';
\$smtp_user = '$smtpUser';
\$smtp_pass = '$smtpPass';
\$smtp_from = '$smtpFrom';
\$smtp_reply_to = '$smtpReplyTo';

\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);
if (\$conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . \$conn->connect_error);
}
PHP;

    file_put_contents(__DIR__ . '/config.php', $configContent);

    // Session beenden
    session_destroy();

    echo "<h2>Installation abgeschlossen!</h2>";
    echo "<p>Die Datei <code>config.php</code> wurde erstellt und ein Admin-Account wurde eingerichtet.</p>";
    echo "<p><a href='login.php'>Zum Login</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Installations-Assistent</title>
</head>
<body>
    <?php if ($currentStep == 1): ?>
        <h2>Schritt 1: Datenbank- & SMTP-Einstellungen</h2>
        <form method="POST">
            <h3>Datenbank</h3>
            <input name="dbHost" placeholder="Datenbank-Host" required><br>
            <input name="dbUser" placeholder="Datenbank-Benutzer" required><br>
            <input name="dbPass" type="password" placeholder="Datenbank-Passwort"><br>
            <input name="dbName" placeholder="Datenbank-Name" required><br>

            <h3>SMTP</h3>
            <input name="smtpHost" placeholder="SMTP-Host" required><br>
            <input name="smtpPort" placeholder="SMTP-Port" value="587" required><br>
            <input name="smtpEncryption" placeholder="SMTP-Verschlüsselung (tls/ssl)" value="tls"><br>
            <input name="smtpUser" placeholder="SMTP-Benutzer" required><br>
            <input name="smtpPass" type="password" placeholder="SMTP-Passwort" required><br>
            <input name="smtpFrom" placeholder="Absenderadresse" required><br>
            <input name="smtpReplyTo" placeholder="Reply-To-Adresse" required><br>
            <button type="submit">Weiter</button>
        </form>
    <?php elseif ($currentStep == 2): ?>
        <h2>Schritt 2: Admin-Zugang einrichten</h2>
        <form method="POST">
            <input name="adminEmail" placeholder="Admin-E-Mail" required><br>
            <input name="adminPassword" type="password" placeholder="Admin-Passwort" required><br>
            <button type="submit">Installation abschließen</button>
        </form>
    <?php endif; ?>
</body>
</html>

<?php
// install.php - Interaktive Installation mit Schema-Import

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Prüfen, ob bereits installiert
if (file_exists(__DIR__.'/config.php')) {
    die("❌ Das System ist bereits installiert. Lösche config.php für eine Neuinstallation.");
}

// Installationsformular anzeigen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ticketsystem Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .tab { display: none; }
        .tab.active { display: block; }
        .tab-nav { display: flex; margin-bottom: 20px; }
        .tab-link { padding: 10px; background: #ddd; margin-right: 5px; cursor: pointer; }
        .tab-link.active { background: #007bff; color: white; }
    </style>
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`[data-tab="\${tabName}"]`).classList.add('active');
        }
    </script>
</head>
<body>
    <h1>Ticketsystem Installation</h1>
    <div class="tab-nav">
        <div class="tab-link active" data-tab="db-tab" onclick="showTab('db-tab')">Datenbank</div>
        <div class="tab-link" data-tab="smtp-tab" onclick="showTab('smtp-tab')">E-Mail</div>
        <div class="tab-link" data-tab="admin-tab" onclick="showTab('admin-tab')">Admin</div>
    </div>
    <form method="POST">
        <div id="db-tab" class="tab active">
            <h2>Datenbank-Konfiguration</h2>
            <div class="form-group"><label for="db_host">Datenbank-Host:</label><input type="text" id="db_host" name="db_host" value="localhost" required></div>
            <div class="form-group"><label for="db_name">Datenbank-Name:</label><input type="text" id="db_name" name="db_name" value="ticketsystem" required></div>
            <div class="form-group"><label for="db_user">Datenbank-Benutzer:</label><input type="text" id="db_user" name="db_user" value="root" required></div>
            <div class="form-group"><label for="db_pass">Datenbank-Passwort:</label><input type="password" id="db_pass" name="db_pass"></div>
        </div>

        <div id="smtp-tab" class="tab">
            <h2>E-Mail-Konfiguration (SMTP)</h2>
            <div class="form-group"><label for="smtp_host">SMTP-Host:</label><input type="text" id="smtp_host" name="smtp_host" value="mail.example.com" required></div>
            <div class="form-group"><label for="smtp_port">SMTP-Port:</label><input type="number" id="smtp_port" name="smtp_port" value="587" required></div>
            <div class="form-group"><label for="smtp_encryption">Verschlüsselung:</label>
                <select id="smtp_encryption" name="smtp_encryption" required>
                    <option value="tls">TLS (empfohlen)</option>
                    <option value="ssl">SSL</option>
                    <option value="">Keine</option>
                </select>
            </div>
            <div class="form-group"><label for="smtp_user">SMTP-Benutzer:</label><input type="text" id="smtp_user" name="smtp_user" value="noreply@example.com" required></div>
            <div class="form-group"><label for="smtp_pass">SMTP-Passwort:</label><input type="password" id="smtp_pass" name="smtp_pass" required></div>
            <div class="form-group"><label for="smtp_from">Absender-E-Mail:</label><input type="email" id="smtp_from" name="smtp_from" value="noreply@example.com" required></div>
            <div class="form-group"><label for="smtp_reply_to">Antwort-an-E-Mail:</label><input type="email" id="smtp_reply_to" name="smtp_reply_to" value="support@example.com" required></div>
        </div>

        <div id="admin-tab" class="tab">
            <h2>Admin-Benutzer erstellen</h2>
            <div class="form-group"><label for="admin_username">Benutzername:</label><input type="text" id="admin_username" name="admin_username" required></div>
            <div class="form-group"><label for="admin_email">E-Mail:</label><input type="email" id="admin_email" name="admin_email" required></div>
            <div class="form-group"><label for="admin_password">Passwort:</label><input type="password" id="admin_password" name="admin_password" required></div>
            <div class="form-group"><label for="admin_password_confirm">Passwort bestätigen:</label><input type="password" id="admin_password_confirm" name="admin_password_confirm" required></div>
        </div>

        <button type="submit" class="btn">Installation starten</button>
    </form>
</body>
</html>
HTML;
    exit;
}

// Formulardaten verarbeiten
$dbHost = $_POST['db_host'];
$dbName = $_POST['db_name'];
$dbUser = $_POST['db_user'];
$dbPass = $_POST['db_pass'] ?? '';

$smtpHost = $_POST['smtp_host'];
$smtpPort = (int)$_POST['smtp_port'];
$smtpEncryption = $_POST['smtp_encryption'];
$smtpUser = $_POST['smtp_user'];
$smtpPass = $_POST['smtp_pass'];
$smtpFrom = $_POST['smtp_from'];
$smtpReplyTo = $_POST['smtp_reply_to'];

$adminUsername = $_POST['admin_username'];
$adminEmail = $_POST['admin_email'];
$adminPassword = $_POST['admin_password'];
$adminPasswordConfirm = $_POST['admin_password_confirm'];

if ($adminPassword !== $adminPasswordConfirm) {
    die("❌ Die Admin-Passwörter stimmen nicht überein.");
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPass);
if ($mysqli->connect_error) {
    die("❌ Datenbankverbindung fehlgeschlagen: " . $mysqli->connect_error);
}

// Datenbank erstellen, falls nicht vorhanden
$mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($dbName);

// Schema importieren
$schema = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) DEFAULT 0,
    email VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(10) NOT NULL DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'offen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

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
SQL;

$mysqli->multi_query($schema);
while ($mysqli->more_results()) $mysqli->next_result();

// Admin-Nutzer anlegen
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (username, password, is_admin, email) VALUES (?, ?, 1, ?)");
$stmt->bind_param("sss", $adminUsername, $hashedPassword, $adminEmail);
$stmt->execute();

// Konfigurationsdatei schreiben
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
PHP;

file_put_contents(__DIR__.'/config.php', $configContent);

echo "✅ Installation abgeschlossen. <a href='index.php'>Zum Ticketsystem</a>";

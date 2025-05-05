<?php
// install.php - Interaktive Installation mit Schema-Import

// 1. Vorbereitung
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Prüfen, ob bereits installiert
if (file_exists(__DIR__.'/config.php')) {
    die("❌ Das System ist bereits installiert. Lösche config.php für eine Neuinstallation.");
}

// 3. Installationsformular
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Ticketsystem Installation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input, select { width: 100%; padding: 8px; box-sizing: border-box; }
            .btn { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .notes { font-size: 0.9em; color: #666; margin-top: 5px; }
            .tab { display: none; }
            .tab.active { display: block; }
            .tab-nav { display: flex; margin-bottom: 20px; }
            .tab-link { padding: 10px; background: #ddd; margin-right: 5px; cursor: pointer; }
            .tab-link.active { background: #007bff; color: white; }
        </style>
        <script>
            function showTab(tabName) {
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelectorAll('.tab-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');
                document.querySelector(`.tab-link[data-tab="${tabName}"]`).classList.add('active');
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
                
                <div class="form-group">
                    <label for="db_host">Datenbank-Host:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Datenbank-Name:</label>
                    <input type="text" id="db_name" name="db_name" value="ticketsystem" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Datenbank-Benutzer:</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Datenbank-Passwort:</label>
                    <input type="password" id="db_pass" name="db_pass">
                    <div class="notes">Leer lassen, wenn kein Passwort benötigt wird</div>
                </div>
            </div>
            
            <div id="smtp-tab" class="tab">
                <h2>E-Mail-Konfiguration (SMTP)</h2>
                
                <div class="form-group">
                    <label for="smtp_host">SMTP-Host:</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="mail.example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_port">SMTP-Port:</label>
                    <input type="number" id="smtp_port" name="smtp_port" value="587" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_encryption">Verschlüsselung:</label>
                    <select id="smtp_encryption" name="smtp_encryption" required>
                        <option value="tls">TLS (empfohlen)</option>
                        <option value="ssl">SSL</option>
                        <option value="">Keine</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="smtp_user">SMTP-Benutzer:</label>
                    <input type="text" id="smtp_user" name="smtp_user" value="noreply@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_pass">SMTP-Passwort:</label>
                    <input type="password" id="smtp_pass" name="smtp_pass" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_from">Absender-E-Mail:</label>
                    <input type="email" id="smtp_from" name="smtp_from" value="noreply@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="smtp_reply_to">Antwort-an-E-Mail:</label>
                    <input type="email" id="smtp_reply_to" name="smtp_reply_to" value="support@example.com" required>
                </div>
            </div>
            
            <div id="admin-tab" class="tab">
                <h2>Admin-Benutzer erstellen</h2>
                
                <div class="form-group">
                    <label for="admin_username">Benutzername:</label>
                    <input type="text" id="admin_username" name="admin_username" value="admin" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">E-Mail:</label>
                    <input type="email" id="admin_email" name="admin_email" value="admin@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Passwort:</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password_confirm">Passwort bestätigen:</label>
                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
                </div>
            </div>

            <button type="submit" class="btn">Installation starten</button>
        </form>
    </body>
    </html>
    HTML;
    exit;
}

// 4. Formulardaten verarbeiten
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

// 5. Validierungen
if ($adminPassword !== $adminPasswordConfirm) {
    die("❌ Die Admin-Passwörter stimmen nicht überein.");
}

// 6. Datenbankverbindung testen
try {
    $testConn = new mysqli($dbHost, $dbUser, $dbPass);
    if ($testConn->connect_error) {
        throw new Exception("Datenbankverbindung fehlgeschlagen: " . $testConn->connect_error);
    }
    
    // Datenbank erstellen, falls nicht vorhanden
    if (!$testConn->select_db($dbName)) {
        $createDb = $testConn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (!$createDb) {
            throw new Exception("Datenbank konnte nicht erstellt werden: " . $testConn->error);
        }
        $testConn->select_db($dbName);
    }
    
    // 7. Schema importieren
    $schemaSql = <<<SQL
    -- Tabelle für Benutzer
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        email VARCHAR(255) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'active',
        UNIQUE KEY (username),
        UNIQUE KEY (email)
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
        created_at DATETIME NOT NULL,
        PRIMARY KEY (email, token),
        FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
    );
    SQL;

    if ($testConn->multi_query($schemaSql)) {
        do {
            $testConn->store_result();
        } while ($testConn->more_results() && $testConn->next_result());
    } else {
        throw new Exception("Datenbanktabellen konnten nicht erstellt werden: " . $testConn->error);
    }

    // 8. Admin-Benutzer erstellen
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $testConn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $adminUsername, $adminEmail, $hashedPassword);
    if (!$stmt->execute()) {
        throw new Exception("Admin-Benutzer konnte nicht erstellt werden: " . $stmt->error);
    }
    $stmt->close();

    $testConn->close();
} catch (Exception $e) {
    die("❌ Fehler: " . $e->getMessage());
}

// 9. config.php erstellen
$configContent = <<<PHP
<?php
// Automatisch generierte Konfiguration

// 1. Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // In Produktion auf 0 setzen!

// 2. Datenbank-Konfiguration
\$dbConfig = [
    'host'     => '$dbHost',
    'username' => '$dbUser',
    'password' => '$dbPass',
    'name'     => '$dbName'
];

// 3. SMTP-Konfiguration
\$smtpConfig = [
    'host'      => '$smtpHost',
    'username'  => '$smtpUser',
    'password'  => '$smtpPass',
    'port'      => $smtpPort,
    'from'      => '$smtpFrom',
    'reply_to'  => '$smtpReplyTo',
    'encryption'=> '$smtpEncryption'
];

// 4. Datenbankverbindung
\$conn = new mysqli(
    \$dbConfig['host'],
    \$dbConfig['username'],
    \$dbConfig['password'],
    \$dbConfig['name']
);

if (\$conn->connect_error) {
    error_log("Database error: " . \$conn->connect_error);
    die("Database connection failed");
}

// 5. E-Mail-Funktion
function send_email(\$to, \$subject, \$message, \$altText = '') {
    global \$smtpConfig;

    require_once 'vendor/autoload.php';
    \$mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // SMTP-Einstellungen
        \$mail->isSMTP();
        \$mail->Host       = \$smtpConfig['host'];
        \$mail->SMTPAuth   = true;
        \$mail->Username   = \$smtpConfig['username'];
        \$mail->Password   = \$smtpConfig['password'];
        \$mail->SMTPSecure = \$smtpConfig['encryption'];
        \$mail->Port       = \$smtpConfig['port'];

        // Absender/Empfänger
        \$mail->setFrom(\$smtpConfig['from'], 'Ticket System');
        \$mail->addAddress(\$to);
        \$mail->addReplyTo(\$smtpConfig['reply_to'], 'Support');

        // Inhalt
        \$mail->isHTML(true);
        \$mail->Subject = \$subject;
        \$mail->Body    = \$message;
        \$mail->AltBody = \$altText ?: strip_tags(\$message);

        return \$mail->send();
    } catch (Exception \$e) {
        error_log("Mailer Error: {\$mail->ErrorInfo}");
        return false;
    }
}

// 6. Session-Einstellungen
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => true,    // Nur HTTPS
    'httponly' => true,  // Schutz vor XSS
    'samesite' => 'Lax'  // Schutz vor CSRF
]);
session_start();
PHP;

// 10. config.php speichern
if (file_put_contents(__DIR__.'/config.php', $configContent) === false) {
    die("❌ Konfigurationsdatei konnte nicht erstellt werden. Überprüfe Schreibrechte.");
}

// 11. Erfolgsmeldung
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Installation erfolgreich</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; }
        .credentials { background: #fff8e1; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <h1 class="success">Installation erfolgreich!</h1>
    
    <div class="info">
        <p>Das Ticketsystem wurde erfolgreich installiert.</p>
        
        <div class="credentials">
            <h3>Admin-Zugangsdaten:</h3>
            <p><strong>Benutzername:</strong> $adminUsername</p>
            <p><strong>E-Mail:</strong> $adminEmail</p>
        </div>
        
        <p><a href="login.php" class="btn">Zum Login</a></p>
        
        <p><strong>Sicherheitshinweis:</strong></p>
        <ul>
            <li>Löschen Sie die install.php nach der Installation!</li>
        </ul>
    </div>
</body>
</html>
HTML;

<?php
require_once __DIR__ . '/vendor/autoload.php';

// Hilfsfunktionen
function createDatabaseConnection($host, $user, $pass, $dbname = null) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Verbindung fehlgeschlagen: " . $conn->connect_error);
    }
    return $conn;
}

function writeEnvFile($data) {
    $content = "DB_HOST={$data['db_server']}\nDB_USERNAME={$data['db_username']}\nDB_PASSWORD={$data['db_password']}\nDB_DATABASE={$data['db_name']}\n";
    $content .= "SMTP_HOST={$data['smtp_host']}\nSMTP_USERNAME={$data['smtp_username']}\nSMTP_PASSWORD={$data['smtp_password']}\nSMTP_PORT={$data['smtp_port']}\n";
    return file_put_contents(__DIR__ . '/.env', $content);
}

function writeConfigPHP($data) {
    $content = "<?php\n";
    $content .= "// Automatisch generierte Konfigurationsdatei\n";
    $content .= "\$db_host = '{$data['db_server']}';\n";
    $content .= "\$db_user = '{$data['db_username']}';\n";
    $content .= "\$db_pass = '{$data['db_password']}';\n";
    $content .= "\$db_name = '{$data['db_name']}';\n\n";
    $content .= "\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);\n";
    $content .= "if (\$conn->connect_error) {\n";
    $content .= "    die('Verbindung zur Datenbank fehlgeschlagen: ' . \$conn->connect_error);\n";
    $content .= "}\n\n";
    $content .= "// SMTP-Konfiguration\n";
    $content .= "\$smtp_host = '{$data['smtp_host']}';\n";
    $content .= "\$smtp_user = '{$data['smtp_username']}';\n";
    $content .= "\$smtp_pass = '{$data['smtp_password']}';\n";
    $content .= "\$smtp_port = '{$data['smtp_port']}';\n\n";
    $content .= "function send_email(\$to, \$subject, \$message) {\n";
    $content .= "    global \$smtp_user;\n\n";
    $content .= "    \$headers = \"MIME-Version: 1.0\\r\\n\";\n";
    $content .= "    \$headers .= \"Content-type: text/html; charset=UTF-8\\r\\n\";\n";
    $content .= "    \$headers .= \"From: Support <{\$smtp_user}>\\r\\n\";\n\n";
    $content .= "    return mail(\$to, \$subject, \$message, \$headers);\n";
    $content .= "}\n";
    return file_put_contents(__DIR__ . '/config.php', $content);
}

function runSQLSchema($conn) {
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Fehler: schema.sql nicht gefunden.");
    }
    $sql = file_get_contents($schemaFile);
    if (!$conn->multi_query($sql)) {
        die("Fehler beim Ausführen des SQL-Schemas: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()) {;}
}

function createAdminUser($conn, $username, $password, $email) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    return $stmt->execute();
}

function generateStrongPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Hauptlogik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['db_server','db_username','db_password','db_name','smtp_host','smtp_username','smtp_password','smtp_port','admin_username','admin_email'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            die("Fehlendes Feld: $field");
        }
    }

    // Passwort generieren falls nicht angegeben
    $admin_password = empty($_POST['admin_password']) ? generateStrongPassword() : $_POST['admin_password'];

    if (!writeEnvFile($_POST)) die("Fehler beim Erstellen der .env-Datei.");
    if (!writeConfigPHP($_POST)) die("Fehler beim Erstellen der config.php.");

    $conn = createDatabaseConnection($_POST['db_server'], $_POST['db_username'], $_POST['db_password'], $_POST['db_name']);
    runSQLSchema($conn);
    if (createAdminUser($conn, $_POST['admin_username'], $admin_password, $_POST['admin_email'])) {
        echo "<div class='success-message'>Installation erfolgreich abgeschlossen.</div>";
        echo "<div class='password-info'>Das generierte Admin-Passwort lautet: <strong>$admin_password</strong></div>";
        echo "<div class='password-warning'>Bitte notieren Sie dieses Passwort sicher!</div>";
    } else {
        echo "<div class='error-message'>Fehler beim Erstellen des Admin-Benutzers.</div>";
    }

    // Test-Mail mit Passwort (optional)
    $to = $_POST['admin_email'];
    $subject = "Installation abgeschlossen";
    $message = "Die Installation wurde erfolgreich abgeschlossen.\n\n";
    $message .= "Ihr Admin-Passwort lautet: $admin_password\n";
    $message .= "Bitte ändern Sie dieses Passwort nach dem ersten Login.\n";
    
    // Temporäre send_email Funktion für die Installation
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Support <{$_POST['smtp_username']}>\r\n";
    mail($to, $subject, $message, $headers);
    
    exit;
}

$generated_password = generateStrongPassword();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Installationsassistent</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            max-width: 100%;
            box-sizing: border-box;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            width: 100%;
        }
        button:hover {
            background: #45a049;
        }
        .password-container {
            display: flex;
            gap: 10px;
        }
        .password-container input {
            flex: 1;
        }
        .password-container button {
            width: auto;
            padding: 10px 15px;
        }
        .success-message {
            background: #dff0d8;
            color: #3c763d;
            padding: 15px;
            border-radius: 4px;
            margin: 20px auto;
            max-width: 600px;
        }
        .password-info {
            background: #d9edf7;
            color: #31708f;
            padding: 15px;
            border-radius: 4px;
            margin: 10px auto;
            max-width: 600px;
            word-break: break-all;
        }
        .password-warning {
            background: #fcf8e3;
            color: #8a6d3b;
            padding: 15px;
            border-radius: 4px;
            margin: 10px auto;
            max-width: 600px;
        }
        .error-message {
            background: #f2dede;
            color: #a94442;
            padding: 15px;
            border-radius: 4px;
            margin: 20px auto;
            max-width: 600px;
        }
        @media (max-width: 480px) {
            form {
                padding: 15px;
            }
            input, button {
                padding: 12px;
            }
            .password-container {
                flex-direction: column;
            }
            .password-container button {
                width: 100%;
            }
        }
    </style>
    <script>
        function generatePassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('admin_password').value = password;
            return false;
        }
    </script>
</head>
<body>
    <h2>Installationsassistent</h2>
    <form method="POST">
        <label>Datenbankserver:</label>
        <input type="text" name="db_server" required>

        <label>Datenbankbenutzer:</label>
        <input type="text" name="db_username" required>

        <label>Datenbankpasswort:</label>
        <input type="password" name="db_password" required>

        <label>Datenbankname:</label>
        <input type="text" name="db_name" required>

        <label>SMTP-Host:</label>
        <input type="text" name="smtp_host" required>

        <label>SMTP-Benutzername:</label>
        <input type="text" name="smtp_username" required>

        <label>SMTP-Passwort:</label>
        <input type="password" name="smtp_password" required>

        <label>SMTP-Port:</label>
        <input type="text" name="smtp_port" required>

        <label>Admin-Benutzername:</label>
        <input type="text" name="admin_username" required>

        <label>Admin-Passwort:</label>
        <div class="password-container">
            <input type="text" id="admin_password" name="admin_password" value="<?php echo htmlspecialchars($generated_password); ?>" required>
            <button type="button" onclick="generatePassword()">Neu generieren</button>
        </div>

        <label>Admin-E-Mail:</label>
        <input type="email" name="admin_email" required>

        <button type="submit">Installation starten</button>
    </form>
</body>
</html>
   

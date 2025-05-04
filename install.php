<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'];
    $dbUser = $_POST['db_user'];
    $dbPass = $_POST['db_pass'];
    $dbName = $_POST['db_name'];
    $mailHost = $_POST['mail_host'];
    $mailUsername = $_POST['mail_username'];
    $mailPassword = $_POST['mail_password'];
    $mailPort = $_POST['mail_port'];
    $mailFrom = $_POST['mail_from'];
    $mailFromName = $_POST['mail_from_name'];

    $adminUsername = $_POST['admin_username'];
    $adminPassword = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
    $adminEmail = $_POST['admin_email'];

    // Verbindung zur Datenbank
    $conn = new mysqli($dbHost, $dbUser, $dbPass);

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Datenbank erstellen (wenn nicht vorhanden)
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
    $conn->select_db($dbName);

    // Tabellen anlegen
    $schema = file_get_contents('schema.sql');
    if (!$conn->multi_query($schema)) {
        die("Schema import failed: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()) { }

    // Admin-Nutzer einfügen
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin, status) VALUES (?, ?, ?, 1, 'active')");
    $stmt->bind_param("sss", $adminUsername, $adminPassword, $adminEmail);
    $stmt->execute();

    // config.php generieren
    $configContent = "<?php\n";
    $configContent .= "define('DB_HOST', '" . addslashes($dbHost) . "');\n";
    $configContent .= "define('DB_USER', '" . addslashes($dbUser) . "');\n";
    $configContent .= "define('DB_PASS', '" . addslashes($dbPass) . "');\n";
    $configContent .= "define('DB_NAME', '" . addslashes($dbName) . "');\n\n";
    $configContent .= "define('MAIL_HOST', '" . addslashes($mailHost) . "');\n";
    $configContent .= "define('MAIL_USERNAME', '" . addslashes($mailUsername) . "');\n";
    $configContent .= "define('MAIL_PASSWORD', '" . addslashes($mailPassword) . "');\n";
    $configContent .= "define('MAIL_PORT', " . (int)$mailPort . ");\n";
    $configContent .= "define('MAIL_FROM_ADDRESS', '" . addslashes($mailFrom) . "');\n";
    $configContent .= "define('MAIL_FROM_NAME', '" . addslashes($mailFromName) . "');\n";

    file_put_contents('config.php', $configContent);
    chmod('config.php', 0644);

    echo "<p style='color:green;'>Installation successful. <strong>config.php</strong> was created and admin user added.</p>";
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
            font-family: sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }
        h2 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"] {
            padding: 10px;
            font-size: 1rem;
            margin-top: 5px;
        }
        input[type="submit"] {
            margin-top: 20px;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h2>Install Ticket System</h2>
    <form method="POST">
        <label>Database Host</label>
        <input type="text" name="db_host" required>

        <label>Database User</label>
        <input type="text" name="db_user" required>

        <label>Database Password</label>
        <input type="password" name="db_pass" required>

        <label>Database Name</label>
        <input type="text" name="db_name" required>

        <label>Admin Username</label>
        <input type="text" name="admin_username" required>

        <label>Admin Password</label>
        <input type="password" name="admin_password" required>

        <label>Admin Email</label>
        <input type="email" name="admin_email" required>

        <label>SMTP Host</label>
        <input type="text" name="mail_host" required>

        <label>SMTP Username</label>
        <input type="text" name="mail_username" required>

        <label>SMTP Password</label>
        <input type="password" name="mail_password" required>

        <label>SMTP Port</label>
        <input type="number" name="mail_port" required>

        <label>Mail From Address</label>
        <input type="email" name="mail_from" required>

        <label>Mail From Name</label>
        <input type="text" name="mail_from_name" required>

        <input type="submit" value="Install">
    </form>
</body>
</html>

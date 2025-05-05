<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Dynamische Support-Adresse
$support_email = $smtp_config['reply_to'] ?? 'support@example.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Benutzer aus der Datenbank holen
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Überprüfen, ob der Benutzer gesperrt ist
        if ($user['status'] === 'locked') {
            $error_message = "Your account is disabled. Please contact support at <a href=\"mailto:$support_email\">$support_email</a>.";
        } else {
            // Passwort überprüfen
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Wrong password.";
            }
        }
    } else {
        $error_message = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ... dein bestehendes CSS bleibt unverändert ... */
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit">Submit</button>
            </div>
        </form>

        <p>Forgot Password? You can reset it <a href="reset_password_request.php">here</a>.</p>
        <p>Don't have an account? <a href="register.php">Sign up here</a>.</p>
    </div>
</body>
</html>

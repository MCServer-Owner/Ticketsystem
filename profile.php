<?php
session_start();
require_once 'config.php';
require_once 'admin_functions.php';
require_once 'send_email.php';
include 'navigation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Benutzerinformationen abrufen
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// Aktualisierung verarbeiten
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);

    $sql = "UPDATE users SET username = ?, email = ?" . (!empty($new_password) ? ", password = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $user_id);
    } else {
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
    }

    if ($stmt->execute()) {
        $message = "Profile successful edited.";

        // E-Mail-Benachrichtigung
        $mail_message = "<p>Hello {$new_username},</p>
        <p>Your Profile in our Ticketsystem was edited.</p>";

        if (!empty($new_password)) {
            $mail_message .= "<p>Your password was changed.</p>";
        }

        $mail_message .= "<p>If you didn't edit anything, contact immediately our Support at support@myts3server.at.</p>";

        send_email($new_email, "Profilechange in our Ticketsystem", $mail_message);

        $_SESSION['username'] = $new_username;
    } else {
        $message = "An error occured editing your Profile.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Profile Editor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 500px; margin: auto; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin: 5px 0 10px;
        }
        input[type="submit"] {
            padding: 10px 20px; background-color: #28a745;
            color: white; border: none; cursor: pointer;
        }
        .message { margin: 10px auto; color: green; }
    </style>
</head>
<body>

<h2>Profile Editor</h2>
<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<form method="post">
    <label>Username:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>
    
    <label>E-Mail:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
    
    <label>New Passwort (optional):</label>
    <input type="password" name="password">
    
    <input type="submit" value="Submit">
</form>

</body>
</html>


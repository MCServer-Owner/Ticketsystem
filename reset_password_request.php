<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Überprüfen, ob die E-Mail im System vorhanden ist
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Token generieren
        $token = bin2hex(random_bytes(50));
        $created_at = date("Y-m-d H:i:s");

        // Token in password_resets speichern
        $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $email, $token, $created_at);
        $stmt_insert->execute();

        // Reset-Link erzeugen
        $reset_link = "http://support.myts3server.at/support/reset_password.php?token=" . $token;
        $subject = "Reset Password";

        // HTML und Text-Version der Nachricht
        $html_message = "Please click on following link to reset your password:<br><br><a href='$reset_link'>$reset_link</a><br><br>If you didnt filed the request, please ignore it.";
        $text_message = "Please click on following link to reset your password: $reset_link\n\nIf you didnt filed the request, please ignore it..";

        // Mail senden
        if (send_email($email, $subject, $html_message, $text_message)) {
            echo "A link to reset your password was send.";
        } else {
            echo "An error occured sending this E-Mail. Please try again later.";
        }

    } else {
        echo "This E-Mail-Address is not registerd.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 400px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        input[type="email"], input[type="submit"] {
            width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #007BFF; color: white; border: none; cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        a { display: block; text-align: center; margin-top: 15px; color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form method="post">
            <label for="email">Please insert your E-Mail-Address:</label>
            <input type="email" name="email" id="email" required>
            <input type="submit" value="Send Reset-Link">
        </form>
        <a href="login.php">Back to Login</a>
    </div>
</body>
</html>


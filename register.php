<?php
session_start();
include('config.php');

// Überprüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Wenn das Formular abgesendet wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Überprüfen, ob alle Felder ausgefüllt wurden
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Alle Felder müssen ausgefüllt werden.";
    } elseif ($password !== $confirm_password) {
        $error = "Die Passwörter stimmen nicht überein.";
    } else {
        // Passwort hashen
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Überprüfen, ob der Benutzername oder die E-Mail bereits existiert
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Benutzername oder E-Mail bereits vergeben.";
        } else {
            // Neuen Benutzer in die Datenbank einfügen
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registrierung erfolgreich! Sie können sich jetzt einloggen.";
                header('Location: login.php');
                exit();
            } else {
                $error = "Fehler bei der Registrierung. Bitte versuchen Sie es später erneut.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
</head>
<body>
    <h1>Registrieren</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="register.php" method="post">
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="email">E-Mail:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="confirm_password">Passwort bestätigen:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <input type="submit" value="Registrieren">
    </form>

    <p>Bereits registriert? <a href="login.php">Einloggen</a></p>
</body>
</html>


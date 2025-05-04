<?php
// admin_functions.php

// Funktion zur Überprüfung, ob ein Benutzer ein Admin ist
function isAdmin($userId, $conn) {
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($isAdmin);
        $stmt->fetch();
        $stmt->close();
        return $isAdmin == 1;  // Gibt true zurück, wenn der Benutzer Admin ist, sonst false
    } else {
        return false;  // Falls es ein Fehler gibt, false zurückgeben
    }
}
?>


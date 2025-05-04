<?php
session_start();
session_destroy();  // Alle Sessions löschen
header("Location: index.php");  // Weiter zum Login
exit;
?>


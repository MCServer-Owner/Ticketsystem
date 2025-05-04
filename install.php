<?php
require_once 'config.php';

$sql = file_get_contents(__DIR__ . '/schema.sql'); // oder direkt hier SQL einfügen

if ($conn->multi_query($sql)) {
    do {
        // alle Ergebnisse abarbeiten
    } while ($conn->more_results() && $conn->next_result());

    echo "✅ Installation abgeschlossen. Die Tabellen wurden erstellt.";
} else {
    echo "❌ Fehler bei der Installation: " . $conn->error;
}


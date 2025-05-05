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
            .error { color: red; margin-top: 5px; }
        </style>
        <script>
            function showTab(tabName) {
                // Verstecke alle Tabs
                document.querySelectorAll('.tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Entferne aktive Klasse von allen Tab-Links
                document.querySelectorAll('.tab-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Zeige den gewählten Tab an
                document.getElementById(tabName).classList.add('active');
                
                // Markiere den aktiven Tab-Link
                document.querySelector(`.tab-link[data-tab="${tabName}"]`).classList.add('active');
            }

            // Beim Laden der Seite den ersten Tab aktivieren
            document.addEventListener('DOMContentLoaded', function() {
                showTab('db-tab');
            });
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
                    <div class="notes">Normalerweise 'localhost' oder '127.0.0.1'</div>
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
                    <div class="notes">Z.B. 'smtp.gmail.com' für Gmail</div>
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
                    <div class="notes">Wird für Passwort-Zurücksetzen benötigt</div>
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

// [Rest des ursprünglichen Installationscodes bleibt gleich...]

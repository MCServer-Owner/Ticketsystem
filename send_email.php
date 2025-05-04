<?php
// PHPMailer importieren
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Verzeichnis, in dem Composer die PHPMailer-Bibliothek installiert hat

function send_email($to, $subject, $message) {
    // E-Mail-Instanz erstellen
    $mail = new PHPMailer(true);

    try {
        // SMTP-Konfiguration
        $mail->isSMTP();                                      // Setzt den Mailer auf SMTP
        $mail->Host       = 'myts3server.at';             // SMTP-Server von Poste.io
        $mail->SMTPAuth   = true;                               // Aktiviert die SMTP-Authentifizierung
        $mail->Username   = 'noreply@myts3server.at';          // E-Mail-Adresse des Absenders
        $mail->Password   = 'Adminer123456';                    // Passwort für das Postfach
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // STARTTLS-Verschlüsselung
        $mail->Port       = 587;                                // Port für STARTTLS (587) oder SSL (465)

        // Absender
        $mail->setFrom('noreply@myts3server.at', 'Ticketsystem');

        // Empfänger
        $mail->addAddress($to);                                 // Empfängeradresse

        // Antwort-Adresse (falls vorhanden)
        $mail->addReplyTo('support@myts3server.at', 'Ticketsystem');

        // Inhalt der E-Mail
        $mail->isHTML(true);                                    // Setzt das E-Mail-Format auf HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;                              // Die E-Mail-Nachricht im HTML-Format

        // E-Mail senden
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Fehlerbehandlung
        echo "E-Mail konnte nicht gesendet werden. Mailer-Fehler: {$mail->ErrorInfo}";
        return false;
    }
}
?>


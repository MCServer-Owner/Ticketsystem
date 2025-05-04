
 Ticket System: Installation Guide


1. SYSTEM REQUIREMENTS
-------------------------------
Install the following packages:

sudo apt-get update
sudo apt-get install -y apache2 php php-mysqli php-curl php-mbstring php-xml php-zip php-cli php-common mariadb-server unzip git

Optional (for mail functionality, e.g. using PHPMailer):
sudo apt-get install -y sendmail

Make sure Apache and MariaDB are running:
sudo systemctl enable --now apache2 mariadb

2. FILES
-------------------------------
The following files and folders must be present in the project directory:

- install.php                 → Launches the installation (database, admin user, mail config)
- schema.sql                 → Contains SQL structure (users, tickets, comments, etc.)
- config.php (auto-generated)
- All other PHP files
- Folder: vendor/            → For PHPMailer (if used)

3. INSTALLATION
-------------------------------
a) Open in your browser:
   http://<your-domain>/install.php

b) Enter all required settings:
   - Database credentials
   - Admin account information
   - Mail server (SMTP) configuration

c) Upon successful installation, `config.php` will be automatically generated.

d) You can then log in via `login.php`.

4. DATABASE
-------------------------------
If not executed automatically:
   - You can import `schema.sql` manually (e.g. via phpMyAdmin or MySQL client)

Example:
mysql -u root -p < schema.sql

5. MAIL
-------------------------------
Mail functionality is configured via SMTP.
Credentials are saved securely in `config.php`.

6. SECURITY NOTES
-------------------------------
- Ensure that `config.php` is **not publicly writable**:
  chmod 644 config.php

- After installation, delete or rename `install.php` to prevent reconfiguration:
  rm install.php

===============================
Done!
===============================

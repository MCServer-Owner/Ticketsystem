TICKET SYSTEM INSTALLATION - INSTRUCTIONS
=====================================

1. PREREQUISITES
------------------
The following software must be installed on the web server:

- PHP 8.2 or 8.3 (with the extensions: mysqli, mbstring, json, session, mail)
- MySQL or MariaDB
- Apache2 or Nginx with PHP support
- Optional: mail server or SMTP access for password reset function

You can install packages under Ubuntu/Debian (e.g. for PHP 8.2) like this:

    sudo apt update && apt install apache2 mariadb-server php8.2 php8.2-mysql php8.2-mbstring php8.2-json php8.2-session php8.2-cli php8.2-curl unzip

> If you are using PHP 8.3, replace `php8.2` with `php8.3`.

2. UPLOAD FILES
--------------------
Clone all files of the Repository or extract the ZIP archive to your web server, e.g. to `/var/www/html/ticketsystem`.

3. CONFIGURATION
----------------
Edit the file `config.php` and enter your database access data:

    $host = 'localhost';
    $db = 'your_database';
    $user = 'your_db_user';
    $pass = 'your_db_password';

4. SET UP DATABASE
-----------------------
Execute the installation script `install.php` once via your browser:

    http://deine-domain.de/ticketsystem/install.php

The following tables are created automatically:
- users
- tickets
- ticket_comments
- ticket_status_history

Then **delete or protect the `install.php`** to ensure security.

📋 Ticket System – Installation Guide

This web-based ticket system provides an intuitive interface for managing support requests and internal tasks. Follow the steps below to install and set up the application.

🚀 Features
User and admin management

Ticket creation, editing, assignment, and status tracking

Commenting and history for each ticket

Email notifications via SMTP

Web-based installation with schema import

🧰 Requirements
PHP 8.1 or higher

MySQL/MariaDB

Web server (Apache, Nginx, etc.)

SMTP credentials for email sending

🛠️ Installation Steps
Upload the Files

Upload all project files to your desired web server directory (e.g., /var/www/html/ticketsystem).

Start the Installation

Open your browser and navigate to the installation script:

http://your-domain.com/ticketsystem/install.php
Fill in the Installation Form

The installation is divided into three sections:

📂 Database Configuration
Database host (usually localhost)

Database name (e.g., ticketsystem)

Database user

Database password (optional)

📧 Email (SMTP) Configuration
SMTP host (e.g., mail.example.com)

SMTP port (e.g., 587)

Encryption (TLS, SSL, or none)

SMTP user

SMTP password

Sender email

Reply-to email

👤 Admin User
Admin username

Admin email

Password (with confirmation)

Start Installation

Click on “Start Installation”. The system will:

Test the database connection

Create the database (if it doesn’t exist)

Import all required tables

Save the configuration to config.php

Create the initial admin user

Installation Complete

If successful, you’ll be redirected to the login page.

🔐 Security Note
After installation:

Delete or rename install.php to prevent unauthorized reinstallations.

Make sure config.php is not writable for the web server.

🔧 Troubleshooting
❌ "The system is already installed."
→ Delete config.php if you want to reinstall.

❌ "Database connection failed."
→ Check your database credentials and server.

❌ "Admin passwords do not match."
→ Re-enter the password and confirmation field.

📁 Database Structure (Overview)
users – All user and admin accounts

tickets – Main ticket information

ticket_comments – User comments on tickets

ticket_status_history – History of ticket status changes and assignments

📩 Email Functionality
The system sends notifications (e.g., password reset, ticket updates) via the configured SMTP server.

📞 Support
For questions or support, please contact the system administrator at support@myts3server.at or the development team.

You can take a look at support.myts3server.at to see the full installed and productive system. You can also create Tickets for your installation, if you have suggestions or troubles.

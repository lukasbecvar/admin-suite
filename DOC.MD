# Temporary dev docs
This is a temporary documentation for the project!

## Requirements
* OS Linux
* PHP 8.3
* Mysql - PDO driver
* PHP-OpenSSL extension
* PHP Intl extension
* PHP Curl extension
* MySQL 8
* Composer
* NPM

## Installation
* Clone repo
* Create apache virtual host or nginx configuration line ([apache-config](https://github.com/lordbecvold/admin-suite/blob/main/_docker/configs/apache-site.conf))
* Set document root to public directory (for example /var/www/public)
* Switch env mode to prod and create .env file with your configuration (.env.prod)
* Run the install script (script/install.sh)
* Create admin suite database
* Run database migrations (script/migrate.sh)
* Create user with console command or web interface (webinterface allows to create user if users table is empty)
* Configure your package-requitements.json & services.json in root file (for example use config/suite files)
* Create service for monitoring process (console command infinite loop)

### Dashboard
The Server Dashboard component provides insights and metrics into server performance, resource utilization, and system health. It offers administrators a comprehensive overview to ensure optimal server operation.

### Logs Manager
The Server Logs Manager component provides a centralized location for managing server logs. It allows administrators to view, search, and analyze server logs, ensuring efficient troubleshooting and problem resolution.

### Monitoring Dashboard
The Monitoring Dashboard component offers a graphical interface for tracking system statuses. It assists in proactive monitoring and management of systems and applications.

### Todo Manager
The Todo Manager component allows users to organize tasks. It supports task creation, and status updates, helping stay productive and organized.

### Users Manager
The Users Manager component provides administrators with tools to manage user accounts, permissions, and roles within the system. It simplifies user administration and ensures secure access control.

### Account Settings
The Account Settings component allows users to customize their account preferences and update personal information. It provides a centralized interface for maintaining user-specific configurations.

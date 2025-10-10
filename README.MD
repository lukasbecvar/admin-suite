# <h1 align="center">Admin suite</h1>
### <p align="center">Solution for administration and monitoring linux servers</p>
<p align="center">
    <a href="LICENSE" target="_blank">License</a> |
    <a href="https://becvar.xyz" target="_blank">Author website</a>
</p>

## Dashboard preview
![Dashboard](.github/assets/preview.gif)

## Description
Simple & user-friendly solution for monitoring and managing Linux servers, primarily designed for single server instances debian-based systems.

## Tech stack
#### Development
The complete stack for development is in Docker Compose, including PHP, Node, and Composer. You just need to run the ``sh docker-start-dev.sh`` script to launch it.
#### Production
The application is designed to run on bare-metal rather than as a containerized application. For this reason, it requires minimal software to operate, making it easy to install and manage. For example, the metrics component stores data in MySQL, and the cache uses the filesystem instead of Redis.

## Index
- [Installation](#installation)
- [Configuration](#configuration)
- [Requirements](#requirements)
- [Authentication](#authentication)
- [Monitoring](#monitoring)
- [Notifications](#notifications)
- [Logging](#logging)
- [Dependencies](#dependencies)
- [License](#license)

## Installation
Installation is very simple like any other PHP project.
* Clone repo ``git clone git@github.com:lukasbecvar/admin-suite.git``
* Create apache virtual host or nginx configuration like ([apache-config](.docker/configs/apache-site.conf))
* Create .env file with your configuration (.env.prod)
* Install dependencies ``npm install && composer install``
* Build frontend assets ``npm run build``
* Create database ``php bin/console doctrine:database:create --if-not-exists``
* Migrate database ``php bin/console doctrine:migrations:migrate --no-interaction``
* Create systemd service for monitoring process (console command infinite loop) ([systemd-config](.docker/configs/monitoring-systemd.service))
* Create main admin user using console command or web interface (webinterface allows to create user if users table is empty)
* Create your custom suite configuration files in root directory (see [Configuration](#configuration))

## Configuration
#### Environment variables
The environment variables are loaded from .env file. You can create .env file in root directory with your configuration.
| Variable | Description | Example value |
| --- | --- | --- |
| APP_ENV | Application environment | prod |
| APP_SECRET | Secret encryption key | 0cb9325e8b4b59a90249865085 |
| TRUSTED_HOSTS | Trusted url hosts | becvar.xyz,becvar.test |
| SSL_ONLY | Enable only ssl traffic | true |
| MAINTENANCE_MODE | Enable maintenance mode | true |
| PWA_APP_SUPPORT | Enable pwa app support | true |
| ADMIN_CONTACT | The system maintener contact email | admin@becvar.xyz |
| IP_APIS | The list of APIs to get host server pulic ip | IP_APIS=IP_APIS=https://ifconfig.me,url2,url2 |
| AUTHOR_WEBSITE_URL | The system maintener contact email | https://becvar.xyz |
| PROJECT_GITHUB_URL | The system maintener contact email | https://github.com/lukasbecvar/admin-suite |
| IP_INFO_API | API for get information about visitor ip | http://ip-api.com |
| RATE_LIMIT_ENABLED | Enable rate limit | false |
| RATE_LIMIT_INTERVAL | Rate limit interval | 60 |
| RATE_LIMIT_LIMIT | Rate limit limit | 100 |
| EXTERNAL_API_LOG_TOKEN | Token for external api logging | 1234567890 |
| ANTI_LOG_TOKEN | Token for disable logging | 1234567890 |
| SYSTEM_LOGS_DIR | System logs directory | /var/log |
| DATABASE_LOGGING | Enable database logging | true |
| LOG_LEVEL | Log level | 4 |
| MEMORY_COST | Hash memory cost | 1024 |
| TIME_COST | Hash time cost | 10 |
| THREADS | Hash threads | 2 |
| LIMIT_CONTENT_PER_PAGE | Limit items per page | 10 |
| MONITORING_INTERVAL | Monitoring interval | 60 |
| METRICS_SAVE_INTERVAL | Metrics save interval | 60 |
| NETWORK_SPEED_MAX | Network speed (Mbps) limit for usage calculation | 1000 |
| DATABASE_DRIVER | Database driver | pdo_mysql |
| DATABASE_HOST | Database host | localhost |
| DATABASE_PORT | Database port | 3306 |
| DATABASE_NAME | Database name | admin_suite |
| DATABASE_USERNAME | Database user | root |
| DATABASE_PASSWORD | Database password | root |
| MAILER_ENABLED | Enable mailer | true |
| MAILER_HOST | Mailer host | localhost |
| MAILER_PORT | Mailer port | 25 |
| MAILER_USERNAME | Mailer username | service@becvar.xyz |
| MAILER_PASSWORD | Mailer password | password |
| PUSH_NOTIFICATIONS_ENABLED | Enable push notifications | true |
| PUSH_NOTIFICATIONS_MAX_TTL | Push notifications max ttl | 86400 |
| PUSH_NOTIFICATIONS_CONTENT_ENCODING | Push notifications content encoding | aes-256-gcm |
| PUSH_NOTIFICATIONS_VAPID_PUBLIC_KEY | Public key for push notifications | 12938747T6T6R236 |
| PUSH_NOTIFICATIONS_VAPID_PRIVATE_KEY | Private key for push notifications | 12938747T6T6R236 |
#### Suite configuration
The default admin suite configuration is stored in json files in config/suite directory. You can create your own configuration files in app root and override default configuration.
* ``config/suite/blocked-usernames.json`` - list of blocked usernames
* ``config/suite/package-requirements.json`` - list of packages and php extensions to be installed
* ``config/suite/services-monitoring.json`` - list of services to be monitored
* ``config/suite/terminal-aliases.json`` - list of terminal aliases
* ``config/suite/terminal-blocked-commands.json`` - list of blocked terminal commands

## Requirements
#### Software
* PHP 8.4 and compatible web server
* MySQL 8 or higher
* Composer
* NPM
#### PHP extensions
* PHP-PDO extension (db driver)
* PHP-OpenSSL extension
* PHP-Intl extension
* PHP-Curl extension
* PHP-DOM extension
* PHP-GMP extension
* PHP-GD extension

## Authentication
Authentication uses a completely custom user system with a username and password, managed by the application user manager. Only the first user can use the registration form to create admin user. Login uses standard PHP session and cookie for the 'remember me' feature.

## Monitoring
#### Services monitoring
HTTP and systemd services monitoring is ensured by a console command (infinite loop) ``console app:monitoring:process``. This command needs to be run as a service, and the monitored services should be defined in the services config file in config/suite/services-monitoring.json. it is necessary to set the monitoring interval and the SMTP server or push notifications in the environment variables for sending alerts. The status and monitoring data can then be viewed in the web dashboard page.
#### Metrics
The metrics component stores data during the monitoring process, so its function requires the monitoring process to be running. The metrics store average usage values for CPU, RAM, and storage during the interval set in ``.env`` as ``METRICS_SAVE_INTERVAL``.
#### Metrics Collector for HTTP services
The Metric Collector allows you to collect metrics from monitored HTTP web applications. You can enable the metric collector in the `services-monitoring.json` file ([services-monitoring.json](config/suite/services-monitoring.json)) by setting `collect_metrics` to true and specifying the `metrics_collector_url`. The Metric Collector accepts `key` (string) and `value` (int|float).
#### Example response from the Metric Collector URL:
```json
{
  "cpu_usage": 64,
  "ram_usage": 77,
  "storage_usage": 10,
}
```
#### Visitor tracking
The visitor tracking feature allows you to monitor visitors to HTTP-based services. To enable tracking, you need to configure the service in the ([services-monitoring.json](config/suite/services-monitoring.json)) file. The service name and URL must be specified (the URL is used for verification as `HTTP_ORIGIN`). Tracking is performed via an API that needs to be called from your application.
```javascript
const visitorReferer = document.referrer || 'Unknown'

fetch('https://admin-suite.url/api/monitoring/visitor/tracking', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        service_name: 'monitored-service-name',
        referer: visitorReferer
    })
})
```

## Notifications
Notifications from application are sent to admin users via web push notifications if the admin enables them. It also allows sending alerts directly to the admin's email.

## Logging
The system allows storing actions in the database to track the actions of admin suite users and also receive logs from external applications via an API.

## Contributing
Contributions are welcome! Please fork this repository and submit pull requests for any features, bug fixes, or enhancements. If you have any questions or need help, feel free to open an issue or contact me directly.

## Dependencies
* PHP
    * [Website](https://php.net)
* Symfony with doctrine
    * [Website](https://symfony.com)
* PHPUnit
    * [Github](https://github.com/sebastianbergmann/phpunit)
* Better PHPUnit CLI output
    * [Github](https://github.com/robiningelbrecht/phpunit-pretty-print)
* PHPStan
    * [Github](https://github.com/phpstan/phpstan)
* Tailwind
    * [Website](https://tailwindcss.com)
* ApexCharts
    * [Github](https://github.com/apexcharts/apexcharts.js)
* NPM / Webpack encore
    * [NPM](https://docs.npmjs.com)
    * [Encore](https://symfony.com/doc/current/frontend/encore/index.html)

## License
This software is licensed under the [MIT license](LICENSE). 

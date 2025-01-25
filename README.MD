# <h1 align="center">Admin suite</h1>
### <p align="center">Solution for administration and monitoring linux servers</p>
<p align="center">
    <a href="https://github.com/lukasbecvar/admin-suite/blob/main/LICENSE" target="_blank">License</a> |
    <a href="https://becvar.xyz" target="_blank">Author website</a>
</p>

## Dashboard preview
![Dashboard](.github/assets/preview.png)

## Description
A simple, user-friendly solution for monitoring and managing Linux servers. Designed specifically for single server instances.

## Installation
Installation is very simple like any other PHP project.
* Clone repo ``git clone git@github.com:lukasbecvar/admin-suite.git``
* Create apache virtual host or nginx configuration like ([apache-config](https://github.com/lukasbecvar/admin-suite/blob/main/.docker/configs/apache-site.conf))
* Create .env file with your configuration (.env.prod)
* Install dependencies ``npm install && composer install``
* Build assets ``npm run build``
* Create database ``php bin/console doctrine:database:create --if-not-exists``
* Migrate database ``php bin/console doctrine:migrations:migrate --no-interaction``
* Create user with console command or web interface (webinterface allows to create user if users table is empty)

## Configuration
* You can edit the config files directly in config/suite but it is better to create your own in the project root because these files will be loaded primarily and ignored by git.
* Configure your package-requirements.json & services-monitoring.json in root file (for example use config/suite files)
* Create systemd or screen service for monitoring process (console command infinite loop) ([systemd-config](https://github.com/lukasbecvar/admin-suite/blob/main/.docker/configs/monitoring-systemd.service))

## Authentication
Authentication uses a completely custom user system with only a username and password, and all users are managed by the application administrator. Only the first user can use the registration to create the first user in the database. The login uses a standard PHP session and a cookie for the 'remember me' feature.

## Exception monitoring
In config exceptions-monitoring.json it is possible to set paths to exception files and track exceptions from your applications via log manager

## Monitoring
Monitoring is ensured by a console command (infinite loop) ``console app:process:monitoring``. This command needs to be run as a service, and the monitored services should be defined in the services file in the root of the project, which is considered the primary source, or directly in config/suite/services-monitoring.json. it is necessary to set the monitoring interval and the SMTP server or push notifications in the environment variables for sending alerts. The status and monitoring data can then be viewed in the web dashboard page. 

## Metrics
The metrics component stores data during the monitoring process, so its function requires the monitoring process to be running. The metrics store average usage values for CPU, RAM, and storage during the interval set in ``.env`` as ``METRICS_SAVE_INTERVAL``.

## Metrics Collector for HTTP services
The Metric Collector allows you to collect metrics from monitored HTTP web applications. You can enable the metric collector in the `services-monitoring.json` file ([services-monitoring.json](https://github.com/lukasbecvar/admin-suite/blob/main/config/suite/services-monitoring.json)) by setting `collect_metrics` to true and specifying the `metrics_collector_url`. The Metric Collector accepts `key` (string) and `value` (int|float).
Example response from the Metric Collector URL:
```json
{
  "cpu_usage": 64,
  "ram_usage": 77,
  "storage_usage": 10,
}
```

## Push notifications
If you enable push notifications in the environment config, make sure to allow notifications in the browser when loading the admin page. Additionally, if you delete subscribers or VAPID keys, you’ll need to reset notification permissions in the browser.

## External log API
External log API is used for retrieving logs from external or monitored services.
* ``/api/external/log`` - POST (data: name, message, level, access-token(from .env))

## Requirements
* NPM package manager and assets build tool for encore build
* Composer for managing PHP dependencies
* PHP 8.4
* MySQL 8 or higher
* MySQL-PDO extension (db driver)
* PHP-OpenSSL extension
* PHP-Intl extension
* PHP-Curl extension
* PHP-GD extension

## Development environment
The complete stack for development is in Docker Compose, including PHP, Node, and Composer. You just need to run the docker-start script to launch it.

## Contributing
Contributions are welcome! Please fork this repository and submit pull requests for any features, bug fixes, or enhancements. If you have any questions or need help, feel free to open an issue or contact me directly.

## Dependencies
This project uses the following technologies
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
This software is licensed under the [MIT license](https://github.com/lukasbecvar/admin-suite/blob/main/LICENSE).

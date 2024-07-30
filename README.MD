# <h1 align="center">Admin suite</h1>
### <p align="center">Solution for administration and monitoring linux private servers</p>
<p align="center">
    <a href="https://github.com/lordbecvold/admin-suite/blob/main/LICENSE" target="_blank">License</a> |
    <a href="https://becvar.xyz" target="_blank">Author</a>
</p>

## Dashboard preview
![Dashboard](.github/assets/preview.png)

## ROADMAP
- [ ] Internal chat
- [ ] File explorer
- [ ] Terminal component
- [ ] Doc/About & download page (github pages)
    - [ ] About homepage
    - [ ] Download page
    - [ ] Base documentation
    - [ ] Installation documentation
    - [ ] Components documentation

## Requirements
* OS Linux
* PHP 8.3
* Mysql - PDO driver
* PHP-OpenSSL extension
* PHP Intl extension
* PHP Curl extension
* MySQL 8
* Composer
* NPM 9.2.0 or higher

## Installation
Installation is very simple like any other PHP project.
* Clone repo
* Create apache virtual host or nginx configuration like ([apache-config](https://github.com/lordbecvold/admin-suite/blob/main/_docker/configs/apache-site.conf))
* Create .env file with your configuration (.env.prod)
* Create admin suite database
* Run database migrations (script/migrate.sh)
* Create user with console command or web interface (webinterface allows to create user if users table is empty)

## Configuration
* Configure your package-requitements.json & services.json in root file (for example use config/suite files)
* Create systemd or screen service for monitoring process (console command infinite loop)
* You can edit the config files directly in config/suite but it is better to create your own in the project root because these files will be loaded primarily and ignored by git when using the pull for update

## Primary use
I created this project to manage my personal servers. I wanted a simple and easy-to-use solution that would allow me to monitor and manage my servers without relying on complex software or complicated setups. This project aims to manage single server instances, not clusters.

## Contributing
Contributions are welcome! Please fork this repository and submit pull requests for any features, bug fixes, or enhancements. If you have any questions or need help, feel free to open an issue or contact me directly.

## Dependencies
This project uses the following technologies
* PHP
    * [Website](https://php.net)
* Symfony
    * [Website](https://symfony.com)
* Tailwind
    * [Website](https://tailwindcss.com)
* NPM / Webpack encore
    * [NPM](https://docs.npmjs.com)
    * [Encore](https://symfony.com/doc/current/frontend/encore/index.html)

## License
The framework is open-sourced software licensed under the [MIT license](https://github.com/lordbecvold/admin-suite/blob/main/LICENSE).

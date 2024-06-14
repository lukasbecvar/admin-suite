# Admin suite
Solution for Linux server with custom services administration and monitoring

## Documentation
The complete source documentation is [here](https://lordbecvold.github.io/admin-suite/)

## ROADMAP
## 0.1
- [X] Setup symfony framework
    - [X] Install dependencies
    - [X] Install webpack
    - [X] Install phpunit
    - [X] Install phpstan
    - [X] Install phpdocumentor
- [X] Dev docker env
- [X] Github actions
- [X] APP base
    - [X] Index controller
    - [X] Log manager
    - [X] Error handler
    - [X] Security middlewares
    - [X] Email service
    - [X] Log level config
    - [X] Basic templates layout
    - [X] Base unit tests

## 0.2
- [X] User system
- [X] User register & admin role grant command
- [X] Auth system
    - [X] Register (only if users table is empty)
    - [X] Login component with remember me
    - [X] Allow login & register route only if user not logged
    - [X] Logout component
    - [X] Index redirect to dashboard or login
    - [X] Login check middleware
    - [X] Split auth methods to Auth manager from User manager
    - [X] User -> log one to many
    - [X] Store user-agent in user entity
    - [X] Update last login time, ip address & user agent in login process
    - [X] Implement log to auth process
    - [X] Auth components style
    - [X] End-to-end auth tests
- [X] Get user info in admin dashboard

## 0.3
- [X] Link logs to users
- [X] Online users list & online count
- [X] Main dashboard
    - [X] Admin site layout
    - [X] Main nav panel
    - [X] Sidebar
    - [X] Component box
    - [X] User info
    - [X] Default sidebar toggle in dashboard (only pc)
- [ ] User managment system
    - [X] View users list table
    - [X] Show online/offline status in table
    - [X] user agent shortify
    - [X] Add user os value
    - [X] Add new users
    - [X] User manager only for admin users
    - [X] Delete users (with index reset)
    - [X] Filter only online users list
    - [X] Update role
    - [X] Value color
        - [X] Same ip like client
        - [X] Online status
        - [X] Unknown browser
        - [X] Role
- [X] View data output escape

## 0.4
- [X] Anti log
- [X] New login email alert
- [ ] Ban system
    - [X] Banned entity (reason, time, status, banned by id, banned user id)
    - [X] Ban manager
    - [X] Ban/Unban user controller
    - [X] Ban status in users table
    - [X] Ban/unban actions in users table
    - [X] Banned check in auto login middleware
    - [ ] Ban/unban command
    - [ ] Banned alert send to email
    - [ ] Delete banned user automatic delete ban
    - [ ] Testing banned users fixtures
    - [ ] Ban system unit tests
- [ ] Users table filter system
- [ ] Users manager test
- [ ] Refactor & design fixs

## 0.5
- [ ] Server info
    - [ ] OS info
    - [ ] CPU usage
    - [ ] Ram usage
    - [ ] SSD space
    - [ ] Uptime info
- [ ] Server diagnostics component
    - [ ] Detect requirements (os & php extensions)
    - [ ] Detect linux
    - [ ] Detect SSH low space
    - [ ] CPU overload detect
    - [ ] Ram overload detect
    - [ ] Check web server permissions
- [ ] Web app diagnostics
    - [ ] Check https (if https only)
    - [ ] Check if devmode is enabled
- [ ] Dashboard warning box (react to diagnostic data component)

## 0.6
- [ ] Service manager component
    - [ ] Systemctl status
    - [ ] Systemctl stop, start, restart
    - [ ] Docker service status
    - [ ] Docker service stop, start, restart
- [ ] Service monitoring component
    - [ ] Monitoring loop command (check if all services is online)
    - [ ] Log offline status
    - [ ] Send offline alert in email
    - [ ] Send back online notification

## 0.7
- [ ] Terminal component

## 0.8
- [ ] File browser & editor
    - [ ] List files in filesystem
    - [ ] Rename files
    - [ ] Remove files
    - [ ] Move files
    - [ ] Open files in txt editor

## 0.9
- [ ] Database browser
    - [ ] All databases list
    - [ ] Browse tables
    - [ ] Add row
    - [ ] Edit values
    - [ ] Delete rows
    - [ ] Delete all rows
    - [ ] Dump SQL structure
    - [ ] Backup database (download SQL file)

## 1.0
- [ ] Log manager
    - [ ] View unreaded logs
    - [ ] View all logs 
    - [ ] Set all readed
    - [ ] View unformated logs (redirect to database browser)
    - [ ] View exception log from monolog
    - [ ] View logs by user (redirect from user managment)
- [ ] CLI log reader command
    - [ ] Return unreaded
    - [ ] Return all (with -all argument)

## 1.1
- [ ] Todo manager
    - [ ] Add todo
    - [ ] Move todo in list (up or down)
    - [ ] Edit todo
    - [ ] Close todo
    - [ ] View closed todo list
- [ ] Internal chat component
- [ ] Account settings component
    - [ ] Change profile picture
    - [ ] Change username
    - [ ] Change password

## 1.2
- [ ] About component (developer, github, version, info, etc..)
- [ ] User manual with config & setup doc
- [ ] Setup script (for non docker env)
- [ ] Readme doc

## 1.3
- [ ] Connecting other services (like contact inbox, visitor systems, etc)

## License
The framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

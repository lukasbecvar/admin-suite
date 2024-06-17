# Admin suite
Solution for administration and monitoring linux private servers

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
- [X] User managment system
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
- [X] Ban system
    - [X] Banned entity (reason, time, status, banned by id, banned user id)
    - [X] Ban manager
    - [X] Ban/Unban user controller
    - [X] Ban status in users table
    - [X] Ban/unban actions in users table
    - [X] Banned check in auto login middleware
    - [X] Ban/unban command
    - [X] Delete banned user automatic delete ban
    - [X] Testing banned users fixtures
- [X] Users table filter system
- [X] Account settings component
    - [X] Account settings home table
    - [X] Change username
    - [X] Change password
    - [X] Change profile picture
    - [X] End to end test
- [X] Better permissions check system
- [X] Refactor

## 0.5
- [ ] Banned (with reason) & online status in profile info
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
- [ ] About component (developer, github, version, info, etc..)
- [ ] Design improvements
    - [ ] Swipe sidebar activate/deactivate
    - [ ] Better background colors

## 0.6
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

## 0.7
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

## 0.8
- [ ] Terminal component

## 0.9
- [ ] File browser & editor
    - [ ] List files in filesystem
    - [ ] Rename files
    - [ ] Remove files
    - [ ] Move files
    - [ ] Open files in txt editor

## 1.0
- [ ] Database browser
    - [ ] All databases list
    - [ ] Browse tables
    - [ ] Add row
    - [ ] Edit values
    - [ ] Delete rows
    - [ ] Delete all rows
    - [ ] Dump SQL structure
    - [ ] Backup database (download SQL file)

## 1.1
- [ ] Todo manager
    - [ ] Add todo
    - [ ] Move todo in list (up or down)
    - [ ] Edit todo
    - [ ] Close todo
    - [ ] View closed todo list
- [ ] Internal chat componenta

## 1.2
- [ ] User manual with config & setup doc
- [ ] Setup script (for non docker env)
- [ ] Readme doc

## 1.3
- [ ] Connecting other services (like contact inbox, visitor systems, etc)

## License
The framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

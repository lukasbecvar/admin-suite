# Admin suite
Solution for administration and monitoring linux private servers

## ROADMAP
## 0.1
- [X] Setup symfony framework
    - [X] Install dependencies
    - [X] Install webpack
    - [X] Install phpunit
    - [X] Install phpstan
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
- [X] Password reset command
- [X] Banned (with reason) & online status in profile info
- [X] About component (developer, github, version, info, etc..)
- [X] Log manager
    - [X] View unreaded logs
    - [X] Logs table filter
    - [X] Set all readed
    - [X] Set log readed by id
    - [X] User in log table link to user profile
    - [X] Anti log config
    - [X] View logs by user
    - [X] Log table pagination (like users manager)
    - [X] Syslog table
    - [X] Syslog table style
    - [X] Colored data in log table
    - [X] View exception log from monolog
    - [X] Log manager tests
- [X] CLI log reader command

## 0.6
- [X] Server util + tests
- [X] Linux check middleware
- [X] Web app diagnostics
    - [X] Check https (if https only)
    - [X] Check if devmode is enabled
- [X] Server diagnostics component
    - [X] Detect SSD low space
    - [X] CPU overload detect
    - [X] Ram overload detect
    - [X] Check web server permissions
    - [X] Detect requirements (os & php extensions)
- [X] Dashboard warning box (react to diagnostic data component)
- [X] Server info
    - [X] OS info
    - [X] Uptime info
    - [X] CPU usage
    - [X] Ram usage
    - [X] SSD space

## 0.7
- [X] Logs info dashboard card
- [X] User info dashboard card
- [X] Error pages redesign
- [X] Process list dashboard card
- [X] Service manager class
- [X] Service status dashboard card
- [X] Service manager action runner
    - [X] Systemctl status
    - [X] Systemctl stop, start, restart

## 0.8
- [X] Service monitoring component
    - [X] Monitoring loop command (check if all services is online)
    - [X] HTTP service type (check website response code & response time)
    - [X] Monitor system resource usage
    - [X] Monitroing entity for log services state
    - [X] Log offline status
    - [X] Service status change email alert
    - [X] Service monitoring entity datafixtures
    - [X] Monitoring manager unit test
- [X] Service monitoring dashboard
    - [X] Service status card
    - [X] Status log
    - [X] View service tree (form services.json)

## 0.9
- [X] System user (for non loggedin logs & CLI process)
- [X] Todo manager
    - [X] Add todo
    - [X] Todo view
    - [X] Todo entity datafixture
    - [X] Close todo
    - [X] View closed todo list
    - [X] Edit todo
    - [X] Todo data AES encryption
    - [X] Todo manager tests

## 1.0
- [X] Internal improvements
    - [X] Json util (get from file or url)
    - [X] Config suite rework (use config/suite only for example)
    - [X] Create ignored configs in root and use to primary configs
- [ ] UI redesign
    - [X] Email template style
    - [ ] Complete design improvement
    - [ ] Color correction
    - [ ] Responsive design
    - [ ] Test on mobile devices
- [ ] Refactor
    - [ ] Naming
    - [ ] Doc
    - [ ] Code style
- [ ] Complete manual testing
- [ ] Readme doc

## 1.1
- [ ] Database browser
    - [ ] All databases list
    - [ ] Browse tables
    - [ ] Add row
    - [ ] Edit values
    - [ ] Delete rows
    - [ ] Delete all rows
    - [ ] Dump SQL structure
    - [ ] Backup database (download SQL file)

## 1.2
- [ ] Terminal component

## 1.3
- [ ] File browser & editor
    - [ ] List files in filesystem
    - [ ] Rename files
    - [ ] Remove files
    - [ ] Move files
    - [ ] Open files in txt editor

## 1.4
- [ ] Host server shutdown function
- [ ] Internal chat component
- [ ] Presentation doc & download page
- [ ] Docker services support
- [ ] Setup script (for non docker env)

## License
The framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

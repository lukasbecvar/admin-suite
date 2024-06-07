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
    - [X] Base refactor

## 0.2
- [X] User system
- [X] User register & admin role grant command
- [ ] Auth system
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
    - [ ] Auth components style
    - [ ] End-to-end auth tests
- [ ] Base refactor
- [ ] Deep user system test

## 0.3
- [ ] Main dashboard
- [ ] User managment system (only for admin users)
- [ ] User ban system
- [ ] Server info
- [ ] Server diagnostics component
- [ ] Dashboard warning box
- [ ] Check requirements warning

## 0.4
- [ ] Service manager component
- [ ] Service monitoring component

## 0.5
- [ ] Terminal component

## 0.6
- [ ] File browser & editor

## 0.7
- [ ] Database browser

## 0.8
- [ ] Log manager
- [ ] Server diagnostic
- [ ] CLI log reader command

## 0.9
- [ ] Todo manager
- [ ] Internal chat component
- [ ] Account settings component

## 1.0
- [ ] Connecting other services (line contact inbox, visitor systems, etc)

## ROADMAP 1.1
- [ ] About component (developer, github, version, info, etc..)
- [ ] Refactor
- [ ] User manual with config & setup doc

## License
The framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

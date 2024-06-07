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
    - [ ] Register (only if users table is empty)
    - [ ] Login component with remember me
    - [ ] Logout component
    - [ ] Allow login & register route only if user not logged
    - [ ] Login check middleware
    - [ ] End-to-end auth tests
    - [ ] Index redirect to dashboard or login
    - [ ] User -> log one to many

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

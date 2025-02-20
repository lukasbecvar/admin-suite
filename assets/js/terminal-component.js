/** terminal component functionality */
document.addEventListener('DOMContentLoaded', function() {
    // get html element list
    const pathElement = document.getElementById('path')
    const commandInput = document.getElementById('command')
    const usernameElement = document.getElementById('usermame')
    const terminal = document.getElementById('output-container')
    const commandContainer = document.getElementById('command-container')

    // current working directory
    let currentPath = ''

    // current user
    let currentUser = ''

    // api url link
    const api_url = '/api/system/terminal'

    // command history
    let commandHistory = JSON.parse(localStorage.getItem('commandHistory')) || []
    let historyIndex = commandHistory.length

    // focus command input
    commandInput.focus()

    // update cwd on page load
    getCurrentPath()

    // update user on page load
    getCurrentUser()

    // update cwd
    function updatePath() {
        pathElement.textContent = currentPath
    }

    // update user
    function updateUser() {
        usernameElement.textContent = currentUser
    }

    // scroll the bottom
    function scrollToBottom() { 
        terminal.scrollTop = terminal.scrollHeight
    }
    
    // fetch the current cwd from the server
    function getCurrentPath() {
        setTimeout(function() {
            const xhr = new XMLHttpRequest()
            xhr.open('POST', api_url, true)
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    currentPath = xhr.responseText
                    updatePath()
                }
            }
            xhr.send('command=get_current_path_1181517815187484')
        }, 50)
    }

    // fetch the current user from the server
    function getCurrentUser() {
        setTimeout(function() {
            const xhr = new XMLHttpRequest()
            xhr.open('POST', api_url, true)
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    currentUser = xhr.responseText
                    updateUser()
                }
            }
            xhr.send('command=whoami')
        }, 50)
    }

    // event listener for keypress in the command input
    commandInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const command = this.value.trim()
            if (command.length > 0) {

                // display command in the terminal
                terminal.innerHTML += '<div class="command-history-prompt"><span class="text-green-500">' + currentUser + '</span><span class="text-white">:</span><span class="text-blue-600">' + pathElement.textContent + '</span><span class="text-white">$<span class="last-command">' + command + '</span></span></div>'
                
                // save command to history
                commandHistory.push(command)
                localStorage.setItem('commandHistory', JSON.stringify(commandHistory))
                historyIndex = commandHistory.length

                // clear the input
                this.value = ''

                // execute the command
                executeCommand(command)

                // update cwd after command execution
                if (command.startsWith('cd ')) {
                    getCurrentPath()
                }

                // update user after command execution
                getCurrentUser()

                // scroll to the bottom of the terminal
                scrollToBottom()
            }
        // handle command history navigation up
        } else if (e.key === 'ArrowUp') {
            if (historyIndex > 0) {
                historyIndex--
                commandInput.value = commandHistory[historyIndex]
            }
        // handle command history navigation down
        } else if (e.key === 'ArrowDown') {
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++
                commandInput.value = commandHistory[historyIndex]
            } else {
                historyIndex = commandHistory.length
                commandInput.value = ''
            }
        }
    })

    // focus on command input when user starts typing
    document.addEventListener('keydown', function(e) {
        // disable focus when ctrl+c is pressed
        if (e.ctrlKey && e.key.toLowerCase() === 'c') {
            return
        }
        
        if (e.key !== 'Control') {
            commandInput.focus()
        }
    })  
    
    // colorize the output using bash color codes
    function convertBashColors(input) {
        // regex to match the bash color codes
        const regex = /\x1b\[([0-9;]*)m/g
        let output = input
        
        // replace color codes with corresponding HTML styles
        output = output.replace(regex, function(match, code) {
            let color = ''
            switch(code) {
                case '31': color = 'color: red;'; break
                case '32': color = 'color: green;'; break
                case '33': color = 'color: yellow;'; break
                case '34': color = 'color: blue;'; break
                case '0': color = 'color: white;'; break
                case '1': color = 'font-weight: bold;'; break
                default: color = ''; break
            }
            return `<span style='${color}'>`
        })
        
        // reset code
        output = output.replace(/\x1b\[0m/g, '</span>')
        
        return output
    }

    // execute the entered command
    function executeCommand(command) {
        // clear terminal history
        if (command === 'clear' || command === 'cls') {
            terminal.innerHTML = ''
        } else {
            commandContainer.style.display = 'none'

            const xhr = new XMLHttpRequest()
            xhr.open('POST', api_url, true)
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // display command output
                        terminal.innerHTML += convertBashColors('<div>' + xhr.responseText + '</div>')
                        
                        // check if command starts with 'cd '
                        if (command.startsWith('cd ')) {
                            setTimeout(function() {
                                commandContainer.style.display = ''
                                commandInput.focus()
                            }, 300)
                        } else {
                            commandContainer.style.display = ''
                            commandInput.focus()
                        }
                    } else {
                        console.log(xhr.responseText)
                        terminal.innerHTML += '<div class="text-yellow-400">Error communicating with the API.</div>'
                        
                        // focus on the command input
                        commandContainer.style.display = ''
                        commandInput.focus()
                    }
                    
                    // scroll to the bottom of the terminal
                    scrollToBottom()
                }
            }
            
            // execute the command on the server
            xhr.send('command=' + encodeURIComponent(command))
        }
    }

    // convert the first letter of the command to lowercase
    document.getElementById('command').addEventListener('input', function(e) {
        if (this.value.length === 1) {
            this.value = this.value.toLowerCase()
        }
    })
})

/** terminal component functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // ELEMENT DECLARATIONS
    // -----------------------------
    const pathElement = document.getElementById('path')
    const cursorElement = document.createElement('span')
    const commandInput = document.getElementById('command')
    const terminalWrapper = document.getElementById('terminal')
    const usernameElement = document.getElementById('terminal-username')
    const terminal = document.getElementById('output-container')
    const commandContainer = document.getElementById('command-container')
    const promptSeparatorElement = usernameElement ? usernameElement.nextElementSibling : null
    const promptSuffixElement = pathElement ? pathElement.nextElementSibling : null
    cursorElement.className = 'terminal-inline-cursor'
    cursorElement.textContent = '|'
    let scrollHost = null

    if (!terminal || !commandInput) {
        return
    }

    // -----------------------------
    // GLOBAL VARIABLES & CONFIGURATION
    // -----------------------------
    // current working directory
    let currentPath = ''

    // current user
    let currentUser = ''

    // API URL links
    const apiUrl = '/api/system/terminal'
    const asyncApiUrl = '/api/system/terminal/job'
    const asyncStatusBaseUrl = '/api/system/terminal/job/'

    let promptSnapshot = null
    let inlineInputPreview = null

    // command history
    let commandHistory = JSON.parse(localStorage.getItem('commandHistory')) || []
    let historyIndex = commandHistory.length

    // background job state
    let activeJob = null
    let pollTimer = null
    const POLL_INTERVAL = 1200

    // streamed output state
    let partialOutputBuffer = ''
    let partialOutputElement = null
    let lastOutputEndedWithNewline = true

    // -----------------------------
    // TERMINAL UI MANAGEMENT
    // -----------------------------
    // update cwd
    function updatePath() {
        if (!pathElement) {
            return
        }
        if (promptSnapshot !== null) {
            promptSnapshot.path = currentPath
            pathElement.textContent = ''
        } else {
            pathElement.textContent = currentPath
        }
    }

    // update user
    function updateUser() {
        if (!usernameElement) {
            return
        }
        if (promptSnapshot !== null) {
            promptSnapshot.user = currentUser
            usernameElement.textContent = ''
        } else {
            usernameElement.textContent = currentUser
        }
    }

    // resolve scroll host
    function resolveScrollHost() {
        if (scrollHost && scrollHost.isConnected) {
            const cachedStyle = window.getComputedStyle(scrollHost)
            const cachedOverflow = cachedStyle ? cachedStyle.overflowY : ''
            if (
                (cachedOverflow === 'auto' || cachedOverflow === 'scroll' || scrollHost === terminal || scrollHost === terminalWrapper) &&
                scrollHost.scrollHeight - scrollHost.clientHeight > 1
            ) {
                return scrollHost
            }
        }

        let current = terminalWrapper ?? terminal
        const fallback = terminalWrapper ?? terminal

        while (current) {
            const style = window.getComputedStyle(current)
            const overflowY = style ? style.overflowY : ''
            if (
                (overflowY === 'auto' || overflowY === 'scroll' || current === terminal || current === terminalWrapper) &&
                current.scrollHeight - current.clientHeight > 1
            ) {
                scrollHost = current
                return current
            }

            current = current.parentElement
        }

        scrollHost = fallback
        return fallback
    }

    // check if command container is visible
    function isCommandContainerVisible() {
        return !!(commandContainer && commandContainer.style.position !== 'absolute')
    }

    // get scroll target
    function getScrollTarget() {
        if (isCommandContainerVisible()) {
            return commandContainer
        }

        const outputChildren = terminal.children
        if (outputChildren.length > 0) {
            return outputChildren[outputChildren.length - 1]
        }

        return terminal
    }

    // check if element is at bottom
    function isAtBottom(element) {
        if (!element) {
            return false
        }

        return Math.abs(element.scrollHeight - element.clientHeight - element.scrollTop) < 4
    }

    // check if element should stick to bottom
    function shouldStickToBottom() {
        const host = resolveScrollHost()

        if (!host) {
            return true
        }

        return isAtBottom(host)
    }

    // scroll to bottom
    function scrollToBottom(force = true) {
        const host = resolveScrollHost()

        if (!host) {
            return
        }

        if (!force && !isAtBottom(host)) {
            return
        }

        const target = getScrollTarget()

        if (!target) {
            return
        }

        requestAnimationFrame(function() {
            if (typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({
                    block: 'end',
                    inline: 'nearest',
                    behavior: 'auto'
                })
            }

            if (host === document.body || host === document.documentElement) {
                window.scrollTo(0, document.documentElement.scrollHeight || document.body.scrollHeight)
            } else {
                host.scrollTop = host.scrollHeight
            }

            setTimeout(function() {
                if (host === document.body || host === document.documentElement) {
                    window.scrollTo(0, document.documentElement.scrollHeight || document.body.scrollHeight)
                } else {
                    host.scrollTop = host.scrollHeight
                }
            }, 20)
        })
    }

    // fetch the current cwd from the server
    function getCurrentPath() {
        setTimeout(function() {
            const xhr = new XMLHttpRequest()
            xhr.open('POST', apiUrl, true)
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

    // fetch current user from the server
    function getCurrentUser() {
        setTimeout(function() {
            const xhr = new XMLHttpRequest()
            xhr.open('POST', apiUrl, true)
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

    // -----------------------------
    // OUTPUT HANDLING
    // -----------------------------
    // escape HTML
    function escapeHtml(input) {
        return input
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/'/g, '&#39;')
            .replace(/"/g, '&quot;')
    }

    // sanitize chunk for terminal output
    function sanitizeTerminalContent(input) {
        return convertBashColors(escapeHtml(input))
    }

    // ensure partial output element exists
    function ensurePartialOutputElement() {
        if (!partialOutputElement) {
            partialOutputElement = document.createElement('div')
            partialOutputElement.className = 'terminal-output-line terminal-output-partial'
            terminal.appendChild(partialOutputElement)
        }

        return partialOutputElement
    }

    // update the rendered partial output
    function updatePartialOutputElement() {
        const element = ensurePartialOutputElement()
        element.innerHTML = sanitizeTerminalContent(partialOutputBuffer)
    }

    // append an empty visual line
    function appendEmptyLine() {
        const element = document.createElement('div')
        element.className = 'terminal-output-line'
        element.innerHTML = '&nbsp;'
        terminal.appendChild(element)
    }

    // finalize the current partial output
    function finalizePartialOutput() {
        if (partialOutputElement) {
            if (!partialOutputBuffer) {
                terminal.removeChild(partialOutputElement)
            } else {
                partialOutputElement.classList.remove('terminal-output-partial')
                partialOutputElement.innerHTML = partialOutputBuffer ? sanitizeTerminalContent(partialOutputBuffer) : ''
            }
            partialOutputElement = null
            partialOutputBuffer = ''
            return
        }

        if (partialOutputBuffer) {
            const element = document.createElement('div')
            element.className = 'terminal-output-line'
            element.innerHTML = partialOutputBuffer ? sanitizeTerminalContent(partialOutputBuffer) : ''
            terminal.appendChild(element)
            partialOutputBuffer = ''
        }
    }

    // flush the partial output if present
    function flushPartialOutput() {
        finalizePartialOutput()
        lastOutputEndedWithNewline = true
    }

    // append output chunk to terminal
    function appendOutputChunk(chunk) {
        if (!chunk) {
            return
        }

        const stickToBottom = shouldStickToBottom()
        resetInlinePreview()
        const pending = terminal.getElementsByClassName('terminal-output-awaiting-input')
        while (pending.length > 0) {
            pending[0].classList.remove('terminal-output-awaiting-input')
        }

        let processedChunk = chunk

        if (activeJob && typeof activeJob.expectedEcho === 'string') {
            const normalized = processedChunk.replace(/\r/g, '')
            const expected = activeJob.expectedEcho
            const expectedWithNewline = expected + '\n'

            if (normalized === expected || normalized === expectedWithNewline) {
                activeJob.expectedEcho = null
                return
            }

            if (normalized.startsWith(expectedWithNewline)) {
                processedChunk = normalized.slice(expectedWithNewline.length)
            } else if (normalized.startsWith(expected)) {
                const remainder = normalized.slice(expected.length)
                if (remainder.startsWith('\n')) {
                    processedChunk = remainder.slice(1)
                } else {
                    processedChunk = remainder
                }
            }

            activeJob.expectedEcho = null
        }

        if (!processedChunk) {
            return
        }

        const normalized = processedChunk.replace(/\r\n/g, '\n').replace(/\x1b\[[0-9;?]*K/g, '')

        for (let i = 0; i < normalized.length; i++) {
            const char = normalized[i]

            if (char === '\r') {
                partialOutputBuffer = ''
                if (partialOutputElement) {
                    partialOutputElement.innerHTML = ''
                }
                lastOutputEndedWithNewline = false
                continue
            }

            if (char === '\n') {
                const hadPendingNewline = lastOutputEndedWithNewline
                finalizePartialOutput()
                if (hadPendingNewline) {
                    appendEmptyLine()
                }
                lastOutputEndedWithNewline = true
                continue
            }

            partialOutputBuffer += char
            updatePartialOutputElement()
            lastOutputEndedWithNewline = false
        }

        if (activeJob) {
            handlePotentialPrompt(processedChunk)
        }
        scrollToBottom(stickToBottom)
    }

    // append message to terminal
    function appendMessage(message, style = 'text-yellow-400') {
        const stickToBottom = shouldStickToBottom()
        flushPartialOutput()
        const wrapper = document.createElement('div')
        wrapper.className = style
        wrapper.innerHTML = escapeHtml(message)
        terminal.appendChild(wrapper)
        lastOutputEndedWithNewline = true
        scrollToBottom(stickToBottom)
    }

    // colorize the output using bash color codes
    function convertBashColors(input) {
        // regex to match the bash color codes
        const regex = /\x1b\[([0-9;]*)m/g
        let output = input

        // replace color codes with corresponding HTML styles
        output = output.replace(regex, function(match, code) {
            let color = ''
            switch (code) {
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

    // -----------------------------
    // COMMAND INPUT & JOB MANAGEMENT
    // -----------------------------
    // disable command input
    function setInputDisabled(disabled) {
        commandInput.disabled = disabled
        if (!disabled) {
            commandInput.focus()
        }
    }

    // hide command container
    function hideCommandContainer() {
        if (!commandContainer) {
            return
        }
        if (!commandContainer.dataset.prevPosition) {
            commandContainer.dataset.prevPosition = commandContainer.style.position || ''
            commandContainer.dataset.prevLeft = commandContainer.style.left || ''
            commandContainer.dataset.prevTop = commandContainer.style.top || ''
            commandContainer.dataset.prevRight = commandContainer.style.right || ''
            commandContainer.dataset.prevBottom = commandContainer.style.bottom || ''
        }
        commandContainer.style.position = 'absolute'
        commandContainer.style.left = '-9999px'
    }

    // show command container
    function showCommandContainer() {
        if (!commandContainer) {
            return
        }
        commandContainer.style.position = commandContainer.dataset.prevPosition ?? ''
        commandContainer.style.left = commandContainer.dataset.prevLeft ?? ''
        commandContainer.style.top = commandContainer.dataset.prevTop ?? ''
        commandContainer.style.right = commandContainer.dataset.prevRight ?? ''
        commandContainer.style.bottom = commandContainer.dataset.prevBottom ?? ''
    }

    // reset inline preview
    function resetInlinePreview(removeNode = true) {
        if (!inlineInputPreview) {
            if (cursorElement.parentNode) {
                cursorElement.parentNode.removeChild(cursorElement)
            }
            return
        }
        const host = inlineInputPreview.parentElement
        if (host && host.classList) {
            host.classList.remove('terminal-output-awaiting-input')
        }
        if (removeNode && inlineInputPreview.parentNode) {
            inlineInputPreview.parentNode.removeChild(inlineInputPreview)
        }
        inlineInputPreview = null
        if (cursorElement.parentNode) {
            cursorElement.parentNode.removeChild(cursorElement)
        }
    }

    // ensure inline input preview
    function ensureInlineInputPreview() {
        const outputLines = terminal.getElementsByClassName('terminal-output-line')
        let host = outputLines.length > 0 ? outputLines[outputLines.length - 1] : null

        if (!host) {
            const prompts = terminal.getElementsByClassName('command-history-prompt')
            host = prompts.length > 0 ? prompts[prompts.length - 1] : null
        }

        if (!host) {
            return null
        }

        inlineInputPreview = document.createElement('span')
        inlineInputPreview.className = 'interactive-input-preview text-gray-200'

        const hostText = host.textContent ?? ''
        if (!hostText.endsWith(' ')) {
            host.appendChild(document.createTextNode(' '))
        }

        host.appendChild(inlineInputPreview)

        if (cursorElement.parentNode !== host) {
            if (inlineInputPreview.nextSibling) {
                host.insertBefore(cursorElement, inlineInputPreview.nextSibling)
            } else {
                host.appendChild(cursorElement)
            }
        }

        host.classList.add('terminal-output-awaiting-input')

        return inlineInputPreview
    }

    // update inline preview value
    function updateInlinePreviewValue(value) {
        if (!activeJob || !activeJob.awaitingInput) {
            return
        }

        const preview = ensureInlineInputPreview()

        if (!preview) {
            return
        }

        preview.textContent = value ?? ''
    }

    // set prompt visibility
    function setPromptVisibility(visible) {
        if (visible) {
            if (promptSnapshot !== null) {
                if (usernameElement) {
                    usernameElement.textContent = promptSnapshot.user ?? ''
                }
                if (pathElement) {
                    pathElement.textContent = promptSnapshot.path ?? ''
                }
                promptSnapshot = null
            }
            if (commandContainer) {
                commandContainer.classList.remove('terminal-interactive-mode')
            }
            showCommandContainer()
            if (promptSeparatorElement) {
                promptSeparatorElement.style.visibility = ''
            }
            if (promptSuffixElement) {
                promptSuffixElement.style.visibility = ''
            }
        } else {
            if (promptSnapshot === null) {
                promptSnapshot = {
                    user: usernameElement ? usernameElement.textContent : '',
                    path: pathElement ? pathElement.textContent : ''
                }
            }
            if (usernameElement) {
                usernameElement.textContent = ''
            }
            if (pathElement) {
                pathElement.textContent = ''
            }
            if (commandContainer) {
                commandContainer.classList.add('terminal-interactive-mode')
            }
            hideCommandContainer()
            if (promptSeparatorElement) {
                promptSeparatorElement.style.visibility = 'hidden'
            }
            if (promptSuffixElement) {
                promptSuffixElement.style.visibility = 'hidden'
            }
        }
        if (cursorElement.parentNode && visible) {
            cursorElement.parentNode.removeChild(cursorElement)
        }
    }

    // check if chunk contains prompt
    function chunkContainsPrompt(chunk) {
        const promptPatterns = [
            /\x5By\/N\x5D/,
            /\x5By\/n\x5D/i,
            /\(y\/n\)/i,
            /\(yes\/no\)/i,
            /confirm/i,
            /username:/i,
            /password:/i,
            /enter\s+password/i,
            /press.+enter to continue/i,
            /do you want to continue\?/i
        ]

        for (const pattern of promptPatterns) {
            if (pattern.test(chunk)) {
                return true
            }
        }

        const lines = chunk.split(/\r?\n/).filter(line => line.trim().length > 0)

        if (lines.length === 0) {
            return false
        }

        const lastLine = lines[lines.length - 1].trim()

        if (lastLine.length <= 140) {
            if (/[?:]$/.test(lastLine)) {
                return true
            }

            if (/\x5B[^\x5D]*(?:y\/n|yes\/no)[^\x5D]*\x5D$/i.test(lastLine)) {
                return true
            }
        }

        const normalizedChunk = chunk.replace(/\r/g, '')
        const rawLines = normalizedChunk.split('\n')

        for (let i = rawLines.length - 1; i >= 0; i--) {
            const rawLine = rawLines[i]

            if (!rawLine) {
                continue
            }

            const trimmedRight = rawLine.replace(/\s+$/, '')

            if (!trimmedRight) {
                continue
            }

            const trimmed = trimmedRight.trim()

            if (!trimmed) {
                continue
            }

            const hasTrailingSpace = rawLine.length !== trimmedRight.length

            if (trimmed.length <= 80) {
                if (trimmed === '>>>' || trimmed === '...') {
                    return true
                }

                if (hasTrailingSpace) {
                    if (!/\s/.test(trimmed) && /[#$%>]$/.test(trimmed)) {
                        return true
                    }

                    if (/^\([^()\s]{1,30}\)$/.test(trimmed)) {
                        return true
                    }
                }
            }

            break
        }

        return false
    }

    // handle potential prompt in chunk
    function handlePotentialPrompt(chunk) {
        if (!activeJob) {
            return
        }

        if (chunkContainsPrompt(chunk)) {
            if (!activeJob.awaitingInput) {
                activeJob.awaitingInput = true
            }
            ensureInlineInputPreview()
            updateInlinePreviewValue(commandInput.value)
            commandInput.focus()
        }
    }

    // complete command execution
    function onCommandComplete() {
        getCurrentPath()
        getCurrentUser()
    }

    // execute the entered command
    function executeCommand(command) {
        if (activeJob) {
            appendMessage('A command is already running. Use Ctrl+C to stop it before starting a new one.', 'text-blue-300')
            return
        }

        if (command === 'clear' || command === 'cls') {
            terminal.innerHTML = ''
            partialOutputBuffer = ''
            partialOutputElement = null
            lastOutputEndedWithNewline = true
            return
        }

        if (command.startsWith('cd ') || command.startsWith('su ')) {
            executeImmediateCommand(command)
            return
        }

        startBackgroundCommand(command)
    }

    // execute immediate command
    function executeImmediateCommand(command) {
        setInputDisabled(true)
        setPromptVisibility(false)

        const xhr = new XMLHttpRequest()
        xhr.open('POST', apiUrl, true)
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    appendOutputChunk(xhr.responseText)
                } else {
                    appendMessage('Error communicating with the API.')
                }

                setInputDisabled(false)
                setPromptVisibility(true)
                onCommandComplete()
                requestAnimationFrame(function() {
                    scrollToBottom(shouldStickToBottom())
                })
            }
        }
        xhr.send('command=' + encodeURIComponent(command))
    }

    // start background command
    function startBackgroundCommand(command) {
        setInputDisabled(true)
        setPromptVisibility(false)

        fetch(asyncApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'command=' + encodeURIComponent(command)
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'blocked' || data.status === 'error') {
                    appendMessage(data.message ?? 'Unable to execute command.', 'text-blue-300')
                    finishBackgroundCommand()
                    return
                }

                if (data.status !== 'running') {
                    appendMessage('Command completed with unknown status.')
                    finishBackgroundCommand()
                    return
                }

                activeJob = {
                    id: data.jobId,
                    offset: data.offset || 0,
                    command: command,
                    awaitingInput: false
                }

                setInputDisabled(false)

                if (data.chunk) {
                    appendOutputChunk(data.chunk)
                    activeJob.offset = data.offset
                }

                scheduleNextPoll()
            })
            .catch(() => {
                appendMessage('Error communicating with the API.')
                finishBackgroundCommand()
            })
    }

    // schedule next poll
    function scheduleNextPoll() {
        if (!activeJob) {
            return
        }

        pollTimer = setTimeout(pollBackgroundCommand, POLL_INTERVAL)
    }

    // poll background command
    function pollBackgroundCommand() {
        if (!activeJob) {
            return
        }

        const url = `${asyncStatusBaseUrl}?jobId=${activeJob.id}&offset=${activeJob.offset}`

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!activeJob) {
                    return
                }

                if (data.chunk) {
                    appendOutputChunk(data.chunk)
                }

                if (typeof data.offset === 'number') {
                    activeJob.offset = data.offset
                }

                if (data.status === 'running') {
                    scheduleNextPoll()
                } else {
                    finishBackgroundCommand()
                    if (data.exitCode !== undefined && data.exitCode !== null && data.exitCode !== 0) {
                        appendMessage('Command exited with status code: ' + data.exitCode, 'text-red-400')
                    }
                }
            })
            .catch(() => {
                appendMessage('Error communicating with the API.')
                finishBackgroundCommand()
            })
    }

    // stop background command
    function stopBackgroundCommand() {
        if (!activeJob) {
            return
        }

        fetch(`${asyncStatusBaseUrl}stop?jobId=${activeJob.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).finally(() => {
            resetInlinePreview()
            appendMessage('^C', 'text-gray-400')
            finishBackgroundCommand()
        })
    }

    // finish background command
    function finishBackgroundCommand() {
        if (pollTimer) {
            clearTimeout(pollTimer)
            pollTimer = null
        }

        resetInlinePreview()
        activeJob = null
        setPromptVisibility(true)
        setInputDisabled(false)
        onCommandComplete()
        requestAnimationFrame(function() {
            scrollToBottom(shouldStickToBottom())
        })
    }

    // append user input to terminal
    function appendUserInput(value) {
        const stickToBottom = shouldStickToBottom()
        const normalized = (value ?? '').replace(/\r/g, '')
        const displayValue = normalized

        const outputLines = terminal.getElementsByClassName('terminal-output-line')
        const lastOutputLine = outputLines.length > 0 ? outputLines[outputLines.length - 1] : null

        if (inlineInputPreview) {
            const host = inlineInputPreview.parentElement
            if (host) {
                host.classList.remove('terminal-output-awaiting-input')
                if (cursorElement.parentNode === host) {
                    host.removeChild(cursorElement)
                }
                host.removeChild(inlineInputPreview)
                host.appendChild(document.createTextNode(displayValue))
            }
            inlineInputPreview = null
            lastOutputEndedWithNewline = false
            scrollToBottom(stickToBottom)
            return
        }

        if (lastOutputLine && !lastOutputLine.classList.contains('command-history-prompt')) {
            if (!lastOutputLine.textContent.endsWith(' ')) {
                lastOutputLine.appendChild(document.createTextNode(' '))
            }
            lastOutputLine.appendChild(document.createTextNode(displayValue))
            if (cursorElement.parentNode === lastOutputLine) {
                lastOutputLine.removeChild(cursorElement)
            }
        } else {
            const prompts = terminal.getElementsByClassName('command-history-prompt')
            const lastPrompt = prompts.length > 0 ? prompts[prompts.length - 1] : null

            if (lastPrompt) {
                let inlineContainer = lastPrompt.querySelector('.command-inline-input')

                if (!inlineContainer) {
                    inlineContainer = document.createElement('span')
                    inlineContainer.className = 'command-inline-input text-gray-300'
                    lastPrompt.appendChild(inlineContainer)
                }

                if (!inlineContainer.textContent.endsWith(' ')) {
                    inlineContainer.appendChild(document.createTextNode(' '))
                }

                inlineContainer.appendChild(document.createTextNode(displayValue))
                if (cursorElement.parentNode === inlineContainer) {
                    inlineContainer.removeChild(cursorElement)
                }
            } else {
                const fallback = document.createElement('div')
                fallback.className = 'text-gray-300'
                fallback.appendChild(document.createTextNode(displayValue))
                terminal.appendChild(fallback)
            }
        }
        scrollToBottom(stickToBottom)
        lastOutputEndedWithNewline = false
    }

    // send input to active job
    function sendInputToActiveJob(value) {
        if (!activeJob) {
            return
        }

        const payload = value ?? ''

        if (activeJob) {
            activeJob.expectedEcho = payload.replace(/\r/g, '')
        }

        fetch(`${asyncStatusBaseUrl}input?jobId=${activeJob.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'input=' + encodeURIComponent(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'ok') {
                    appendMessage(data.message ?? 'Failed to send input to the command.', 'text-red-400')
                    return
                }

                appendUserInput(value)
                activeJob.awaitingInput = false
            })
            .catch(() => {
                if (activeJob) {
                    activeJob.expectedEcho = null
                }
                appendMessage('Error sending input to the command.', 'text-red-400')
            })
    }

    // -----------------------------
    // EVENT LISTENERS
    // -----------------------------
    // event listener for keypress in the command input
    commandInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault()

            if (activeJob) {
                const inputValue = this.value
                sendInputToActiveJob(inputValue)
                this.value = ''
                return
            }

            const command = this.value.trim()
            if (command.length > 0) {
                const stickToBottom = shouldStickToBottom()
                flushPartialOutput()
                terminal.insertAdjacentHTML(
                    'beforeend',
                    '<div class="command-history-prompt"><span class="text-green-500">' + currentUser + '</span><span class="text-white">:</span><span class="text-blue-600">' + pathElement.textContent + '</span><span class="text-white">$<span class="last-command">' + escapeHtml(command) + '</span></span><span class="command-inline-input text-gray-300"></span></div>'
                )
                lastOutputEndedWithNewline = true

                commandHistory.push(command)
                localStorage.setItem('commandHistory', JSON.stringify(commandHistory))
                historyIndex = commandHistory.length

                this.value = ''
                executeCommand(command)
                scrollToBottom(stickToBottom)
            }
        } else if (e.key === 'ArrowUp') {
            if (activeJob) {
                e.preventDefault()
                return
            }
            if (historyIndex > 0) {
                historyIndex--
                commandInput.value = commandHistory[historyIndex]
            }
        } else if (e.key === 'ArrowDown') {
            if (activeJob) {
                e.preventDefault()
                return
            }
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++
                commandInput.value = commandHistory[historyIndex]
            } else {
                historyIndex = commandHistory.length
                commandInput.value = ''
            }
        }
    })

    // focus on command input & handle ctrl+c
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key.toLowerCase() === 'c') {
            if (activeJob) {
                e.preventDefault()
                activeJob.awaitingInput = false
                stopBackgroundCommand()
                return
            }
        }

        if (e.key !== 'Control') {
            commandInput.focus()
        }
    })

    // convert the first letter of the command to lowercase
    commandInput.addEventListener('input', function() {
        if (activeJob && activeJob.awaitingInput) {
            updateInlinePreviewValue(this.value)
            return
        }
        if (activeJob) {
            return
        }
        if (this.value.length === 1) {
            this.value = this.value.toLowerCase()
        }
    })

    // -----------------------------
    // INITIALIZATION
    // -----------------------------
    // focus command input
    commandInput.focus()

    // update cwd on page load
    getCurrentPath()

    // update user on page load
    getCurrentUser()
})

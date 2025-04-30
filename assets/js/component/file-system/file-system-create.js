/** file-system create component functionality */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form')
    const editor = document.getElementById('editor')
    const errorContainer = document.createElement('div')
    const filenameInput = document.getElementById('filename')

    // create error container
    errorContainer.className = 'mt-2'
    filenameInput.parentNode.appendChild(errorContainer)

    // create slash error message
    const slashErrorMessage = document.createElement('p')
    slashErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    slashErrorMessage.textContent = 'Filename cannot contain path separators (/)'
    errorContainer.appendChild(slashErrorMessage)

    // create length error message
    const lengthErrorMessage = document.createElement('p')
    lengthErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    lengthErrorMessage.textContent = 'Filename must be between 1 and 255 characters'
    errorContainer.appendChild(lengthErrorMessage)

    // create empty error message
    const emptyErrorMessage = document.createElement('p')
    emptyErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    emptyErrorMessage.textContent = 'Filename cannot be empty'
    errorContainer.appendChild(emptyErrorMessage)

    // validate filename
    function validateFilename() {
        const filename = filenameInput.value.trim()
        let isValid = true

        // reset all error states
        slashErrorMessage.classList.add('hidden')
        lengthErrorMessage.classList.add('hidden')
        emptyErrorMessage.classList.add('hidden')
        filenameInput.classList.remove('border-red-500')

        // check if filename is empty
        if (filename === '') {
            emptyErrorMessage.classList.remove('hidden')
            filenameInput.classList.add('border-red-500')
            isValid = false
        }

        // check if filename contains slashes
        if (filename.includes('/')) {
            slashErrorMessage.classList.remove('hidden')
            filenameInput.classList.add('border-red-500')
            isValid = false
        }

        // check filename length (max 255 characters)
        if (filename.length > 255) {
            lengthErrorMessage.classList.remove('hidden')
            filenameInput.classList.add('border-red-500')
            isValid = false
        }

        return isValid
    }

    // check for slashes on input
    filenameInput.addEventListener('input', validateFilename)

    // prevent form submission if filename contains slashes
    form.addEventListener('submit', function(e) {
        if (!validateFilename()) {
            e.preventDefault()
        }
    })

    // enable tab key in textarea
    editor.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault()

            // get cursor position
            const start = this.selectionStart
            const end = this.selectionEnd

            // insert tab at cursor position
            this.value = this.value.substring(0, start) + '    ' + this.value.substring(end)

            // move cursor after tab
            this.selectionStart = this.selectionEnd = start + 4
        }
    })

    // ctrl+s to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault()
            if (validateFilename()) {
                form.submit()
            }
        }
    })

    // focus filename input
    filenameInput.focus()
})

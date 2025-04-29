/** file-system create component functionality */
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor')
    const filenameInput = document.getElementById('filename')
    const form = document.querySelector('form')
    const errorMessage = document.createElement('p')

    // error messages
    errorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    errorMessage.textContent = 'Filename cannot contain path separators (/)'
    filenameInput.parentNode.appendChild(errorMessage)

    // validate filename does not contain slashes
    function validateFilename() {
        const filename = filenameInput.value
        const isValid = !filename.includes('/')

        if (!isValid) {
            errorMessage.classList.remove('hidden')
            filenameInput.classList.add('border', 'border-red-500')
        } else {
            errorMessage.classList.add('hidden')
            filenameInput.classList.remove('border', 'border-red-500')
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

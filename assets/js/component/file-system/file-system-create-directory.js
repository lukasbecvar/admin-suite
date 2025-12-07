/** file-system create directory functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // ELEMENT DECLARATIONS
    // -----------------------------
    const directoryNameInput = document.getElementById('directoryname')
    const errorContainer = document.createElement('div')
    const form = directoryNameInput.closest('form')

    // create error container
    errorContainer.className = 'mt-2'
    directoryNameInput.parentNode.appendChild(errorContainer)

    // create slash error message
    const slashErrorMessage = document.createElement('p')
    slashErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    slashErrorMessage.textContent = 'Directory name cannot contain path separators (/)'
    errorContainer.appendChild(slashErrorMessage)

    // create length error message
    const lengthErrorMessage = document.createElement('p')
    lengthErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    lengthErrorMessage.textContent = 'Directory name must be between 1 and 255 characters'
    errorContainer.appendChild(lengthErrorMessage)

    // create empty error message
    const emptyErrorMessage = document.createElement('p')
    emptyErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    emptyErrorMessage.textContent = 'Directory name cannot be empty'
    errorContainer.appendChild(emptyErrorMessage)

    // -----------------------------
    // UTILITY FUNCTIONS
    // -----------------------------
    // validate directory name
    function validateDirectoryName() {
        const directoryName = directoryNameInput.value.trim()
        let isValid = true

        // reset all error states
        slashErrorMessage.classList.add('hidden')
        lengthErrorMessage.classList.add('hidden')
        emptyErrorMessage.classList.add('hidden')
        directoryNameInput.classList.remove('border-red-500')

        // check if directory name is empty
        if (directoryName === '') {
            emptyErrorMessage.classList.remove('hidden')
            directoryNameInput.classList.add('border-red-500')
            isValid = false
        }

        // check if directory name contains slashes
        if (directoryName.includes('/')) {
            slashErrorMessage.classList.remove('hidden')
            directoryNameInput.classList.add('border-red-500')
            isValid = false
        }

        // check directory name length (max 255 characters)
        if (directoryName.length > 255) {
            lengthErrorMessage.classList.remove('hidden')
            directoryNameInput.classList.add('border-red-500')
            isValid = false
        }

        return isValid
    }

    // -----------------------------
    // EVENT LISTENERS
    // -----------------------------
    // check for slashes on input
    directoryNameInput.addEventListener('input', validateDirectoryName)

    // prevent form submission if directory name contains slashes
    form.addEventListener('submit', function(e) {
        if (!validateDirectoryName()) {
            e.preventDefault()
        }
    })

    // strl+s to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault()
            if (validateDirectoryName()) {
                form.submit()
            }
        }
    })

    // enter in directory name field submits the form
    directoryNameInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault()
            if (validateDirectoryName()) {
                form.submit()
            }
        }
    })

    // -----------------------------
    // INITIALIZATION
    // -----------------------------
    // focus on directory name input
    directoryNameInput.focus()
})

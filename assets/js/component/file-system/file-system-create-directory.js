/** file-system create directory functionality */
document.addEventListener('DOMContentLoaded', function() {
    const directoryNameInput = document.getElementById('directoryname')
    const form = document.querySelector('form')
    const errorMessage = document.createElement('p')

    // error messages
    errorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    errorMessage.textContent = 'Directory name cannot contain path separators (/)'
    directoryNameInput.parentNode.appendChild(errorMessage)

    // validate directory name doesn't contain slashes
    function validateDirectoryName() {
        const directoryName = directoryNameInput.value
        const isValid = !directoryName.includes('/')

        if (!isValid) {
            errorMessage.classList.remove('hidden')
            directoryNameInput.classList.add('border-red-500')
        } else {
            errorMessage.classList.add('hidden')
            directoryNameInput.classList.remove('border-red-500')
        }

        return isValid
    }

    // check for slashes on input
    directoryNameInput.addEventListener('input', validateDirectoryName)

    // prevent form submission if directory name contains slashes
    form.addEventListener('submit', function(e) {
        if (!validateDirectoryName()) {
            e.preventDefault()
        }
    })

    // focus on directory name input
    directoryNameInput.focus()

    // ctrl+s to save
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
})

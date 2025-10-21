/** file-system rename functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // new name input field
    const newNameInput = document.getElementById('newName')
    const form = document.querySelector('form')
    const originalName = newNameInput.value

    // create error messages container
    const errorContainer = document.createElement('div')
    errorContainer.className = 'mt-2'
    newNameInput.parentNode.appendChild(errorContainer)

    // slash detect error messages
    const slashErrorMessage = document.createElement('p')
    slashErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    slashErrorMessage.textContent = 'Name cannot contain path separators (/)'
    errorContainer.appendChild(slashErrorMessage)

    // unchanged error messages
    const unchangedErrorMessage = document.createElement('p')
    unchangedErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    unchangedErrorMessage.textContent = 'New name must be different from the current name'
    errorContainer.appendChild(unchangedErrorMessage)

    // empty error messages
    const emptyErrorMessage = document.createElement('p')
    emptyErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    emptyErrorMessage.textContent = 'Name cannot be empty'
    errorContainer.appendChild(emptyErrorMessage)

    // length error messages
    const lengthErrorMessage = document.createElement('p')
    lengthErrorMessage.className = 'text-red-500 text-xs mt-1 hidden'
    lengthErrorMessage.textContent = 'Name must be between 1 and 255 characters'
    errorContainer.appendChild(lengthErrorMessage)

    // validate name
    function validateNewName() {
        const newName = newNameInput.value.trim()

        // reset all error states
        slashErrorMessage.classList.add('hidden')
        unchangedErrorMessage.classList.add('hidden')
        emptyErrorMessage.classList.add('hidden')
        lengthErrorMessage.classList.add('hidden')
        newNameInput.classList.remove('border-red-500')

        // check if name is empty
        if (newName === '') {
            emptyErrorMessage.classList.remove('hidden')
            newNameInput.classList.add('border-red-500')
            return false
        }

        // check if name contains slashes
        if (newName.includes('/')) {
            slashErrorMessage.classList.remove('hidden')
            newNameInput.classList.add('border-red-500')
            return false
        }

        // check if name is unchanged
        if (newName === originalName) {
            unchangedErrorMessage.classList.remove('hidden')
            newNameInput.classList.add('border-red-500')
            return false
        }

        // check name length (max 255 characters)
        if (newName.length > 255) {
            lengthErrorMessage.classList.remove('hidden')
            newNameInput.classList.add('border-red-500')
            return false
        }

        return true
    }

    // check validation on input
    newNameInput.addEventListener('input', validateNewName)

    // prevent form submission if validation fails
    form.addEventListener('submit', function(e) {
        if (!validateNewName()) {
            e.preventDefault()
        }
    })

    // select text in input field
    newNameInput.select()

    // ctrl+s to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault()
            if (validateNewName()) {
                form.submit()
            }
        }
    })

    // enter in new name field submits the form
    newNameInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault()
            if (validateNewName()) {
                form.submit()
            }
        }
    })
})

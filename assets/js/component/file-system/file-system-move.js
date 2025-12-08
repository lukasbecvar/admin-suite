/** file-system move functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // ELEMENT DECLARATIONS
    // -----------------------------
    // get form elements
    const customPathError = document.getElementById('customPathError')
    const form = customPathError.closest('form')
    const sourcePath = document.querySelector('input[name="sourcePath"]').value
    const customDestinationPath = document.getElementById('customDestinationPath')

    // get directory part of the source path
    const sourceDir = sourcePath.lastIndexOf('/') > 0 ? sourcePath.substring(0, sourcePath.lastIndexOf('/')) : '/'

    // -----------------------------
    // VALIDATION FUNCTIONS
    // -----------------------------
    // validate custom path
    function validateCustomPath() {
        const path = customDestinationPath.value.trim()

        // reset error state
        customPathError.classList.add('hidden')
        customDestinationPath.classList.remove('border-red-500')

        // check if path is empty
        if (path === '') {
            customPathError.textContent = 'Path cannot be empty'
            customPathError.classList.remove('hidden')
            customDestinationPath.classList.add('border-red-500')
            return false
        }

        // check if path starts with /
        if (!path.startsWith('/')) {
            customPathError.textContent = 'Path must start with /'
            customPathError.classList.remove('hidden')
            customDestinationPath.classList.add('border-red-500')
            return false
        }

        // check if path exists (this will be validated on the server side as well)
        return true
    }

    // validate destination path
    function validateDestination() {
        if (!validateCustomPath()) {
            return false
        }

        const destinationPath = customDestinationPath.value.trim()

        if (sourcePath !== '/' && destinationPath.startsWith(sourcePath + '/')) {
            alert('Cannot move a directory into its own subdirectory')
            return false
        }

        if (destinationPath === sourceDir) {
            alert('The destination folder is the same as the current location. Please select a different folder.')
            return false
        }

        if (sourcePath === '/' && destinationPath === '/') {
            alert('Cannot move the root directory to itself')
            return false
        }

        return true
    }

    // -----------------------------
    // EVENT LISTENERS
    // -----------------------------
    // add validation for custom path input
    customDestinationPath.addEventListener('input', validateCustomPath)

    // prevent form submission if validation fails
    form.addEventListener('submit', function(e) {
        if (!validateDestination()) {
            e.preventDefault()
        }
    })

    // ctrl+s to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault()
            if (validateDestination()) {
                form.submit()
            }
        }
    })
})

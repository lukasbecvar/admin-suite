/** file-system move functionality */
document.addEventListener('DOMContentLoaded', function()
{
    // get form elements
    const form = document.querySelector('form')
    const customPathError = document.getElementById('customPathError')
    const destinationPathSelect = document.getElementById('destinationPath')
    const selectPathContainer = document.getElementById('selectPathContainer')
    const customPathContainer = document.getElementById('customPathContainer')
    const sourcePath = document.querySelector('input[name="sourcePath"]').value
    const customDestinationPath = document.getElementById('customDestinationPath')
    const destinationTypeRadios = document.querySelectorAll('input[name="destinationPathType"]')

    // get directory part of the source path
    const sourceDir = sourcePath.lastIndexOf('/') > 0 ? sourcePath.substring(0, sourcePath.lastIndexOf('/')) : '/'

    // toggle between select and custom path inputs
    destinationTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'select') {
                selectPathContainer.classList.remove('hidden')
                customPathContainer.classList.add('hidden')
            } else {
                selectPathContainer.classList.add('hidden')
                customPathContainer.classList.remove('hidden')
            }
        })
    })

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

    // add validation for custom path input
    customDestinationPath.addEventListener('input', validateCustomPath)

    // validate destination path
    function validateDestination() {
        // check which type of destination is selected
        const isCustomPath = document.querySelector('input[name="destinationPathType"]:checked').value === 'custom'

        if (isCustomPath) {
            // validate custom path
            if (!validateCustomPath()) {
                return false
            }

            const destinationPath = customDestinationPath.value.trim()

            // check if destination is a subdirectory of the source (for directories)
            if (sourcePath !== '/' && destinationPath.startsWith(sourcePath + '/')) {
                alert('Cannot move a directory into its own subdirectory')
                return false
            }

            // check if destination is the same as the source directory
            if (destinationPath === sourceDir) {
                alert('The destination folder is the same as the current location. Please select a different folder.')
                return false
            }

            // special case for root directory
            if (sourcePath === '/' && destinationPath === '/') {
                alert('Cannot move the root directory to itself')
                return false
            }
        } else {
            // validate selected path
            const destinationPath = destinationPathSelect.value

            // check if destination is a subdirectory of the source (for directories)
            if (sourcePath !== '/' && destinationPath.startsWith(sourcePath + '/')) {
                alert('Cannot move a directory into its own subdirectory')
                return false
            }

            // check if destination is the same as the source directory
            if (destinationPath === sourceDir) {
                alert('The destination folder is the same as the current location. Please select a different folder.')
                return false
            }

            // special case for root directory
            if (sourcePath === '/' && destinationPath === '/') {
                alert('Cannot move the root directory to itself')
                return false
            }
        }

        return true
    }

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

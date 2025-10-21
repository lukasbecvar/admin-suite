/** file-system view component functionality */
document.addEventListener('DOMContentLoaded', function()
{
    const cancelButton = document.getElementById('cancel-delete')
    const deleteModal = document.getElementById('delete-file-modal')
    const deletePathInput = document.getElementById('delete-file-path')
    const deleteButtons = document.querySelectorAll('.delete-file-button')
    const deleteConfirmationText = document.getElementById('delete-confirmation-text')

    // show modal when delete button is clicked
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const path = this.getAttribute('data-path')
            const isDir = this.getAttribute('data-is-dir') === 'true'

            // set path in form
            deletePathInput.value = path

            // update confirmation text based on item type
            if (isDir) {
                deleteConfirmationText.textContent = 'Are you sure you want to delete this directory? All files and subdirectories will be permanently deleted.'
            } else {
                deleteConfirmationText.textContent = 'Are you sure you want to delete this file?'
            }

            // show modal
            deleteModal.classList.remove('hidden')
        })
    })

    // hide modal when cancel button is clicked
    cancelButton.addEventListener('click', function() {
        deleteModal.classList.add('hidden')
    })

    // hide modal when clicking outside
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden')
        }
    })

    // prevent form submission when pressing Enter in the modal
    deleteModal.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault()
        }
    })

    // close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
            deleteModal.classList.add('hidden')
        }
    })
})

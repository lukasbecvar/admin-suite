/** service exception file delete confirmation popup functionality */
document.addEventListener('DOMContentLoaded', function()
{
    let formToSubmit = null
    
    // get view elements
    const deletePopup = document.getElementById('deletePopup')
    const deleteButtons = document.querySelectorAll('.delete-button')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')

    // handle delete confirmation popup show
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            formToSubmit = this.dataset.formId
            deletePopup.classList.remove('hidden')
        })
    })

    // handle cancel button click
    cancelDeleteButton.addEventListener('click', function () {
        deletePopup.classList.add('hidden')
        formToSubmit = null
    })

    // handle escape key press
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (!deletePopup.classList.contains('hidden')) {
                deletePopup.classList.add('hidden')
                formToSubmit = null
            }
        }
    })

    // handle click outside popup
    deletePopup.addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
            formToSubmit = null
        }
    })

    // POST submit when confirmed
    confirmDeleteButton.addEventListener('click', function () {
        if (formToSubmit) {
            document.getElementById(formToSubmit).submit()
        }
    })
})

/** delete confirmation popup for metrics component */
document.addEventListener('DOMContentLoaded', function()
{
    let formToSubmit = null
    
    const deletePopup = document.getElementById('deletePopup')
    const deleteButtons = document.querySelectorAll('.deleteButton')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')

    // open popup
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            formToSubmit = this.dataset.formId
            deletePopup.classList.remove('hidden')
        })
    })

    // cancel
    cancelDeleteButton.addEventListener('click', function () {
        deletePopup.classList.add('hidden')
        formToSubmit = null
    })

    // confirm delete -> submit POST form
    confirmDeleteButton.addEventListener('click', function () {
        if (formToSubmit) {
            document.getElementById(formToSubmit).submit()
        }
    })

    // esc
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            deletePopup.classList.add('hidden')
            formToSubmit = null
        }
    })

    // click outside
    deletePopup.addEventListener('click', function (event) {
        if (event.target === this) {
            deletePopup.classList.add('hidden')
            formToSubmit = null
        }
    })
})

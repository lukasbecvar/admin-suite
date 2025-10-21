/** service exception file delete confirmation popup functionality */
document.addEventListener('DOMContentLoaded', function()
{
    let fileToDelete
    
    // get view elements
    const deletePopup = document.getElementById('deletePopup')
    const deleteButtons = document.querySelectorAll('.delete-button')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')

    // handle delete confirmation popup show
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            fileToDelete = this.dataset.file
            deletePopup.classList.remove('hidden')
        })
    })

    // handle cancel button click
    cancelDeleteButton.addEventListener('click', function () {
        deletePopup.classList.add('hidden')
        fileToDelete = null
    })

    // handle escape key press
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (!deletePopup.classList.contains('hidden')) {
                deletePopup.classList.add('hidden')
            }
        }
    })

    // handle click outside of popup
    document.getElementById('deletePopup').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // handle click on confirm button
    confirmDeleteButton.addEventListener('click', function () {
        if (fileToDelete) {
            window.location.href = '/manager/logs/exception/delete?file=' + encodeURIComponent(fileToDelete)
        }
    })
})

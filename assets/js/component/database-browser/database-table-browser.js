/* database table reader component functionality */
document.addEventListener('DOMContentLoaded', function()
{
    let deleteId = null

    // select popup elements
    const popup = document.getElementById('textPopup')
    const popupText = document.getElementById('popupText')
    const deletePopup = document.getElementById('deletePopup')
    const deleteButton = document.querySelectorAll('.delete-button')
    const viewRawButton = document.querySelectorAll('.view-raw-button')
    const closePopupButton = document.getElementById('closePopupButton')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')

    // get raw string
    function decodeInput(input) {
        const e = document.createElement('div')
        e.innerHTML = input
        return e.childNodes.length === 0 ? '' : e.childNodes[0].nodeValue
    }

    // handle popup open (raw data viewer)
    function openPopup(text) {
        popupText.textContent = text
        popup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // handle popup close (raw data viewer)
    function closePopup() {
        popup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    // handle close popup with esc key press (raw data viewer)
    function handleEscKey(event) {
        if (event.key === 'Escape') {
            closePopup()
            closeDeletePopup()
        }
    }

    // detect click on truncate button (raw data viewer)
    viewRawButton.forEach(function(button) {
        button.addEventListener('click', function() {
            openPopup(decodeInput(button.getAttribute('data-fulltext')))
        })
    })

    // init close popup button event
    closePopupButton.addEventListener('click', closePopup)

    // handle row delete confirmation popup open
    function openDeletePopup(url) {
        deleteUrl = url
        deletePopup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // handle row delete confirmation popup close
    function closeDeletePopup() {
        deletePopup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    // confirm delete action
    confirmDeleteButton.addEventListener('click', function() {
        if (deleteId) {
            document.getElementById('delete-form-' + deleteId).submit()
        }
    })

    // cancel delete action
    cancelDeleteButton.addEventListener('click', closeDeletePopup)

    // detect click on delete button
    deleteButton.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            deleteId = button.getAttribute('data-id')
            openDeletePopup()
        })
    })

    // close delete popup when clicking outside of it
    document.getElementById('deletePopup').addEventListener('click', function(event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

// scroll to highlighted row
document.addEventListener('DOMContentLoaded', () => {
    const highlightedRow = document.querySelector('[data-highlighted-row="true"]')

    // check if highlight element exists
    if (highlightedRow) {
        highlightedRow.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        })
    }
})

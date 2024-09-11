document.addEventListener('DOMContentLoaded', function() {
    // select popup elements
    const popup = document.getElementById('textPopup')
    const popupText = document.getElementById('popupText')
    const closePopupButton = document.getElementById('closePopupButton')
    
    // delete confirmation popup elements
    const deletePopup = document.getElementById('deletePopup')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    
    let deleteUrl = ''

    // get raw string from escaped data
    function decodeInput(input) {
        const e = document.createElement('div')
        e.innerHTML = input
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue
    }

    // handle open popup
    function openPopup(text) {
        popupText.textContent = text
        popup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // handle close popup
    function closePopup() {
        popup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    // handle esc key
    function handleEscKey(event) {
        if (event.key === 'Escape') {
            closePopup()
            closeDeletePopup()
        }
    }

    // detect click on truncate button
    document.querySelectorAll('.truncate-button').forEach(function(button) {
        button.addEventListener('click', function() {
            openPopup(decodeInput(button.getAttribute('data-fulltext')))
        })
    })

    // init close popup button event
    closePopupButton.addEventListener('click', closePopup)

    // handle open delete confirmation popup
    function openDeletePopup(url) {
        deleteUrl = url
        deletePopup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // handle close delete confirmation popup
    function closeDeletePopup() {
        deletePopup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    // confirm delete action
    confirmDeleteButton.addEventListener('click', function() {
        window.location.href = deleteUrl
    })

    // cancel delete action
    cancelDeleteButton.addEventListener('click', closeDeletePopup)

    // detect click on delete button
    document.querySelectorAll('.delete-button').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            openDeletePopup(button.getAttribute('data-url'))
        })
    })
})

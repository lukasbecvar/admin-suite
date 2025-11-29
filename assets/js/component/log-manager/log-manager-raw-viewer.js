/** log manager raw message viewer */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // ELEMENT DECLARATIONS
    // -----------------------------
    const popup = document.getElementById('textPopup')
    const popupText = document.getElementById('popupText')
    const rawButtons = document.querySelectorAll('.view-raw-button')
    const closePopupButton = document.getElementById('closePopupButton')

    if (!popup || !popupText || !closePopupButton || rawButtons.length === 0) {
        return
    }

    // -----------------------------
    // UTILITY FUNCTIONS
    // -----------------------------
    const decodeInput = (input) => {
        const wrapper = document.createElement('div')
        wrapper.innerHTML = input ?? ''
        return wrapper.textContent || wrapper.innerText || ''
    }

    // -----------------------------
    // POPUP CONTROL FUNCTIONS
    // -----------------------------
    const closePopup = () => {
        popup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    // handle close popup with esc key press
    const handleEscKey = (event) => {
        if (event.key === 'Escape') {
            closePopup()
        }
    }

    // open popup with raw data
    const openPopup = (text) => {
        popupText.textContent = text
        popup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // -----------------------------
    // EVENT LISTENERS
    // -----------------------------
    // event listener for raw data view buttons
    rawButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openPopup(decodeInput(button.getAttribute('data-fulltext')))
        })
    })

    // close popup when clicking outside of it
    closePopupButton.addEventListener('click', closePopup)
    popup.addEventListener('click', (event) => {
        if (event.target === popup) {
            closePopup()
        }
    })
})

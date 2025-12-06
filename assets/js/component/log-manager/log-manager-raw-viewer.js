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

    const escapeHtml = (input = '') => input
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')

    const linkifyText = (text = '') => escapeHtml(text).replace(
        /(https?:\/\/[^\s]+)/g,
        '<a href="$1" class="link" target="_blank" rel="noopener noreferrer">$1</a>'
    )

    // open popup with raw data
    const openPopup = (text) => {
        popupText.innerHTML = linkifyText(text)
        popup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    // -----------------------------
    // EVENT LISTENERS
    // -----------------------------
    const toggleRawButtonsVisibility = () => {
        rawButtons.forEach((button) => {
            const messageElement = button.previousElementSibling
            if (!messageElement) {
                return
            }

            const isOverflowing = messageElement.scrollWidth - messageElement.clientWidth > 1
            button.classList.toggle('hidden', !isOverflowing)
        })
    }

    // event listener for raw data view buttons
    rawButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openPopup(decodeInput(button.getAttribute('data-fulltext')))
        })
    })

    // initial visibility check + updates on resize
    toggleRawButtonsVisibility()
    window.addEventListener('resize', () => {
        window.requestAnimationFrame(toggleRawButtonsVisibility)
    })

    // close popup when clicking outside of it
    closePopupButton.addEventListener('click', closePopup)
    popup.addEventListener('click', (event) => {
        if (event.target === popup) {
            closePopup()
        }
    })
})

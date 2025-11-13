/* log manager raw message viewer */
document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('textPopup')
    const popupText = document.getElementById('popupText')
    const rawButtons = document.querySelectorAll('.view-raw-button')
    const closePopupButton = document.getElementById('closePopupButton')

    if (!popup || !popupText || !closePopupButton || rawButtons.length === 0) {
        return
    }

    const decodeInput = (input) => {
        const wrapper = document.createElement('div')
        wrapper.innerHTML = input ?? ''
        return wrapper.textContent || wrapper.innerText || ''
    }

    const closePopup = () => {
        popup.classList.add('hidden')
        document.removeEventListener('keydown', handleEscKey)
    }

    const handleEscKey = (event) => {
        if (event.key === 'Escape') {
            closePopup()
        }
    }

    const openPopup = (text) => {
        popupText.textContent = text
        popup.classList.remove('hidden')
        document.addEventListener('keydown', handleEscKey)
    }

    rawButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openPopup(decodeInput(button.getAttribute('data-fulltext')))
        })
    })

    closePopupButton.addEventListener('click', closePopup)

    popup.addEventListener('click', (event) => {
        if (event.target === popup) {
            closePopup()
        }
    })
})

document.addEventListener('DOMContentLoaded', () => {
    const warningBox = document.getElementById('warning-box')
    if (!warningBox) {
        return
    }

    hydrateCloseButton(warningBox)
    animateWarningBoxEntrance(warningBox)
})

function hydrateCloseButton(warningBox) {
    const closeButton = document.getElementById('close-warning-box')
    if (!closeButton) {
        return
    }

    closeButton.addEventListener('click', () => closeWarningBox(warningBox))
}

function closeWarningBox(warningBox) {
    // add closing animation class
    warningBox.classList.add('warning-closing')

    // simple, elegant fade and slide animation
    warningBox.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)'
    warningBox.style.transformOrigin = 'center center'

    // smooth fade out with gentle slide up
    setTimeout(() => {
        warningBox.style.transform = 'translateY(-20px) scale(0.95)'
        warningBox.style.filter = 'blur(1px)'
        warningBox.style.opacity = '0'
    }, 30)

    // remove element after animation completes
    setTimeout(() => {
        if (warningBox.parentNode) {
            warningBox.parentNode.removeChild(warningBox)
        }
    }, 280)
}

// box animation
function animateWarningBoxEntrance(warningBox) {
    // start with hidden state
    warningBox.style.opacity = '0'
    warningBox.style.filter = 'blur(2px)'
    warningBox.style.transform = 'translateY(-20px) scale(0.95)'

    // animate to visible state
    setTimeout(() => {
        warningBox.style.opacity = '1'
        warningBox.style.filter = 'blur(0px)'
        warningBox.style.transform = 'translateY(0) scale(1)'
        warningBox.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)'
    }, 100)
}

// hide warning box if no elements are found
window.addEventListener('DOMContentLoaded', () => {
    const warningBox = document.getElementById('warning-box')
    const warningElements = document.getElementById('wraning-elements')
    if (!warningBox || !warningElements) {
        return
    }

    warningBox.style.display = warningElements.innerHTML.trim() === '' ? 'none' : 'block'
})

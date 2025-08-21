/* suite config manager component (handle reset to default popup) */
document.addEventListener('DOMContentLoaded', function () {
    var resetButtons = document.querySelectorAll('.reset-button')
    var popupOverlay = document.getElementById('reset-popup-overlay')
    var cancelButton = document.getElementById('reset-cancel-button')
    var confirmButton = document.getElementById('reset-confirm-button')
    var resetUrl = ''

    if (popupOverlay) {
        // handle reset buttons
        if (resetButtons.length > 0) {
            resetButtons.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault()
                    resetUrl = this.getAttribute('data-url')
                    popupOverlay.classList.remove('hidden')
                })
            })
        }

        // handle confirm button
        if (confirmButton) {
            confirmButton.addEventListener('click', function() {
                window.location.href = resetUrl
            })
        }

        // handle cancel button
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                popupOverlay.classList.add('hidden')
            })
        }

        // handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (!popupOverlay.classList.contains('hidden')) {
                    popupOverlay.classList.add('hidden')
                }
            }
        })

        // handle click outside popup
        popupOverlay.addEventListener('click', function (event) {
            if (event.target === this) {
                this.classList.add('hidden')
            }
        })
    }
})

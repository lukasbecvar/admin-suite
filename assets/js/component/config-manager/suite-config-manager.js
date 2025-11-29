/** suite config manager component */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // RESET TO DEFAULT FUNCTIONALITY
    // -----------------------------
    var resetButtons = document.querySelectorAll('.reset-button')
    var popupOverlay = document.getElementById('reset-popup-overlay')
    var cancelButton = document.getElementById('reset-cancel-button')
    var confirmButton = document.getElementById('reset-confirm-button')

    // external reset form
    var selectedFilename = ''
    var resetForm = document.getElementById('reset-form')
    var filenameInput = document.getElementById('reset-filename')

    if (popupOverlay) {
        // handle reset buttons
        if (resetButtons.length > 0) {
            resetButtons.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault()
                    
                    // get filename from attribute
                    selectedFilename = this.getAttribute('data-filename')

                    // show popup
                    popupOverlay.classList.remove('hidden')
                })
            })
        }

        // handle confirm button (submit POST)
        if (confirmButton) {
            confirmButton.addEventListener('click', function() {
                // set filename for reset
                filenameInput.value = selectedFilename

                // submit reset POST form
                resetForm.submit()
            })
        }

        // handle cancel button
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                popupOverlay.classList.add('hidden')
            })
        }
    }

    // -----------------------------
    // GLOBAL EVENT LISTENERS
    // -----------------------------
    // escape closes popup
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (!popupOverlay.classList.contains('hidden')) {
                popupOverlay.classList.add('hidden')
            }
        }
    })

    // click outside closes popup
    popupOverlay.addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

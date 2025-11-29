/** users manager component */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // DELETE USER
    // -----------------------------
    var selectedForm = null
    var popupOverlay = document.getElementById('popup-overlay')
    var cancelButton = document.getElementById('cancel-button')
    var deleteForms = document.querySelectorAll('.delete-form')
    var confirmButton = document.getElementById('confirm-button')
    var deleteButtons = document.querySelectorAll('.delete-button')

    // event listener for delete button
    deleteButtons.forEach(function(button, index) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            selectedForm = deleteForms[index]
            popupOverlay.classList.remove('hidden')
        })
    })

    // event listener for confirmation submit
    confirmButton.addEventListener('click', function() {
        if (selectedForm) {
            selectedForm.submit()
        }
    })

    // event listener for cancel button
    cancelButton.addEventListener('click', function() {
        popupOverlay.classList.add('hidden')
    })

    // -----------------------------
    // ROLE UPDATE
    // -----------------------------
    var roleUpdateForm = document.getElementById('role-update-form')
    var roleUpdateButtons = document.querySelectorAll('.role-update-button')
    var roleUpdatePopupOverlay = document.getElementById('role-update-popup-overlay')
    var roleUpdateCancelButton = document.getElementById('role-update-cancel-button')
    var roleUpdateSubmitButton = document.getElementById('role-update-submit-button')

    // show role update popup
    function showRoleUpdatePopup(username, currentRole, userId) {
        document.getElementById('role-update-username').textContent = username
        document.getElementById('current-role').value = currentRole
        document.getElementById('role-update-user-id').value = userId
        document.getElementById('new-role').value = '' // clear input field
        document.getElementById('role-error-message').classList.add('hidden')
        roleUpdatePopupOverlay.classList.remove('hidden')
    }

    // event listener for role update button
    roleUpdateButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var username = button.getAttribute('data-username')
            var currentRole = button.getAttribute('data-role')
            var userId = button.getAttribute('data-id')
            showRoleUpdatePopup(username, currentRole, userId)
        })
    })

    // event listener for cancel button
    roleUpdateCancelButton.addEventListener('click', function() {
        roleUpdatePopupOverlay.classList.add('hidden')
    })

    // event listener for new role input
    document.getElementById('new-role').addEventListener('input', function() {
        var currentRole = document.getElementById('current-role').value
        var newRole = this.value.trim()
        if (newRole.length > 1 && (newRole.toUpperCase() !== currentRole)) {
            roleUpdateSubmitButton.removeAttribute('disabled')
        } else {
            roleUpdateSubmitButton.setAttribute('disabled', 'disabled')
        }
    })

    // event listener for form submission
    roleUpdateForm.addEventListener('submit', function(event) {
        var currentRole = document.getElementById('current-role').value
        var newRole = document.getElementById('new-role').value.trim()
        if (newRole === currentRole) {
            event.preventDefault() // prevent form submission
            document.getElementById('role-error-message').classList.remove('hidden')
        }
    })

    // -----------------------------
    // BAN USER
    // -----------------------------
    var selectedBanForm = null
    var banForms = document.querySelectorAll('.ban-form')
    var banButtons = document.querySelectorAll('.ban-button')
    var banReasonInput = document.getElementById('ban-reason')
    var banPopupOverlay = document.getElementById('ban-popup-overlay')
    var banCancelButton = document.getElementById('ban-cancel-button')
    var banConfirmButton = document.getElementById('ban-confirm-button')

    // show ban popup
    function showBanPopup() {
        banPopupOverlay.classList.remove('hidden')
    }

    // event listener for ban button
    banButtons.forEach(function(button, index) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var form = banForms[index]
            if (form) {
                selectedBanForm = form
                showBanPopup()
            }
        })
    })

    // event listener for confirmation submit
    banConfirmButton.addEventListener('click', function() {
        var reason = banReasonInput.value.trim()
        if (selectedBanForm && reason.length > 0) {
            var banReasonField = selectedBanForm.querySelector('input[name="reason"]')
            if (banReasonField) {
                banReasonField.value = reason
            }
            selectedBanForm.submit()
        }
    })

    // event listener for cancel button
    banCancelButton.addEventListener('click', function() {
        banPopupOverlay.classList.add('hidden')
        selectedBanForm = null
    })

    // -----------------------------
    // UNBAN USER
    // -----------------------------
    var selectedUnbanForm = null
    var unbanForms = document.querySelectorAll('.unban-form')
    var unbanButtons = document.querySelectorAll('.unban-button')
    var unbanPopupOverlay = document.getElementById('unban-popup-overlay')
    var unbanCancelButton = document.getElementById('unban-cancel-button')
    var unbanConfirmButton = document.getElementById('unban-confirm-button')

    // show unban popup
    function showUnbanPopup() {
        unbanPopupOverlay.classList.remove('hidden')
    }

    // event listener for unban button
    unbanButtons.forEach(function(button, index) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var form = unbanForms[index]
            if (form) {
                selectedUnbanForm = form
                showUnbanPopup()
            }
        })
    })

    // event listener for confirmation submit
    unbanConfirmButton.addEventListener('click', function() {
        if (selectedUnbanForm) {
            selectedUnbanForm.submit()
        }
    })

    // event listener for cancel button
    unbanCancelButton.addEventListener('click', function() {
        unbanPopupOverlay.classList.add('hidden')
        selectedUnbanForm = null
    })

    // -----------------------------
    // TOKEN REGENERATION
    // -----------------------------
    var selectedTokenRegenerateForm = null
    var tokenRegenerateForms = document.querySelectorAll('.token-regenerate-form')
    var tokenRegenerateButtons = document.querySelectorAll('.token-regenerate-button')
    var tokenRegeneratePopupOverlay = document.getElementById('token-regenerate-popup-overlay')
    var tokenRegenerateCancelButton = document.getElementById('token-regenerate-cancel-button')
    var tokenRegenerateConfirmButton = document.getElementById('token-regenerate-confirm-button')

    // show token regenerate popup
    function showTokenRegeneratePopup() {
        tokenRegeneratePopupOverlay.classList.remove('hidden')
    }

    // event listener for token regenerate button
    tokenRegenerateButtons.forEach(function(button, index) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var form = tokenRegenerateForms[index]
            if (form) {
                selectedTokenRegenerateForm = form
                showTokenRegeneratePopup()
            }
        })
    })

    // event listener for confirmation submit
    tokenRegenerateConfirmButton.addEventListener('click', function() {
        if (selectedTokenRegenerateForm) {
            selectedTokenRegenerateForm.submit()
        }
    })

    // event listener for cancel button
    tokenRegenerateCancelButton.addEventListener('click', function() {
        tokenRegeneratePopupOverlay.classList.add('hidden')
        selectedTokenRegenerateForm = null
    })

    // -----------------------------
    // API ACCESS
    // -----------------------------
    var selectedApiAccessForm = null
    var apiAccessForms = document.querySelectorAll('.api-access-form')
    var apiAccessButtons = document.querySelectorAll('.api-access-button')
    var apiAccessUsernameLabel = document.getElementById('api-access-username')
    var apiAccessActionLabel = document.getElementById('api-access-action-label')
    var apiAccessPopupOverlay = document.getElementById('api-access-popup-overlay')
    var apiAccessCancelButton = document.getElementById('api-access-cancel-button')
    var apiAccessConfirmButton = document.getElementById('api-access-confirm-button')
    var apiAccessConfirmText = apiAccessConfirmButton ? apiAccessConfirmButton.querySelector('span') : null
    var apiAccessConfirmIcon = apiAccessConfirmButton ? apiAccessConfirmButton.querySelector('i') : null

    // show api access popup
    function showApiAccessPopup(username, action) {
        apiAccessActionLabel.textContent = action === 'enable' ? 'enable' : 'disable'
        apiAccessUsernameLabel.textContent = username
        if (apiAccessConfirmText) {
            apiAccessConfirmText.textContent = action === 'enable' ? 'Enable API Access' : 'Disable API Access'
        }
        if (apiAccessConfirmIcon) {
            apiAccessConfirmIcon.classList.remove('fa-toggle-on', 'fa-toggle-off')
            apiAccessConfirmIcon.classList.add(action === 'enable' ? 'fa-toggle-on' : 'fa-toggle-off')
        }
        apiAccessPopupOverlay.classList.remove('hidden')
    }

    // event listener for api access button
    apiAccessButtons.forEach(function(button, index) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var username = button.getAttribute('data-username')
            var action = button.getAttribute('data-action')
            var form = apiAccessForms[index]
            if (form) {
                selectedApiAccessForm = form
                showApiAccessPopup(username, action)
            }
        })
    })

    // event listener for confirmation submit
    apiAccessConfirmButton.addEventListener('click', function() {
        if (selectedApiAccessForm) {
            selectedApiAccessForm.submit()
        }
    })

    // event listener for cancel button
    apiAccessCancelButton.addEventListener('click', function() {
        apiAccessPopupOverlay.classList.add('hidden')
        selectedApiAccessForm = null
    })

    // -----------------------------
    // GLOBAL EVENT LISTENERS / UTILITIES
    // -----------------------------
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (!popupOverlay.classList.contains('hidden')) {
                popupOverlay.classList.add('hidden')
            }
            if (!roleUpdatePopupOverlay.classList.contains('hidden')) {
                roleUpdatePopupOverlay.classList.add('hidden')
            }
            if (!banPopupOverlay.classList.contains('hidden')) {
                banPopupOverlay.classList.add('hidden')
            }
            if (!unbanPopupOverlay.classList.contains('hidden')) {
                unbanPopupOverlay.classList.add('hidden')
            }
            if (!apiAccessPopupOverlay.classList.contains('hidden')) {
                apiAccessPopupOverlay.classList.add('hidden')
            }
            if (!tokenRegeneratePopupOverlay.classList.contains('hidden')) {
                tokenRegeneratePopupOverlay.classList.add('hidden')
            }
        }
    })

    // event listener for popup overlay click
    document.getElementById('popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // event listener for role update popup overlay click
    document.getElementById('role-update-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // event listener for ban popup overlay click
    document.getElementById('ban-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // event listener for unban popup overlay click
    document.getElementById('unban-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // event listener for token regenerate popup overlay click
    document.getElementById('token-regenerate-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

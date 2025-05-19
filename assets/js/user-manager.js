/* users manager component (handle delete, role update, ban, unban popup) */
document.addEventListener('DOMContentLoaded', function() {
    // elements related to delete functionality
    var popupOverlay = document.getElementById('popup-overlay')
    var cancelButton = document.getElementById('cancel-button')
    var confirmButton = document.getElementById('confirm-button')
    var deleteButtons = document.querySelectorAll('.delete-button')
    var deleteUrl = ''

    // elements related to role update functionality
    var roleUpdateForm = document.getElementById('role-update-form')
    var roleUpdateButtons = document.querySelectorAll('.role-update-button')
    var roleUpdatePopupOverlay = document.getElementById('role-update-popup-overlay')
    var roleUpdateCancelButton = document.getElementById('role-update-cancel-button')
    var roleUpdateSubmitButton = document.getElementById('role-update-submit-button')

    // elements related to ban functionality
    var banButtons = document.querySelectorAll('.ban-button')
    var banReasonInput = document.getElementById('ban-reason')
    var banPopupOverlay = document.getElementById('ban-popup-overlay')
    var banCancelButton = document.getElementById('ban-cancel-button')
    var banConfirmButton = document.getElementById('ban-confirm-button')
    var banUrl = ''

    // elements related to unban functionality
    var unbanButtons = document.querySelectorAll('.unban-button')
    var unbanPopupOverlay = document.getElementById('unban-popup-overlay')
    var unbanCancelButton = document.getElementById('unban-cancel-button')
    var unbanConfirmButton = document.getElementById('unban-confirm-button')
    var unbanUrl = ''

    // elements related to token regeneration functionality
    var tokenRegenerateButtons = document.querySelectorAll('.token-regenerate-button')
    var tokenRegeneratePopupOverlay = document.getElementById('token-regenerate-popup-overlay')
    var tokenRegenerateCancelButton = document.getElementById('token-regenerate-cancel-button')
    var tokenRegenerateConfirmButton = document.getElementById('token-regenerate-confirm-button')
    var tokenRegenerateUrl = ''

    // show the ban confirmation popup
    function showBanPopup(url) {
        banUrl = url
        banPopupOverlay.classList.remove('hidden')
    }

    // event listeners to each ban button
    banButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var banUrl = this.href
            showBanPopup(banUrl)
        })
    })

    // event listener for confirming ban action
    banConfirmButton.addEventListener('click', function() {
        var reason = banReasonInput.value.trim()
        if (reason.length > 0) {
            window.location.href = banUrl + '&reason=' + encodeURIComponent(reason)
        }
    })

    // event listener for cancelling ban action
    banCancelButton.addEventListener('click', function() {
        banPopupOverlay.classList.add('hidden')
    })

    // show the unban confirmation popup
    function showUnbanPopup(url) {
        unbanUrl = url
        unbanPopupOverlay.classList.remove('hidden')
    }

    // event listeners to each unban button
    unbanButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var unbanUrl = this.href
            showUnbanPopup(unbanUrl)
        })
    })

    // event listener for confirming unban action
    unbanConfirmButton.addEventListener('click', function() {
        window.location.href = unbanUrl
    })

    // event listener for cancelling unban action
    unbanCancelButton.addEventListener('click', function() {
        unbanPopupOverlay.classList.add('hidden')
    })

    // show the token regeneration confirmation popup
    function showTokenRegeneratePopup(url) {
        tokenRegenerateUrl = url
        tokenRegeneratePopupOverlay.classList.remove('hidden')
    }

    // event listeners to each token regenerate button
    tokenRegenerateButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var url = this.href
            showTokenRegeneratePopup(url)
        })
    })

    // event listener for confirming token regeneration action
    tokenRegenerateConfirmButton.addEventListener('click', function() {
        window.location.href = tokenRegenerateUrl
    })

    // event listener for cancelling token regeneration action
    tokenRegenerateCancelButton.addEventListener('click', function() {
        tokenRegeneratePopupOverlay.classList.add('hidden')
    })

    // show the role update popup with user data
    function showRoleUpdatePopup(username, currentRole, userId) {
        document.getElementById('role-update-username').textContent = username
        document.getElementById('current-role').value = currentRole
        document.getElementById('role-update-user-id').value = userId
        document.getElementById('new-role').value = '' // clear input field
        document.getElementById('role-error-message').classList.add('hidden')
        roleUpdatePopupOverlay.classList.remove('hidden')
    }

    // event listeners to each role update button
    roleUpdateButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            var username = button.getAttribute('data-username')
            var currentRole = button.getAttribute('data-role')
            var userId = button.getAttribute('data-id')
            showRoleUpdatePopup(username, currentRole, userId)
        })
    })

    // event listener for cancelling role update action
    roleUpdateCancelButton.addEventListener('click', function() {
        roleUpdatePopupOverlay.classList.add('hidden')
    })

    // event listener for input changes in the new role field
    document.getElementById('new-role').addEventListener('input', function() {
        var currentRole = document.getElementById('current-role').value
        var newRole = this.value.trim()
        if (newRole.length > 1 && (newRole.toUpperCase() !== currentRole)) {
            roleUpdateSubmitButton.removeAttribute('disabled')
        } else {
            roleUpdateSubmitButton.setAttribute('disabled', 'disabled')
        }
    })

    // event listener for role update form submission
    roleUpdateForm.addEventListener('submit', function(event) {
        var currentRole = document.getElementById('current-role').value
        var newRole = document.getElementById('new-role').value.trim()
        if (newRole === currentRole) {
            event.preventDefault() // prevent form submission
            document.getElementById('role-error-message').classList.remove('hidden')
        }
    })

    // event listeners to each delete button
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault()
            deleteUrl = this.href
            popupOverlay.classList.remove('hidden')
        })
    })

    // event listener for confirming delete action
    confirmButton.addEventListener('click', function() {
        window.location.href = deleteUrl
    })

    // event listener for cancelling delete action
    cancelButton.addEventListener('click', function() {
        popupOverlay.classList.add('hidden')
    })

    // event listener for the 'Escape' key to close all popups
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
            if (!tokenRegeneratePopupOverlay.classList.contains('hidden')) {
                tokenRegeneratePopupOverlay.classList.add('hidden')
            }
        }
    })

    // close popup overlay when clicking outside of it
    document.getElementById('popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // close role update popup overlay when clicking outside of it
    document.getElementById('role-update-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // close ban popup overlay when clicking outside of it
    document.getElementById('ban-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // close unban popup overlay when clicking outside of it
    document.getElementById('unban-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })

    // close token regenerate popup overlay when clicking outside of it
    document.getElementById('token-regenerate-popup-overlay').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

/** account settings component */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // API TOKEN VISIBILITY TOGGLE
    // -----------------------------
    var tokenValueElement = document.getElementById('api-token-value')
    var tokenToggleButton = document.getElementById('api-token-toggle')
    if (!tokenValueElement || !tokenToggleButton) {
        return
    }

    var realToken = tokenValueElement.getAttribute('data-token') || ''
    var maskLength = realToken.length > 0 ? Math.min(realToken.length, 32) : 12
    var maskedToken = ''.padEnd(maskLength, '*')
    var isVisible = false
    tokenValueElement.textContent = maskedToken
    tokenValueElement.classList.add('whitespace-nowrap', 'overflow-hidden')

    // handle access toggle button
    tokenToggleButton.addEventListener('click', function() {
        isVisible = !isVisible
        if (isVisible) {
            tokenValueElement.textContent = realToken
            tokenValueElement.classList.remove('whitespace-nowrap', 'overflow-hidden')
            tokenValueElement.classList.add('break-all')
        } else {
            tokenValueElement.textContent = maskedToken
            tokenValueElement.classList.add('whitespace-nowrap', 'overflow-hidden')
            tokenValueElement.classList.remove('break-all')
        }

        var icon = tokenToggleButton.querySelector('i')
        if (icon != null) {
            icon.classList.remove('fa-eye', 'fa-eye-slash')
            icon.classList.add(isVisible ? 'fa-eye-slash' : 'fa-eye')
        }

        var label = tokenToggleButton.querySelector('span')
        if (label != null) {
            label.textContent = isVisible ? 'Hide token' : 'View token'
        }
    })

    // -----------------------------
    // COPY TO CLIPBOARD FUNCTIONALITY
    // -----------------------------
    // copy token to clipboard (on click)
    function copyTokenToClipboard(value) {
        if (!value) {
            return
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(showCopyFeedback).catch(function() {
                fallbackCopy(value)
                showCopyFeedback()
            })
            return
        }

        fallbackCopy(value)
        showCopyFeedback()
    }

    // fallback copy method
    function fallbackCopy(value) {
        var textarea = document.createElement('textarea')
        textarea.value = value
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.focus()
        textarea.select()
        try {
            document.execCommand('copy')
        } catch (e) {
            console.warn('Unable to copy token automatically', e)
        }
        document.body.removeChild(textarea)
    }

    // show copy feedback (change token value color)
    function showCopyFeedback() {
        tokenValueElement.classList.add('text-green-300')
        setTimeout(function() {
            tokenValueElement.classList.remove('text-green-300')
        }, 800)
    }

    // event listener for token value click
    tokenValueElement.addEventListener('click', function() {
        copyTokenToClipboard(realToken)
    })
})

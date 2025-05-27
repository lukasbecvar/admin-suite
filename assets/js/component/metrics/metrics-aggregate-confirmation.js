/** aggregate confirmation popup for metrics component */
document.addEventListener('DOMContentLoaded', function () {
    // get elements related to aggregate functionality
    const aggregatePopup = document.getElementById('aggregatePopup')
    const aggregateButton = document.getElementById('aggregateButton')
    const cancelAggregateButton = document.getElementById('cancelAggregateButton')
    const confirmAggregateButton = document.getElementById('confirmAggregateButton')

    // check if elements exist (they might not exist on all pages)
    if (!aggregatePopup || !aggregateButton || !cancelAggregateButton || !confirmAggregateButton) {
        return
    }

    // event listener for aggregate button click
    aggregateButton.addEventListener('click', function (event) {
        event.preventDefault()
        aggregatePopup.classList.remove('hidden')
    })

    // event listener for cancelling aggregate action
    cancelAggregateButton.addEventListener('click', function () {
        aggregatePopup.classList.add('hidden')
    })

    // event listener for confirming aggregate action
    confirmAggregateButton.addEventListener('click', function () {
        // redirect to aggregate endpoint
        window.location.href = '/metrics/aggregate'
    })

    // close popup when user presses 'Escape' key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (!aggregatePopup.classList.contains('hidden')) {
                aggregatePopup.classList.add('hidden')
            }
        }
    })

    // close popup overlay when clicking outside of it
    aggregatePopup.addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

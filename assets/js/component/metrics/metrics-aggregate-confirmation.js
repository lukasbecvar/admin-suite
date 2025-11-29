/** aggregate confirmation popup for metrics component */
document.addEventListener('DOMContentLoaded', function()
{
    // -----------------------------
    // CONSTANTS AND INITIAL SETUP
    // -----------------------------
    // get elements related to aggregate functionality
    const aggregatePopup = document.getElementById('aggregatePopup')
    const aggregateButton = document.getElementById('aggregateButton')
    const cancelAggregateButton = document.getElementById('cancelAggregateButton')
    const confirmAggregateButton = document.getElementById('confirmAggregateButton')
    const aggregateStatusMessage = document.getElementById('aggregateStatusMessage')

    const statusStyles = {
        success: 'bg-green-500/10 border-green-500/40 text-green-300',
        info: 'bg-blue-500/10 border-blue-500/40 text-blue-300',
        error: 'bg-red-500/10 border-red-500/40 text-red-300'
    }

    const statusClasses = []
    Object.values(statusStyles).forEach(style => {
        style.split(' ').forEach(className => {
            if (!statusClasses.includes(className)) {
                statusClasses.push(className)
            }
        })
    })

    const confirmButtonDefaultText = confirmAggregateButton ? confirmAggregateButton.textContent.trim() : 'Aggregate Now'

    // check if elements exist (they might not exist on all pages)
    if (!aggregatePopup || !aggregateButton || !cancelAggregateButton || !confirmAggregateButton || !aggregateStatusMessage) {
        return
    }

    // -----------------------------
    // UTILITY FUNCTIONS
    // -----------------------------
    // helper to clear status box between actions
    const resetStatusMessage = () => {
        if (!aggregateStatusMessage) {
            return
        }

        aggregateStatusMessage.classList.add('hidden')
        aggregateStatusMessage.innerHTML = ''
        statusClasses.forEach(className => aggregateStatusMessage.classList.remove(className))
    }

    // build one key/value row inside the status box
    const createDetailRow = (label, value) => {
        const row = document.createElement('div')
        row.className = 'flex items-center justify-between gap-3'

        const labelSpan = document.createElement('span')
        labelSpan.className = 'text-white/70 text-xs'
        labelSpan.textContent = label

        const valueSpan = document.createElement('span')
        valueSpan.className = 'text-white text-xs font-semibold text-right'
        valueSpan.textContent = value

        row.appendChild(labelSpan)
        row.appendChild(valueSpan)

        return row
    }

    // render status message plus optional aggregation statistics
    const showStatusMessage = (status, message, details = null) => {
        if (!aggregateStatusMessage) {
            return
        }

        resetStatusMessage()

        const resolvedStatus = statusStyles[status] ? status : 'info'
        statusStyles[resolvedStatus].split(' ').forEach(className => aggregateStatusMessage.classList.add(className))

        const messageBlock = document.createElement('div')
        messageBlock.className = 'text-sm font-semibold'
        messageBlock.textContent = message
        aggregateStatusMessage.appendChild(messageBlock)

        if (resolvedStatus !== 'success' || !details || typeof details !== 'object') {
            aggregateStatusMessage.classList.remove('hidden')
            return
        }

        if (details && typeof details === 'object') {
            const detailRows = []

            const hasMonthCount = typeof details.month_count === 'number'
            const hasPeriodSummary = typeof details.period_summary === 'string' && details.period_summary.trim() !== ''

            if (hasMonthCount || hasPeriodSummary) {
                const monthLabel = hasMonthCount && details.month_count === 1 ? 'month' : 'months'
                const summaryParts = []

                if (hasPeriodSummary) {
                    summaryParts.push(details.period_summary.trim())
                }

                if (hasMonthCount) {
                    summaryParts.push(`(${details.month_count} ${monthLabel})`)
                }

                const periodSummary = summaryParts.join(' ')
                detailRows.push(createDetailRow('Period', periodSummary.trim()))
            }

            if (typeof details.old_records === 'number') {
                if (details.old_records === 0) {
                    aggregateStatusMessage.classList.remove('hidden')
                    return
                }
                detailRows.push(createDetailRow('Old records aggregated', String(details.old_records)))
            }

            if (typeof details.created === 'number') {
                detailRows.push(createDetailRow('Monthly averages created', String(details.created)))
            }

            if (typeof details.preserved === 'number') {
                detailRows.push(createDetailRow('Recent records preserved', String(details.preserved)))
            }

            if (detailRows.length > 0) {
                const detailsContainer = document.createElement('div')
                detailsContainer.className = 'mt-3 space-y-1'
                detailRows.forEach(row => detailsContainer.appendChild(row))
                aggregateStatusMessage.appendChild(detailsContainer)
            }
        }

        aggregateStatusMessage.classList.remove('hidden')
    }

    // toggle disabled/loading state on the confirm button
    const setButtonLoadingState = (isLoading) => {
        if (!confirmAggregateButton) {
            return
        }

        if (isLoading) {
            confirmAggregateButton.disabled = true
            confirmAggregateButton.textContent = 'Aggregating...'
            confirmAggregateButton.classList.add('opacity-70', 'cursor-not-allowed')
        } else {
            confirmAggregateButton.disabled = false
            confirmAggregateButton.textContent = confirmButtonDefaultText
            confirmAggregateButton.classList.remove('opacity-70', 'cursor-not-allowed')
        }
    }

    // -----------------------------
    // EVENT LISTENERS FOR AGGREGATE POPUP
    // -----------------------------
    // event listener for aggregate button click
    aggregateButton.addEventListener('click', function (event) {
        event.preventDefault()
        resetStatusMessage()
        setButtonLoadingState(false)
        aggregatePopup.classList.remove('hidden')
    })

    // event listener for cancelling aggregate action
    cancelAggregateButton.addEventListener('click', function () {
        aggregatePopup.classList.add('hidden')
        resetStatusMessage()
        setButtonLoadingState(false)
    })

    // event listener for confirming aggregate action
    confirmAggregateButton.addEventListener('click', function () {
        const csrfToken = this.dataset.csrf

        // prepare form-data payload
        const formData = new URLSearchParams()
        formData.append('csrf_token', csrfToken)
        formData.append('source', 'metrics-dashboard')

        setButtonLoadingState(true)
        showStatusMessage('info', 'Aggregating old metrics...')

        // -----------------------------
        // AGGREGATION LOGIC
        // -----------------------------
        fetch('/metrics/aggregate', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json().then(data => {
            if (!response.ok) {
                const errorMessage = data && data.message ? data.message : 'Failed to aggregate metrics. Please try again.'
                const error = new Error(errorMessage)
                error.status = data && data.status ? data.status : 'error'
                throw error
            }
            return data
        }))
        .then(data => {
            const status = data && data.status ? data.status : 'success'
            const message = data && data.message ? data.message : 'Aggregation completed successfully.'
            const details = data && data.details ? data.details : null
            showStatusMessage(status, message, details)
        })
        .catch(error => {
            let fallbackMessage = 'Failed to aggregate metrics. Please try again.'

            if (error && typeof error.message === 'string' &&
                error.message.indexOf('Unexpected token') === -1 &&
                error.message.trim() !== '') {
                fallbackMessage = error.message
            }

            const status = error && error.status ? error.status : 'error'
            const details = error && error.details ? error.details : null
            showStatusMessage(status, fallbackMessage, details)
        })
        .finally(() => {
            setButtonLoadingState(false)
        })
    })

    // close popup when user presses 'Escape' key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (!aggregatePopup.classList.contains('hidden')) {
                aggregatePopup.classList.add('hidden')
                resetStatusMessage()
                setButtonLoadingState(false)
            }
        }
    })

    // close popup overlay when clicking outside of it
    aggregatePopup.addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
            resetStatusMessage()
            setButtonLoadingState(false)
        }
    })
})

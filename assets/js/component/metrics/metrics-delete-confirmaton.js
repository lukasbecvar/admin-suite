/** delete confirmation popup for metrics component */
document.addEventListener('DOMContentLoaded', function()
{
    // get elements related to delete functionality
    const deletePopup = document.getElementById('deletePopup')
    const deleteButtons = document.querySelectorAll('.deleteButton')
    const cancelDeleteButton = document.getElementById('cancelDeleteButton')
    const confirmDeleteButton = document.getElementById('confirmDeleteButton')

    let currentMetric = null
    let currentService = null

    // event listeners to each delete button
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            referer = this.getAttribute('data-referer')
            currentMetric = this.getAttribute('data-metric-name')
            currentService = this.getAttribute('data-service-name')
            deletePopup.classList.remove('hidden')
        })
    })

    // event listener for cancelling delete action
    cancelDeleteButton.addEventListener('click', function () {
        deletePopup.classList.add('hidden')
    })

    // event listener for confirming delete action
    confirmDeleteButton.addEventListener('click', function () {
        if (currentMetric && currentService && referer) {
            window.location.href = '/metrics/delete?metric_name=' + currentMetric + '&service_name=' + currentService + '&referer=' + referer
        }
    })

    // close popup when user presses 'Escape' key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (!deletePopup.classList.contains('hidden')) {
                deletePopup.classList.add('hidden')
            }
        }
    })

    // close popup overlay when clicking outside of it
    document.getElementById('deletePopup').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.add('hidden')
        }
    })
})

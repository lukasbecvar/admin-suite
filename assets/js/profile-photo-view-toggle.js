/** profile photo view toggle component */
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('open-profile-modal')
    const closeBtn = document.getElementById('close-profile-modal')
    const modal = document.getElementById('profile-modal')

    openBtn.addEventListener('click', () => {
        modal.classList.remove('hidden')
        modal.classList.add('flex')
    })

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('flex')
        modal.classList.add('hidden')
    })

    // close modal when clicking outside the image box
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('flex')
            modal.classList.add('hidden')
        }
    })

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            modal.classList.remove('flex')
            modal.classList.add('hidden')
        }
    })
})

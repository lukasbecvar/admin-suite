/** profile photo view component */
document.addEventListener('DOMContentLoaded', function()
{
    // get dom elements
    const sidebarModal = document.getElementById('profile-modal')
    const sidebarOpenBtn = document.getElementById('open-profile-modal')
    const sidebarCloseBtn = document.getElementById('close-profile-modal')
    const userProfileModal = document.getElementById('user-profile-modal')
    const userProfilePhoto = document.getElementById('user-profile-photo')
    const userProfileCloseBtn = document.getElementById('close-user-profile-modal')

    // initialize sidebar modal functionality
    if (sidebarModal && sidebarOpenBtn && sidebarCloseBtn) {
        // handle sidebar profile photo click
        sidebarOpenBtn.addEventListener('click', () => {
            sidebarModal.classList.remove('hidden')
            sidebarModal.classList.add('flex')
        })

        // close button handler
        sidebarCloseBtn.addEventListener('click', () => {
            sidebarModal.classList.remove('flex')
            sidebarModal.classList.add('hidden')
        })

        // close modal when clicking outside image box
        sidebarModal.addEventListener('click', (e) => {
            if (e.target === sidebarModal) {
                sidebarModal.classList.remove('flex')
                sidebarModal.classList.add('hidden')
            }
        })
    }

    // initialize user profile modal functionality
    if (userProfileModal && userProfilePhoto && userProfileCloseBtn) {
        // handle user profile photo click
        userProfilePhoto.addEventListener('click', () => {
            userProfileModal.classList.remove('hidden')
            userProfileModal.classList.add('flex')
        })

        // close button handler
        userProfileCloseBtn.addEventListener('click', () => {
            userProfileModal.classList.remove('flex')
            userProfileModal.classList.add('hidden')
        })

        // close modal when clicking outside image box
        userProfileModal.addEventListener('click', (e) => {
            if (e.target === userProfileModal) {
                userProfileModal.classList.remove('flex')
                userProfileModal.classList.add('hidden')
            }
        })
    }

    // global escape key handler for all modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // close sidebar modal if it exists and is open
            if (sidebarModal && sidebarModal.classList.contains('flex')) {
                sidebarModal.classList.remove('flex')
                sidebarModal.classList.add('hidden')
            }
            // close user profile modal if it exists and is open
            if (userProfileModal && userProfileModal.classList.contains('flex')) {
                userProfileModal.classList.remove('flex')
                userProfileModal.classList.add('hidden')
            }
        }
    })
})

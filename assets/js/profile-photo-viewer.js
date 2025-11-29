/** profile photo view component */
document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------
    // GLOBAL DOMContentLoaded EVENT LISTENER
    // -----------------------------
    initProfilePhotoViewer()
    initIpToggle(
        document.getElementById('ip-short'),
        document.getElementById('ip-full')
    )
})

// -----------------------------
// PROFILE PHOTO VIEWER INITIALIZATION
// -----------------------------
function initProfilePhotoViewer() {
    const modal = document.getElementById('profile-viewer-modal')
    if (!modal) {
        return
    }

    const triggers = document.querySelectorAll('.js-open-profile-viewer')
    const modalImage = document.getElementById('js-profile-viewer-image')
    const closeButton = document.getElementById('js-close-profile-viewer')
    const downloadButton = document.getElementById('js-download-profile-image')

    const showModal = (src) => {
        if (modalImage && src) {
            modalImage.src = src
            modal.classList.remove('hidden')
            modal.classList.add('flex')
        }
    }

    const hideModal = () => {
        modal.classList.add('hidden')
        modal.classList.remove('flex')
    }
    
    // event listener for download button
    if (downloadButton) {
        downloadButton.addEventListener('click', () => {
            const imgSrc = modalImage.src
            if (imgSrc) {
                const a = document.createElement('a')
                a.href = imgSrc
                a.download = 'profile_image.jpg' // default image filename
                document.body.appendChild(a)
                a.click()
                document.body.removeChild(a)
            }
        })
    }

    // event listener for profile image trigger
    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            let imgSrc = null
            if (trigger.tagName === 'IMG') {
                imgSrc = trigger.src
            } else {
                const img = trigger.querySelector('img')
                if (img) {
                    imgSrc = img.src
                }
            }

            if (imgSrc) {
                showModal(imgSrc)
            }
        })
    })

    // event listener for close button
    if (closeButton) {
        closeButton.addEventListener('click', hideModal)
    }

    // event listener for modal click
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            hideModal()
        }
    })

    // event listener for escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal()
        }
    })
}

// -----------------------------
// IP ADDRESS VIEW TOGGLE INITIALIZATION
// -----------------------------
function initIpToggle(ipShort, ipFull) {
    if (!ipShort || !ipFull) {
        return
    }

    const toggle = () => {
        ipShort.classList.toggle('hidden')
        ipFull.classList.toggle('hidden')
    }

    // event listener for ip short toggle
    ipShort.addEventListener('click', toggle)
    ipFull.addEventListener('click', toggle)
}

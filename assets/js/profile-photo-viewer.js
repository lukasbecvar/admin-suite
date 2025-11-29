/** profile photo view component */
document.addEventListener('DOMContentLoaded', () => {
    initProfilePhotoViewer()
    initIpToggle(
        document.getElementById('ip-short'),
        document.getElementById('ip-full')
    )
})

function initProfilePhotoViewer() {
    const modal = document.getElementById('profile-viewer-modal')
    if (!modal) {
        return
    }

    const closeButton = document.getElementById('js-close-profile-viewer')
    const modalImage = document.getElementById('js-profile-viewer-image')
    const triggers = document.querySelectorAll('.js-open-profile-viewer')

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

    const downloadButton = document.getElementById('js-download-profile-image')

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

    if (closeButton) {
        closeButton.addEventListener('click', hideModal)
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            hideModal()
        }
    })

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal()
        }
    })
}

function initIpToggle(ipShort, ipFull) {
    if (!ipShort || !ipFull) {
        return
    }

    const toggle = () => {
        ipShort.classList.toggle('hidden')
        ipFull.classList.toggle('hidden')
    }

    ipShort.addEventListener('click', toggle)
    ipFull.addEventListener('click', toggle)
}

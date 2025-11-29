/** profile photo view component */
document.addEventListener('DOMContentLoaded', () => {
    const modals = [
        initProfileModal({
            modal: document.getElementById('profile-modal'),
            openTrigger: document.getElementById('open-profile-modal'),
            closeTrigger: document.getElementById('close-profile-modal')
        }),
        initProfileModal({
            modal: document.getElementById('user-profile-modal'),
            openTrigger: document.getElementById('user-profile-photo'),
            closeTrigger: document.getElementById('close-user-profile-modal')
        })
    ].filter(Boolean)

    initIpToggle(
        document.getElementById('ip-short'),
        document.getElementById('ip-full')
    )

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            modals.forEach((modal) => hideModal(modal))
        }
    })
})

function initProfileModal({ modal, openTrigger, closeTrigger }) {
    if (!modal) {
        return null
    }

    if (openTrigger) {
        openTrigger.addEventListener('click', () => showModal(modal))
    }

    if (closeTrigger) {
        closeTrigger.addEventListener('click', () => hideModal(modal))
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            hideModal(modal)
        }
    })

    return modal
}

function showModal(modal) {
    modal.classList.remove('hidden')
    modal.classList.add('flex')
}

function hideModal(modal) {
    modal.classList.remove('flex')
    modal.classList.add('hidden')
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

/** push notifications settings component */
document.addEventListener('DOMContentLoaded', async () => {
    let publicKey = null
    
    // get site elements
    const statusElement = document.getElementById('push-status')
    const subscribeButton = document.getElementById('subscribe-btn')

    // resubscribe to push notifications
    subscribeButton.addEventListener("click", async () => {
        console.log('Subscription button clicked.')
        if (Notification.permission === 'denied') {
            alert('Push notifications are disabled in your browser settings. Please enable them manually.')
            return
        }
        try {
            const permission = await Notification.requestPermission()
    
            // check if permission is granted
            if (permission !== 'granted') {
                statusElement.textContent = 'Push notifications permission denied'
                return
            }

            // subscribe to push notifications
            const registration = await navigator.serviceWorker.ready
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey),
            })

            // send subscription to server
            const response = await fetch('/api/notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(subscription),
            })

            const data = await response.json()

            // check if subscription was successful
            if (data.status === 'success') {
                statusElement.textContent = 'Successfully subscribed!'
                subscribeButton.classList.add('hidden')
            } else {
                statusElement.textContent = 'Subscription failed'
            }
        } catch (error) {
            console.error('Subscription error: ', error)
            statusElement.textContent = 'Subscription failed'
        }
    })

    // check subscription status
    try {
        // get VAPID public key
        const keyResponse = await fetch('/api/notifications/public-key')
        const keyData = await keyResponse.json()

        // check if VAPID public key is loaded
        if (keyData.status === 'success') {
            publicKey = keyData.vapid_public_key
        } else {
            console.error('Failed to load VAPID public key: ', keyData.message)
            return
        }

        // check if notifications are disabled
        if (Notification.permission === "denied") {
            statusElement.textContent = 'Push notifications are disabled. Please enable them in your browser settings.'
            subscribeButton.classList.remove('hidden')
            return
        }

        // check if service worker is registered
        const registration = await navigator.serviceWorker.ready
        const subscription = await registration.pushManager.getSubscription()
        
        if (!subscription) {
            statusElement.textContent = 'You are not subscribed to push notifications'
            subscribeButton.classList.remove('hidden')
            return
        }

        const endpoint = subscription.endpoint

        // check if subscription registered on the server
        const response = await fetch('/api/notifications/check-push-subscription', { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ endpoint }),
        })

        const data = await response.json()
        
        // check if subscription is registered on the server
        if (data.status == 'success') {
            statusElement.textContent = data.message
        } else {
            statusElement.textContent = 'You are not subscribed to push notifications'
            subscribeButton.classList.remove('hidden')
        }
    } catch (error) {
        statusElement.textContent = 'Error while checking'
        subscribeButton.classList.remove('hidden')
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/')
        const rawData = window.atob(base64)
        return new Uint8Array([...rawData].map(char => char.charCodeAt(0)))
    }
})

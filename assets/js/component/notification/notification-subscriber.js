/** push notifications subscriber functionality */
document.addEventListener('DOMContentLoaded', function() {
    // check if notifications are enabled on backend
    async function checkNotificationsEnabled() {
        const response = await fetch('/api/notifications/enabled')
        const data = await response.json()

        // check if request was successful
        if (data.status === 'success' && data.enabled == 'true') {
            return true
        }
        return false
    }

    // subscribe user to push notifications
    async function subscribeUser() {
        const notificationsEnabled = await checkNotificationsEnabled()
        // check if notifications future enabled
        if (!notificationsEnabled) {
            return
        }

        // check if notifications are allowed
        if (Notification.permission === 'denied') {
            return
        }

        if (Notification.permission === 'default') {
            // request permission to send notifications
            const permission = await Notification.requestPermission()
            if (permission !== 'granted') {
                return
            }
        }

        // check if user is subscribed
        const registration = await navigator.serviceWorker.ready
        const existingSubscription = await registration.pushManager.getSubscription()

        if (existingSubscription === null) {
            // get public VAPID key from admin suite api
            const response = await fetch('/api/notifications/public-key')
            const data = await response.json()

            // check if request was successful
            if (data.status === 'success') {
                const vapidPublicKey = data.vapid_public_key

                // convert VAPID public key to Uint8Array
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey)

                // register subscriber
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                })

                // send subscription to admin suite api
                await sendSubscriptionToServer(subscription)
            } else if (data.status == 'disabled') {
                console.log('Push notifications is disabled')
            } else {
                console.error('Failed to retrieve VAPID public key:', data)
            }
        }
    }

    // send subscription to server
    async function sendSubscriptionToServer(subscription) {
        const response = await fetch('/api/notifications/subscribe', {
            method: 'POST',
            body: JSON.stringify(subscription),
            headers: {
                'Content-Type': 'application/json'
            }
        })

        // check if request was successful
        if (!response.ok) {
            console.error('Failed to send subscription to server:', response.statusText)
        }
    }

    // convert Base64 URL to Uint8Array
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4)
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
        const rawData = window.atob(base64)
        return new Uint8Array([...rawData].map(char => char.charCodeAt(0)))
    }

    // register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).then(() => {
            subscribeUser()
        }).catch((error) => {
            console.error('Service Worker registration failed:', error)
        })
    }
})

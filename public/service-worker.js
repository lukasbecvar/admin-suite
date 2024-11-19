/** web push notifications api listener */
self.addEventListener('push', function(event) {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/assets/images/warning.png',
        badge: '/assets/images/favicon.png'
    };
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

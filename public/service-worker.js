
self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Turnier-Benachrichtigung';
    const options = {
        body: data.body || 'Du bist an der Reihe!',
        data: {
            url: data.url || '/',
            tourneyId: data.tourneyId || null,
            tourneyName: data.tourneyName || null
        }
    };
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';
    event.waitUntil(
        clients.openWindow(url)
    );
});

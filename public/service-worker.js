self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (error) {
        data = { title: 'Vozi Miško', body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(data.title || 'Vozi Miško', {
        body: data.body || '',
        icon: data.icon || '/assets/images/app-icon-192.png',
        badge: data.badge || '/assets/images/favicon-32.png',
        tag: data.tag || undefined,
        renotify: true,
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200],
        data: { url: data.url || '/notifications' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = new URL(event.notification.data?.url || '/notifications', self.location.origin).href;

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
        const existing = windowClients.find((client) => client.url === targetUrl);
        return existing ? existing.focus() : clients.openWindow(targetUrl);
    }));
});

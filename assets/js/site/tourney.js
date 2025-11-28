import '../../css/modules/tourney.scss';

// Hash navigation for accordion
if (location.hash !== null && location.hash !== "") {
    document.querySelector(location.hash)?.querySelector('.collapse')?.classList.add('show');
}

// Push Notification Logic
const pushBtn = document.getElementById('push-subscribe-btn');

if (pushBtn) {
    const publicVapidKey = pushBtn.dataset.vapidKey;

    if (!publicVapidKey) {
        pushBtn.style.display = 'none';
    } else {
        function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function subscribeUserToPush() {
        if (!('serviceWorker' in navigator)) {
            alert('Service Worker werden nicht unterstützt.');
            return;
        }
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
            });
            const response = await fetch('/api/push-subscription/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscription)
            });
            const result = await response.json();
            if (response.ok) {
                if (result.message === 'Already subscribed') {
                    alert('Benachrichtigungen bereits abonniert!');
                } else {
                    alert('Benachrichtigungen aktiviert!');
                }
            } else {
                alert('Fehler beim Abonnieren.');
            }
        } catch (e) {
            alert('Fehler: ' + e.message);
        }
    }

        pushBtn.addEventListener('click', subscribeUserToPush);
    }
}

// CAA Desk service worker. Deliberately does NOT cache anything: the Desk shows
// live client data behind a login, so every request goes to the network.
// It exists so browsers treat the Desk as an installable app.
self.addEventListener("install", () => self.skipWaiting());
self.addEventListener("activate", (e) => e.waitUntil(self.clients.claim()));
self.addEventListener("fetch", () => {});

const CACHE_NAME = 'vuelos-web-cache-v1';
const ASSETS = [
    './',
    './index.html',
    './styles.css',
    './app.js',
    './manifest.json'
];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((k) => k !== CACHE_NAME ? caches.delete(k) : null))));
});

self.addEventListener('fetch', (e) => {
    if (e.request.url.includes('.php') || e.request.method !== 'GET') return;
    e.respondWith(caches.match(e.request).then((res) => res || fetch(e.request)));
});
const cacheName = 'offline';

const filesToCache = [
  '/',
  '/app/dashboard/',
  '/app/products/',
  '/offline.html'
];

async function preLoad() {
  const cache = await caches.open(cacheName);
  await cache.addAll(filesToCache);
}

self.addEventListener('install', function (event) {
  event.waitUntil(preLoad());
});

async function checkResponse(request) {
  try {
    const response = await fetch(request);
    if (response && response.status !== 404) {
      if (request.method === 'GET' && request.url.startsWith('http')) {
        const cache = await caches.open(cacheName);
        await cache.put(request, response.clone());
      }
      return response;
    }
  } catch (error) {
    console.error('Error fetching from network:', error);
  }
  return caches.match(request).then(function (cachedResponse) {
    return cachedResponse || caches.match('offline.html');
  });
}

async function addToCache(request) {
  if (request.method !== 'GET' || !request.url.startsWith('http')) return;
  const cache = await caches.open(cacheName);
  const response = await fetch(request);
  if (response && response.status !== 404) {
    await cache.put(request, response.clone());
  }
}

async function returnFromCache(request) {
  const cache = await caches.open(cacheName);
  const cachedResponse = await cache.match(request);
  return cachedResponse || caches.match('offline.html');
}

async function loadDataFromIndexedDBProducts() {
  const indexedDBHelper = new IndexedDBHelper('ProductsDBs', 1, 'products');
  const data = await indexedDBHelper.getData();
  if (data && data.length > 0) {
    return new Response(JSON.stringify(data), {
      headers: {
        'Content-Type': 'application/json'
      }
    });
  } else {
    return new Response(null, {
      status: 500,
      statusText: 'Internal Server Error'
    });
  }
}

self.addEventListener('fetch', function (event) {
  const requestUrl = new URL(event.request.url);

  if (!['http:', 'https:'].includes(requestUrl.protocol)) {
    return;
  }

  // Check if the intercepted request URL matches the desired URL
  if (requestUrl.pathname === '/app/products/list' && !navigator.onLine) {
    event.respondWith(loadDataFromIndexedDBProducts());
  } else {
    // Rest of your code for caching and responding with cached data
    event.respondWith(checkResponse(event.request).catch(function () {
      return returnFromCache(event.request);
    }));

    if (event.request.method === 'GET') {
      event.waitUntil(addToCache(event.request));
    }
  }
});


self.addEventListener('activate', function (event) {
  console.log('Service Worker Activated');

  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames.filter(function (name) {
          return name !== cacheName;
        }).map(function (name) {
          return caches.delete(name);
        })
      );
    })
  );
});

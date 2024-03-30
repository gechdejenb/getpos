const cacheName = 'your-cache-name';

const filesToCache = [
  '/',
  '/app/dashboard/',
  '/app/products/list',
  '/app/purchases/list',
  '/app/sales/list',
  '/app/products/store',
  '/app/purchases/store',
  '/app/sales/store',
  '/app/adjustments/list',
  '/app/adjustments/store',
  '/app/quotations/list',
  '/app/quotations/store',
  '/app/sale_return/list',
  '/app/sale_return/list',
  '/app/transfers/list',
  '/app/transfers/store',
  '/app/reports/customers_report',
  '/app/reports/detail_customer/2',
  '/app/pos',
  '/offline.html'
];

async function preLoad() {
  const cache = await caches.open(cacheName);
  await cache.addAll(filesToCache);
}

self.addEventListener('install', function (event) {
  event.waitUntil(preLoad());
});

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

  // Check if the intercepted request URL matches the desired URL
  if (requestUrl.pathname === '/app/products/list' && !navigator.onLine) {
    event.respondWith(loadDataFromIndexedDBProducts());
  } else {
    event.respondWith(checkResponse(event.request));
  }
});

async function checkResponse(request) {
  try {
    const response = await fetch(request);
    if (response && response.status !== 404 && request.method === 'GET') {
      const cache = await caches.open(cacheName);
      await cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.error('Error fetching from network:', error);
    return caches.match(request).then(function (cachedResponse) {
      return cachedResponse || caches.match('offline.html');
    });
  }
}

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

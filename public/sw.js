const cacheName = 'offline';

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

// IndexedDB Helper Class
class IndexedDBHelper {
  constructor(dbName, dbVersion) {
    this.dbName = dbName;
    this.dbVersion = dbVersion;
    this.dbPromise = this.openDatabase();
  }

  async openDatabase() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);
      request.onupgradeneeded = event => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains('products')) {
          db.createObjectStore('products', { keyPath: 'id', autoIncrement: true });
        }
      };
      request.onsuccess = event => resolve(event.target.result);
      request.onerror = event => reject(event.target.error);
    });
  }

  async getData(storeName) {
    const db = await this.dbPromise;
    return new Promise((resolve, reject) => {
      const transaction = db.transaction(storeName, 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }
}

async function loadDataFromIndexedDBProducts() {
  const indexedDBHelper = new IndexedDBHelper('ProductsDBs', 2);
  const data = await indexedDBHelper.getData('products');

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

  // Serve from IndexedDB if offline and requesting product list
  if (requestUrl.pathname === '/app/products/list' && !navigator.onLine) {
    event.respondWith(loadDataFromIndexedDBProducts());
  } else {
    event.respondWith(checkResponse(event.request));
  }
});

// Check response function
async function checkResponse(request) {
  // Skip caching for requests from chrome-extension
  if (request.url.startsWith('chrome-extension://')) {
    return fetch(request);
  }

  // Skip caching for non-GET requests
  if (request.method !== 'GET') {
    return fetch(request);
  }

  try {
    const response = await fetch(request);

    // Cache successful GET responses that are not 404
    if (response && response.status !== 404) {
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
class IndexedDBHelper {
    constructor(dbName, storeName) {
        this.dbName = dbName;
        this.storeName = storeName;
        this.db = null;
    }

    async openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, 1);

            request.onupgradeneeded = (event) => {
                this.db = event.target.result;
                this.db.createObjectStore(this.storeName, {
                    keyPath: 'id'
                });
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    async saveProducts(products) {
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        const objectStore = transaction.objectStore(this.storeName);

        products.forEach((product) => {
            objectStore.put(product);
        });

        await transaction.complete;
    }

    async getProducts() {
        const transaction = this.db.transaction([this.storeName], 'readonly');
        const objectStore = transaction.objectStore(this.storeName);
        const products = [];

        return new Promise((resolve, reject) => {
            objectStore.openCursor().onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    products.push(cursor.value);
                    cursor.continue();
                } else {
                    resolve(products);
                }
            };

            transaction.oncomplete = () => {
                resolve(products);
            };

            transaction.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }
}

export default IndexedDBHelper;
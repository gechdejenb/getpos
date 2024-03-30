class IndexedDBHelper {
    constructor(dbName, version, storeName) {
        this.dbName = dbName;
        this.version = version;
        this.storeName = storeName;
        this.db = null;
    }

    async openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onupgradeneeded = (event) => {
                this.db = event.target.result;
                this.db.createObjectStore(this.storeName, {
                    keyPath: 'id',
                    autoIncrement: true
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

    async saveData(productData) {
        try {
            let db = await this.openDatabase();
            return new Promise(resolve => {
                let trans = db.transaction(['products'], 'readwrite');
                trans.oncomplete = () => {
                    resolve();
                };
                let store = trans.objectStore('products');
                store.put(productData);
            });
        } catch (error) {
            throw new Error('Error saving data to IndexedDB: ' + error.message);
        }
    }
    
    

    async getData() {
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

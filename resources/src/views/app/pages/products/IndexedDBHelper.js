class IndexedDBHelper {
    static dbInstance = null; // Singleton instance

    constructor(dbName, version, storeName) {
        if (IndexedDBHelper.dbInstance) {
            return IndexedDBHelper.dbInstance; // Return the existing instance if already created
        }
        this.dbName = dbName;
        this.version = version;
        this.storeName = storeName;
        IndexedDBHelper.dbInstance = this;
    }

    async openDatabase() {
        if (this.db) {
            return this.db; // Return the existing DB connection if already open
        }
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error("IndexedDB error:", event.target.error);
                reject(event.target.error);
            };
        });
    }

    async saveData(productData) {
        await this.openDatabase(); // Ensure the database is open
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.put(productData); // Use put to add or update

            request.onsuccess = () => resolve();
            request.onerror = (event) => {
                console.error("Save data error:", event.target.error);
                reject(event.target.error);
            };
        });
    }

    async getData() {
        await this.openDatabase(); // Ensure the database is open
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.getAll(); // Simpler than using a cursor for this case

            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => {
                console.error("Get data error:", event.target.error);
                reject(event.target.error);
            };
        });
    }
}

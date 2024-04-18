import { openDB } from 'idb';

class IndexedDBHelper {
    constructor(dbName, dbVersion, storeName) {
        this.dbName = dbName;
        this.dbVersion = dbVersion;
        this.storeName = storeName;
        this.dbPromise = openDB(this.dbName, this.dbVersion, {
            upgrade: (db, oldVersion, newVersion, transaction) => {
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                }
            }
        });
    }

    async openDatabase() {
        return this.dbPromise;
    }

    async saveData(productData) {
        try {
            if (!Array.isArray(productData)) {
                productData = [productData];  // Ensure productData is an array
            }

            let db = await this.openDatabase();
            let tx = db.transaction(this.storeName, 'readwrite');
            let store = tx.objectStore(this.storeName);
            console.log('product product data: .',productData)


            productData.forEach(data => {
                console.log('product data: .',data)
                store.add(data);
            });

            await tx.complete;
            console.log('Product data saved successfully');
        } catch (error) {
            console.error('Error saving data to IndexedDB:', error);
            throw new Error('Error saving data to IndexedDB: ' + error.message);
        }
    }

    async getData() {
        try {
            const db = await this.openDatabase();
            const tx = db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            return store.getAll();
        } catch (error) {
            console.error('Error getting data from IndexedDB:', error);
            throw error;
        }
    }
}

export default IndexedDBHelper;

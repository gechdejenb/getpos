import { openDB } from 'idb';

class IndexedDBHelper {
  constructor(dbName, dbVersion, storeName) {
    this.dbName = dbName;
    this.dbVersion = dbVersion;
    this.storeName = storeName;
    this.dbPromise = openDB(this.dbName, this.dbVersion, {
      upgrade: (db) => {
        if (!db.objectStoreNames.contains(this.storeName)) {
          db.createObjectStore(this.storeName, {
            keyPath: 'id',
            autoIncrement: true
          });
        }
      }
    });
  }

  async openDatabase() {
    return this.dbPromise;
  }

  async saveData(data) {
    try {
      const db = await this.openDatabase();
      const tx = db.transaction(this.storeName, 'readwrite');
      const store = tx.objectStore(this.storeName);

      for (const item of data) {
        await store.put(item);
      }

      await tx.done;
      console.log('Data saved successfully!');
    } catch (error) {
      console.error('Error saving data:', error);
      throw error;
    }
  }

  async getData() {
    try {
      const db = await this.openDatabase();
      const tx = db.transaction(this.storeName, 'readonly');
      const store = tx.objectStore(this.storeName);
      return await store.getAll();
    } catch (error) {
      console.error('Error getting data:', error);
      throw error;
    }
  }
}

export default IndexedDBHelper;

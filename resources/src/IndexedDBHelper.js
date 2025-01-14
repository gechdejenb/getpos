// import { openDB } from 'idb';

// class IndexedDBHelper {
//   constructor(dbName, dbVersion, storeName) {
//     this.dbName = dbName;
//     this.dbVersion = dbVersion;
//     this.storeName = storeName;
//     this.dbPromise = openDB(this.dbName, this.dbVersion, {
//       upgrade: (db) => {
//         if (!db.objectStoreNames.contains(this.storeName)) {
//           db.createObjectStore(this.storeName, {
//             keyPath: 'id',
//             autoIncrement: true
//           });
//         }
//       }
//     });
//   }

//   async openDatabase() {
//     return this.dbPromise;
//   }

//   async saveData(data) {
//     try {
//       const db = await this.openDatabase();
//       const tx = db.transaction(this.storeName, 'readwrite');
//       const store = tx.objectStore(this.storeName);

//       for (const item of data) {
//         await store.put(item);
//       }

//       await tx.done;
//       console.log('Data saved successfully!');
//     } catch (error) {
//       console.error('Error saving data:', error);
//       throw error;
//     }
//   }

//   async getData() {
//     try {
//       const db = await this.openDatabase();
//       const tx = db.transaction(this.storeName, 'readonly');
//       const store = tx.objectStore(this.storeName);
//       return await store.getAll();
//     } catch (error) {
//       console.error('Error getting data:', error);
//       throw error;
//     }
//   }
// }

// export default IndexedDBHelper;
// IndexedDBHelper.js
import { openDB } from 'idb';

class IndexedDBHelper {
  // constructor(dbName, dbVersion) {
  //   this.dbName = dbName;
  //   this.dbVersion = dbVersion;
  //   this.dbPromise = openDB(this.dbName, this.dbVersion, {
  //     upgrade: (db) => {
  //      { console.log('Upgrading database...');
  //       if (!db.objectStoreNames.contains('products')) {
  //         db.createObjectStore('products', { keyPath: 'id', autoIncrement: true });
  //       }
  //       if (!db.objectStoreNames.contains('categories')) {
  //         db.createObjectStore('categories', { keyPath: 'id', autoIncrement: true });
  //       }
  //       if (!db.objectStoreNames.contains('units')) {
  //         db.createObjectStore('units', { keyPath: 'id', autoIncrement: true });
  //       }
  //       if (!db.objectStoreNames.contains('brands')) {
  //         db.createObjectStore('brands', { keyPath: 'id', autoIncrement: true });
  //       }
  //       if (!db.objectStoreNames.contains('status')) {
  //         db.createObjectStore('status', { keyPath: 'id' });
  //       }}
  //     }
  //   });
  // }
  constructor(dbName, dbVersion) {
    this.dbName = dbName;
    this.dbVersion = dbVersion;
    this.dbPromise = openDB(this.dbName, this.dbVersion, {
      upgrade: (db, oldVersion, newVersion) => {
        console.log('Upgrading database...');
        if (oldVersion < 2) {
          if (!db.objectStoreNames.contains('products')) {
            db.createObjectStore('products', { keyPath: 'id', autoIncrement: true });
          }
          if (!db.objectStoreNames.contains('categories')) {
            db.createObjectStore('categories', { keyPath: 'id', autoIncrement: true });
          }
          if (!db.objectStoreNames.contains('units')) {
            db.createObjectStore('units', { keyPath: 'id', autoIncrement: true });
          }
          if (!db.objectStoreNames.contains('brands')) {
            db.createObjectStore('brands', { keyPath: 'id', autoIncrement: true });
          }
          if (!db.objectStoreNames.contains('status')) {
            db.createObjectStore('status', { keyPath: 'id' });
          }
        }
      }
    });
  }

  async openDatabase() {
    return this.dbPromise;
  }

  // async saveData(storeName, data) {
  //   try {
  //     const db = await this.openDatabase();
  //     const transaction = db.transaction(storeName, 'readwrite');
  //     const objectStore = transaction.objectStore(storeName);

  //     if (!objectStore) {
  //       throw new Error(`Object store ${storeName} does not exist`);
  //     }

  //     data.forEach(item => {
  //       objectStore.add(item);
  //     });

  //     await transaction.complete;
  //     console.log('Data saved successfully');
  //   } catch (error) {
  //     console.error('Error saving data:', error);
  //   }
  // }

  async saveData(storeName, data) {
    try {
      const db = await this.openDatabase();
      const transaction = db.transaction(storeName, 'readwrite');
      const objectStore = transaction.objectStore(storeName);
  
      if (!objectStore) {
        throw new Error(`Object store ${storeName} does not exist`);
      }
  
      // Add an id property to each object in the data array if it does not already exist
      data.forEach((item, index) => {
        if (!item.id) {
          item.id = index + 1;
        }
      });
  
      data.forEach(item => {
        objectStore.add(item);
      });
  
      await transaction.complete;
      console.log('Data saved successfully');
    } catch (error) {
      console.error('Error saving data:', error);
    }
  }
  async saveDataStatus(storeName, data) {
    try {
      const db = await this.openDatabase();
      const tx = db.transaction(storeName, 'readwrite');
      const store = tx.objectStore(storeName);
      await store.put(data); // Use put to update or add
      await tx.done;
      console.log('Data saved successfully!');
    } catch (error) {
      console.error('Error saving data:', error);
    }
  }

  async getData(storeName) {
    try {
      const db = await this.openDatabase();
      const tx = db.transaction(storeName, 'readonly');
      const store = tx.objectStore(storeName);
      const data = await store.getAll();
      console.log('Data:', data); // Add this line
      return data;
    } catch (error) {
      console.error('Error getting data:', error);
    }
  }
 
  async removeData(storeName, id) {
    try {
        const db = await this.openDatabase();
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const deleteRequest = store.delete(id);

        await new Promise((resolve, reject) => {
            deleteRequest.onsuccess = () => {
                console.log(`Product with ID ${id} deleted successfully`);
                resolve();
            };
            deleteRequest.onerror = () => {
                console.error(`Error deleting product with ID ${id}`);
                reject(deleteRequest.error);
            };
        });

        await tx.done;
    } catch (error) {
        console.error('Error removing data:', error);
    }
}

  // async getData(storeName) {
  //   try {
  //     const db = await this.openDatabase();
  //     const transaction = db.transaction(storeName, 'readonly');
  //     const objectStore = transaction.objectStore(storeName);

  //     if (!objectStore) {
  //       throw new Error(`Object store ${storeName} does not exist`);
  //     }

  //     const request = objectStore.getAll();
  //     const data = await new Promise((resolve, reject) => {
  //       request.onsuccess = () => resolve(request.result);
  //       request.onerror = () => reject(request.error);
  //     });

  //     return data;
  //   } catch (error) {
  //     console.error('Error getting data:', error);
  //   }
  // }
}

export default IndexedDBHelper;
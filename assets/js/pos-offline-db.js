/**
 * =========================================================================================
 * 📦 GESTOR DE ALMACENAMIENTO LOCAL E INDEXEDDB PARA MODO OFFLINE (POS)
 * =========================================================================================
 * Maneja el catálogo local de productos, códigos alternativos, promociones y la cola
 * persistente de ventas realizadas durante cortes de internet o luz en el negocio.
 */

class PosOfflineDB {
  constructor() {
    this.dbName = 'MinimarketPosDB';
    this.dbVersion = 1;
    this.db = null;
  }

  /**
   * Inicializa la base de datos IndexedDB y crea los almacenes de objetos necesarios
   */
  async init() {
    if (this.db) return this.db;

    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // 1. Catálogo de productos
        if (!db.objectStoreNames.contains('productos')) {
          const storeProd = db.createObjectStore('productos', { keyPath: 'ProductoID' });
          storeProd.createIndex('CodigoBarras', 'CodigoBarras', { unique: false });
          storeProd.createIndex('Nombre', 'Nombre', { unique: false });
          storeProd.createIndex('CodigoPLU', 'CodigoPLU', { unique: false });
        }

        // 2. Códigos alternativos (packs, sixpacks)
        if (!db.objectStoreNames.contains('codigos_alt')) {
          const storeAlt = db.createObjectStore('codigos_alt', { keyPath: 'CodigoID' });
          storeAlt.createIndex('CodigoBarras', 'CodigoBarras', { unique: false });
          storeAlt.createIndex('ProductoID', 'ProductoID', { unique: false });
        }

        // 3. Promociones vigentes
        if (!db.objectStoreNames.contains('promociones')) {
          const storePromo = db.createObjectStore('promociones', { keyPath: 'PromocionID' });
          storePromo.createIndex('ProductoID', 'ProductoID', { unique: false });
        }

        // 4. Clientes
        if (!db.objectStoreNames.contains('clientes')) {
          const storeCli = db.createObjectStore('clientes', { keyPath: 'ClienteID' });
          storeCli.createIndex('Nombre', 'Nombre', { unique: false });
        }

        // 5. Cola de ventas pendientes de sincronizar
        if (!db.objectStoreNames.contains('ventas_pendientes')) {
          const storeVentas = db.createObjectStore('ventas_pendientes', { keyPath: 'id_temporal' });
          storeVentas.createIndex('fecha_creacion', 'fecha_creacion', { unique: false });
        }

        // 6. Configuraciones y metadatos del local
        if (!db.objectStoreNames.contains('meta')) {
          db.createObjectStore('meta', { keyPath: 'clave' });
        }
      };

      request.onsuccess = (event) => {
        this.db = event.target.result;
        resolve(this.db);
      };

      request.onerror = (event) => {
        console.error('[IndexedDB] Error al abrir la base de datos:', event.target.error);
        reject(event.target.error);
      };
    });
  }

  /**
   * Guarda el catálogo completo recibido del servidor en IndexedDB
   */
  async guardarCatalogo(data) {
    await this.init();
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(
        ['productos', 'codigos_alt', 'promociones', 'clientes', 'meta'],
        'readwrite'
      );

      // Limpiar datos previos
      const storeProd = tx.objectStore('productos');
      const storeAlt = tx.objectStore('codigos_alt');
      const storePromo = tx.objectStore('promociones');
      const storeCli = tx.objectStore('clientes');
      const storeMeta = tx.objectStore('meta');

      storeProd.clear();
      storeAlt.clear();
      storePromo.clear();
      storeCli.clear();

      // Guardar productos
      if (Array.isArray(data.productos)) {
        for (const p of data.productos) {
          storeProd.put({
            ProductoID: parseInt(p.ProductoID),
            CodigoBarras: p.CodigoBarras ? String(p.CodigoBarras).trim() : '',
            Nombre: p.Nombre,
            PrecioVenta: parseInt(p.PrecioVenta) || 0,
            CostoCompra: parseInt(p.CostoCompra) || 0,
            Stock: parseFloat(p.Stock) || 0,
            StockMinimo: parseFloat(p.StockMinimo) || 0,
            UnidadMedida: p.UnidadMedida || 'UN',
            ControlaStock: p.ControlaStock !== undefined ? parseInt(p.ControlaStock) : 1,
            EsPesable: !!(parseInt(p.EsPesable) === 1 || p.EsPesable === true),
            CodigoPLU: p.CodigoPLU ? String(p.CodigoPLU).trim() : null,
            CategoriaID: p.CategoriaID ? parseInt(p.CategoriaID) : null,
            CategoriaNombre: p.CategoriaNombre || 'General'
          });
        }
      }

      // Guardar códigos alternativos
      if (Array.isArray(data.codigos_alt)) {
        for (const alt of data.codigos_alt) {
          storeAlt.put({
            CodigoID: parseInt(alt.CodigoID),
            ProductoID: parseInt(alt.ProductoID),
            CodigoBarras: String(alt.CodigoBarras).trim(),
            Descripcion: alt.Descripcion || '',
            Cantidad: parseFloat(alt.Cantidad) || 1,
            PrecioVenta: alt.PrecioVenta !== null && alt.PrecioVenta !== undefined ? parseInt(alt.PrecioVenta) : null
          });
        }
      }

      // Guardar promociones
      if (Array.isArray(data.promociones)) {
        for (const pr of data.promociones) {
          storePromo.put({
            PromocionID: parseInt(pr.PromocionID),
            ProductoID: parseInt(pr.ProductoID),
            Tipo: pr.Tipo,
            CantidadMinima: parseFloat(pr.CantidadMinima) || 0,
            DescuentoPorcentaje: parseFloat(pr.DescuentoPorcentaje) || 0,
            PrecioOferta: pr.PrecioOferta !== null ? parseInt(pr.PrecioOferta) : null
          });
        }
      }

      // Guardar clientes
      if (Array.isArray(data.clientes)) {
        for (const c of data.clientes) {
          storeCli.put(c);
        }
      }

      // Guardar configuraciones y timestamp
      storeMeta.put({ clave: 'ultima_sincronizacion', valor: data.timestamp || Date.now() });
      if (data.configuracion) {
        storeMeta.put({ clave: 'configuracion', valor: data.configuracion });
      }

      tx.oncomplete = () => {
        console.log(`[IndexedDB] Catálogo offline actualizado: ${data.productos?.length || 0} productos guardados.`);
        resolve(true);
      };

      tx.onerror = (e) => {
        console.error('[IndexedDB] Error guardando catálogo:', e);
        reject(e);
      };
    });
  }

  /**
   * Búsqueda de productos en IndexedDB (código de barras, PLU, código alternativo o texto)
   */
  async buscarProductoOffline(query) {
    await this.init();
    const q = String(query).trim().toLowerCase();
    if (!q) return [];

    return new Promise(async (resolve) => {
      const tx = this.db.transaction(['productos', 'codigos_alt', 'promociones'], 'readonly');
      const storeProd = tx.objectStore('productos');
      const storeAlt = tx.objectStore('codigos_alt');
      const storePromo = tx.objectStore('promociones');

      // Traer todos los productos para búsqueda en memoria (muy rápido para catálogos de hasta 15.000 ítems)
      const reqProd = storeProd.getAll();
      const reqAlt = storeAlt.getAll();
      const reqPromo = storePromo.getAll();

      let prods = [];
      let alts = [];
      let promos = [];

      reqProd.onsuccess = () => { prods = reqProd.result || []; };
      reqAlt.onsuccess = () => { alts = reqAlt.result || []; };
      reqPromo.onsuccess = () => { promos = reqPromo.result || []; };

      tx.oncomplete = () => {
        const promoMap = new Map();
        for (const pr of promos) {
          promoMap.set(pr.ProductoID, pr);
        }

        // Mapeo de códigos alternativos por código de barra
        const altByCode = new Map();
        for (const a of alts) {
          altByCode.set(a.CodigoBarras.toLowerCase(), a);
        }

        const resultados = [];

        // 1. Coincidencia exacta por código alternativo
        if (altByCode.has(q)) {
          const matchAlt = altByCode.get(q);
          const p = prods.find(item => item.ProductoID === matchAlt.ProductoID);
          if (p) {
            const promo = promoMap.get(p.ProductoID);
            resultados.push(this._formatearResultado(p, promo, matchAlt));
          }
        }

        // 2. Coincidencias en productos principales
        for (const p of prods) {
          const cod = p.CodigoBarras.toLowerCase();
          const plu = p.CodigoPLU ? String(p.CodigoPLU).toLowerCase() : '';
          const nom = p.Nombre.toLowerCase();

          // Evitar duplicar si ya fue agregado por código alternativo
          if (resultados.some(r => r.ProductoID === p.ProductoID)) continue;

          if (cod === q || plu === q || nom.includes(q)) {
            const promo = promoMap.get(p.ProductoID);
            resultados.push(this._formatearResultado(p, promo, null));
          }

          if (resultados.length >= 40) break;
        }

        resolve(resultados);
      };
    });
  }

  _formatearResultado(p, promo, altMatch) {
    return {
      ProductoID: p.ProductoID,
      CodigoBarras: p.CodigoBarras,
      Nombre: p.Nombre,
      PrecioVenta: p.PrecioVenta,
      CostoCompra: p.CostoCompra,
      Stock: p.Stock,
      StockMinimo: p.StockMinimo,
      UnidadMedida: p.UnidadMedida,
      ControlaStock: p.ControlaStock !== undefined ? parseInt(p.ControlaStock) : 1,
      EsPesable: p.EsPesable ? 1 : 0,
      CodigoPLU: p.CodigoPLU,
      CategoriaID: p.CategoriaID,
      CategoriaNombre: p.CategoriaNombre,
      PromocionID: promo ? promo.PromocionID : null,
      PromoTipo: promo ? promo.Tipo : null,
      PromoCantMin: promo ? promo.CantidadMinima : null,
      PromoDescPorc: promo ? promo.DescuentoPorcentaje : null,
      PromoPrecioOf: promo ? promo.PrecioOferta : null,
      CodigoAltMatch: altMatch ? altMatch.CodigoBarras : null,
      AltDescripcion: altMatch ? altMatch.Descripcion : null,
      AltCantidad: altMatch ? altMatch.Cantidad : 1.000,
      AltPrecioVenta: altMatch ? altMatch.PrecioVenta : null
    };
  }

  /**
   * Guarda una venta en la cola de ventas pendientes (Offline)
   */
  async encolarVenta(ventaPayload) {
    await this.init();
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(['ventas_pendientes', 'productos'], 'readwrite');
      const storeVentas = tx.objectStore('ventas_pendientes');
      const storeProd = tx.objectStore('productos');

      const tempId = 'OFFLINE-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
      const fechaLocal = new Date().toISOString().slice(0, 19).replace('T', ' ');

      const ventaGuardar = {
        ...ventaPayload,
        id_temporal: tempId,
        fecha_creacion: fechaLocal,
        estado_sync: 'pendiente'
      };

      storeVentas.put(ventaGuardar);

      // Descontar stock localmente en IndexedDB para coherencia visual durante el corte
      if (Array.isArray(ventaPayload.items)) {
        for (const it of ventaPayload.items) {
          const reqP = storeProd.get(it.producto_id);
          reqP.onsuccess = () => {
            const p = reqP.result;
            if (p) {
              const controla = p.ControlaStock === undefined || parseInt(p.ControlaStock) === 1;
              if (controla) {
                const factor = it.factor || 1;
                p.Stock = Math.max(0, p.Stock - (it.cantidad * factor));
                storeProd.put(p);
              }
            }
          };
        }
      }

      tx.oncomplete = () => {
        console.log('[IndexedDB] Venta offline encolada con éxito:', tempId);
        resolve({
          success: true,
          id_temporal: tempId,
          fecha: fechaLocal
        });
      };

      tx.onerror = (e) => {
        console.error('[IndexedDB] Error encolando venta:', e);
        reject(e);
      };
    });
  }

  /**
   * Retorna todas las ventas pendientes de sincronizar
   */
  async obtenerVentasPendientes() {
    await this.init();
    return new Promise((resolve) => {
      const tx = this.db.transaction(['ventas_pendientes'], 'readonly');
      const store = tx.objectStore('ventas_pendientes');
      const req = store.getAll();

      req.onsuccess = () => {
        resolve(req.result || []);
      };
      req.onerror = () => {
        resolve([]);
      };
    });
  }

  /**
   * Elimina de la cola local las ventas que ya fueron insertadas en el VPS
   */
  async eliminarVentasSincronizadas(idsTemporales) {
    await this.init();
    if (!Array.isArray(idsTemporales) || idsTemporales.length === 0) return true;

    return new Promise((resolve) => {
      const tx = this.db.transaction(['ventas_pendientes'], 'readwrite');
      const store = tx.objectStore('ventas_pendientes');

      for (const id of idsTemporales) {
        store.delete(id);
      }

      tx.oncomplete = () => {
        console.log(`[IndexedDB] ${idsTemporales.length} ventas offline eliminadas tras sincronización.`);
        resolve(true);
      };
    });
  }

  /**
   * Cuenta cuántas ventas pendientes hay en cola
   */
  async contarVentasPendientes() {
    await this.init();
    return new Promise((resolve) => {
      const tx = this.db.transaction(['ventas_pendientes'], 'readonly');
      const store = tx.objectStore('ventas_pendientes');
      const req = store.count();

      req.onsuccess = () => resolve(req.result || 0);
      req.onerror = () => resolve(0);
    });
  }
}

// Instancia global disponible en todo el POS
window.posOfflineDB = new PosOfflineDB();

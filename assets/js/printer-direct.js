/**
 * printer-direct.js - Controlador de Impresión Térmica Directa ESC/POS y Gaveta de Dinero
 * Minimarket POS v4.0.1
 * 
 * Soporta:
 * - Web Serial API (Google Chrome / Edge) para Puertos Serie / Virtual COM
 * - WebUSB API (Google Chrome / Edge) para conexión USB directa
 * - Protocolo estándar ESC/POS (POSBANK A11 Prime 80mm, Epson, Bixolon, Star)
 * - Apertura física de Gaveta de Dinero (Cajón portamonedas puerto DK 24V RJ11/RJ12)
 * - Corte automático de papel (Guillotina)
 * - Cero drivers requeridos en Windows / Sin permisos de Administrador
 */

(function (window) {
  'use strict';

  class DirectPrinter {
    constructor() {
      this.port = null;          // Para Web Serial
      this.usbDevice = null;     // Para WebUSB
      this.usbEndpoint = null;
      this.type = localStorage.getItem('pos_direct_printer_type') || 'none'; // 'serial', 'usb', 'none'
      this.baudRate = parseInt(localStorage.getItem('pos_direct_printer_baud') || '9600', 10);
      this.portName = localStorage.getItem('pos_direct_printer_name') || '';
      this.paperWidth = 48; // Caracteres estándar por línea en papel de 80mm (Font A)
      this.autoDrawer = localStorage.getItem('pos_direct_printer_autodrawer') !== 'false';
      this.autoCut = localStorage.getItem('pos_direct_printer_autocut') !== 'false';
      this.isPrinting = false;

      // Intentar autoreconectar silenciosamente si ya había un puerto serial autorizado
      this.initAutoReconnect();
    }

    /**
     * Verifica si el navegador soporta Web Serial o WebUSB
     */
    isSupported() {
      return {
        serial: 'serial' in navigator,
        usb: 'usb' in navigator
      };
    }

    /**
     * Autoreconexión silenciosa al cargar la página
     */
    async initAutoReconnect() {
      if (this.type === 'serial' && 'serial' in navigator) {
        try {
          const ports = await navigator.serial.getPorts();
          if (ports.length > 0) {
            this.port = ports[0];
            this.updateUiStatus(true, this.portName || 'Puerto COM Vinculado');
          }
        } catch (e) {
          console.warn('[DirectPrinter] No se pudo autoreconectar a puerto serial:', e);
        }
      } else if (this.type === 'usb' && 'usb' in navigator) {
        try {
          const devices = await navigator.usb.getDevices();
          if (devices.length > 0) {
            this.usbDevice = devices[0];
            this.updateUiStatus(true, this.usbDevice.productName || 'Impresora USB');
          }
        } catch (e) {
          console.warn('[DirectPrinter] No se pudo autoreconectar a dispositivo USB:', e);
        }
      }
    }

    /**
     * Vincula un puerto serie (COM1, COM2, etc.) vía Web Serial
     */
    async connectSerial(baudRate = null) {
      if (!('serial' in navigator)) {
        throw new Error('Tu navegador no soporta Web Serial API. Usa Google Chrome o Microsoft Edge.');
      }

      if (baudRate) {
        this.baudRate = parseInt(baudRate, 10);
        localStorage.setItem('pos_direct_printer_baud', this.baudRate.toString());
      }

      // Abre el selector nativo del navegador
      const port = await navigator.serial.requestPort();
      this.port = port;
      this.type = 'serial';
      this.usbDevice = null;

      // Obtener info del puerto si está disponible
      const info = port.getInfo ? port.getInfo() : {};
      const name = (info.usbVendorId ? `USB-Serial (${info.usbVendorId.toString(16)})` : 'Puerto Serie COM');
      this.portName = name;

      localStorage.setItem('pos_direct_printer_type', 'serial');
      localStorage.setItem('pos_direct_printer_name', name);

      this.updateUiStatus(true, name);
      return name;
    }

    /**
     * Vincula un dispositivo USB vía WebUSB
     */
    async connectUsb() {
      if (!('usb' in navigator)) {
        throw new Error('Tu navegador no soporta WebUSB API. Usa Google Chrome o Microsoft Edge.');
      }

      // Abre el selector de dispositivos USB de Chrome
      const device = await navigator.usb.requestDevice({ filters: [] });
      this.usbDevice = device;
      this.type = 'usb';
      this.port = null;

      const name = device.productName || `Dispositivo USB (${device.vendorId.toString(16)})`;
      this.portName = name;

      localStorage.setItem('pos_direct_printer_type', 'usb');
      localStorage.setItem('pos_direct_printer_name', name);

      this.updateUiStatus(true, name);
      return name;
    }

    /**
     * Desvincula la impresora directa y vuelve al modo normal
     */
    async disconnect() {
      if (this.port) {
        try { await this.port.close(); } catch (_) {}
      }
      if (this.usbDevice && this.usbDevice.opened) {
        try { await this.usbDevice.close(); } catch (_) {}
      }

      this.port = null;
      this.usbDevice = null;
      this.type = 'none';
      this.portName = '';

      localStorage.removeItem('pos_direct_printer_type');
      localStorage.removeItem('pos_direct_printer_name');

      this.updateUiStatus(false, 'Desconectada');
    }

    /**
     * Envía bytes crudos ESC/POS al puerto conectado
     */
    async sendRaw(byteArray) {
      if (this.type === 'none') {
        throw new Error('No hay ninguna impresora directa configurada.');
      }

      const data = (byteArray instanceof Uint8Array) ? byteArray : new Uint8Array(byteArray);

      if (this.type === 'serial') {
        if (!this.port) {
          const ports = await navigator.serial.getPorts();
          if (ports.length > 0) this.port = ports[0];
          else throw new Error('El puerto serie no está disponible o fue desconectado.');
        }

        // Si el puerto no está abierto, abrirlo
        let shouldClose = false;
        try {
          if (!this.port.readable || !this.port.writable) {
            await this.port.open({ baudRate: this.baudRate });
            shouldClose = true;
          }
        } catch (e) {
          // Si ya estaba abierto, continuar
          if (!e.message.includes('already open')) {
            throw e;
          }
        }

        const writer = this.port.writable.getWriter();
        try {
          await writer.write(data);
        } finally {
          writer.releaseLock();
          if (shouldClose) {
            // Esperar 300ms para asegurar que el buffer físico termine de transmitir antes de cerrar
            await new Promise(resolve => setTimeout(resolve, 300));
            try { await this.port.close(); } catch (_) {}
          }
        }
        return true;
      }

      if (this.type === 'usb') {
        if (!this.usbDevice) {
          throw new Error('El dispositivo USB no está conectado.');
        }

        if (!this.usbDevice.opened) {
          try {
            await this.usbDevice.open();
          } catch (e) {
            throw new Error('Windows no permite acceso directo al cable USB (' + e.message + '). Te recomendamos usar el botón "1. Conectar por Puerto Serie / COM" que sí tiene permiso.');
          }
        }

        if (this.usbDevice.configuration === null || this.usbDevice.configuration.configurationValue !== 1) {
          try {
            await this.usbDevice.selectConfiguration(1);
          } catch (e) {
            console.warn('[DirectPrinter] selectConfiguration warning:', e);
          }
        }

        // Buscar en TODAS las interfaces la que contenga el endpoint de salida (OUT bulk)
        let targetInterfaceNumber = 0;
        let targetEndpointNumber = null;

        if (this.usbDevice.configuration && Array.isArray(this.usbDevice.configuration.interfaces)) {
          for (const iface of this.usbDevice.configuration.interfaces) {
            for (const alternate of iface.alternates) {
              const outEp = alternate.endpoints.find(e => e.direction === 'out');
              if (outEp) {
                targetInterfaceNumber = iface.interfaceNumber;
                targetEndpointNumber = outEp.endpointNumber;
                break;
              }
            }
            if (targetEndpointNumber !== null) break;
          }
        }

        if (targetEndpointNumber === null) {
          throw new Error('No se encontró el canal de salida de impresión en este dispositivo USB. Te sugerimos conectar mediante "1. Conectar por Puerto Serie / COM".');
        }

        this.usbEndpoint = targetEndpointNumber;

        // Reclamar la interfaz correspondiente
        try {
          await this.usbDevice.claimInterface(targetInterfaceNumber);
        } catch (e) {
          if (!e.message.includes('already claimed') && !e.message.includes('already open')) {
            throw new Error('Windows tiene reservado este puerto USB (' + e.message + '). Por favor haz clic en "1. Conectar por Puerto Serie / COM" para enviar la orden sin bloqueo.');
          }
        }

        await this.usbDevice.transferOut(this.usbEndpoint, data);
        return true;
      }
    }

    // =========================================================================
    // COMANDOS NATIVOS ESC/POS
    // =========================================================================

    /**
     * Genera los bytes de apertura física para la gaveta de dinero
     * (Envía pulso DK a Pin 2 y Pin 5 para asegurar compatibilidad total)
     */
    getDrawerBytes() {
      return [
        0x1B, 0x70, 0x00, 0x19, 0xFA, // ESC p 0 25 250 (Pin 2: 50ms ON, 500ms OFF)
        0x1B, 0x70, 0x01, 0x19, 0xFA  // ESC p 1 25 250 (Pin 5)
      ];
    }

    /**
     * Genera los bytes de corte total de papel (Guillotina)
     */
    getCutBytes() {
      return [
        0x1B, 0x64, 0x03,             // ESC d 3 (Avanzar 3 líneas antes de cortar)
        0x1D, 0x56, 0x42, 0x00        // GS V 66 0 (Corte completo)
      ];
    }

    /**
     * Abre físicamente la gaveta de dinero sin gastar papel
     */
    async openDrawer() {
      const bytes = [
        0x1B, 0x40,                   // ESC @ (Inicializar impresora)
        ...this.getDrawerBytes()
      ];
      return await this.sendRaw(new Uint8Array(bytes));
    }

    /**
     * Imprime un ticket de diagnóstico y prueba de 80mm con apertura de gaveta y corte
     */
    async printTestTicket(businessName = 'MINIMARKET POS', rut = '76.543.210-K') {
      const b = new EscPosBuilder(this.paperWidth);

      b.init();
      if (this.autoDrawer) {
        b.addBytes(this.getDrawerBytes());
      }

      b.alignCenter();
      b.bold(true);
      b.doubleSize(true);
      b.textLine(businessName);
      b.doubleSize(false);
      b.bold(false);
      b.textLine(`R.U.T.: ${rut}`);
      b.textLine('COMPROBANTE DE PRUEBA DE CONEXIÓN');
      b.textLine('------------------------------------------------');
      b.alignLeft();
      b.textLine(`Fecha: ${new Date().toLocaleDateString('es-CL')}  Hora: ${new Date().toLocaleTimeString('es-CL')}`);
      b.textLine(`Modo Conexión: ${this.type.toUpperCase()} (${this.portName})`);
      b.textLine(`Baudios: ${this.baudRate} bps | Ancho: 80mm (48 col)`);
      b.textLine('------------------------------------------------');
      b.bold(true);
      b.textLine('ESTADO DE DISPOSITIVOS:');
      b.bold(false);
      b.textLine(' [OK] Impresora Térmica POSBANK A11 Prime');
      b.textLine(' [OK] Puerto Serie / USB Directo sin Drivers');
      b.textLine(' [OK] Pulso de Apertura de Gaveta DK 24V');
      b.textLine(' [OK] Guillotina de Corte Automático');
      b.textLine('================================================');
      b.alignCenter();
      b.bold(true);
      b.textLine('¡IMPRESIÓN DIRECTA CONFIGURADA CON ÉXITO!');
      b.bold(false);
      b.textLine('venta8.duckdns.org - v4.0.1');
      b.feed(3);

      if (this.autoCut) {
        b.addBytes(this.getCutBytes());
      }

      return await this.sendRaw(b.build());
    }

    /**
     * Imprime el comprobante completo de una venta real en 80mm
     * @param {Object} data Datos de la venta retornados por el servidor o IndexedDB
     * @param {Object} config Opciones comerciales (Nombre local, RUT, dirección, pie)
     */
    async printSale(data, config = {}) {
      if (this.isPrinting) return;
      this.isPrinting = true;

      try {
        const b = new EscPosBuilder(this.paperWidth);
        b.init();

        // 1. Abrir cajón de dinero si está configurado
        if (this.autoDrawer) {
          b.addBytes(this.getDrawerBytes());
        }

        const localName = config.MINIMARKET_NOMBRE || 'MINIMARKET';
        const localRut = config.MINIMARKET_RUT || '';
        const localGiro = config.MINIMARKET_GIRO || '';
        const localDir = config.MINIMARKET_DIRECCION || '';
        const localTel = config.MINIMARKET_TELEFONO || '';
        const ticketPie = config.TICKET_PIE_PAGINA || '¡Gracias por su preferencia!';

        // 2. Membrete
        b.alignCenter();
        b.bold(true);
        b.doubleSize(true);
        b.textLine(localName);
        b.doubleSize(false);
        b.bold(false);

        if (localRut) b.textLine(`RUT: ${localRut}`);
        if (localGiro) b.textLine(localGiro);
        if (localDir) b.textLine(localDir);
        if (localTel) b.textLine(`Tel: ${localTel}`);

        b.textLine('------------------------------------------------');
        b.bold(true);
        const folioStr = data.venta_id ? `#${data.venta_id}` : (data.folio_offline ? `OFF-${data.folio_offline}` : '-');
        b.textLine(`COMPROBANTE DE VENTA ${folioStr}`);
        b.bold(false);
        b.textLine('------------------------------------------------');

        // 3. Metadatos
        b.alignLeft();
        const fechaHora = data.fecha || `${new Date().toLocaleDateString('es-CL')} ${new Date().toLocaleTimeString('es-CL')}`;
        b.textLine(`Fecha: ${fechaHora}`);
        if (data.cajero_nombre) b.textLine(`Cajero: ${data.cajero_nombre}`);
        if (data.caja_nombre) b.textLine(`Caja: ${data.caja_nombre}`);
        if (data.cliente_nombre && data.cliente_nombre !== 'Público General') {
          b.textLine(`Cliente: ${data.cliente_nombre}`);
          if (data.cliente_rut) b.textLine(`RUT: ${data.cliente_rut}`);
        }

        b.textLine('------------------------------------------------');
        b.textLine(b.formatRow3('CANT/DETALLE', '', 'TOTAL'));
        b.textLine('------------------------------------------------');

        // 4. Detalle de productos
        if (Array.isArray(data.items)) {
          data.items.forEach(it => {
            const cant = it.cantidad || 1;
            const precioUnit = it.precio_unitario || it.precio || 0;
            const subtotal = it.subtotal || (cant * precioUnit);
            const nombre = (it.nombre_producto || it.nombre || 'Producto').toUpperCase();

            // Línea 1: Nombre del producto
            b.bold(true);
            b.textLine(nombre);
            b.bold(false);

            // Línea 2: Cantidad x Precio unitario = Subtotal
            const cantStr = `  ${cant} x $${formatNum(precioUnit)}`;
            const totStr = `$${formatNum(subtotal)}`;
            b.textLine(b.formatRow2(cantStr, totStr));
          });
        }

        b.textLine('------------------------------------------------');

        // 5. Totales
        const total = data.total || 0;
        b.bold(true);
        b.doubleHeight(true);
        b.textLine(b.formatRow2('TOTAL A PAGAR:', `$${formatNum(total)}`));
        b.doubleHeight(false);
        b.bold(false);

        // Desglose de pagos
        if (Array.isArray(data.pagos) && data.pagos.length > 0) {
          b.textLine('------------------------------------------------');
          b.bold(true);
          b.textLine('MEDIOS DE PAGO:');
          b.bold(false);
          data.pagos.forEach(p => {
            const mNom = (p.metodo || 'Pago').toUpperCase();
            b.textLine(b.formatRow2(`  ${mNom}`, `$${formatNum(p.monto)}`));
          });
          if (data.vuelto && data.vuelto > 0) {
            b.bold(true);
            b.textLine(b.formatRow2('  SU VUELTO:', `$${formatNum(data.vuelto)}`));
            b.bold(false);
          }
        }

        // Pagaré en caso de crédito interno (fiado)
        if (data.credito) {
          b.textLine('------------------------------------------------');
          b.alignCenter();
          b.bold(true);
          b.textLine('*** COMPROBANTE DE FIADO / CRÉDITO ***');
          b.bold(false);
          b.feed(3);
          b.textLine('________________________________________');
          b.textLine('FIRMA DEL CLIENTE');
          b.textLine(`Acepto adeudar el monto de $${formatNum(total)}`);
        }

        // Talón de sorteo si se emitieron cupones
        if (data.sorteo && data.sorteo.cupones && data.sorteo.cupones.length > 0 && data.sorteo.imprimir) {
          b.feed(1);
          b.textLine('------------------------------------------------');
          b.alignCenter();
          b.bold(true);
          b.textLine('*** TALON PARA SORTEO ***');
          b.textLine(data.sorteo.titulo || 'Gran Sorteo');
          b.bold(false);
          b.textLine('Conserva este talon para reclamar tu premio');
          b.feed(1);
          data.sorteo.cupones.forEach(cp => {
            b.bold(true);
            b.textLine(`CUPON N: ${cp.codigo}`);
            b.bold(false);
          });
          b.feed(1);
          b.textLine('🍀 Mucha Suerte! 🍀');
          b.alignLeft();
          b.textLine('------------------------------------------------');
        }

        // Pie de ticket
        b.textLine('================================================');
        b.alignCenter();
        b.textLine(ticketPie);
        b.feed(3);

        // 6. Corte automático
        if (this.autoCut) {
          b.addBytes(this.getCutBytes());
        }

        await this.sendRaw(b.build());
        return true;
      } finally {
        this.isPrinting = false;
      }
    }

    /**
     * Actualiza el badge visual en la interfaz si existen los elementos en el DOM
     */
    updateUiStatus(connected, portText) {
      const badge = document.getElementById('printerStatusBadge');
      if (badge) {
        if (connected) {
          badge.className = 'badge badge-success';
          badge.innerHTML = `<i class="fa-solid fa-print"></i> Impresora: ${escapeHtml(portText)}`;
          badge.title = 'Impresión Directa ESC/POS Activa';
        } else {
          badge.className = 'badge badge-secondary';
          badge.innerHTML = `<i class="fa-solid fa-print"></i> Impresora Estándar`;
          badge.title = 'Usando diálogo de Windows';
        }
      }

      // Elementos específicos de la pantalla de configuración
      const statusSpan = document.getElementById('directPrinterStatusText');
      if (statusSpan) {
        if (connected) {
          statusSpan.innerHTML = `<span style="color:#34d399; font-weight:600;"><i class="fa-solid fa-circle-check"></i> Conectada (${escapeHtml(portText)})</span>`;
        } else {
          statusSpan.innerHTML = `<span style="color:#94a3b8;"><i class="fa-solid fa-circle-xmark"></i> Sin vincular</span>`;
        }
      }

      const disconnectBtn = document.getElementById('btnDisconnectPrinter');
      if (disconnectBtn) {
        disconnectBtn.style.display = connected ? 'inline-flex' : 'none';
      }
    }
  }

  // =========================================================================
  // CONSTRUCTOR BINARIO ESC/POS
  // =========================================================================

  class EscPosBuilder {
    constructor(maxWidth = 48) {
      this.maxWidth = maxWidth;
      this.buffer = [];
    }

    init() {
      // ESC @ (Inicializar) + ESC t 16 (Página de códigos Windows-1252 / Latin-1)
      this.buffer.push(0x1B, 0x40, 0x1B, 0x74, 0x10);
      return this;
    }

    alignLeft() {
      this.buffer.push(0x1B, 0x61, 0x00);
      return this;
    }

    alignCenter() {
      this.buffer.push(0x1B, 0x61, 0x01);
      return this;
    }

    alignRight() {
      this.buffer.push(0x1B, 0x61, 0x02);
      return this;
    }

    bold(enable = true) {
      this.buffer.push(0x1B, 0x45, enable ? 0x01 : 0x00);
      return this;
    }

    doubleHeight(enable = true) {
      this.buffer.push(0x1D, 0x21, enable ? 0x01 : 0x00);
      return this;
    }

    doubleSize(enable = true) {
      this.buffer.push(0x1D, 0x21, enable ? 0x11 : 0x00);
      return this;
    }

    feed(lines = 1) {
      this.buffer.push(0x1B, 0x64, lines);
      return this;
    }

    addBytes(bytes) {
      if (Array.isArray(bytes) || bytes instanceof Uint8Array) {
        for (let i = 0; i < bytes.length; i++) {
          this.buffer.push(bytes[i]);
        }
      }
      return this;
    }

    /**
     * Convierte string a bytes compatibles con impresora térmica (sanitiza tildes/ñ)
     */
    text(str) {
      const clean = cleanString(str);
      for (let i = 0; i < clean.length; i++) {
        const code = clean.charCodeAt(i);
        this.buffer.push(code < 256 ? code : 0x20);
      }
      return this;
    }

    textLine(str = '') {
      this.text(str);
      this.buffer.push(0x0A); // LF
      return this;
    }

    /**
     * Formatea 2 columnas: izquierda y derecha justificadas a los extremos
     */
    formatRow2(leftStr, rightStr) {
      const l = cleanString(leftStr);
      const r = cleanString(rightStr);
      const spaceLen = this.maxWidth - (l.length + r.length);
      if (spaceLen <= 0) {
        return l.substring(0, this.maxWidth - r.length - 1) + ' ' + r;
      }
      return l + ' '.repeat(spaceLen) + r;
    }

    /**
     * Formatea 3 columnas
     */
    formatRow3(col1, col2, col3) {
      const c1 = cleanString(col1);
      const c2 = cleanString(col2);
      const c3 = cleanString(col3);
      const sideWidth = Math.floor((this.maxWidth - c2.length) / 2);
      const leftPart = c1.padEnd(sideWidth, ' ');
      const rightPart = c3.padStart(this.maxWidth - leftPart.length - c2.length, ' ');
      return leftPart + c2 + rightPart;
    }

    build() {
      return new Uint8Array(this.buffer);
    }
  }

  // =========================================================================
  // UTILIDADES
  // =========================================================================

  function cleanString(str) {
    if (str === null || str === undefined) return '';
    return str
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Elimina diacríticos/tildes para máxima legibilidad en térmica
      .replace(/Ñ/g, 'N')
      .replace(/ñ/g, 'n');
  }

  function formatNum(num) {
    return new Intl.NumberFormat('es-CL').format(Math.round(num || 0));
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
  }

  // Exportar instancia global
  window.DirectPrinter = DirectPrinter;
  window.directPrinter = new DirectPrinter();

})(window);

// Lógica del Punto de Venta (POS) en JavaScript Vanilla con Modal Avanzado FormPagoPOS
let cart = [];
let productosCache = [];
let metodoSeleccionadoModal = 'Efectivo';
let puntosCanje = 0; // puntos del cliente usados como parte del pago (1 punto = $1)
let valeAplicado = null; // { codigo, disponible }
let cotizacionActiva = null; // CotizacionID si la venta actual salió de una cotización

function playBeep() {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(800, audioCtx.currentTime);
    gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.08);
  } catch (e) {}
}

let modoDescuento = 'monto'; // 'monto' | 'porc'
let supervisorPassAutorizadoDescuento = null;

async function pedirAutorizacionSupervisor(accion, detalle = '') {
  if (window.CURRENT_USER_ROL === 'Administrador' || window.CURRENT_USER_ROL === 'Supervisor') {
    return { autorizado: true, supervisor_pass: null, supervisor_nombre: window.CURRENT_USER_NAME || 'Supervisor' };
  }

  let promptMsg = 'Se requiere la clave de un Supervisor o Administrador para continuar.';
  let promptTitle = '🛡️ Autorización de Supervisor';
  let isDanger = false;

  if (accion === 'cancelar_venta_proceso') {
    promptTitle = '🛡️ Autorizar Cancelar Venta';
    promptMsg = 'Se requiere clave de Supervisor para vaciar el carrito y cancelar la venta en proceso.';
    isDanger = true;
  } else if (accion === 'eliminar_item_carrito') {
    promptTitle = '🛡️ Autorizar Eliminar Producto';
    promptMsg = `Se requiere clave de Supervisor para quitar "${detalle}" de la venta.`;
    isDanger = true;
  } else if (accion === 'descuento_excedido') {
    const maxPct = window.CONFIG_SUPERVISION?.POS_DESCUENTO_MAX_PORC ?? 5;
    promptTitle = '🛡️ Autorizar Descuento Especial';
    promptMsg = `El descuento aplicado supera el límite permitido sin supervisión (${maxPct}%). Ingresa la clave de un Supervisor para autorizar:`;
  }

  const pass = await supervisorPromptDialog({
    title: promptTitle,
    message: promptMsg,
    danger: isDanger
  });

  if (!pass) {
    return { autorizado: false };
  }

  try {
    const res = await fetch('api/autorizar_supervisor.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN || ''
      },
      body: JSON.stringify({
        password: pass,
        accion: accion,
        detalle: detalle
      })
    });
    
    let data;
    try {
      data = await res.json();
    } catch (parseErr) {
      toast('Error al comunicarse con el servidor.', 'error');
      return { autorizado: false };
    }

    if (!data.success) {
      toast(data.error || 'Clave de supervisor incorrecta.', 'error');
      return { autorizado: false };
    }

    toast(`Autorizado por ${data.supervisor_nombre}`, 'success');
    return { autorizado: true, supervisor_pass: pass, supervisor_nombre: data.supervisor_nombre };
  } catch (e) {
    toast('Error al verificar supervisor: ' + e.message, 'error');
    return { autorizado: false };
  }
}

async function validarSupervisorDescuentoInline() {
  const input = document.getElementById('inputPassSupervisorInline');
  const pass = input ? input.value.trim() : '';
  if (!pass) {
    toast('Ingresa la clave de supervisor para autorizar el descuento.', 'warn');
    if (input) input.focus();
    return false;
  }

  const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
  const subtotalBruto = cart.reduce((s, i) => s + Math.round(i.cantidad * i.PrecioVenta), 0);
  const pctEfectivo = subtotalBruto > 0 ? (descGlobal / subtotalBruto) * 100 : 0;
  const detalle = `Descuento de $${formatNumber(descGlobal)} (${pctEfectivo.toFixed(1)}%)`;

  const btn = document.getElementById('btnAuthSupervisorInline');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i>`;
  }

  try {
    const res = await fetch('api/autorizar_supervisor.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN || ''
      },
      body: JSON.stringify({
        password: pass,
        accion: 'descuento_excedido',
        detalle: detalle
      })
    });

    let data;
    try {
      data = await res.json();
    } catch (parseErr) {
      toast('Error al comunicarse con el servidor.', 'error');
      return false;
    }

    if (!data.success) {
      toast(data.error || 'Clave de supervisor incorrecta.', 'error');
      if (input) {
        input.value = '';
        input.focus();
      }
      return false;
    }

    supervisorPassAutorizadoDescuento = pass;
    toast(`Autorizado correctamente por ${data.supervisor_nombre}`, 'success');

    const boxInline = document.getElementById('boxSupervisorAuthInline');
    const boxOk = document.getElementById('boxSupervisorAuthOk');
    const txtOk = document.getElementById('txtSupervisorAuthOk');
    if (boxInline) boxInline.style.display = 'none';
    if (boxOk) {
      boxOk.style.display = 'flex';
      if (txtOk) txtOk.textContent = `Autorizado por ${data.supervisor_nombre}`;
    }

    return true;
  } catch (e) {
    toast('Error al verificar supervisor: ' + e.message, 'error');
    return false;
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<i class="fa-solid fa-key"></i> Autorizar`;
    }
  }
}

function setModoDescuento(modo) {
  modoDescuento = modo;
  const btnMonto = document.getElementById('btnDescModoMonto');
  const btnPorc = document.getElementById('btnDescModoPorc');
  const suffix = document.getElementById('descInputSuffix');
  const input = document.getElementById('descuentoInput');

  if (modo === 'porc') {
    if (btnMonto) btnMonto.classList.remove('active', 'btn-primary');
    if (btnPorc) btnPorc.classList.add('active', 'btn-primary');
    if (suffix) suffix.textContent = '%';
    if (input) {
      input.placeholder = '0';
      input.max = '100';
    }
  } else {
    if (btnPorc) btnPorc.classList.remove('active', 'btn-primary');
    if (btnMonto) btnMonto.classList.add('active', 'btn-primary');
    if (suffix) suffix.textContent = '$';
    if (input) {
      input.placeholder = '0';
      input.removeAttribute('max');
    }
  }
  onDescuentoInputChange();
}

function onDescuentoInputChange() {
  const input = document.getElementById('descuentoInput');
  const hidden = document.getElementById('descuentoGlobal');
  const rawVal = input ? parseFloat(input.value) : 0;
  const subtotal = getCartSubtotal();
  let descPesos = 0;
  let pctEfectivo = 0;

  if (modoDescuento === 'porc') {
    const pct = Math.min(100, Math.max(0, isNaN(rawVal) ? 0 : rawVal));
    pctEfectivo = pct;
    descPesos = Math.round(subtotal * (pct / 100));
  } else {
    descPesos = Math.min(subtotal, Math.max(0, isNaN(rawVal) ? 0 : Math.round(rawVal)));
    pctEfectivo = subtotal > 0 ? (descPesos / subtotal) * 100 : 0;
  }

  const prevDesc = parseInt(hidden ? hidden.value : 0) || 0;
  if (hidden) hidden.value = descPesos;

  // Si el monto de descuento cambió, invalidar autorización previa
  if (prevDesc !== descPesos) {
    supervisorPassAutorizadoDescuento = null;
    const boxInline = document.getElementById('boxSupervisorAuthInline');
    const boxOk = document.getElementById('boxSupervisorAuthOk');
    const passInput = document.getElementById('inputPassSupervisorInline');
    if (boxInline) boxInline.style.display = 'flex';
    if (boxOk) boxOk.style.display = 'none';
    if (passInput) passInput.value = '';
  }

  // Evaluar si requiere autorización de supervisor
  const maxPorc = parseFloat(window.CONFIG_SUPERVISION?.POS_DESCUENTO_MAX_PORC ?? 5);
  const avisoEl = document.getElementById('descuentoAvisoSupervisor');
  const lblMaxEl = document.getElementById('lblDescMaxPorc');
  if (lblMaxEl) lblMaxEl.textContent = `${maxPorc}%`;

  if (window.CURRENT_USER_ROL === 'Cajero' && descPesos > 0 && (pctEfectivo > maxPorc || maxPorc === 0)) {
    if (avisoEl) avisoEl.style.display = 'flex';
  } else {
    if (avisoEl) avisoEl.style.display = 'none';
  }

  renderCart();
}

function resetDescuento() {
  const input = document.getElementById('descuentoInput');
  const hidden = document.getElementById('descuentoGlobal');
  const avisoEl = document.getElementById('descuentoAvisoSupervisor');
  if (input) input.value = '';
  if (hidden) hidden.value = '0';
  if (avisoEl) avisoEl.style.display = 'none';

  supervisorPassAutorizadoDescuento = null;
  const passInline = document.getElementById('inputPassSupervisorInline');
  if (passInline) passInline.value = '';
  const boxInline = document.getElementById('boxSupervisorAuthInline');
  const boxOk = document.getElementById('boxSupervisorAuthOk');
  if (boxInline) boxInline.style.display = 'flex';
  if (boxOk) boxOk.style.display = 'none';
}

// --- Control de Layout de Pantalla POS (Supermercado / Táctil / Clásico) ---
let modoPosLayout = window.POS_LAYOUT_MODO || 'supermercado'; // 'supermercado' | 'tactil' | 'clasico'
let filtroCatalogoActual = 'mas_vendidos'; // 'mas_vendidos' | 'ofertas' | 'todos' | 'categoria'
let categoriaFiltroActual = 0;
let categoriaTactilActual = 0;
let disenoGridActual = 'estandar';

function setModoPosLayout(modo) {
  modoPosLayout = modo;
  const container = document.getElementById('posContainer');
  if (container) {
    container.classList.remove('pos-mode--supermercado', 'pos-mode--tactil', 'pos-mode--clasico');
    container.classList.add(`pos-mode--${modo}`);
  }

  if (modo === 'tactil') {
    cambiarDisenoGrid('tactil');
    cargarProductos('');
  } else if (modo === 'clasico') {
    let savedGrid = 'estandar';
    try { savedGrid = localStorage.getItem('pos_diseno_grid') || 'estandar'; } catch(e) {}
    cambiarDisenoGrid(savedGrid === 'tactil' ? 'estandar' : savedGrid);
    cargarProductos('');
  }

  setTimeout(() => {
    if (modo === 'supermercado') {
      const superInput = document.getElementById('posSearchSuper');
      if (superInput) superInput.focus();
    } else {
      const normalInput = document.getElementById('posSearch');
      if (normalInput) normalInput.focus();
    }
  }, 100);

  renderCart();
}

function setFiltroCatalogo(tipo, btn) {
  filtroCatalogoActual = tipo;
  categoriaFiltroActual = 0;

  const chips = [
    { id: 'btnFiltroMasVendidos', tipo: 'mas_vendidos' },
    { id: 'btnFiltroOfertas', tipo: 'ofertas' },
    { id: 'btnFiltroTodos', tipo: 'todos' }
  ];
  chips.forEach(c => {
    const el = document.getElementById(c.id);
    if (el) {
      if (c.tipo === tipo) el.classList.add('active');
      else el.classList.remove('active');
    }
  });

  const selectCat = document.getElementById('selectFiltroCategoria');
  if (selectCat) selectCat.value = '0';

  const searchInput = document.getElementById('posSearch');
  if (searchInput) searchInput.value = '';

  cargarProductos('');
}

function setFiltroCatalogoCategoria(catId) {
  catId = parseInt(catId) || 0;
  if (catId > 0) {
    filtroCatalogoActual = 'categoria';
    categoriaFiltroActual = catId;
    ['btnFiltroMasVendidos', 'btnFiltroOfertas', 'btnFiltroTodos'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.classList.remove('active');
    });
  } else {
    const btn = document.getElementById('btnFiltroMasVendidos');
    setFiltroCatalogo('mas_vendidos', btn);
    return;
  }

  const searchInput = document.getElementById('posSearch');
  if (searchInput) searchInput.value = '';

  cargarProductos('');
}

function setCategoriaTactil(catId, btn) {
  categoriaTactilActual = parseInt(catId) || 0;
  const container = document.getElementById('posTactilCategories');
  if (container) {
    const pills = container.querySelectorAll('.tactil-cat-pill');
    pills.forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
  }

  const searchInput = document.getElementById('posSearch');
  if (searchInput) searchInput.value = '';

  cargarProductos('');
}

// Helper unificado de búsqueda con tolerancia a fallos y fallback offline a IndexedDB
async function buscarProductoConFallback(query) {
  query = String(query).trim();
  if (!query) return [];

  // 1. Si no estamos forzados y no estamos en offline confirmado, intentar la API remota con timeout breve
  if (!posModoOfflineForzado && !posEstadoOfflineActivo && navigator.onLine) {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 2000);
      const res = await fetch(`api/buscar_producto.php?q=${encodeURIComponent(query)}`, {
        signal: controller.signal
      });
      clearTimeout(timeoutId);
      const data = await res.json();
      if (data && data.success && Array.isArray(data.productos)) {
        return data.productos;
      }
    } catch (netErr) {
      console.warn('[POS] API remota no respondió. Activando fallback offline para:', query);
      marcarModoDesconectado(true);
    }
  } else {
    marcarModoDesconectado(true);
  }

  // 2. Fallback transparente a IndexedDB
  if (window.posOfflineDB) {
    try {
      const offlineResults = await window.posOfflineDB.buscarProductoOffline(query);
      if (offlineResults && offlineResults.length > 0) {
        return offlineResults;
      }
    } catch (idbErr) {
      console.error('[POS] Error al consultar catálogo IndexedDB:', idbErr);
    }
  }

  return [];
}

async function procesarEntradaCodigo(inputEl) {
  if (!inputEl) return;
  const code = inputEl.value.trim();
  if (!code) return;

  const prefijoIndiv = window.BALANZA_PREFIJO_INDIVIDUAL || '20';
  const tipoEan = window.BALANZA_TIPO_EAN || 'plu_peso';

  // Si cumple con el estándar EAN-13 de balanza individual
  if (window.BALANZA_MODO !== 'manual' && code.length === 13 && code.startsWith(prefijoIndiv)) {
    const plu = code.substring(2, 6);
    const cantidadBruta = parseInt(code.substring(6, 11)) || 0;

    try {
      const productos = await buscarProductoConFallback(plu);

      if (productos.length > 0) {
        const match = productos.find(p => p.CodigoPLU === plu) || productos[0];
        
        let cantidadFinal = 1;
        if (tipoEan === 'plu_peso') {
          cantidadFinal = cantidadBruta / 1000;
        } else {
          const precioVenta = parseInt(match.PrecioVenta) || 1;
          cantidadFinal = Math.round((cantidadBruta / precioVenta) * 1000) / 1000;
        }

        agregarAlCarrito(match, cantidadFinal);
        inputEl.value = '';
        if (modoPosLayout !== 'supermercado') cargarProductos('');
      } else {
        toast(`Producto con PLU de balanza #${plu} no encontrado.`, 'error');
      }
    } catch (e) {
      toast('Error al consultar balanza.', 'error');
    }
  } else {
    // Flujo de código de barra normal
    try {
      const productos = await buscarProductoConFallback(code);

      if (productos.length > 0) {
        const match = productos.find(p => p.CodigoBarras === code || p.CodigoAltMatch === code) || productos[0];

        // Si calzó específicamente con un código alternativo que define factor de pack o precio propio
        if (match.CodigoAltMatch === code) {
          const factor = parseFloat(match.AltCantidad || 1);
          let precioPack = null;
          let esPromoPack = false;

          // Regla 1: Precio fijo explícito en el código alternativo
          if (match.AltPrecioVenta !== null && match.AltPrecioVenta !== undefined && parseInt(match.AltPrecioVenta) > 0) {
            precioPack = parseInt(match.AltPrecioVenta);
          }
          // Regla 2: Heredar automáticamente el precio de la promoción activa si el precio está vacío
          else if (match.PromoTipo === 'MULTIBUY' && parseFloat(match.PromoCantMin) > 0) {
            const cantMin = parseFloat(match.PromoCantMin);
            const precioOf = parseInt(match.PromoPrecioOf);
            if (factor >= cantMin && factor % cantMin === 0) {
              precioPack = Math.round((factor / cantMin) * precioOf);
              esPromoPack = true;
            }
          } else if (match.PromoTipo === 'DESCUENTO_UNIT' && parseFloat(match.PromoDescPorc) > 0) {
            const descUnit = Math.round(parseInt(match.PrecioVenta) * (parseFloat(match.PromoDescPorc) / 100));
            precioPack = Math.round((parseInt(match.PrecioVenta) - descUnit) * factor);
            esPromoPack = true;
          }

          // Regla 3: Si no hay precio fijo ni promoción para esa cantidad, multiplicar precio base * factor
          if (precioPack === null) {
            precioPack = Math.round(parseInt(match.PrecioVenta) * factor);
          }

          const descPack = match.AltDescripcion ? match.AltDescripcion : (factor > 1 ? `Pack x${factor}` : '');

          agregarAlCarrito(match, 1, factor, precioPack, descPack, esPromoPack);
        } else {
          agregarProductoManual(match);
        }

        inputEl.value = '';
        if (modoPosLayout !== 'supermercado') cargarProductos('');
      } else {
        toast(`Producto "${code}" no encontrado`, 'error');
      }
    } catch (e) {
      toast('Error al consultar producto.', 'error');
    }
  }

  inputEl.focus();
}

async function vaciarCarritoPos() {
  if (cart.length === 0) return;
  const reqSup = (window.CONFIG_SUPERVISION?.POS_REQ_SUPERVISOR_CANCELAR ?? 'SI') === 'SI';
  if (window.CURRENT_USER_ROL === 'Cajero' && reqSup) {
    const auth = await pedirAutorizacionSupervisor('cancelar_venta_proceso', `${cart.length} productos en el carrito`);
    if (!auth.autorizado) return;
  } else {
    const ok = await confirmDialog({
      title: 'Cancelar venta en proceso',
      message: '¿Seguro que quieres quitar todos los productos del carrito y cancelar la venta?',
      confirmText: 'Vaciar',
      danger: true,
    });
    if (!ok) return;
  }
  cart = [];
  cotizacionActiva = null;
  resetDescuento();
  renderCart();
  toast('Venta en proceso cancelada y carrito vaciado.', 'info');
}

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('posSearch');
  const superSearchInput = document.getElementById('posSearchSuper');

  // Prevenir y limpiar de raíz cualquier inyección automática de "admin" o credenciales guardadas en el navegador
  const limpiarAutofillCredenciales = () => {
    [searchInput, superSearchInput].forEach(inp => {
      if (!inp) return;
      const v = (inp.value || '').trim().toLowerCase();
      if (v === 'admin' || v === 'administrador' || v === 'root' || v === 'cajero' || v === 'supervisor') {
        inp.value = '';
      }
    });
  };

  limpiarAutofillCredenciales();
  setTimeout(limpiarAutofillCredenciales, 50);
  setTimeout(limpiarAutofillCredenciales, 150);
  setTimeout(limpiarAutofillCredenciales, 400);
  setTimeout(limpiarAutofillCredenciales, 1000);

  [searchInput, superSearchInput].forEach(inp => {
    if (!inp) return;
    inp.addEventListener('focus', () => {
      const v = (inp.value || '').trim().toLowerCase();
      if (v === 'admin' || v === 'administrador' || v === 'root' || v === 'cajero' || v === 'supervisor') {
        inp.value = '';
      }
    });
  });

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        cargarProductos(e.target.value.trim());
      }, 250);
    });

    searchInput.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        await procesarEntradaCodigo(searchInput);
      }
    });
  }

  if (superSearchInput) {
    superSearchInput.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        await procesarEntradaCodigo(superSearchInput);
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    const vpModal = document.getElementById('ventaPesoModal');
    if (vpModal && vpModal.style.display !== 'none') {
      if (e.key === 'Escape') {
        e.preventDefault();
        cerrarModalVentaPeso();
      }
      return;
    }

    // Si el modal de precio variable está abierto, cerrar con Escape
    const pvModal = document.getElementById('precioVariableModal');
    if (pvModal && pvModal.style.display !== 'none') {
      if (e.key === 'Escape') {
        e.preventDefault();
        cerrarModalPrecioVariable();
        return;
      }
    }

    // Si el comprobante/ticket está abierto, cerrar con Escape o Enter
    const ticketModal = document.getElementById('ticketModal');
    if (ticketModal && ticketModal.style.display !== 'none') {
      if (e.key === 'Escape' || e.key === 'Enter') {
        e.preventDefault();
        cerrarTicket();
        return;
      }
    }

    // Bloquear terminal con Alt + L o tecla F9
    if ((e.altKey && (e.key === 'l' || e.key === 'L')) || e.key === 'F9') {
      e.preventDefault();
      bloquearCaja();
      return;
    }

    if (e.key === 'F12') {
      // Si la caja está bloqueada, no abrir cobro
      if (sessionStorage.getItem('pos_locked') === '1') return;
      e.preventDefault();
      abrirModalPago();
    }
  });

  // Si se llegó con ?cotizacion=ID, cargar esa cotización en el carrito
  if (window.COTIZACION_PRELOAD) {
    cargarCotizacion(window.COTIZACION_PRELOAD);
  }

  // Inicializar modo de layout POS configurado desde la base de datos (Configuraciones)
  setModoPosLayout(window.POS_LAYOUT_MODO || 'supermercado');

  // Si la caja estaba bloqueada antes de refrescar, restaurar el bloqueo
  if (sessionStorage.getItem('pos_locked') === '1') {
    bloquearCaja();
  }
});

function cambiarDisenoGrid(modo) {
  disenoGridActual = modo;
  const grid = document.getElementById('productGrid');
  if (grid) {
    grid.classList.remove('product-grid--tactil', 'product-grid--compacto');
    if (modo === 'tactil') grid.classList.add('product-grid--tactil');
    if (modo === 'compacto') grid.classList.add('product-grid--compacto');
  }

  // Actualizar botones activos de densidad
  const btns = {
    estandar: document.getElementById('btnGridEstandar'),
    compacto: document.getElementById('btnGridCompacto')
  };
  Object.keys(btns).forEach(k => {
    if (btns[k]) {
      if (k === modo) {
        btns[k].classList.add('active', 'btn-primary');
        btns[k].classList.remove('btn-secondary');
      } else {
        btns[k].classList.remove('active', 'btn-primary');
        btns[k].classList.add('btn-secondary');
      }
    }
  });

  try { localStorage.setItem('pos_diseno_grid', modo); } catch(e) {}
  renderProductosGrid();
}

function renderProductosGrid() {
  const grid = document.getElementById('productGrid');
  if (!grid || !productosCache) return;

  if (productosCache.length === 0) {
    grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">No se encontraron productos.</div>`;
    return;
  }

  if (disenoGridActual === 'compacto') {
    grid.innerHTML = productosCache.map(p => `
      <div class="product-card" onclick="agregarAlCarritoId(${p.ProductoID})" title="Clic para agregar a la venta">
        <div class="product-name" title="${escapeHtml(p.Nombre)}">${escapeHtml(p.Nombre)}</div>
        <div class="product-sku">${p.CodigoBarras ? '#' + p.CodigoBarras : (p.CodigoPLU ? 'PLU #' + p.CodigoPLU : 'Sin código')}</div>
        <div class="product-meta">
          <div class="product-price">${parseInt(p.EsPrecioVariable) === 1 ? '<span style="color: #60a5fa; font-size: 0.82em;"><i class="fa-solid fa-tag"></i> Variable</span>' : '$' + formatNumber(p.PrecioVenta)}</div>
          <div class="product-stock" title="Stock en tienda">${(p.ControlaStock !== undefined && parseInt(p.ControlaStock) === 0) ? '<i class="fa-solid fa-infinity"></i> Fijo' : p.Stock + ' disp.'}</div>
        </div>
      </div>
    `).join('');
  } else {
    grid.innerHTML = productosCache.map(p => `
      <div class="product-card" onclick="agregarAlCarritoId(${p.ProductoID})">
        <div class="product-name">${escapeHtml(p.Nombre)}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted);">${p.CodigoBarras ? '#' + p.CodigoBarras : (p.CodigoPLU ? 'PLU #' + p.CodigoPLU : 'Sin código')}</div>
        <div class="product-meta">
          <div class="product-price">${parseInt(p.EsPrecioVariable) === 1 ? '<span style="color: #60a5fa; font-size: 0.85em;"><i class="fa-solid fa-tag"></i> Variable</span>' : '$' + formatNumber(p.PrecioVenta)}</div>
          <div class="product-stock">${(p.ControlaStock !== undefined && parseInt(p.ControlaStock) === 0) ? 'Stock: Fijo' : 'Stock: ' + p.Stock}</div>
        </div>
      </div>
    `).join('');
  }
}

async function cargarProductos(query = '') {
  const grid = document.getElementById('productGrid');
  if (!grid) return;

  if (query) {
    const productos = await buscarProductoConFallback(query);
    productosCache = productos;
    renderProductosGrid();
    return;
  }

  let url = 'api/buscar_producto.php';
  if (modoPosLayout === 'tactil') {
    if (categoriaTactilActual > 0) {
      url += `?filtro=categoria&cat=${categoriaTactilActual}`;
    } else {
      url += `?filtro=todos`;
    }
  } else {
    if (filtroCatalogoActual === 'categoria' && categoriaFiltroActual > 0) {
      url += `?filtro=categoria&cat=${categoriaFiltroActual}`;
    } else {
      url += `?filtro=${encodeURIComponent(filtroCatalogoActual || 'mas_vendidos')}`;
    }
  }

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2500);
    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeoutId);
    const data = await res.json();

    if (!data.success) throw new Error(data.error);

    productosCache = data.productos;
    renderProductosGrid();
    marcarModoDesconectado(false);
  } catch (err) {
    console.warn('[POS] Error al cargar productos por red. Intentando IndexedDB...', err);
    marcarModoDesconectado(true);
    if (window.posOfflineDB) {
      const offlineProds = await window.posOfflineDB.buscarProductoOffline('');
      productosCache = offlineProds;
      renderProductosGrid();
    } else {
      grid.innerHTML = `<div style="grid-column: 1/-1; color: var(--danger); text-align: center; padding: 2rem;">Error al cargar productos: ${err.message}</div>`;
    }
  }
}

function agregarAlCarritoId(id) {
  const p = productosCache.find(prod => prod.ProductoID == id);
  if (p) agregarProductoManual(p);
}

// Agregado sin cantidad conocida (clic en catálogo o escaneo del código propio del producto).
// Los pesables no traen peso, así que se pide en el modal en vez de asumir 1 kg.
function agregarProductoManual(producto) {
  if (parseInt(producto.EsPesable) === 1 && parseInt(producto.EsPrecioVariable) !== 1) {
    abrirModalVentaPeso(producto);
    return;
  }
  agregarAlCarrito(producto);
}

let pendingVentaPesoProducto = null;
let modoVentaPeso = 'peso';

function abrirModalVentaPeso(producto) {
  pendingVentaPesoProducto = producto;
  document.getElementById('ventaPesoNombre').textContent = producto.Nombre;
  document.getElementById('ventaPesoPrecioKg').textContent = `$${formatNumber(parseInt(producto.PrecioVenta) || 0)} / kg`;
  setModoVentaPeso(window.BALANZA_INGRESO_MANUAL === 'precio' ? 'precio' : 'peso');
  document.getElementById('ventaPesoModal').style.display = 'flex';
}

function setModoVentaPeso(modo) {
  modoVentaPeso = modo;
  const esPeso = modo === 'peso';
  document.getElementById('ventaPesoTabPeso').className = 'btn ' + (esPeso ? 'btn-primary' : 'btn-secondary');
  document.getElementById('ventaPesoTabPrecio').className = 'btn ' + (esPeso ? 'btn-secondary' : 'btn-primary');
  document.getElementById('ventaPesoLabel').textContent = esPeso ? 'PESO (KG) *' : 'MONTO A COBRAR ($ CLP) *';
  document.getElementById('ventaPesoUnidad').textContent = esPeso ? 'kg' : '$';
  document.getElementById('ventaPesoAyuda').textContent = esPeso ? 'Ej: 0,535 = 535 gramos' : 'Ej: 2000 = $2.000 del producto';
  const input = document.getElementById('ventaPesoValor');
  input.placeholder = esPeso ? '0,000' : '0';
  input.value = '';
  actualizarPreviewVentaPeso();
  setTimeout(() => input.focus(), 80);
}

// Devuelve { kg, total } o null. En modo monto busca el peso (en gramos enteros, la
// precisión que guarda la BD) cuyo total redondeado quede más cerca del monto pedido.
function calcularVentaPeso() {
  const producto = pendingVentaPesoProducto;
  if (!producto) return null;
  const precioKg = parseInt(producto.PrecioVenta) || 0;
  const valor = parseFloat(document.getElementById('ventaPesoValor').value.replace(',', '.'));
  if (!(valor > 0) || precioKg <= 0) return null;

  if (modoVentaPeso === 'peso') {
    const gramos = Math.round(valor * 1000);
    if (gramos <= 0) return null;
    return { kg: gramos / 1000, total: Math.round(gramos * precioKg / 1000) };
  }

  const monto = Math.round(valor);
  const base = Math.floor(monto * 1000 / precioKg);
  let mejor = null;
  for (const gramos of [base, base + 1]) {
    if (gramos <= 0) continue;
    const total = Math.round(gramos * precioKg / 1000);
    if (!mejor || Math.abs(total - monto) < Math.abs(mejor.total - monto)) mejor = { kg: gramos / 1000, total };
  }
  return mejor;
}

function formatKg(kg) {
  return kg < 1 ? `${Math.round(kg * 1000)} g` : `${kg.toLocaleString('es-CL', { maximumFractionDigits: 3 })} kg`;
}

function actualizarPreviewVentaPeso() {
  const prev = document.getElementById('ventaPesoPreview');
  const r = calcularVentaPeso();
  if (!r) {
    prev.innerHTML = (parseInt(pendingVentaPesoProducto?.PrecioVenta) || 0) <= 0
      ? '<span style="color: var(--danger);">El producto no tiene precio por kg configurado.</span>'
      : 'Ingrese un valor';
    return;
  }
  prev.innerHTML = `<strong style="color: #fff;">${formatKg(r.kg)}</strong> &nbsp;=&nbsp; <strong style="color: var(--success); font-size: 1.15rem;">$${formatNumber(r.total)}</strong>`;
}

function cerrarModalVentaPeso() {
  document.getElementById('ventaPesoModal').style.display = 'none';
  pendingVentaPesoProducto = null;
  setTimeout(() => {
    const input = document.getElementById(modoPosLayout === 'supermercado' ? 'posSearchSuper' : 'posSearch');
    if (input) { input.focus(); input.select(); }
  }, 50);
}

function confirmarVentaPesoModal(event) {
  if (event) event.preventDefault();
  const producto = pendingVentaPesoProducto;
  const r = calcularVentaPeso();
  if (!producto || !r) {
    toast('Ingrese un peso o monto válido.', 'warn');
    document.getElementById('ventaPesoValor').focus();
    return;
  }
  cerrarModalVentaPeso();
  agregarAlCarrito(producto, r.kg);
}

let pendingPrecioVariableItem = null;

function abrirModalPrecioVariable(producto, cantidad = 1, factor = 1, descPack = '', esPromoPack = false) {
  pendingPrecioVariableItem = { producto, cantidad, factor, descPack, esPromoPack };
  
  const modal = document.getElementById('precioVariableModal');
  const nomEl = document.getElementById('precioVariableNombre');
  const metaEl = document.getElementById('precioVariableMeta');
  const montoInput = document.getElementById('precioVariableMonto');

  if (nomEl) nomEl.textContent = producto.Nombre;
  if (metaEl) {
    const stockStr = (producto.Stock !== undefined && producto.Stock !== null) ? `Stock: ${producto.Stock} un.` : '';
    const codStr = producto.CodigoBarras ? `Código: ${producto.CodigoBarras}` : (producto.CodigoPLU ? `PLU: #${producto.CodigoPLU}` : '');
    metaEl.textContent = [codStr, stockStr].filter(Boolean).join(' • ') || 'Ingrese el precio acordado para este producto';
  }

  // Si tiene un precio sugerido mayor a 0, precargarlo; sino dejar vacío
  if (montoInput) {
    const sug = parseInt(producto.PrecioVenta) || 0;
    montoInput.value = sug > 0 ? sug : '';
  }

  if (modal) {
    modal.style.display = 'flex';
    setTimeout(() => {
      if (montoInput) {
        montoInput.focus();
        montoInput.select();
      }
    }, 80);
  }
}

function cerrarModalPrecioVariable() {
  const modal = document.getElementById('precioVariableModal');
  if (modal) modal.style.display = 'none';
  pendingPrecioVariableItem = null;

  // Devolver el foco al escáner según el modo
  setTimeout(() => {
    if (window.modoPosLayout === 'supermercado' || (typeof modoPosLayout !== 'undefined' && modoPosLayout === 'supermercado')) {
      const superInput = document.getElementById('posSearchSuper');
      if (superInput) { superInput.focus(); superInput.select(); }
    } else {
      const normalInput = document.getElementById('posSearch');
      if (normalInput) { normalInput.focus(); normalInput.select(); }
    }
  }, 50);
}

function confirmarPrecioVariableModal(event) {
  if (event) event.preventDefault();
  if (!pendingPrecioVariableItem) return;

  const montoInput = document.getElementById('precioVariableMonto');
  const precio = parseInt(montoInput ? montoInput.value : 0) || 0;

  if (precio <= 0) {
    toast('Por favor ingrese un precio mayor a $0.', 'warn');
    if (montoInput) montoInput.focus();
    return;
  }

  const item = pendingPrecioVariableItem;
  cerrarModalPrecioVariable();

  // Llamar agregarAlCarrito con el precio variable asignado explícitamente en precioPack
  agregarAlCarrito(item.producto, item.cantidad, item.factor, precio, item.descPack, item.esPromoPack);
}

function agregarAlCarrito(producto, cantidad = 1, factor = 1, precioPack = null, descPack = '', esPromoPack = false) {
  // Si el producto tiene precio variable y no se le ha asignado precio aún, pedirlo en modal
  if (parseInt(producto.EsPrecioVariable) === 1 && (precioPack === null || precioPack === undefined)) {
    abrirModalPrecioVariable(producto, cantidad, factor, descPack, esPromoPack);
    return;
  }

  factor = parseFloat(factor) || 1;
  const precioBaseUnitario = parseInt(producto.PrecioVenta) || 0;

  // Si no vino precioPack explícito pero factor > 1 y tiene promo activa (ej. llamadas programáticas)
  if ((precioPack === null || precioPack === undefined) && factor > 1) {
    if (producto.PromoTipo === 'MULTIBUY' && parseFloat(producto.PromoCantMin) > 0) {
      const cantMin = parseFloat(producto.PromoCantMin);
      const precioOf = parseInt(producto.PromoPrecioOf);
      if (factor >= cantMin && factor % cantMin === 0) {
        precioPack = Math.round((factor / cantMin) * precioOf);
        esPromoPack = true;
      }
    } else if (producto.PromoTipo === 'DESCUENTO_UNIT' && parseFloat(producto.PromoDescPorc) > 0) {
      const descUnit = Math.round(precioBaseUnitario * (parseFloat(producto.PromoDescPorc) / 100));
      precioPack = Math.round((precioBaseUnitario - descUnit) * factor);
      esPromoPack = true;
    }
  }

  const precioUnitario = (precioPack !== null && precioPack !== undefined && parseInt(precioPack) > 0)
    ? parseInt(precioPack)
    : Math.round(precioBaseUnitario * (factor > 1 && factor === Math.floor(factor) ? factor : 1));

  const nombreFinal = descPack ? `${producto.Nombre} (${descPack})` : producto.Nombre;
  const codigoUsado = producto.CodigoAltMatch || producto.CodigoBarras || '';

  // Buscar si ya existe exactamente esta misma presentación (mismo producto, mismo factor y mismo precio)
  const existIndex = cart.findIndex(item => item.ProductoID == producto.ProductoID && (item.factor || 1) == factor && item.PrecioVenta == precioUnitario);
  
  // Unidades físicas totales que este producto ya ocupa en el carrito
  const totalFisicoActual = cart
    .filter(item => item.ProductoID == producto.ProductoID)
    .reduce((sum, item) => sum + (item.cantidad * (item.factor || 1)), 0);

  const unidadesNuevas = cantidad * factor;

  const controlaStock = producto.ControlaStock === undefined || parseInt(producto.ControlaStock) === 1;
  if (controlaStock && !permiteStockNegativo() && totalFisicoActual + unidadesNuevas > parseFloat(producto.Stock)) {
    toast(`No hay suficiente stock para "${producto.Nombre}". Disponible: ${producto.Stock} unidades (en carrito: ${totalFisicoActual}).`, 'warn');
    return;
  }

  if (existIndex > -1) {
    // Redondeo a 3 decimales (gramos) para que las pesadas sumadas no arrastren error de punto flotante
    cart[existIndex].cantidad = Math.round((cart[existIndex].cantidad + cantidad) * 1000) / 1000;
  } else {
    cart.push({
      ProductoID: producto.ProductoID,
      CodigoBarras: codigoUsado,
      CodigoPLU: producto.CodigoPLU || '',
      Nombre: nombreFinal,
      NombreBase: producto.Nombre,
      PrecioBaseUnitario: precioBaseUnitario,
      PrecioVenta: precioUnitario,
      Stock: parseFloat(producto.Stock),
      ControlaStock: producto.ControlaStock !== undefined ? parseInt(producto.ControlaStock) : 1,
      cantidad: cantidad,
      factor: factor,
      descPack: descPack,
      esPromoPack: esPromoPack,
      EsPesable: producto.EsPesable,
      EsPrecioVariable: producto.EsPrecioVariable,
      PromocionID: factor <= 1 ? (producto.PromoTipo ? producto.PromocionID : null) : null,
      PromoTipo: factor <= 1 ? (producto.PromoTipo || null) : null,
      PromoCantMin: factor <= 1 ? (parseFloat(producto.PromoCantMin) || 0) : 0,
      PromoDescPorc: factor <= 1 ? (parseFloat(producto.PromoDescPorc) || 0) : 0,
      PromoPrecioOf: factor <= 1 ? (parseInt(producto.PromoPrecioOf) || 0) : 0
    });
  }

  playBeep();
  renderCart();
}

// Configuración "Vender con stock negativo": si está permitido, el POS no bloquea por stock
// (el backend aplica la misma regla en api/registrar_venta.php)
function permiteStockNegativo() {
  return window.CONFIG_SUPERVISION?.PERMITIR_STOCK_NEGATIVO === 'true';
}

async function eliminarItemCarrito(index) {
  const item = cart[index];
  if (!item) return;

  const reqSup = (window.CONFIG_SUPERVISION?.POS_REQ_SUPERVISOR_ELIMINAR_ITEM ?? 'SI') === 'SI';
  if (window.CURRENT_USER_ROL === 'Cajero' && reqSup) {
    const auth = await pedirAutorizacionSupervisor('eliminar_item_carrito', item.Nombre);
    if (!auth.autorizado) return;
  } else {
    const ok = await confirmDialog({
      title: 'Quitar producto',
      message: `¿Seguro que deseas quitar "${item.Nombre}" del carrito?`,
      confirmText: 'Quitar',
      danger: true
    });
    if (!ok) return;
  }

  cart.splice(index, 1);
  if (cart.length === 0) {
    resetDescuento();
  } else {
    onDescuentoInputChange();
  }
  renderCart();
  toast(`"${item.Nombre}" retirado del carrito.`, 'info');
}

function cambiarCantidad(index, delta) {
  const item = cart[index];
  if (!item) return;

  const step = (parseInt(item.EsPesable) === 1) ? 0.1 : 1;
  const nuevaCant = Math.round((item.cantidad + (delta * step)) * 1000) / 1000;

  if (nuevaCant <= 0) {
    eliminarItemCarrito(index);
    return;
  }

  const factor = item.factor || 1;
  const totalFisicoOtros = cart
    .filter((it, idx) => idx !== index && it.ProductoID === item.ProductoID)
    .reduce((sum, it) => sum + (it.cantidad * (it.factor || 1)), 0);

  const controlaStock = item.ControlaStock === undefined || parseInt(item.ControlaStock) === 1;
  if (controlaStock && !permiteStockNegativo() && (totalFisicoOtros + (nuevaCant * factor)) > item.Stock) {
    toast(`Stock máximo disponible: ${item.Stock} unidades (${Math.floor(item.Stock / factor)} packs)`, 'warn');
    return;
  }
  item.cantidad = nuevaCant;
  onDescuentoInputChange();
  renderCart();
}

function calcularDescuentoItem(item) {
  if (!item.PromocionID || !item.PromoTipo) return 0;
  
  if (item.PromoTipo === 'DESCUENTO_UNIT') {
    const descUnit = Math.round(item.PrecioVenta * (item.PromoDescPorc / 100));
    return Math.round(item.cantidad * descUnit);
  } else if (item.PromoTipo === 'MULTIBUY') {
    const cantMin = item.PromoCantMin;
    const precioOf = item.PromoPrecioOf;
    
    if (item.cantidad >= cantMin) {
      const packs = Math.floor(item.cantidad / cantMin);
      const resto = item.cantidad % cantMin;
      const subtotalConPromo = (packs * precioOf) + (resto * item.PrecioVenta);
      const subtotalNormal = Math.round(item.cantidad * item.PrecioVenta);
      return Math.max(0, subtotalNormal - subtotalConPromo);
    }
  }
  return 0;
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const totalEl = document.getElementById('cartTotal');
  const superTable = document.getElementById('cartTable');
  const superTableBody = document.getElementById('cartTableBody');
  const superTableEmpty = document.getElementById('cartTableEmpty');
  const superTotalEl = document.getElementById('superCartTotal');
  const superItemCountEl = document.getElementById('superItemCount');

  let totalFinal = getCartTotal();

  // 1. RENDER VISTA ESTÁNDAR (Clásico y Táctil)
  if (container) {
    if (cart.length === 0) {
      container.innerHTML = `
        <div class="cart-empty">
          <i class="fa-solid fa-basket-shopping"></i>
          <p>El carrito está vacío</p>
          <span>Escanea o haz clic en un producto</span>
        </div>
      `;
    } else {
      container.innerHTML = cart.map((item, idx) => {
        const subtotalNormal = Math.round(item.cantidad * item.PrecioVenta);
        const desc = calcularDescuentoItem(item);
        const subtotalFinal = subtotalNormal - desc;

        let promoBadgeHtml = '';
        let oldPriceHtml = '';

        if (desc > 0) {
          if (item.PromoTipo === 'DESCUENTO_UNIT') {
            promoBadgeHtml = `<span class="promo-badge">-${item.PromoDescPorc}% Dcto</span>`;
          } else if (item.PromoTipo === 'MULTIBUY') {
            promoBadgeHtml = `<span class="promo-badge promo-badge--pack">Promo Pack</span>`;
          }
          oldPriceHtml = `<span class="cart-item__old">$${formatNumber(subtotalNormal)}</span>`;
        } else if (item.esPromoPack) {
          promoBadgeHtml = `<span class="promo-badge promo-badge--pack">Promo Pack</span>`;
          const subtotalBase = item.cantidad * Math.round((item.PrecioBaseUnitario || item.PrecioVenta) * (item.factor || 1));
          if (subtotalBase > subtotalNormal) {
            oldPriceHtml = `<span class="cart-item__old">$${formatNumber(subtotalBase)}</span>`;
          }
        }

        return `
          <div class="cart-item">
            <div class="cart-item__main">
              <div class="cart-item__name">
                ${escapeHtml(item.Nombre)}
                ${parseInt(item.EsPrecioVariable) === 1 ? ' <span style="font-size: 0.68rem; padding: 0.1rem 0.35rem; border-radius: 4px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);"><i class="fa-solid fa-tag"></i> Var</span>' : ''}
              </div>
              <div class="cart-item__unit">$${formatNumber(item.PrecioVenta)} c/u ${promoBadgeHtml}</div>
            </div>

            <div class="cart-item__actions">
              <div class="qty-controls">
                <button class="btn-qty" onclick="cambiarCantidad(${idx}, -1)">&minus;</button>
                <span class="qty-value">${item.cantidad}</span>
                <button class="btn-qty" onclick="cambiarCantidad(${idx}, 1)">+</button>
              </div>
              <div class="cart-item__subtotal">
                ${oldPriceHtml}<span>$${formatNumber(subtotalFinal)}</span>
                <button type="button" class="btn-delete-item" onclick="eliminarItemCarrito(${idx})" title="Eliminar producto del carrito">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }
  }

  // 2. RENDER VISTA SUPERMERCADO (Tabla central amplia)
  if (superTableBody) {
    if (cart.length === 0) {
      if (superTableEmpty) superTableEmpty.style.display = 'block';
      if (superTable) superTable.style.display = 'none';
      superTableBody.innerHTML = '';
    } else {
      if (superTableEmpty) superTableEmpty.style.display = 'none';
      if (superTable) superTable.style.display = 'table';
      
      superTableBody.innerHTML = cart.map((item, idx) => {
        const subtotalNormal = Math.round(item.cantidad * item.PrecioVenta);
        const desc = calcularDescuentoItem(item);
        const subtotalFinal = subtotalNormal - desc;

        let promoCellHtml = '<span style="color: var(--text-muted);">&minus;</span>';
        if (desc > 0) {
          if (item.PromoTipo === 'DESCUENTO_UNIT') {
            promoCellHtml = `<span class="promo-badge">-${item.PromoDescPorc}% ($${formatNumber(desc)})</span>`;
          } else if (item.PromoTipo === 'MULTIBUY') {
            promoCellHtml = `<span class="promo-badge promo-badge--pack">Pack (-$${formatNumber(desc)})</span>`;
          }
        } else if (item.esPromoPack) {
          const ahorro = Math.max(0, (((item.PrecioBaseUnitario || item.PrecioVenta) * (item.factor || 1)) - item.PrecioVenta) * item.cantidad);
          promoCellHtml = `<span class="promo-badge promo-badge--pack">Pack (-$${formatNumber(ahorro)})</span>`;
        }

        const sku = item.CodigoBarras ? item.CodigoBarras : (item.CodigoPLU ? `PLU #${item.CodigoPLU}` : '&minus;');
        const esPesableBadge = parseInt(item.EsPesable) === 1 ? '<span style="font-size: 0.72rem; color: #818cf8; margin-left: 0.4rem;"><i class="fa-solid fa-weight-scale"></i> Kg</span>' : '';
        const esVariableBadge = parseInt(item.EsPrecioVariable) === 1 ? '<span style="font-size: 0.68rem; padding: 0.1rem 0.35rem; border-radius: 4px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); margin-left: 0.35rem;" title="Precio asignado en caja"><i class="fa-solid fa-tag"></i> Var</span>' : '';

        return `
          <tr>
            <td class="item-num" style="text-align: center;">${idx + 1}</td>
            <td class="item-sku">${sku}</td>
            <td>
              <div class="item-desc">${escapeHtml(item.Nombre)} ${esPesableBadge} ${esVariableBadge}</div>
            </td>
            <td class="item-price">$${formatNumber(item.PrecioVenta)}</td>
            <td style="text-align: center;">
              <div class="qty-controls" style="display: inline-flex;">
                <button type="button" class="btn-qty" onclick="cambiarCantidad(${idx}, -1)">&minus;</button>
                <span class="qty-value">${item.cantidad}</span>
                <button type="button" class="btn-qty" onclick="cambiarCantidad(${idx}, 1)">+</button>
              </div>
            </td>
            <td>${promoCellHtml}</td>
            <td class="item-subtotal">$${formatNumber(subtotalFinal)}</td>
            <td style="text-align: center;">
              <button type="button" class="btn-delete-item" onclick="eliminarItemCarrito(${idx})" title="Quitar producto de la venta">
                <i class="fa-solid fa-trash-can" style="font-size: 1.05rem;"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    }
  }

  // Totales en ambos formatos
  if (totalEl) totalEl.textContent = `$${formatNumber(totalFinal)}`;
  if (superTotalEl) superTotalEl.textContent = `$${formatNumber(totalFinal)}`;
  if (superItemCountEl) {
    const totalCant = cart.reduce((s, i) => s + i.cantidad, 0);
    const itemS = cart.length === 1 ? 'ítem' : 'ítems';
    superItemCountEl.textContent = `${cart.length} ${itemS} (${totalCant} un.)`;
  }

  // Cliente, Documento, Descuento y Vale ahora viven dentro del modal de pago,
  // así que si se editan con el modal abierto hay que refrescar el total y el vuelto ahí también.
  const pagoModalEl = document.getElementById('pagoModal');
  if (pagoModalEl && pagoModalEl.style.display === 'flex') {
    refrescarResumenPago();
  }

  // Actualizar el infoEl dinámicamente según el estado del carrito
  const infoEl = document.getElementById('valeAplicadoInfo');
  if (valeAplicado && infoEl) {
    const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
    const subtotalTrasDescuento = Math.max(0, getCartSubtotal() - descGlobal);
    if (valeAplicado.codigo.startsWith('TC-') && subtotalTrasDescuento < valeAplicado.disponible) {
      infoEl.style.display = 'block';
      infoEl.style.color = 'var(--danger)';
      infoEl.textContent = `Cambio inválido: el total de la compra ($${formatNumber(subtotalTrasDescuento)}) debe ser igual o mayor al Ticket de Cambio ($${formatNumber(valeAplicado.disponible)}).`;
    } else {
      infoEl.style.display = 'block';
      infoEl.style.color = 'var(--success)';
      infoEl.textContent = `Vale válido: se aplican $${formatNumber(getValeMontoAplicado())} de $${formatNumber(valeAplicado.disponible)} disponibles.`;
    }
  }
}

function getCartSubtotal() {
  return cart.reduce((sum, item) => sum + Math.round(item.cantidad * item.PrecioVenta) - calcularDescuentoItem(item), 0);
}

function getValeMontoAplicado() {
  if (!valeAplicado) return 0;
  const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
  const subtotalTrasDescuento = Math.max(0, getCartSubtotal() - descGlobal);
  
  // Si es un Ticket de Cambio (TC-), el subtotal debe ser igual o mayor al saldo del vale
  if (valeAplicado.codigo.startsWith('TC-') && subtotalTrasDescuento < valeAplicado.disponible) {
    return 0;
  }
  
  return Math.min(valeAplicado.disponible, subtotalTrasDescuento);
}

function getCartTotal() {
  const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
  const subtotal = Math.max(0, getCartSubtotal() - descGlobal);
  return Math.max(0, subtotal - getValeMontoAplicado());
}

async function aplicarValeCarrito() {
  const codigo = document.getElementById('valeCodigoInput').value.trim().toUpperCase();
  const infoEl = document.getElementById('valeAplicadoInfo');

  if (!codigo) {
    valeAplicado = null;
    infoEl.style.display = 'none';
    renderCart();
    return;
  }

  try {
    const res = await fetch(`api/verificar_vale.php?codigo=${encodeURIComponent(codigo)}`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    // Auto-llenar el código del vale real por si se buscó por número de boleta
    valeAplicado = { codigo: data.codigo, disponible: data.disponible };
    document.getElementById('valeCodigoInput').value = data.codigo;
    
    // Validar restricción inmediata de Ticket de Cambio
    const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
    const subtotalTrasDescuento = Math.max(0, getCartSubtotal() - descGlobal);
    if (data.codigo.startsWith('TC-') && subtotalTrasDescuento < data.disponible) {
      valeAplicado = null;
      document.getElementById('valeCodigoInput').value = '';
      throw new Error(`Para cambios de mercadería, el total de la compra ($${formatNumber(subtotalTrasDescuento)}) debe ser igual o mayor al valor del Ticket de Cambio ($${formatNumber(data.disponible)}).`);
    }

    renderCart();
    infoEl.style.display = 'block';
    infoEl.style.color = 'var(--success)';
    infoEl.textContent = `Vale válido: se aplican $${formatNumber(getValeMontoAplicado())} de $${formatNumber(data.disponible)} disponibles.`;
  } catch (err) {
    valeAplicado = null;
    renderCart();
    infoEl.style.display = 'block';
    infoEl.style.color = 'var(--danger)';
    infoEl.textContent = err.message;
  }
}

/* Modal FormPagoPOS */
function abrirModalPago() {
  if (cart.length === 0) {
    toast('Agrega productos al carrito antes de cobrar.', 'warn');
    return;
  }

  puntosCanje = 0;
  const inpPts = document.getElementById('puntosCanjeInput');
  if (inpPts) inpPts.value = '';
  ['mixtoEfectivo', 'mixtoTarjeta', 'mixtoTransf', 'mixtoFiado'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const mRec = document.getElementById('montoRecibidoModal');
  if (mRec) mRec.value = getTotalPorPagar();
  onClientePagoChange();

  // Cada venta parte en Efectivo
  const btnEfec = document.querySelector('.btn-metodo[data-metodo="Efectivo"]');
  setFormaPago('Efectivo', btnEfec);

  const modal = document.getElementById('pagoModal');
  if (modal) modal.style.display = 'flex';
}

// Lo que queda por pagar con la forma de pago elegida, descontados los puntos canjeados
function getTotalPorPagar() {
  return Math.max(0, getCartTotal() - puntosCanje);
}

function getClientePago() {
  const sel = document.getElementById('clienteSelect');
  const opt = sel ? sel.selectedOptions[0] : null;
  if (!opt || !opt.value) return null;
  return {
    id: opt.value,
    puntos: parseInt(opt.dataset.puntos) || 0,
    limite: parseInt(opt.dataset.limite) || 0,
    cupo: parseInt(opt.dataset.cupo) || 0,
  };
}

function onClientePagoChange() {
  const cli = getClientePago();
  const card = document.getElementById('pmClienteCard');
  if (!card) return;

  // Al cambiar de cliente, el canje anterior deja de valer
  if (puntosCanje > 0 && (!cli || cli.puntos < puntosCanje)) {
    puntosCanje = 0;
  }

  if (!cli) {
    card.style.display = 'none';
  } else {
    card.style.display = 'flex';
    const bPts = document.getElementById('pmBadgePuntos');
    if (bPts) {
      if (window.LOYALTY_ACTIVO === false) {
        bPts.style.display = 'none';
      } else {
        bPts.style.display = 'inline-flex';
        bPts.innerHTML = `<i class="fa-solid fa-star"></i> ${formatNumber(cli.puntos)} pts`;
        bPts.className = 'pm-badge ' + (cli.puntos > 0 ? 'pts' : 'sin');
      }
    }
    const bCupo = document.getElementById('pmBadgeCupo');
    if (bCupo) {
      if (window.CREDITO_INTERNO_ACTIVO === false) {
        bCupo.style.display = 'none';
      } else {
        bCupo.style.display = 'inline-flex';
        bCupo.innerHTML = cli.limite > 0
          ? `<i class="fa-solid fa-handshake"></i> Cupo fiado $${formatNumber(cli.cupo)}`
          : `<i class="fa-solid fa-handshake"></i> Sin crédito`;
        bCupo.className = 'pm-badge ' + (cli.limite > 0 && cli.cupo > 0 ? 'cupo' : 'sin');
      }
    }
    const cBox = document.getElementById('pmCanjeBox');
    if (cBox) {
      cBox.style.display = (window.LOYALTY_ACTIVO !== false && cli.puntos > 0) ? 'block' : 'none';
    }
    const inp = document.getElementById('puntosCanjeInput');
    if (inp) {
      inp.placeholder = `Hasta ${formatNumber(Math.min(cli.puntos, getCartTotal()))} pts`;
    }
  }
  refrescarResumenPago();
}

function aplicarCanjePuntos() {
  if (window.LOYALTY_ACTIVO === false) {
    toast('El Club de Puntos está deshabilitado en la configuración.', 'warn');
    return;
  }
  const cli = getClientePago();
  if (!cli) return;
  const inp = document.getElementById('puntosCanjeInput');
  const maximo = Math.min(cli.puntos, getCartTotal());
  let pts = inp.value === '' ? maximo : (parseInt(inp.value) || 0);
  if (pts <= 0) {
    toast('Ingresa cuántos puntos usar.', 'warn');
    return;
  }
  if (pts > maximo) {
    toast(`Se pueden usar como máximo ${formatNumber(maximo)} puntos en esta venta.`, 'info');
    pts = maximo;
  }
  puntosCanje = pts;
  inp.value = '';
  document.getElementById('montoRecibidoModal').value = getTotalPorPagar();
  refrescarResumenPago();
}

function quitarCanjePuntos() {
  puntosCanje = 0;
  document.getElementById('montoRecibidoModal').value = getTotalPorPagar();
  refrescarResumenPago();
}

// Total, desglose, canje y paneles del modal de cobro
function refrescarResumenPago() {
  const totalVenta = getCartTotal();
  if (puntosCanje > totalVenta) puntosCanje = totalVenta;
  const porPagar = getTotalPorPagar();

  document.getElementById('modalMontoTotal').textContent = `$${formatNumber(porPagar)}`;
  const nItems = cart.length;
  document.getElementById('modalItemsResumen').textContent = `${nItems} ${nItems === 1 ? 'producto' : 'productos'} en la venta`;

  const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
  const vale = getValeMontoAplicado();
  const filas = [];
  if (descGlobal > 0 || vale > 0 || puntosCanje > 0) {
    filas.push(`<div><span>Subtotal</span><span>$${formatNumber(getCartSubtotal())}</span></div>`);
    if (descGlobal > 0) filas.push(`<div class="neg"><span>Descuento</span><span>-$${formatNumber(descGlobal)}</span></div>`);
    if (vale > 0) filas.push(`<div class="neg"><span>Vale aplicado</span><span>-$${formatNumber(vale)}</span></div>`);
    if (puntosCanje > 0) filas.push(`<div class="neg"><span><i class="fa-solid fa-star"></i> Puntos canjeados</span><span>-$${formatNumber(puntosCanje)}</span></div>`);
  }
  const desg = document.getElementById('pmDesglose');
  desg.innerHTML = filas.join('');
  desg.style.display = filas.length ? 'flex' : 'none';

  const canjeOk = puntosCanje > 0;
  const fCanje = document.getElementById('pmCanjeForm');
  if (fCanje) fCanje.style.display = canjeOk ? 'none' : 'flex';
  const oCanje = document.getElementById('pmCanjeOk');
  if (oCanje) oCanje.style.display = canjeOk ? 'flex' : 'none';
  const tCanje = document.getElementById('pmCanjeTexto');
  if (tCanje) tCanje.textContent = `Usando ${formatNumber(puntosCanje)} pts (-$${formatNumber(puntosCanje)})`;

  calcularVueltoModal();
}

function cerrarModalPago() {
  document.getElementById('pagoModal').style.display = 'none';
}

function setFormaPago(metodo, btn) {
  metodoSeleccionadoModal = metodo;

  document.querySelectorAll('.btn-metodo').forEach(b => b.classList.toggle('active', b === btn));

  const pnlEfec = document.getElementById('panelEfectivoModal');
  const pnlMix = document.getElementById('panelMixtoModal');
  const pnlInfo = document.getElementById('panelInfoMetodoModal');

  if (pnlEfec) pnlEfec.style.display = metodo === 'Efectivo' ? 'block' : 'none';
  if (pnlMix) pnlMix.style.display = metodo === 'Mixto' ? 'block' : 'none';

  const info = INFO_METODO_PAGO[metodo];
  if (pnlInfo) {
    pnlInfo.style.display = info ? 'block' : 'none';
    if (info) {
      const icon = document.getElementById('infoMetodoIcon');
      icon.innerHTML = `<i class="fa-solid ${info.icono}"></i>`;
      icon.style.setProperty('--m-color', btn ? btn.style.getPropertyValue('--m-color') : '');
      document.getElementById('infoMetodoTitulo').textContent = info.titulo;
      document.getElementById('infoMetodoTexto').textContent = info.texto;
    }
  }

  if (metodo === 'Mixto') actualizarResumenMixto();
  actualizarBotonConfirmarPago();
}

const INFO_METODO_PAGO = {
  'Tarjeta Debito': { icono: 'fa-credit-card', titulo: 'Pago con tarjeta', texto: 'Cobra en el terminal (POS) y confirma cuando el pago esté aprobado.' },
  'Transferencia': { icono: 'fa-building-columns', titulo: 'Pago por transferencia', texto: 'Verifica que la transferencia haya llegado antes de confirmar.' },
  'Credito': { icono: 'fa-handshake', titulo: 'Fiado / crédito interno', texto: 'Se carga a la cuenta del cliente. Debes seleccionar un cliente.' },
  'Puntos': { icono: 'fa-star', titulo: 'Pago con puntos', texto: 'Se descuentan los puntos acumulados del cliente seleccionado.' },
};

const NOMBRE_METODO_BOTON = {
  'Efectivo': 'Efectivo', 'Tarjeta Debito': 'Tarjeta', 'Transferencia': 'Transferencia',
  'Credito': 'Fiado / Crédito', 'Puntos': 'Puntos', 'Mixto': 'Pago mixto',
};

// El botón deja claro cuánto se cobra y con qué forma de pago
function textoBotonConfirmarPago() {
  const total = getTotalPorPagar();
  const metodo = NOMBRE_METODO_BOTON[metodoSeleccionadoModal] || metodoSeleccionadoModal;
  const detalle = [puntosCanje > 0 ? 'Puntos' : null, total > 0 || puntosCanje === 0 ? metodo : null].filter(Boolean).join(' + ');
  return `<i class="fa-solid fa-check-double"></i> COBRAR $${formatNumber(total)} <small>· ${detalle}</small>`;
}

function actualizarBotonConfirmarPago() {
  const btn = document.getElementById('btnConfirmarPagoModal');
  if (btn && !btn.disabled) btn.innerHTML = textoBotonConfirmarPago();
}

function actualizarResumenMixto() {
  const el = document.getElementById('mixtoResumen');
  if (!el) return;
  const total = getTotalPorPagar();
  const val = id => parseInt(document.getElementById(id).value) || 0;
  const suma = val('mixtoEfectivo') + val('mixtoTarjeta') + val('mixtoTransf') + val('mixtoFiado');
  const noEfectivo = suma - val('mixtoEfectivo');

  // Mismo formato que en efectivo: Recibido | Vuelto (o Falta, en rojo)
  const falta = suma < total;
  document.getElementById('mixtoRecibido').textContent = `$${formatNumber(suma)}`;
  document.getElementById('mixtoVueltoBox').classList.toggle('falta', falta);
  document.getElementById('mixtoVueltoLabel').textContent = falta ? 'Falta' : 'Vuelto';
  document.getElementById('mixtoVuelto').textContent = `$${formatNumber(Math.abs(suma - total))}`;

  // Solo se muestra un aviso cuando hay un error que corregir
  if (noEfectivo > total) {
    el.style.display = 'block';
    el.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> Tarjeta, transferencia y fiado no pueden superar $${formatNumber(total)}`;
  } else {
    el.style.display = 'none';
  }
}

function setMontoQuick(val) {
  const total = getTotalPorPagar();
  const input = document.getElementById('montoRecibidoModal');
  if (val === 'exacto') {
    input.value = total;
  } else {
    input.value = val;
  }
  calcularVueltoModal();
}

function calcularVueltoModal() {
  const total = getTotalPorPagar();
  const recibido = parseInt(document.getElementById('montoRecibidoModal').value) || 0;
  const vueltoEl = document.getElementById('vueltoModal');
  const vueltoBox = document.getElementById('vueltoModalBox');
  const vueltoLabel = document.getElementById('vueltoLabelModal');

  const falta = recibido < total;
  vueltoEl.textContent = `$${formatNumber(falta ? total - recibido : recibido - total)}`;
  if (vueltoBox) vueltoBox.classList.toggle('falta', falta);
  if (vueltoLabel) vueltoLabel.textContent = falta ? 'Falta' : 'Vuelto';

  document.querySelectorAll('#pagoModal .pm-chip').forEach(chip => {
    const q = chip.dataset.quick;
    chip.classList.toggle('active', q === 'exacto' ? recibido === total : parseInt(q) === recibido);
  });

  if (metodoSeleccionadoModal === 'Mixto') actualizarResumenMixto();
  actualizarBotonConfirmarPago();
}

async function confirmarPagoModal() {
  // Validar restricciones de Ticket de Cambio antes de proceder
  if (valeAplicado && valeAplicado.codigo.startsWith('TC-')) {
    const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;
    const subtotalTrasDescuento = Math.max(0, getCartSubtotal() - descGlobal);
    if (subtotalTrasDescuento < valeAplicado.disponible) {
      toast(`Para cambios de mercadería, el total de la compra ($${formatNumber(subtotalTrasDescuento)}) debe ser igual o mayor al valor del Ticket de Cambio ($${formatNumber(valeAplicado.disponible)}).`, 'error');
      return;
    }
  }

  const total = getCartTotal();
  const tipoDoc = document.getElementById('tipoDocumento').value;
  const clienteID = document.getElementById('clienteSelect').value;
  const descGlobal = parseInt(document.getElementById('descuentoGlobal').value) || 0;

  // Validación de descuento que requiera supervisor
  const subtotalBruto = cart.reduce((s, i) => s + Math.round(i.cantidad * i.PrecioVenta), 0);
  const maxPorc = parseFloat(window.CONFIG_SUPERVISION?.POS_DESCUENTO_MAX_PORC ?? 5);
  const pctEfectivo = subtotalBruto > 0 ? (descGlobal / subtotalBruto) * 100 : 0;

  let supervisorPass = null;
  if (window.CURRENT_USER_ROL === 'Cajero' && descGlobal > 0 && (pctEfectivo > maxPorc || maxPorc === 0)) {
    if (supervisorPassAutorizadoDescuento) {
      supervisorPass = supervisorPassAutorizadoDescuento;
    } else {
      const inputInline = document.getElementById('inputPassSupervisorInline');
      if (inputInline && inputInline.value.trim() !== '') {
        const okInline = await validarSupervisorDescuentoInline();
        if (!okInline) return;
        supervisorPass = supervisorPassAutorizadoDescuento;
      } else {
        const auth = await pedirAutorizacionSupervisor('descuento_excedido', `Descuento de $${formatNumber(descGlobal)} (${pctEfectivo.toFixed(1)}%)`);
        if (!auth.autorizado) {
          return;
        }
        supervisorPass = auth.supervisor_pass;
        supervisorPassAutorizadoDescuento = auth.supervisor_pass;
        const boxInline = document.getElementById('boxSupervisorAuthInline');
        const boxOk = document.getElementById('boxSupervisorAuthOk');
        const txtOk = document.getElementById('txtSupervisorAuthOk');
        if (boxInline) boxInline.style.display = 'none';
        if (boxOk) {
          boxOk.style.display = 'flex';
          if (txtOk) txtOk.textContent = `Autorizado por ${auth.supervisor_nombre}`;
        }
      }
    }
  }

  let pagos = [];
  const porPagar = getTotalPorPagar();
  let recibido = porPagar;
  let vuelto = 0;
  const cliPago = getClientePago();

  if (puntosCanje > 0 && !clienteID) {
    toast('Selecciona el cliente para usar sus puntos.', 'warn');
    return;
  }

  if (porPagar <= 0) {
    // Los puntos cubren todo: no hay otro pago que registrar
  } else if (metodoSeleccionadoModal === 'Efectivo') {
    recibido = parseInt(document.getElementById('montoRecibidoModal').value) || porPagar;
    if (recibido < porPagar) {
      toast(`El monto recibido ($${formatNumber(recibido)}) es menor a lo que falta pagar ($${formatNumber(porPagar)}).`, 'warn');
      return;
    }
    vuelto = Math.max(0, recibido - porPagar);
    pagos.push({ metodo: 'Efectivo', monto: porPagar });
  } else if (metodoSeleccionadoModal === 'Mixto') {
    const val = id => parseInt(document.getElementById(id).value) || 0;
    const efec = val('mixtoEfectivo');
    const tarj = val('mixtoTarjeta');
    const transf = val('mixtoTransf');
    const fiado = val('mixtoFiado');
    const sumaMixto = efec + tarj + transf + fiado;

    if (tarj + transf + fiado > porPagar) {
      toast(`Tarjeta, transferencia y fiado suman más que el total ($${formatNumber(porPagar)}). Solo el efectivo puede dar vuelto.`, 'warn');
      return;
    }
    if (sumaMixto < porPagar) {
      toast(`La suma del pago mixto ($${formatNumber(sumaMixto)}) no cubre el total ($${formatNumber(porPagar)}).`, 'warn');
      return;
    }
    if (fiado > 0) {
      if (window.CREDITO_INTERNO_ACTIVO === false) {
        toast('Las ventas a crédito/fiado están deshabilitadas en la configuración del sistema.', 'error');
        return;
      }
      if (!clienteID) {
        toast('Selecciona el cliente para cargar una parte a fiado.', 'warn');
        return;
      }
      if (cliPago && fiado > cliPago.cupo) {
        toast(`El cliente solo tiene $${formatNumber(cliPago.cupo)} de cupo para fiado.`, 'warn');
        return;
      }
    }

    // El exceso solo puede venir del efectivo: se entrega como vuelto
    vuelto = sumaMixto - porPagar;
    recibido = sumaMixto;
    if (efec - vuelto > 0) pagos.push({ metodo: 'Efectivo', monto: efec - vuelto });
    if (tarj > 0) pagos.push({ metodo: 'Tarjeta Debito', monto: tarj });
    if (transf > 0) pagos.push({ metodo: 'Transferencia', monto: transf });
    if (fiado > 0) pagos.push({ metodo: 'Credito Interno', monto: fiado });
  } else if (metodoSeleccionadoModal === 'Credito' || metodoSeleccionadoModal === 'Puntos') {
    if (metodoSeleccionadoModal === 'Credito' && window.CREDITO_INTERNO_ACTIVO === false) {
      toast('Las ventas a crédito/fiado están deshabilitadas en la configuración del sistema.', 'error');
      return;
    }
    if (metodoSeleccionadoModal === 'Puntos' && window.LOYALTY_ACTIVO === false) {
      toast('El canje de puntos está deshabilitado en la configuración del sistema.', 'error');
      return;
    }
    if (!clienteID) {
      toast('Debes seleccionar un Cliente para pagos a Crédito / Fiado o Puntos.', 'warn');
      return;
    }
    const metodoDb = metodoSeleccionadoModal === 'Credito' ? 'Credito Interno' : 'Puntos';
    pagos.push({ metodo: metodoDb, monto: porPagar });
  } else {
    pagos.push({ metodo: metodoSeleccionadoModal, monto: porPagar });
  }

  // Puntos canjeados: un pago más, primero en la lista
  if (puntosCanje > 0) {
    const ptsEnPagos = pagos.filter(p => p.metodo === 'Puntos').reduce((s, p) => s + p.monto, 0);
    if (cliPago && puntosCanje + ptsEnPagos > cliPago.puntos) {
      toast(`El cliente tiene ${formatNumber(cliPago.puntos)} puntos; no alcanzan para este pago.`, 'warn');
      return;
    }
    pagos.unshift({ metodo: 'Puntos', monto: puntosCanje });
    recibido += puntosCanje;
  }

  const btn = document.getElementById('btnConfirmarPagoModal');
  btn.disabled = true;
  btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Registrando Venta...`;

  const payloadVenta = {
    items: cart.map(i => ({
      producto_id: i.ProductoID,
      cantidad: i.cantidad,
      factor: i.factor || 1,
      precio_unitario: i.PrecioVenta,
      nombre_item: i.Nombre,
      descripcion_pack: i.descPack || ''
    })),
    tipo_documento: tipoDoc,
    cliente_id: clienteID,
    descuento_global: descGlobal,
    supervisor_pass: supervisorPass,
    vale_codigo: valeAplicado ? valeAplicado.codigo : null,
    cotizacion_id: cotizacionActiva,
    pagos: pagos,
    monto_pagado: recibido,
    vuelto: vuelto,
    monto_total: total
  };

  let dataVenta = null;
  let esOffline = false;

  // 1. Si no estamos en contingencia forzada y hay red, intentar enviar al servidor
  if (!posModoOfflineForzado && !posEstadoOfflineActivo && navigator.onLine) {
    try {
      const res = await fetch('api/registrar_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
        body: JSON.stringify(payloadVenta)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error);
      dataVenta = data;
      marcarModoDesconectado(false);
    } catch (errNet) {
      console.warn('[POS] Error al contactar el servidor:', errNet);
      if (!navigator.onLine || errNet.name === 'TypeError' || errNet.message.includes('fetch') || errNet.message.includes('Failed to fetch')) {
        esOffline = true;
        marcarModoDesconectado(true);
      } else {
        toast(errNet.message, 'error');
        btn.disabled = false;
        btn.innerHTML = textoBotonConfirmarPago();
        return;
      }
    }
  } else {
    esOffline = true;
    marcarModoDesconectado(true);
  }

  // 2. Si no hay conexión o falló la red, encolar en IndexedDB local
  if (esOffline) {
    // Fiado y puntos dependen del saldo real del cliente: no se aceptan sin conexión (tampoco como parte de un pago mixto)
    if (pagos.some(p => p.metodo === 'Credito Interno' || p.metodo === 'Puntos')) {
      toast('Las ventas con Fiado o Puntos no están permitidas sin conexión a internet por seguridad de saldo.', 'error');
      btn.disabled = false;
      btn.innerHTML = textoBotonConfirmarPago();
      return;
    }
    if (valeAplicado) {
      toast('El canje de vales no está permitido sin conexión a internet.', 'error');
      btn.disabled = false;
      btn.innerHTML = textoBotonConfirmarPago();
      return;
    }

    try {
      const resOff = await window.posOfflineDB.encolarVenta(payloadVenta);
      dataVenta = {
        success: true,
        es_offline: true,
        id_temporal: resOff.id_temporal,
        venta_id: resOff.id_temporal,
        fecha: resOff.fecha
      };
      await actualizarBadgePendientes();
      toast('⚠️ Venta registrada en MODO OFFLINE (se guardó en el equipo).', 'warn');
    } catch (errOff) {
      toast('Error crítico al guardar venta local: ' + errOff.message, 'error');
      btn.disabled = false;
      btn.innerHTML = textoBotonConfirmarPago();
      return;
    }
  }

  try {
    cerrarModalPago();

    // El vale aplicado también es un "medio de pago" en el comprobante
    const pagosTicket = [];
    if (valeAplicado) {
      pagosTicket.push({ metodo: 'Vale Devolucion', monto: getValeMontoAplicado() });
    }
    pagos.forEach(p => pagosTicket.push(p));

    const descPromos = cart.reduce((s, i) => s + calcularDescuentoItem(i), 0);
    mostrarTicket(dataVenta, cart, total, recibido, vuelto, pagosTicket, {
      subtotal: Math.round(subtotalBruto),
      descuento: Math.round(descPromos) + descGlobal,
    });

    cart = [];
    cotizacionActiva = null;
    resetDescuento();
    valeAplicado = null;
    document.getElementById('valeCodigoInput').value = '';
    document.getElementById('valeAplicadoInfo').style.display = 'none';
    renderCart();
    cargarProductos('');

  } catch (errPost) {
    toast('Error post-venta: ' + errPost.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = textoBotonConfirmarPago();
  }
}

async function guardarCotizacion() {
  if (cart.length === 0) {
    toast('El carrito está vacío.', 'warn');
    return;
  }

  const ok = await confirmDialog({
    title: 'Pausar venta',
    message: 'Se guarda como cotización pendiente con el cliente asignado. La puedes retomar desde "Pendientes" o desde Cotizaciones.',
    confirmText: 'Pausar',
  });
  if (!ok) return;

  const clienteID = document.getElementById('clienteSelect').value || null;

  try {
    const res = await fetch('api/cotizaciones.php?action=save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({
        cliente_id: clienteID,
        items: cart.map(i => ({
          producto_id: i.ProductoID,
          cantidad: i.cantidad,
          precio: i.PrecioVenta,
          descuento: calcularDescuentoItem(i),
        })),
      })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    toast(`Venta pausada como cotización #${data.cotizacion_id}.`, 'success');
    cart = [];
    cotizacionActiva = null;
    resetDescuento();
    renderCart();
  } catch (err) {
    toast('Error al guardar cotización: ' + err.message, 'error');
  }
}

async function abrirModalCotizaciones() {
  const modal = document.getElementById('cotizacionesModal');
  const lista = document.getElementById('cotizacionesLista');
  modal.style.display = 'flex';
  lista.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Cargando pendientes...</p>';

  try {
    const res = await fetch('api/cotizaciones.php?action=list&estado=Pendiente');
    const data = await res.json();

    if (!data.success || data.cotizaciones.length === 0) {
      lista.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 1rem;">No hay ventas pausadas pendientes.</p>';
      return;
    }

    lista.innerHTML = data.cotizaciones.map(c => `
      <div style="background: rgba(15,23,42,0.6); border: 1px solid var(--border-dark); padding: 0.75rem 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <strong style="color: #fff;">#${c.CotizacionID} · ${escapeHtml(c.Cliente)}</strong>
          <div style="font-size: 0.8rem; color: var(--text-muted);">$${formatNumber(c.Total)} • ${c.Items} ítem(s) • ${c.FechaCotizacion}</div>
        </div>
        <button onclick="cargarCotizacion(${c.CotizacionID})" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
          <i class="fa-solid fa-arrow-rotate-left"></i> Restaurar
        </button>
      </div>
    `).join('');
  } catch (err) {
    lista.innerHTML = `<p style="color: var(--danger);">Error: ${err.message}</p>`;
  }
}

async function cargarCotizacion(id) {
  try {
    const res = await fetch(`api/cotizaciones.php?action=get&id=${id}&restore=1`);
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    cart = data.detalles.map(d => ({
      ProductoID: d.ProductoID,
      Nombre: d.Nombre,
      PrecioVenta: parseInt(d.PrecioUnitario),
      Stock: parseFloat(d.Stock),
      cantidad: parseFloat(d.Cantidad),
      PromocionID: d.PromocionID || null,
      PromoTipo: d.PromoTipo || null,
      PromoCantMin: parseFloat(d.PromoCantMin) || 0,
      PromoDescPorc: parseFloat(d.PromoDescPorc) || 0,
      PromoPrecioOf: parseInt(d.PromoPrecioOf) || 0
    }));

    cotizacionActiva = parseInt(id) || null;
    if (data.cotizacion && data.cotizacion.ClienteID) {
      const sel = document.getElementById('clienteSelect');
      if (sel) sel.value = String(data.cotizacion.ClienteID);
    }

    renderCart();
    cerrarModalCotizaciones();
    toast(`Cotización #${id} cargada al carrito.`, 'success');
  } catch (err) {
    toast('Error al restaurar cotización: ' + err.message, 'error');
  }
}

function cerrarModalCotizaciones() {
  document.getElementById('cotizacionesModal').style.display = 'none';
}

function abrirModalMovimiento() {
  document.getElementById('posMovMonto').value = '';
  document.getElementById('posMovConcepto').value = '';
  document.getElementById('movimientoModal').style.display = 'flex';
}

function cerrarModalMovimiento() {
  document.getElementById('movimientoModal').style.display = 'none';
}

async function registrarMovimientoPos() {
  const tipo = document.getElementById('posMovTipo').value;
  const monto = parseInt(document.getElementById('posMovMonto').value) || 0;
  const concepto = document.getElementById('posMovConcepto').value.trim();

  if (monto <= 0) {
    toast('Ingresa un monto válido mayor a 0.', 'warn');
    return;
  }

  const btn = document.getElementById('btnRegistrarMovimientoPos');
  btn.disabled = true;

  try {
    const res = await fetch('api/movimiento_caja.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: JSON.stringify({ tipo, monto, concepto })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error);

    toast(data.mensaje, 'success');
    cerrarModalMovimiento();
  } catch (err) {
    toast('Error al registrar movimiento: ' + err.message, 'error');
  } finally {
    btn.disabled = false;
  }
}

const NOMBRE_PAGO = {
  'Efectivo': 'Efectivo',
  'Tarjeta Debito': 'Tarjeta débito',
  'Tarjeta Credito': 'Tarjeta crédito',
  'Tarjeta': 'Tarjeta',
  'Transferencia': 'Transferencia',
  'Credito Interno': 'Crédito interno (fiado)',
  'Credito': 'Crédito interno (fiado)',
  'Fiado': 'Crédito interno (fiado)',
  'Puntos': 'Puntos',
  'Vale Devolucion': 'Vale / Nota de crédito',
};

function mostrarTicket(data, items, total, pagado, vuelto, pagos, meta) {
  meta = meta || {};
  document.getElementById('ticketFecha').textContent = data.fecha || '';
  if (data.es_offline) {
    document.getElementById('ticketVentaNum').innerHTML = '<span style="color:#f59e0b;font-weight:bold;">COMPROBANTE PROVISIONAL (OFFLINE)</span>';
  } else {
    document.getElementById('ticketVentaNum').textContent = 'N° ' + (data.venta_id || '-');
  }

  const detalleEl = document.getElementById('ticketDetalle');
  detalleEl.innerHTML = items.map(i => {
    const lineTotal = Math.round(i.cantidad * i.PrecioVenta);
    return `<div class="tk-item-line"><span>${escapeHtml(i.Nombre).substring(0, 24)}</span><span>$${formatNumber(lineTotal)}</span></div>` +
           (parseInt(i.EsPesable) === 1
             ? `<div class="tk-item-sub">${formatNumber(i.cantidad)} kg x $${formatNumber(i.PrecioVenta)}/kg</div>`
             : `<div class="tk-item-sub">${i.cantidad} x $${formatNumber(i.PrecioVenta)}</div>`);
  }).join('');

  const descuento = meta.descuento || 0;
  const subtotal = meta.subtotal != null ? meta.subtotal : total;
  const subRow = document.getElementById('ticketSubtotalRow');
  const descRow = document.getElementById('ticketDescuentoRow');
  if (descuento > 0) {
    subRow.style.display = 'flex';
    document.getElementById('ticketSubtotal').textContent = `$${formatNumber(subtotal)}`;
    descRow.style.display = 'flex';
    document.getElementById('ticketDescuento').textContent = `-$${formatNumber(descuento)}`;
  } else {
    subRow.style.display = 'none';
    descRow.style.display = 'none';
  }
  document.getElementById('ticketTotal').textContent = `$${formatNumber(total)}`;

  const pagosEl = document.getElementById('ticketPagos');
  const listaPagos = (pagos && pagos.length)
    ? pagos
    : [{ metodo: 'Efectivo', monto: pagado }];
  pagosEl.innerHTML = listaPagos
    .map(p => `<div><span>${NOMBRE_PAGO[p.metodo] || p.metodo}</span><span>$${formatNumber(p.monto)}</span></div>`)
    .join('');

  document.getElementById('ticketVuelto').textContent = `$${formatNumber(vuelto)}`;
  document.getElementById('ticketVueltoRow').style.display = vuelto > 0 ? 'flex' : 'none';

  // Comprobante de crédito interno / fiado
  const credBox = document.getElementById('ticketCreditoBox');
  if (data.credito) {
    const c = data.credito;
    document.getElementById('tkCredCliente').textContent = c.cliente || '';
    const rutRow = document.getElementById('tkCredRutRow');
    if (c.rut) {
      rutRow.style.display = 'flex';
      document.getElementById('tkCredRut').textContent = c.rut;
    } else {
      rutRow.style.display = 'none';
    }
    document.getElementById('tkCredMonto').textContent = `$${formatNumber(c.monto)}`;
    document.getElementById('tkCredSaldo').textContent = `$${formatNumber(c.saldo_deudor)}`;
    document.getElementById('tkCredCupo').textContent = `$${formatNumber(c.cupo_disponible)}`;
    credBox.style.display = 'block';
  } else {
    credBox.style.display = 'none';
  }

  // Mostrar u ocultar sección DTE según la respuesta
  const dteInfo = document.getElementById('ticketDteInfo');
  const dteFolio = document.getElementById('ticketDteFolio');
  const dtePdfBtn = document.getElementById('ticketDtePdfBtn');
  const localPrintBtn = document.getElementById('ticketLocalPrintBtn');
  const dtePrintBtn = document.getElementById('ticketDtePrintBtn');

  // El comprobante interno SIEMPRE se puede imprimir (es el respaldo del local y,
  // en ventas a crédito, lleva la firma del cliente).
  if (localPrintBtn) localPrintBtn.style.display = 'inline-flex';

  if (dteInfo && dteFolio && dtePdfBtn) {
    if (data.dte && data.dte.success) {
      dteFolio.textContent = `Folio: ${data.dte.folio}`;
      dtePdfBtn.href = data.dte.pdf_url;
      dtePdfBtn.style.display = 'inline-flex';
      dteInfo.style.display = 'block';

      if (dtePrintBtn) {
        dtePrintBtn.style.display = 'inline-flex';
        dtePrintBtn.dataset.url = data.dte.pdf_url;
      }

      // Impresión directa de la boleta electrónica (salvo venta a crédito:
      // ahí el cajero imprime primero el comprobante firmado).
      if (!data.credito) {
        setTimeout(() => imprimirPdfDirecto(data.dte.pdf_url), 300);
      }
    } else {
      dteInfo.style.display = 'none';
      dtePdfBtn.style.display = 'none';
      if (dtePrintBtn) dtePrintBtn.style.display = 'none';
    }
  }

  // Guardar última venta para posible reimpresión directa
  window.ultimaVentaData = data;

  const directPrintBtn = document.getElementById('ticketDirectPrintBtn');
  const directDrawerBtn = document.getElementById('ticketDirectDrawerBtn');

  // Si hay impresora directa configurada (Web Serial o WebUSB)
  if (window.directPrinter && window.directPrinter.type !== 'none') {
    if (directPrintBtn) directPrintBtn.style.display = 'inline-flex';
    if (directDrawerBtn) directDrawerBtn.style.display = 'inline-flex';

    // Imprimir directamente en segundo plano y disparar apertura de gaveta
    window.directPrinter.printSale(data, window.LOCAL_CONFIG || {}).then(() => {
      toast('Ticket impreso y gaveta abierta en POSBANK', 'success');
    }).catch(err => {
      console.warn('[DirectPrinter] Error al imprimir directo:', err);
      toast('Aviso impresora directa: ' + err.message, 'warning');
    });
  } else {
    if (directPrintBtn) directPrintBtn.style.display = 'none';
    if (directDrawerBtn) directDrawerBtn.style.display = 'none';
  }

  const modal = document.getElementById('ticketModal');
  if (modal) {
    modal.style.display = 'flex';
    document.body.classList.add('ticket-modal-open');
    // Dar foco rápido al botón de cerrar para permitir cerrar con Espacio/Enter de inmediato
    setTimeout(() => {
      const btnCerrar = document.getElementById('btnCerrarTicket');
      if (btnCerrar) btnCerrar.focus();
    }, 100);
  }
}

async function reimprimirTicketDirecto() {
  if (!window.ultimaVentaData) return;
  if (!window.directPrinter || window.directPrinter.type === 'none') {
    alert('No hay impresora directa vinculada. Configúrala en Configuración > Impresora y Gaveta.');
    return;
  }
  try {
    await window.directPrinter.printSale(window.ultimaVentaData, window.LOCAL_CONFIG || {});
    toast('Ticket reimpreso correctamente', 'success');
  } catch (e) {
    alert('Error al reimprimir: ' + e.message);
  }
}

async function abrirGavetaRapida() {
  if (!window.directPrinter || window.directPrinter.type === 'none') {
    alert('Para abrir la gaveta directamente, vincula la impresora en Configuración > Impresora y Gaveta.');
    return;
  }
  try {
    await window.directPrinter.openDrawer();
    toast('Cajón de dinero abierto', 'info');
  } catch (e) {
    alert('Error al abrir gaveta: ' + e.message);
  }
}

function imprimirPdfDirecto(pdfUrl) {
  const oldIframe = document.getElementById('printIframe');
  if (oldIframe) {
    oldIframe.parentNode.removeChild(oldIframe);
  }

  const iframe = document.createElement('iframe');
  iframe.id = 'printIframe';
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = '0';
  iframe.src = pdfUrl;

  iframe.onload = function() {
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      console.error("Error al imprimir el PDF:", e);
      window.open(pdfUrl, '_blank');
    }
  };

  document.body.appendChild(iframe);
}

function cerrarTicket() {
  document.body.classList.remove('ticket-modal-open');
  const modal = document.getElementById('ticketModal');
  if (modal) {
    modal.style.display = 'none';
  }

  // Restaurar el foco inmediatamente al escáner de códigos de barras según el modo activo
  setTimeout(() => {
    if (window.modoPosLayout === 'supermercado' || (typeof modoPosLayout !== 'undefined' && modoPosLayout === 'supermercado')) {
      const superInput = document.getElementById('posSearchSuper');
      if (superInput) {
        superInput.focus();
        superInput.select();
      }
    } else {
      const normalInput = document.getElementById('posSearch');
      if (normalInput) {
        normalInput.focus();
        normalInput.select();
      }
    }
  }, 50);
}

function formatNumber(num) {
  return new Intl.NumberFormat('es-CL').format(num);
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, function(m) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
  });
}

// ----------------------------------------------------
// BLOQUEO RÁPIDO DE PANTALLA DE CAJA (LOCK SCREEN)
// ----------------------------------------------------
let posLockClockInterval = null;

function actualizarRelojBloqueo() {
  const now = new Date();
  const timeEl = document.getElementById('posLockClockTime');
  const dateEl = document.getElementById('posLockClockDate');
  
  if (timeEl) {
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    timeEl.textContent = `${hours}:${minutes}:${seconds}`;
  }
  
  if (dateEl) {
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateEl.textContent = now.toLocaleDateString('es-CL', opciones);
  }
}

function bloquearCaja() {
  const overlay = document.getElementById('posLockOverlay');
  if (!overlay) return;

  try {
    sessionStorage.setItem('pos_locked', '1');
  } catch (e) {}

  overlay.style.display = 'flex';
  const passInput = document.getElementById('posLockPassword');
  if (passInput) {
    passInput.value = '';
    setTimeout(() => passInput.focus(), 150);
  }
  const errEl = document.getElementById('posLockError');
  if (errEl) {
    errEl.style.display = 'none';
    errEl.textContent = '';
  }

  actualizarRelojBloqueo();
  if (posLockClockInterval) clearInterval(posLockClockInterval);
  posLockClockInterval = setInterval(actualizarRelojBloqueo, 1000);
}

async function desbloquearCaja(e) {
  if (e) e.preventDefault();
  
  const passInput = document.getElementById('posLockPassword');
  const btn = document.getElementById('btnPosUnlock');
  const errEl = document.getElementById('posLockError');
  const password = passInput ? passInput.value.trim() : '';

  if (!password) {
    if (errEl) {
      errEl.style.display = 'block';
      errEl.textContent = 'Por favor ingresa la contraseña o PIN.';
    }
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
  }
  if (errEl) errEl.style.display = 'none';

  try {
    const res = await fetch('api/desbloquear_caja.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN || ''
      },
      body: JSON.stringify({ password: password })
    });

    const data = await res.json();

    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Desbloquear Terminal';
    }

    if (!data.success) {
      if (errEl) {
        errEl.style.display = 'block';
        errEl.textContent = data.error || 'Contraseña incorrecta.';
      }
      if (passInput) {
        passInput.select();
      }
      return;
    }

    // Desbloqueo exitoso
    try {
      sessionStorage.removeItem('pos_locked');
    } catch (e) {}

    if (posLockClockInterval) {
      clearInterval(posLockClockInterval);
      posLockClockInterval = null;
    }

    const overlay = document.getElementById('posLockOverlay');
    if (overlay) overlay.style.display = 'none';

    toast(data.mensaje || 'Caja desbloqueada correctamente.', 'success');

    // Refocar el campo de escaneo adecuado según el modo de caja
    setTimeout(() => {
      const superInput = document.getElementById('posSearchSuper');
      const normalInput = document.getElementById('posSearch');
      if (window.modoPosLayout === 'supermercado' && superInput) {
        superInput.focus();
      } else if (normalInput) {
        normalInput.focus();
      }
    }, 150);

  } catch (err) {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Desbloquear Terminal';
    }
    if (errEl) {
      errEl.style.display = 'block';
      errEl.textContent = 'Error de conexión con el servidor.';
    }
  }
}

/* ==========================================================================
 * 🌐 CONTROLADOR DE CONECTIVIDAD Y SINCRONIZACIÓN OFFLINE PWA (HEARTBEAT ACTIVO)
 * ========================================================================== */

let posModoOfflineForzado = false;
let posEstadoOfflineActivo = false;
let posUltimoCheckConectividad = 0;
let posVerificandoConexion = false;

function toggleModoOfflineManual() {
  posModoOfflineForzado = !posModoOfflineForzado;
  if (posModoOfflineForzado) {
    marcarModoDesconectado(true);
    toast('🔌 Modo Contingencia (Offline) forzado manualmente para pruebas.', 'warn');
  } else {
    toast('🟢 Restaurando modo automático. Verificando red...', 'info');
    verificarConectividadReal(true).then((online) => {
      if (online) {
        toast('🟢 Conexión con el servidor confirmada.', 'success');
        sincronizarVentasPendientes();
      } else {
        toast('⚠️ El servidor sigue sin responder. Modo Offline activo.', 'warn');
      }
    });
  }
}

function marcarModoDesconectado(desconectado) {
  const fueCambio = (posEstadoOfflineActivo !== desconectado);
  posEstadoOfflineActivo = desconectado;

  const bar = document.getElementById('posOfflineStatusBar');
  const pill = document.getElementById('posPillOnline');
  const dot = document.getElementById('posOfflineStatusDot');
  const txt = document.getElementById('posOfflineStatusText');

  const appVer = window.APP_VERSION || 'v4.0.1';

  if (desconectado) {
    if (bar) bar.style.display = 'flex';
    if (pill) {
      pill.style.background = 'rgba(245, 158, 11, 0.15)';
      pill.style.borderColor = '#f59e0b';
      pill.style.color = '#fbbf24';
      const label = posModoOfflineForzado ? 'Offline (Prueba)' : 'Offline';
      pill.innerHTML = `<span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span><span>${label}</span><span style="font-size: 0.72rem; opacity: 0.75; font-weight: 500; margin-left: 0.15rem; border-left: 1px solid rgba(245, 158, 11, 0.3); padding-left: 0.35rem;">${appVer}</span>`;
      pill.title = posModoOfflineForzado
        ? 'Modo Offline forzado manualmente. Clic para reactivar conexión automática.'
        : 'Sin conexión con el servidor. Clic para forzar reintento.';
    }
    if (dot) dot.style.background = '#f59e0b';
    if (txt) {
      txt.innerHTML = posModoOfflineForzado
        ? '<i class="fa-solid fa-flask"></i> <strong>Modo Contingencia Forzado (Prueba)</strong> &bull; Operando sobre IndexedDB'
        : '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Modo Contingencia (Sin Conexión)</strong> &bull; Las ventas se guardan en este equipo';
    }
  } else {
    if (pill) {
      pill.style.background = 'rgba(16, 185, 129, 0.12)';
      pill.style.borderColor = 'rgba(16, 185, 129, 0.3)';
      pill.style.color = '#10b981';
      pill.innerHTML = `<span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span><span>En Línea</span><span style="font-size: 0.72rem; opacity: 0.75; font-weight: 500; margin-left: 0.15rem; border-left: 1px solid rgba(16, 185, 129, 0.3); padding-left: 0.35rem;">${appVer}</span>`;
      pill.title = `Conectado al servidor en tiempo real. Versión ${appVer}. Clic para simular modo offline de prueba.`;
    }
    actualizarBadgePendientes();
  }

  return fueCambio;
}

async function verificarConectividadReal(forzar = false) {
  // Si está forzado manualmente, siempre es offline
  if (posModoOfflineForzado) {
    marcarModoDesconectado(true);
    return false;
  }

  // Si el navegador mismo no tiene adaptador de red activo
  if (!navigator.onLine) {
    marcarModoDesconectado(true);
    return false;
  }

  // Evitar solapamientos o consultas redundantes en menos de 2.5s a menos que sea forzado
  const ahora = Date.now();
  if (!forzar && (ahora - posUltimoCheckConectividad < 2500 || posVerificandoConexion)) {
    return !posEstadoOfflineActivo;
  }

  posUltimoCheckConectividad = ahora;
  posVerificandoConexion = true;

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 2000);

    const res = await fetch(`api/ping.php?_t=${ahora}`, {
      method: 'GET',
      cache: 'no-store',
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache'
      },
      signal: controller.signal
    });
    clearTimeout(timeoutId);

    if (!res.ok) {
      marcarModoDesconectado(true);
      return false;
    }

    const data = await res.json();
    if (!data || !data.success) {
      marcarModoDesconectado(true);
      return false;
    }

    // Si estamos en localhost y el servidor reporta que no hay salida a internet
    if (data.has_internet === false) {
      marcarModoDesconectado(true);
      return false;
    }

    // Servidor activo y con conexión
    const eraOffline = posEstadoOfflineActivo;
    marcarModoDesconectado(false);

    // Si acabamos de recuperar la conexión tras estar desconectados, sincronizar inmediatamente
    if (eraOffline) {
      console.log('[POS] Reconexión exitosa verificada por Heartbeat.');
      sincronizarVentasPendientes();
      sincronizarCatalogoOfflineSilencioso();
    }

    return true;
  } catch (err) {
    // Falla de red, timeout o servidor inalcanzable
    marcarModoDesconectado(true);
    return false;
  } finally {
    posVerificandoConexion = false;
  }
}

async function actualizarBadgePendientes() {
  if (!window.posOfflineDB) return;
  try {
    const count = await window.posOfflineDB.contarVentasPendientes();
    const bar = document.getElementById('posOfflineStatusBar');
    const badge = document.getElementById('posOfflineBadgePending');
    const countEl = document.getElementById('posOfflineCountText');
    const btnSync = document.getElementById('btnSincronizarOffline');

    if (count > 0) {
      if (bar) bar.style.display = 'flex';
      if (badge) badge.style.display = 'inline-flex';
      if (countEl) countEl.textContent = count;
      if (btnSync) btnSync.style.display = 'inline-flex';

      // Si hay red y no estamos en modo offline forzado, clarificar que la conexión está activa y solo hay ventas pendientes
      const txt = document.getElementById('posOfflineStatusText');
      const dot = document.getElementById('posOfflineStatusDot');
      if (!posEstadoOfflineActivo && !posModoOfflineForzado) {
        if (dot) dot.style.background = '#38bdf8';
        if (txt) {
          txt.innerHTML = '<i class="fa-solid fa-cloud-arrow-up" style="color: #38bdf8;"></i> <strong>Conexión Activa</strong> &bull; Hay ventas guardadas pendientes de sincronizar con el servidor';
        }
      }
    } else {
      if (badge) badge.style.display = 'none';
      if (btnSync) btnSync.style.display = 'none';
      // Solo ocultar la barra si estamos genuinamente online y no hay pendientes
      if (!posEstadoOfflineActivo && bar) {
        bar.style.display = 'none';
      }
    }
  } catch (e) {
    console.error('[POS] Error al contar pendientes:', e);
  }
}

async function sincronizarCatalogoOfflineSilencioso() {
  if (posEstadoOfflineActivo || posModoOfflineForzado || !window.posOfflineDB) return;
  try {
    const res = await fetch('api/catalogo_offline.php?_t=' + Date.now());
    if (!res.ok) return;
    const data = await res.json();
    if (data && data.success) {
      await window.posOfflineDB.guardarCatalogo(data);
      console.log('[POS] Catálogo offline actualizado en segundo plano.');
    }
  } catch (e) {
    console.warn('[POS] No se pudo sincronizar el catálogo offline en este ciclo:', e);
  }
}

let sincronizandoOfflineEnCurso = false;
async function sincronizarVentasPendientes() {
  if (sincronizandoOfflineEnCurso || !window.posOfflineDB) return;
  if (posEstadoOfflineActivo || posModoOfflineForzado) {
    toast('Sin conexión al servidor. No se puede sincronizar aún.', 'warn');
    return;
  }

  const pendientes = await window.posOfflineDB.obtenerVentasPendientes();
  if (!pendientes || pendientes.length === 0) {
    await actualizarBadgePendientes();
    return;
  }

  sincronizandoOfflineEnCurso = true;
  const btnSync = document.getElementById('btnSincronizarOffline');
  if (btnSync) {
    btnSync.disabled = true;
    btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando...';
  }

  try {
    const res = await fetch('api/sincronizar_offline.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN || ''
      },
      body: JSON.stringify({ ventas: pendientes })
    });

    const data = await res.json();
    if (data.success && Array.isArray(data.sincronizadas)) {
      const idsSincronizados = data.sincronizadas.map(s => s.id_temporal);
      await window.posOfflineDB.eliminarVentasSincronizadas(idsSincronizados);
      await actualizarBadgePendientes();
      marcarModoDesconectado(false);
      toast(`🟢 ${data.total_sincronizadas} venta(s) offline sincronizada(s) con éxito en el servidor.`, 'success');
    } else {
      throw new Error(data.error || 'Respuesta inesperada del servidor');
    }
  } catch (err) {
    console.error('[POS] Error sincronizando ventas offline:', err);
    toast('No se pudo completar la sincronización automática: ' + err.message, 'error');
    verificarConectividadReal();
  } finally {
    sincronizandoOfflineEnCurso = false;
    if (btnSync) {
      btnSync.disabled = false;
      btnSync.innerHTML = '<i class="fa-solid fa-rotate"></i> Sincronizar';
    }
  }
}

// Escuchadores de eventos de red nativos del navegador
window.addEventListener('online', () => {
  console.log('[POS] Evento online del sistema operativo detectado.');
  verificarConectividadReal(true);
});

window.addEventListener('offline', () => {
  console.warn('[POS] Evento offline del sistema operativo detectado.');
  marcarModoDesconectado(true);
});

window.addEventListener('focus', () => {
  verificarConectividadReal();
});

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    verificarConectividadReal();
  }
});

// Inicialización diferida al cargar la ventana
window.addEventListener('load', () => {
  // Comprobación activa inmediata al cargar
  verificarConectividadReal(true);
  actualizarBadgePendientes();
  sincronizarCatalogoOfflineSilencioso();

  // Heartbeat activo periódico cada 5 segundos para detectar caídas de red al instante
  setInterval(() => {
    verificarConectividadReal();
  }, 5000);

  // Intento periódico de sincronización cada 25 segundos si hay pendientes
  setInterval(() => {
    if (!posEstadoOfflineActivo && !posModoOfflineForzado) {
      sincronizarVentasPendientes();
    }
  }, 25000);
});



<div style="margin-bottom: 1.5rem;">
  <h1 style="font-size: 1.5rem; font-weight: 700;">Parametrización y Configuración del Sistema</h1>
  <p style="color: var(--text-muted); font-size: 0.9rem;">Parámetros del negocio, terminales de caja y boleta electrónica SII</p>
</div>

<?php if (!empty($message)): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Tabs de Navegación de Configuración -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.5rem; flex-wrap: wrap;">
  <button onclick="switchTab('tabGenerales')" class="btn btn-secondary tab-btn active" id="btn-tabGenerales">
    ⚙️ Parámetros Generales
  </button>
  <button onclick="switchTab('tabTerminales')" class="btn btn-secondary tab-btn" id="btn-tabTerminales">
    💻 Terminales de Caja
  </button>
  <button onclick="switchTab('tabImpresora')" class="btn btn-secondary tab-btn" id="btn-tabImpresora">
    🖨️ Impresora y Gaveta
  </button>
  <button onclick="switchTab('tabHaulmer')" class="btn btn-secondary tab-btn" id="btn-tabHaulmer">
    🧾 Boleta Electrónica (SII / Haulmer)
  </button>
  <button onclick="switchTab('tabBalanza')" class="btn btn-secondary tab-btn" id="btn-tabBalanza">
    ⚖️ Balanza de Pesaje
  </button>
  <button onclick="switchTab('tabSupervision')" class="btn btn-secondary tab-btn" id="btn-tabSupervision">
    🛡️ Supervisión en Caja (POS)
  </button>
  <button onclick="switchTab('tabApariencia')" class="btn btn-secondary tab-btn" id="btn-tabApariencia">
    🎨 Apariencia y Marca
  </button>
  <button onclick="switchTab('tabAsistenteIA')" class="btn btn-secondary tab-btn" id="btn-tabAsistenteIA">
    🤖 Asistente de IA
  </button>
</div>

<!-- PESTAÑA 1: PARÁMETROS GENERALES -->
<div id="tabGenerales" class="tab-pane">
  <div class="table-card" style="padding: 1.75rem; max-width: 800px;">
    <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">Datos Comerciales de la Empresa</h2>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NOMBRE COMERCIAL / RAZÓN SOCIAL *</label>
          <input type="text" name="config[MINIMARKET_NOMBRE]" value="<?= htmlspecialchars($config['MINIMARKET_NOMBRE'] ?? 'Minimarket & Negocio de Barrio') ?>" class="form-control" required>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">IVA DEFECTO (%)</label>
          <input type="number" name="config[IVA_PORCENTAJE]" value="<?= htmlspecialchars($config['IVA_PORCENTAJE'] ?? '19') ?>" class="form-control" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">RUT EMPRESA</label>
          <input type="text" name="config[MINIMARKET_RUT]" value="<?= htmlspecialchars($config['MINIMARKET_RUT'] ?? '76.543.210-K') ?>" class="form-control">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">GIRO COMERCIAL</label>
          <input type="text" name="config[MINIMARKET_GIRO]" value="<?= htmlspecialchars($config['MINIMARKET_GIRO'] ?? 'Minimarket y Venta de Abarrotes') ?>" class="form-control">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">DIRECCIÓN COMERCIAL</label>
          <input type="text" name="config[MINIMARKET_DIRECCION]" value="<?= htmlspecialchars($config['MINIMARKET_DIRECCION'] ?? 'Av. Principal #123, Santiago') ?>" class="form-control">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TELÉFONO</label>
          <input type="text" name="config[MINIMARKET_TELEFONO]" value="<?= htmlspecialchars($config['MINIMARKET_TELEFONO'] ?? '+56 9 1234 5678') ?>" class="form-control">
        </div>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">MENSAJE PIE DE PÁGINA DEL TICKET</label>
        <input type="text" name="config[TICKET_PIE_PAGINA]" value="<?= htmlspecialchars($config['TICKET_PIE_PAGINA'] ?? '¡Gracias por su preferencia!') ?>" class="form-control">
      </div>

      <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem; margin-top: 0.5rem;">Módulos y Reglas del Sistema</h2>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CLUB DE PUNTOS (FIDELIZACIÓN)</label>
          <select name="config[LOYALTY_ACTIVO]" class="form-control">
            <option value="true" <?= ($config['LOYALTY_ACTIVO'] ?? 'true') === 'true' ? 'selected' : '' ?>>Habilitado (Puntos por Compras)</option>
            <option value="false" <?= ($config['LOYALTY_ACTIVO'] ?? '') === 'false' ? 'selected' : '' ?>>Deshabilitado</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PAGO A CRÉDITO INTERNO (FIADO)</label>
          <select name="config[CREDITO_INTERNO_ACTIVO]" class="form-control">
            <option value="true" <?= ($config['CREDITO_INTERNO_ACTIVO'] ?? 'true') === 'true' ? 'selected' : '' ?>>Permitir Ventas Fiadas</option>
            <option value="false" <?= ($config['CREDITO_INTERNO_ACTIVO'] ?? '') === 'false' ? 'selected' : '' ?>>Solo Pago Inmediato</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">VENDER CON STOCK NEGATIVO</label>
          <select name="config[PERMITIR_STOCK_NEGATIVO]" class="form-control">
            <option value="false" <?= ($config['PERMITIR_STOCK_NEGATIVO'] ?? 'false') === 'false' ? 'selected' : '' ?>>No permitir (bloquear venta sin stock)</option>
            <option value="true" <?= ($config['PERMITIR_STOCK_NEGATIVO'] ?? '') === 'true' ? 'selected' : '' ?>>Permitir (el stock puede quedar en negativo)</option>
          </select>
          <p style="color: var(--text-muted); font-size: 0.72rem; margin-top: 0.25rem;">Útil si registras ventas antes de actualizar el stock de una recepción. Con "Permitir", el sistema deja vender aunque no alcance el stock registrado.</p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">SONIDO LECTOR RÁPIDO</label>
          <select name="config[SONIDO_LECTOR_RAPIDO]" class="form-control">
            <option value="SI" <?= ($config['SONIDO_LECTOR_RAPIDO'] ?? 'SI') === 'SI' ? 'selected' : '' ?>>Habilitado (Beep)</option>
            <option value="NO" <?= ($config['SONIDO_LECTOR_RAPIDO'] ?? '') === 'NO' ? 'selected' : '' ?>>Silencioso</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">ARQUEO DE CAJA CIEGO</label>
          <select name="config[ARQUEO_CIEGO]" class="form-control">
            <option value="true" <?= ($config['ARQUEO_CIEGO'] ?? 'true') === 'true' ? 'selected' : '' ?>>Obligatorio (Oculta saldo en sistema)</option>
            <option value="false" <?= ($config['ARQUEO_CIEGO'] ?? '') === 'false' ? 'selected' : '' ?>>Muestra saldo esperado</option>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="padding: 0.85rem; font-weight: bold;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Parámetros Generales
      </button>
    </form>
  </div>
</div>

<!-- PESTAÑA 2: TERMINALES DE CAJA -->
<div id="tabTerminales" class="tab-pane" style="display: none;">
  <div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem;">
    
    <!-- Formulario Vincular Terminal -->
    <div class="table-card" style="padding: 1.5rem;">
      <h2 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Vincular Nueva Terminal</h2>
      <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_terminal">

        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NOMBRE EQUIPO (PC / HOSTNAME) *</label>
          <input type="text" name="nombre_equipo" class="form-control" required placeholder="Ej: CAJA-LOCAL-01, POS-BARRIO">
        </div>

        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CAJA ASIGNADA *</label>
          <select name="caja_id" class="form-control" required>
            <?php foreach ($cajas as $cj): ?>
              <option value="<?= $cj['CajaID'] ?>"><?= htmlspecialchars($cj['Nombre']) ?> (#<?= $cj['CajaID'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-link"></i> Vincular equipo a Caja</button>
      </form>
    </div>

    <!-- Lista de Terminales vinculadas -->
    <div class="table-card">
      <div class="table-header">
        <h2 style="font-size: 1.1rem; font-weight: 600;">Terminales Mapeadas</h2>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre Equipo (PC)</th>
            <th>Caja Asignada</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($terminales)): ?>
            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay terminales vinculadas.</td></tr>
          <?php else: ?>
            <?php foreach ($terminales as $t): ?>
              <tr>
                <td>#<?= $t['TerminalID'] ?></td>
                <td style="font-weight: 600; color: #fff;"><code><?= htmlspecialchars($t['NombreEquipo']) ?></code></td>
                <td><?= htmlspecialchars($t['CajaName']) ?></td>
                <td><span class="badge badge-success">Activo</span></td>
                <td>
                  <form method="POST" action="configuracion.php" onsubmit="return confirm('¿Desvincular esta terminal?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_terminal">
                    <input type="hidden" name="terminal_id" value="<?= $t['TerminalID'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"><i class="fa-solid fa-trash-can"></i> Desvincular</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- PESTAÑA 3: BOLETA ELECTRÓNICA SII / HAULMER -->
<div id="tabHaulmer" class="tab-pane" style="display: none;">
  <div class="table-card" style="padding: 1.75rem; max-width: 800px;">
    <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">Configuración de Facturación Electrónica SII (DTE)</h2>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PROVEEDOR DE FACTURACIÓN ELECTRÓNICA</label>
        <select name="config[DTE_PROVEEDOR]" id="dteProveedorSelect" class="form-control" onchange="toggleDteFields()" required>
          <option value="ninguno" <?= ($config['DTE_PROVEEDOR'] ?? 'ninguno') === 'ninguno' ? 'selected' : '' ?>>Ninguno (Solo Comprobante Local / Deshabilitado)</option>
          <option value="mock" <?= ($config['DTE_PROVEEDOR'] ?? '') === 'mock' ? 'selected' : '' ?>>Mock Driver (Simulador de DTE para Pruebas)</option>
          <option value="openfactura" <?= ($config['DTE_PROVEEDOR'] ?? '') === 'openfactura' ? 'selected' : '' ?>>OpenFactura API (Facturacion.cl)</option>
          <option value="haulmer" <?= ($config['DTE_PROVEEDOR'] ?? '') === 'haulmer' ? 'selected' : '' ?>>Haulmer API (Boleta Electrónica)</option>
        </select>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
          Determina con qué proveedor o canal se emitirán las boletas, facturas y notas de crédito del local.
        </p>
      </div>

      <!-- Configuración OpenFactura -->
      <div id="divConfigOpenFactura" style="display: none; flex-direction: column; gap: 1rem; border: 1px dashed rgba(129, 140, 248, 0.3); padding: 1rem; border-radius: 8px;">
        <h3 style="font-size: 0.9rem; font-weight: bold; color: #818cf8; margin: 0;">Parámetros OpenFactura (Facturacion.cl)</h3>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">OPENFACTURA API KEY</label>
          <input type="password" name="config[OPENFACTURA_API_KEY]" value="<?= htmlspecialchars($config['OPENFACTURA_API_KEY'] ?? '') ?>" class="form-control" placeholder="Clave de API de OpenFactura">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">AMBIENTE DE EJECUCIÓN (OPENFACTURA)</label>
          <select name="config[OPENFACTURA_AMBIENTE]" class="form-control">
            <option value="dev" <?= ($config['OPENFACTURA_AMBIENTE'] ?? 'dev') === 'dev' ? 'selected' : '' ?>>Desarrollo / Certificación (dev)</option>
            <option value="prod" <?= ($config['OPENFACTURA_AMBIENTE'] ?? '') === 'prod' ? 'selected' : '' ?>>Producción Real (prod)</option>
          </select>
        </div>
      </div>

      <!-- Configuración Haulmer -->
      <div id="divConfigHaulmer" style="display: none; flex-direction: column; gap: 1rem; border: 1px dashed rgba(251, 191, 36, 0.3); padding: 1rem; border-radius: 8px;">
        <h3 style="font-size: 0.9rem; font-weight: bold; color: #fbbf24; margin: 0;">Parámetros Haulmer API</h3>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">HAULMER API KEY</label>
          <input type="password" name="config[HAULMER_API_KEY]" value="<?= htmlspecialchars($config['HAULMER_API_KEY'] ?? '') ?>" class="form-control" placeholder="Clave API entregada por Haulmer">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">AMBIENTE DE EJECUCIÓN (HAULMER)</label>
          <select name="config[HAULMER_AMBIENTE]" class="form-control">
            <option value="dev" <?= ($config['HAULMER_AMBIENTE'] ?? 'dev') === 'dev' ? 'selected' : '' ?>>Desarrollo / Certificación (dev)</option>
            <option value="prod" <?= ($config['HAULMER_AMBIENTE'] ?? '') === 'prod' ? 'selected' : '' ?>>Producción Real (prod)</option>
          </select>
        </div>
      </div>

      <!-- Parámetros Generales SII -->
      <div id="divConfigSIICommon" style="display: none; flex-direction: column; gap: 1rem;">
        <h3 style="font-size: 0.9rem; font-weight: bold; color: var(--text-muted); margin: 0;">Datos del Contribuyente (SII)</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div>
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CÓDIGO ACTIVIDAD ECONÓMICA SII (ACTECO)</label>
            <input type="text" name="config[MINIMARKET_ACTECO]" value="<?= htmlspecialchars($config['MINIMARKET_ACTECO'] ?? '471100') ?>" class="form-control" placeholder="Ej: 471100">
          </div>
          <div>
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COMUNA ORIGEN SII</label>
            <input type="text" name="config[MINIMARKET_COMUNA]" value="<?= htmlspecialchars($config['MINIMARKET_COMUNA'] ?? 'Santiago') ?>" class="form-control" placeholder="Ej: Santiago">
          </div>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FORMATO DE IMPRESIÓN DTE</label>
          <select name="config[HAULMER_FORMATO_PDF]" class="form-control">
            <option value="80mm" <?= ($config['HAULMER_FORMATO_PDF'] ?? '80mm') === '80mm' ? 'selected' : '' ?>>Ticket 80mm</option>
            <option value="57mm" <?= ($config['HAULMER_FORMATO_PDF'] ?? '') === '57mm' ? 'selected' : '' ?>>Ticket 57mm</option>
          </select>
        </div>
      </div>

      <!-- Mensaje informativo para simulador o ninguno -->
      <div id="divConfigMsg" style="background: rgba(129, 140, 248, 0.1); border: 1px solid rgba(129, 140, 248, 0.2); padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.85rem; color: #94a3b8; line-height: 1.4;">
        <i class="fa-solid fa-circle-info" style="color: #818cf8; margin-right: 0.4rem;"></i>
        <span id="txtDteHelp">Selecciona un proveedor de DTE para comenzar.</span>
      </div>

      <button type="submit" class="btn btn-primary" style="padding: 0.85rem; font-weight: bold;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración DTE
      </button>
    </form>
  </div>
</div>

<!-- PESTAÑA 4: CONFIGURACIÓN DE BALANZA -->
<div id="tabBalanza" class="tab-pane" style="display: none;">
  <div class="table-card" style="padding: 1.75rem; max-width: 800px;">
    <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">Configuración de Balanzas y Lector de Códigos (EAN-13)</h2>

      <?php $balanzaModo = $config['BALANZA_MODO'] ?? 'etiqueta'; $balanzaIngreso = $config['BALANZA_INGRESO_MANUAL'] ?? 'peso'; ?>
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TIPO DE BALANZA</label>
        <select name="config[BALANZA_MODO]" id="balanzaModoSelect" class="form-control" onchange="toggleBalanzaModo()" required>
          <option value="etiqueta" <?= $balanzaModo === 'etiqueta' ? 'selected' : '' ?>>Balanza con etiqueta (imprime código de barras EAN-13)</option>
          <option value="manual" <?= $balanzaModo === 'manual' ? 'selected' : '' ?>>Balanza sin etiqueta (el cajero ingresa el dato a mano)</option>
        </select>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
          Con balanza sin etiqueta, al agregar un producto pesable el POS abre una ventana para ingresar el peso o el monto.
        </p>
      </div>

      <div id="balanzaManualBox" style="display: <?= $balanzaModo === 'manual' ? 'block' : 'none' ?>;">
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">INGRESO EN CAJA PARA PRODUCTOS PESABLES</label>
        <select name="config[BALANZA_INGRESO_MANUAL]" class="form-control">
          <option value="peso" <?= $balanzaIngreso === 'peso' ? 'selected' : '' ?>>Por peso: el cajero ingresa los kilos y el sistema calcula el precio</option>
          <option value="precio" <?= $balanzaIngreso === 'precio' ? 'selected' : '' ?>>Por monto: el cajero ingresa el precio y el sistema calcula los kilos</option>
        </select>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
          Es el modo que aparece por defecto; el cajero puede cambiarlo en la misma ventana para una venta puntual (ej. "deme $2.000 de queso").
        </p>
      </div>

      <div id="balanzaEtiquetaBox" style="display: <?= $balanzaModo === 'etiqueta' ? 'flex' : 'none' ?>; flex-direction: column; gap: 1.25rem;">
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FORMATO DEL CÓDIGO DE BARRAS DE BALANZA (INDIVIDUAL)</label>
        <select name="config[BALANZA_TIPO_EAN]" class="form-control" required>
          <option value="plu_peso" <?= ($config['BALANZA_TIPO_EAN'] ?? 'plu_peso') === 'plu_peso' ? 'selected' : '' ?>>PLU + Peso (20 PPPP QQQQQ C) - Recomendado</option>
          <option value="plu_precio" <?= ($config['BALANZA_TIPO_EAN'] ?? '') === 'plu_precio' ? 'selected' : '' ?>>PLU + Precio/Valor (20 PPPP $$$$$ C)</option>
        </select>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">
          Determina si los 5 dígitos de cantidad en la etiqueta representan gramos de peso (ej. 01250 para 1.250kg) o el valor de venta en pesos (ej. 12500 para $12.500).
        </p>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PREFIJO EAN-13 ETIQUETA INDIVIDUAL</label>
          <input type="text" name="config[BALANZA_PREFIJO_INDIVIDUAL]" value="<?= htmlspecialchars($config['BALANZA_PREFIJO_INDIVIDUAL'] ?? '20') ?>" class="form-control" placeholder="Ej: 20" maxlength="2" required style="font-family: monospace;">
          <p style="color: var(--text-muted); font-size: 0.72rem; margin-top: 0.25rem;">Prefijo GS1 para identificar que el código contiene peso/precio (normalmente 20).</p>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PREFIJO EAN-13 VALE CONSOLIDADO</label>
          <input type="text" name="config[BALANZA_PREFIJO_CONSOLIDADO]" value="<?= htmlspecialchars($config['BALANZA_PREFIJO_CONSOLIDADO'] ?? '28') ?>" class="form-control" placeholder="Ej: 28" maxlength="2" required style="font-family: monospace;">
          <p style="color: var(--text-muted); font-size: 0.72rem; margin-top: 0.25rem;">Prefijo GS1 para identificar vales consolidados con monto total final (normalmente 28).</p>
        </div>
      </div>
      </div>

      <button type="submit" class="btn btn-primary" style="padding: 0.85rem; font-weight: bold;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración de Balanza
      </button>
    </form>
  </div>
</div>
<script>
function toggleBalanzaModo() {
  const manual = document.getElementById('balanzaModoSelect').value === 'manual';
  document.getElementById('balanzaManualBox').style.display = manual ? 'block' : 'none';
  const etiquetaBox = document.getElementById('balanzaEtiquetaBox');
  etiquetaBox.style.display = manual ? 'none' : 'flex';
  // Los campos ocultos siguen enviandose para conservar su valor, pero no deben bloquear el submit
  etiquetaBox.querySelectorAll('input, select').forEach(el => { el.required = !manual; });
}
toggleBalanzaModo();
</script>

<!-- PESTAÑA 5: SUPERVISIÓN EN CAJA (POS) -->
<div id="tabSupervision" class="tab-pane" style="display: none;">
  <div class="table-card" style="padding: 1.75rem; max-width: 800px;">
    <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1.25rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <div>
        <h2 style="font-size: 1.15rem; font-weight: 700; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem; margin-bottom: 0.4rem;">
          <i class="fa-solid fa-user-shield"></i> Políticas de Supervisión y Control en Caja
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem;">
          Personaliza qué acciones del cajero en el punto de venta requieren la contraseña o autorización de un Administrador o Supervisor.
        </p>
      </div>

      <!-- Control 1: Anular/Vaciar Venta en Proceso -->
      <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-dark); padding: 1.25rem; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
          <div style="flex: 1;">
            <label style="font-size: 0.95rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
              <i class="fa-solid fa-ban" style="color: var(--danger);"></i> Cancelar Venta en Curso (Vaciar Carrito)
            </label>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4;">
              Si está activado, cuando un Cajero intente cancelar la venta en proceso o vaciar el carrito, el sistema solicitará la clave de un Supervisor para autorizar la acción.
            </p>
          </div>
          <div style="width: 200px; flex-shrink: 0;">
            <select name="config[POS_REQ_SUPERVISOR_CANCELAR]" class="form-control" style="font-weight: 600;">
              <option value="SI" <?= ($config['POS_REQ_SUPERVISOR_CANCELAR'] ?? 'SI') === 'SI' ? 'selected' : '' ?>>🛡️ Exigir Supervisor</option>
              <option value="NO" <?= ($config['POS_REQ_SUPERVISOR_CANCELAR'] ?? '') === 'NO' ? 'selected' : '' ?>>Permitir Libremente</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Control 2: Eliminar Producto de la Grilla -->
      <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-dark); padding: 1.25rem; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
          <div style="flex: 1;">
            <label style="font-size: 0.95rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
              <i class="fa-solid fa-trash-can" style="color: #f59e0b;"></i> Eliminar Producto del Carrito
            </label>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4;">
              Si está activado, cuando un Cajero intente quitar un producto agregado a la grilla (usando el botón papelera o reduciendo la cantidad a cero), se exigirá la autorización de un Supervisor.
            </p>
          </div>
          <div style="width: 200px; flex-shrink: 0;">
            <select name="config[POS_REQ_SUPERVISOR_ELIMINAR_ITEM]" class="form-control" style="font-weight: 600;">
              <option value="SI" <?= ($config['POS_REQ_SUPERVISOR_ELIMINAR_ITEM'] ?? 'SI') === 'SI' ? 'selected' : '' ?>>🛡️ Exigir Supervisor</option>
              <option value="NO" <?= ($config['POS_REQ_SUPERVISOR_ELIMINAR_ITEM'] ?? '') === 'NO' ? 'selected' : '' ?>>Permitir Libremente</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Control 3: Descuento Máximo de Cajero por Porcentaje -->
      <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-dark); padding: 1.25rem; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
          <div style="flex: 1;">
            <label style="font-size: 0.95rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
              <i class="fa-solid fa-percent" style="color: #34d399;"></i> Descuento Máximo en Caja sin Supervisor (%)
            </label>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4;">
              Porcentaje máximo que un cajero puede otorgar como descuento sin pedir autorización. Si el descuento aplicado supera este porcentaje (o si se establece en <strong>0%</strong>), será obligatorio ingresar la clave de un Supervisor para completar el cobro.
            </p>
          </div>
          <div style="width: 200px; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <input type="number" name="config[POS_DESCUENTO_MAX_PORC]" class="form-control" min="0" max="100" step="1" value="<?= htmlspecialchars($config['POS_DESCUENTO_MAX_PORC'] ?? '5') ?>" style="font-size: 1.1rem; font-weight: 700; text-align: center;" required>
              <span style="font-weight: 700; font-size: 1.1rem; color: #fff;">%</span>
            </div>
            <span style="font-size: 0.72rem; color: var(--text-muted); display: block; margin-top: 0.25rem; text-align: center;">0 = Siempre pide clave</span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="align-self: flex-start; padding: 0.75rem 2rem; font-size: 1rem; font-weight: bold; margin-top: 0.5rem;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Políticas de Supervisión
      </button>
    </form>
  </div>
</div>

<!-- PESTAÑA 6: APARIENCIA Y MARCA -->
<div id="tabApariencia" class="tab-pane" style="display: none;">
  <div class="table-card" style="padding: 1.75rem; max-width: 850px;">
    <form method="POST" action="configuracion.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.5rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <div>
        <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">
          <i class="fa-solid fa-palette"></i> Tema de Color y Apariencia
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem;">Personaliza la apariencia general del sistema para adaptarlo a la iluminación de tu local y a los colores de tu marca.</p>
      </div>

      <!-- Modo de Color (Oscuro vs Claro) -->
      <div>
        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.5rem;">MODO DE COLOR PREDETERMINADO</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid <?= ($config['TEMA_MODO'] ?? 'dark') === 'dark' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(15,23,42,0.6);" id="lblTemaDark">
            <input type="radio" name="config[TEMA_MODO]" value="dark" <?= ($config['TEMA_MODO'] ?? 'dark') === 'dark' ? 'checked' : '' ?> onchange="actualizarBordeTema('dark')">
            <div>
              <div style="font-weight: bold; color: var(--text-main);"><i class="fa-solid fa-moon"></i> Modo Oscuro (Dark)</div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">Ideal para turnos largos y pantallas nocturnas. Reduce el cansancio visual.</div>
            </div>
          </label>
          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid <?= ($config['TEMA_MODO'] ?? '') === 'light' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(255,255,255,0.06);" id="lblTemaLight">
            <input type="radio" name="config[TEMA_MODO]" value="light" <?= ($config['TEMA_MODO'] ?? '') === 'light' ? 'checked' : '' ?> onchange="actualizarBordeTema('light')">
            <div>
              <div style="font-weight: bold; color: var(--text-main);"><i class="fa-solid fa-sun"></i> Modo Claro (Light)</div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">Fondo blanco y alto contraste. Recomendado para locales con mucha luz natural.</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Color de Acento Corporativo -->
      <div>
        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.5rem;">COLOR DE ACENTO CORPORATIVO (MARCA)</label>
        <?php $curAcento = $config['TEMA_COLOR_ACENTO'] ?? 'indigo'; ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem;">
          
          <label class="accent-card" style="display: flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 0.75rem 0.5rem; border: 2px solid <?= $curAcento === 'indigo' ? '#4f46e5' : 'var(--border-dark)' ?>; border-radius: 10px; cursor: pointer; text-align: center; background: rgba(30,41,59,0.5);">
            <input type="radio" name="config[TEMA_COLOR_ACENTO]" value="indigo" <?= $curAcento === 'indigo' ? 'checked' : '' ?> style="display: none;" onchange="actualizarAcentoPreview(this.value, this)">
            <span style="width: 28px; height: 28px; border-radius: 50%; background: #4f46e5; display: inline-block; box-shadow: 0 0 10px rgba(79,70,229,0.5);"></span>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Azul Índigo</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Estándar</span>
          </label>

          <label class="accent-card" style="display: flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 0.75rem 0.5rem; border: 2px solid <?= $curAcento === 'verde' ? '#10b981' : 'var(--border-dark)' ?>; border-radius: 10px; cursor: pointer; text-align: center; background: rgba(30,41,59,0.5);">
            <input type="radio" name="config[TEMA_COLOR_ACENTO]" value="verde" <?= $curAcento === 'verde' ? 'checked' : '' ?> style="display: none;" onchange="actualizarAcentoPreview(this.value, this)">
            <span style="width: 28px; height: 28px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 10px rgba(16,185,129,0.5);"></span>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Verde Minimarket</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Fresco / Eco</span>
          </label>

          <label class="accent-card" style="display: flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 0.75rem 0.5rem; border: 2px solid <?= $curAcento === 'naranja' ? '#f59e0b' : 'var(--border-dark)' ?>; border-radius: 10px; cursor: pointer; text-align: center; background: rgba(30,41,59,0.5);">
            <input type="radio" name="config[TEMA_COLOR_ACENTO]" value="naranja" <?= $curAcento === 'naranja' ? 'checked' : '' ?> style="display: none;" onchange="actualizarAcentoPreview(this.value, this)">
            <span style="width: 28px; height: 28px; border-radius: 50%; background: #f59e0b; display: inline-block; box-shadow: 0 0 10px rgba(245,158,11,0.5);"></span>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Naranja / Ámbar</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Comercial</span>
          </label>

          <label class="accent-card" style="display: flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 0.75rem 0.5rem; border: 2px solid <?= $curAcento === 'cyan' ? '#06b6d4' : 'var(--border-dark)' ?>; border-radius: 10px; cursor: pointer; text-align: center; background: rgba(30,41,59,0.5);">
            <input type="radio" name="config[TEMA_COLOR_ACENTO]" value="cyan" <?= $curAcento === 'cyan' ? 'checked' : '' ?> style="display: none;" onchange="actualizarAcentoPreview(this.value, this)">
            <span style="width: 28px; height: 28px; border-radius: 50%; background: #06b6d4; display: inline-block; box-shadow: 0 0 10px rgba(6,182,212,0.5);"></span>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Cyan / Turquesa</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Moderno</span>
          </label>

          <label class="accent-card" style="display: flex; flex-direction: column; align-items: center; gap: 0.4rem; padding: 0.75rem 0.5rem; border: 2px solid <?= $curAcento === 'rojo' ? '#ef4444' : 'var(--border-dark)' ?>; border-radius: 10px; cursor: pointer; text-align: center; background: rgba(30,41,59,0.5);">
            <input type="radio" name="config[TEMA_COLOR_ACENTO]" value="rojo" <?= $curAcento === 'rojo' ? 'checked' : '' ?> style="display: none;" onchange="actualizarAcentoPreview(this.value, this)">
            <span style="width: 28px; height: 28px; border-radius: 50%; background: #ef4444; display: inline-block; box-shadow: 0 0 10px rgba(239,68,68,0.5);"></span>
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);">Rojo Dinámico</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">Enérgico</span>
          </label>

        </div>
      </div>

      <!-- Logo del Negocio -->
      <div>
        <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">
          <i class="fa-solid fa-image"></i> Logo de la Empresa
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem;">Aparecerá en la barra superior (Navbar), en la pantalla de bienvenida y en el inicio de sesión.</p>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem; align-items: center; margin-top: 0.75rem;">
          <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <div>
              <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">SUBIR ARCHIVO DE LOGO (PNG, JPG, SVG, WebP)</label>
              <input type="file" name="logo_file" id="logoFileInput" class="form-control" accept="image/*" onchange="previewLogoFile(this)">
            </div>
            <div>
              <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">O RUTA / URL DE IMAGEN EXISTENTE</label>
              <input type="text" name="config[MINIMARKET_LOGO_URL]" id="logoUrlInput" value="<?= htmlspecialchars($config['MINIMARKET_LOGO_URL'] ?? '') ?>" class="form-control" placeholder="uploads/logo/... o https://..." oninput="previewLogoUrl(this.value)">
            </div>
          </div>
          <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.25); border: 2px dashed var(--border-dark); border-radius: 12px; padding: 1rem; min-height: 120px;">
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; font-weight: 600;">VISTA PREVIA</div>
            <?php $logoActual = $config['MINIMARKET_LOGO_URL'] ?? ''; ?>
            <img id="logoPreviewImg" src="<?= htmlspecialchars($logoActual) ?>" alt="Logo Empresa" style="<?= empty($logoActual) ? 'display:none;' : 'display:block;' ?> max-height: 60px; max-width: 100%; object-fit: contain;">
            <div id="logoPreviewPlaceholder" style="<?= !empty($logoActual) ? 'display:none;' : 'display:flex;' ?> flex-direction: column; align-items: center; color: var(--text-muted);">
              <i class="fa-solid fa-store" style="font-size: 2rem; margin-bottom: 0.25rem; opacity: 0.5;"></i>
              <span style="font-size: 0.75rem;">Sin logo personalizado</span>
            </div>
            <?php if (!empty($logoActual)): ?>
              <button type="button" onclick="quitarLogo()" class="btn btn-secondary" style="margin-top: 0.5rem; font-size: 0.7rem; padding: 0.2rem 0.5rem; color: var(--danger);">
                <i class="fa-solid fa-trash-can"></i> Quitar Logo
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Modo de Operación del POS (Caja Registradora) -->
      <div>
        <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem;">
          <i class="fa-solid fa-cash-register"></i> Modo de Operación de la Caja (POS)
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem;">Elige el modo de caja que se utilizará por defecto en el punto de venta:</p>
        
        <?php $layoutModo = $config['POS_LAYOUT_MODO'] ?? 'supermercado'; ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 0.75rem;">
          
          <label id="lblModoSupermercado" class="pos-mode-card-cfg" style="display: flex; flex-direction: column; padding: 1.1rem; border: 2px solid <?= $layoutModo === 'supermercado' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(30,41,59,0.5); transition: all 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.45rem;">
              <input type="radio" name="config[POS_LAYOUT_MODO]" value="supermercado" <?= $layoutModo === 'supermercado' ? 'checked' : '' ?> onchange="actualizarBordeModoPos('supermercado')">
              <strong style="color: var(--text-main); font-size: 0.95rem;"><i class="fa-solid fa-barcode"></i> Modo Supermercado</strong>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">Caja Rápida por Escáner. Oculta el catálogo y muestra una tabla central amplia de venta para registrar artículos velozmente con lector de código de barras.</span>
          </label>

          <label id="lblModoTactil" class="pos-mode-card-cfg" style="display: flex; flex-direction: column; padding: 1.1rem; border: 2px solid <?= $layoutModo === 'tactil' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(30,41,59,0.5); transition: all 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.45rem;">
              <input type="radio" name="config[POS_LAYOUT_MODO]" value="tactil" <?= $layoutModo === 'tactil' ? 'checked' : '' ?> onchange="actualizarBordeModoPos('tactil')">
              <strong style="color: var(--text-main); font-size: 0.95rem;"><i class="fa-solid fa-hand-pointer"></i> Modo Táctil / Kiosco</strong>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">Optimizado para Touchscreen. Barra horizontal de categorías superiores y tarjetas grandes con alto contraste para presionar cómodamente con el dedo.</span>
          </label>

          <label id="lblModoClasico" class="pos-mode-card-cfg" style="display: flex; flex-direction: column; padding: 1.1rem; border: 2px solid <?= $layoutModo === 'clasico' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(30,41,59,0.5); transition: all 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.45rem;">
              <input type="radio" name="config[POS_LAYOUT_MODO]" value="clasico" <?= $layoutModo === 'clasico' ? 'checked' : '' ?> onchange="actualizarBordeModoPos('clasico')">
              <strong style="color: var(--text-main); font-size: 0.95rem;"><i class="fa-solid fa-table-columns"></i> Modo Clásico Dividido</strong>
            </div>
            <span style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45;">Diseño tradicional de 2 columnas (catálogo a la izquierda y carrito a la derecha) con filtros interactivos de Más Vendidos, Ofertas, Todos o por categoría.</span>
          </label>

        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="align-self: flex-start; padding: 0.75rem 2rem; font-size: 1rem; font-weight: bold; margin-top: 0.5rem;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Apariencia y Marca
      </button>
    </form>
  </div>
</div>

<!-- PESTAÑA: ASISTENTE DE IA (SOPORTE OPERATIVO) -->
<div id="tabAsistenteIA" class="tab-pane" style="display: none;">
  <div class="table-card" style="padding: 1.75rem; max-width: 850px;">
    <form method="POST" action="configuracion.php" style="display: flex; flex-direction: column; gap: 1.5rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_config">

      <div>
        <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-wand-magic-sparkles" style="color: #fde047;"></i> Asistente de IA para Soporte del Sistema
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.35rem;">
          Controla la activación y el proveedor del Asistente virtual tipo chat que guía a cajeros y administradores en el uso de las funciones del POS.
        </p>
      </div>

      <!-- Activar / Desactivar Widget -->
      <div>
        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.5rem;">ESTADO DEL ASISTENTE FLOTANTE</label>
        <?php $iaActivo = $config['ASISTENTE_IA_ACTIVO'] ?? 'true'; ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid <?= $iaActivo === 'true' ? 'var(--primary)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(15,23,42,0.6);">
            <input type="radio" name="config[ASISTENTE_IA_ACTIVO]" value="true" <?= $iaActivo === 'true' ? 'checked' : '' ?>>
            <div>
              <strong style="color: #34d399; display: block;"><i class="fa-solid fa-circle-check"></i> Activado (Recomendado)</strong>
              <small style="color: var(--text-muted); font-size: 0.75rem;">Muestra el botón flotante en todas las pantallas para resolver dudas operativas.</small>
            </div>
          </label>
          <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid <?= $iaActivo === 'false' ? 'var(--danger)' : 'var(--border-dark)' ?>; border-radius: 12px; cursor: pointer; background: rgba(15,23,42,0.6);">
            <input type="radio" name="config[ASISTENTE_IA_ACTIVO]" value="false" <?= $iaActivo === 'false' ? 'checked' : '' ?>>
            <div>
              <strong style="color: #f87171; display: block;"><i class="fa-solid fa-circle-xmark"></i> Desactivado</strong>
              <small style="color: var(--text-muted); font-size: 0.75rem;">Oculta el asistente flotante en todo el sistema.</small>
            </div>
          </label>
        </div>
      </div>

      <!-- Proveedor de IA / Motor -->
      <div style="background: rgba(30, 41, 59, 0.4); border: 1px solid var(--border-dark); border-radius: 12px; padding: 1.25rem;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-microchip" style="color: #38bdf8;"></i> Motor de Inteligencia y Conocimiento
        </h3>
        <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
          El sistema cuenta con un <strong>Motor Local Integrado</strong> que responde al instante sin costo alguno y sin depender de conexión externa. Opcionalmente, puedes configurar una clave de <strong>Google Gemini</strong> para potenciar las respuestas con un modelo generativo en la nube.
        </p>

        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap; gap: 0.3rem;">
            <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin: 0;">CLAVE DE API GOOGLE GEMINI (OPCIONAL)</label>
            <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; color: #38bdf8; text-decoration: none; display: flex; align-items: center; gap: 0.25rem;">
              <i class="fa-solid fa-arrow-up-right-from-square"></i> Obtener clave gratuita en Google AI Studio
            </a>
          </div>

          <div style="display: flex; gap: 0.5rem;">
            <input type="password" 
                   id="inputGeminiApiKey"
                   name="config[GEMINI_API_KEY]" 
                   value="<?= htmlspecialchars($config['GEMINI_API_KEY'] ?? '') ?>" 
                   placeholder="AQ.Ab... o AIza... (Dejar en blanco para usar el motor local integrado sin costo)" 
                   class="form-control" 
                   style="font-family: monospace; font-size: 0.85rem; flex: 1;">
            
            <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility('inputGeminiApiKey', this)" title="Ver / Ocultar clave" style="padding: 0 0.85rem;">
              <i class="fa-solid fa-eye"></i>
            </button>

            <button type="button" id="btnTestGemini" class="btn btn-secondary" onclick="probarConexionGemini()" style="white-space: nowrap; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
              <i class="fa-solid fa-vial"></i> Probar Clave
            </button>
          </div>

          <!-- Estado actual de la clave guardada en la base de datos -->
          <?php
            $currentKey = trim($config['GEMINI_API_KEY'] ?? '');
          ?>
          <div id="divEstadoActualKey" style="margin-top: 0.6rem;">
            <?php if (empty($currentKey)): ?>
              <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #38bdf8; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-bolt"></i> <span><strong>Modo Activo: Motor Local Integrado</strong> (Sin clave configurada. Respuestas instantáneas y offline con más de 36 módulos cubiertos).</span>
              </div>
            <?php elseif ((str_starts_with($currentKey, 'AQ.') || str_starts_with($currentKey, 'AIza')) && strlen($currentKey) >= 30): ?>
              <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #34d399; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-check"></i> <span><strong>Clave de Google AI Studio detectada:</strong> Formato válido (<?= strlen($currentKey) ?> caracteres). Presiona <em>'Probar Clave'</em> para verificar comunicación en vivo con Google.</span>
              </div>
            <?php else: ?>
              <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #f87171; display: flex; align-items: flex-start; gap: 0.5rem;">
                <i class="fa-solid fa-triangle-exclamation" style="margin-top: 2px;"></i> 
                <div>
                  <strong>Formato no compatible con Gemini:</strong> Las API Keys de Google AI Studio comienzan con <code>AQ.</code> o <code>AIza</code>. El sistema conmuta automáticamente al <strong>Motor Local</strong> para no fallar.
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Resultado dinámico de la prueba -->
          <div id="divResultadoTestGemini" style="display: none; margin-top: 0.6rem;"></div>
        </div>
      </div>

      <!-- Alcance y Guardrails -->
      <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 1rem 1.25rem;">
        <strong style="color: #34d399; font-size: 0.88rem; display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-shield-halved"></i> Guardrails de Seguridad y Especialización Activos
        </strong>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.35rem 0 0 0; line-height: 1.45;">
          El asistente está programado para rechazar consultas ajenas al negocio (como política, recetas o entretenimiento) y guiar exclusivamente en las funciones del punto de venta. Para funciones no disponibles directamente en el sistema, ofrece siempre la alternativa práctica adecuada sin inventar menús inexistentes.
        </p>
      </div>

      <button type="submit" class="btn btn-primary" style="align-self: flex-start; padding: 0.75rem 2rem; font-size: 1rem; font-weight: bold;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración de IA
      </button>
    </form>
  </div>
</div>

<!-- PESTAÑA 7: IMPRESORA TÉRMICA Y GAVETA DIRECTA -->
<div id="tabImpresora" class="tab-pane" style="display: none;">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 1100px;">
    
    <!-- Tarjeta 1: Conexión y Hardware -->
    <div class="table-card" style="padding: 1.75rem;">
      <h2 style="font-size: 1.1rem; font-weight: 600; color: #818cf8; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fa-solid fa-plug"></i> Conexión Directa (Sin Drivers / Sin Admin)</span>
        <span id="directPrinterStatusText"><span style="color:#94a3b8;"><i class="fa-solid fa-circle-xmark"></i> Sin vincular</span></span>
      </h2>

      <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 1.25rem;">
        Conecta tu impresora térmica <strong>POSBANK A11 Prime (80mm)</strong> directamente desde Google Chrome o Microsoft Edge. No requiere instalar controladores en Windows ni permisos de administrador.
      </p>

      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">VELOCIDAD DE PUERTO SERIE (BAUD RATE)</label>
          <select id="cfgPrinterBaud" class="form-control" onchange="cambiarBaudios(this.value)">
            <option value="9600" selected>9600 bps (Estándar POSBANK / ESC-POS)</option>
            <option value="19200">19200 bps</option>
            <option value="38400">38400 bps</option>
            <option value="115200">115200 bps (Terminales POS modernas)</option>
          </select>
          <span style="font-size: 0.75rem; color: var(--text-muted);">Si no imprime en 9600 bps, prueba seleccionando 115200 bps.</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.5rem;">
          <button type="button" onclick="conectarImpresoraSerial()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.95rem; font-weight: 600; justify-content: center;">
            <i class="fa-solid fa-microchip"></i> 1. Conectar por Puerto Serie / COM (Web Serial)
          </button>
          
          <button type="button" onclick="conectarImpresoraUsb()" class="btn btn-secondary" style="padding: 0.75rem 1rem; font-size: 0.95rem; font-weight: 600; justify-content: center;">
            <i class="fa-brands fa-usb"></i> 2. Conectar por Cable USB Directo (WebUSB)
          </button>

          <button type="button" id="btnDisconnectPrinter" onclick="desvincularImpresora()" class="btn btn-danger" style="display: none; padding: 0.5rem 1rem; font-size: 0.85rem; justify-content: center;">
            <i class="fa-solid fa-link-slash"></i> Desvincular Impresora Actual
          </button>
        </div>
      </div>
    </div>

    <!-- Tarjeta 2: Panel de Pruebas en Vivo -->
    <div class="table-card" style="padding: 1.75rem; display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <h2 style="font-size: 1.1rem; font-weight: 600; color: #34d399; border-bottom: 1px solid var(--border-dark); padding-bottom: 0.4rem; margin-bottom: 1rem;">
          <i class="fa-solid fa-vial-circle-check"></i> Pruebas en Vivo en este Equipo
        </h2>

        <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 1.25rem;">
          Prueba el hardware inmediatamente. Si la gaveta salta o el papel se imprime, el sistema ya está listo para ventas en caja.
        </p>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <button type="button" onclick="probarGavetaDirecta()" class="btn btn-warning" style="padding: 1rem; font-size: 1.05rem; font-weight: 700; justify-content: center; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);">
            <i class="fa-solid fa-cash-register"></i> 💵 Probar Apertura de Gaveta
          </button>
          <span style="font-size: 0.75rem; color: var(--text-muted); text-align: center;">Envía el micropulso eléctrico de 24V al puerto DK. No gasta papel.</span>

          <button type="button" onclick="probarTicketDirecto()" class="btn btn-success" style="padding: 1rem; font-size: 1.05rem; font-weight: 700; justify-content: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
            <i class="fa-solid fa-receipt"></i> 🧾 Imprimir Ticket de Prueba (80mm) + Corte
          </button>
          <span style="font-size: 0.75rem; color: var(--text-muted); text-align: center;">Imprime diagnóstico con membrete, estado de periféricos y corta el papel.</span>
        </div>
      </div>

      <!-- Ajustes de comportamiento -->
      <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border-dark);">
        <h3 style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">COMPORTAMIENTO EN EL PUNTO DE VENTA (POS)</h3>
        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: var(--text-muted); cursor: pointer; margin-bottom: 0.4rem;">
          <input type="checkbox" id="chkAutoDrawer" checked onchange="guardarPrefImpresora('autodrawer', this.checked)">
          <span>Abrir cajón de dinero automáticamente en cada venta</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: var(--text-muted); cursor: pointer;">
          <input type="checkbox" id="chkAutoCut" checked onchange="guardarPrefImpresora('autocut', this.checked)">
          <span>Cortar papel automáticamente con guillotina</span>
        </label>
      </div>

    </div>

  </div>
</div>

<script src="assets/js/printer-direct.js"></script>
<script>
// Funciones de Impresora Directa
function cambiarBaudios(baud) {
  if (window.directPrinter) {
    window.directPrinter.baudRate = parseInt(baud, 10);
    localStorage.setItem('pos_direct_printer_baud', baud);
  }
}

async function conectarImpresoraSerial() {
  try {
    const baud = document.getElementById('cfgPrinterBaud').value;
    const name = await window.directPrinter.connectSerial(baud);
    alert('¡Conexión establecida con éxito con: ' + name + '!\nAhora presiona "Probar Apertura de Gaveta" o "Imprimir Ticket de Prueba".');
  } catch (e) {
    if (e.name !== 'NotFoundError') {
      alert('Aviso al conectar puerto serie: ' + e.message);
    }
  }
}

async function conectarImpresoraUsb() {
  try {
    const name = await window.directPrinter.connectUsb();
    alert('¡Conexión USB establecida con éxito con: ' + name + '!\nAhora presiona "Probar Apertura de Gaveta" o "Imprimir Ticket de Prueba".');
  } catch (e) {
    if (e.name !== 'NotFoundError') {
      alert('Aviso al conectar USB: ' + e.message);
    }
  }
}

async function desvincularImpresora() {
  if (confirm('¿Deseas desvincular la impresora directa de este navegador?')) {
    await window.directPrinter.disconnect();
  }
}

async function probarGavetaDirecta() {
  try {
    if (!window.directPrinter || window.directPrinter.type === 'none') {
      return alert('Primero debes hacer clic en "1. Conectar por Puerto Serie / COM" o "2. Conectar por Cable USB Directo".');
    }
    await window.directPrinter.openDrawer();
  } catch (e) {
    alert('Error al abrir gaveta: ' + e.message);
  }
}

async function probarTicketDirecto() {
  try {
    if (!window.directPrinter || window.directPrinter.type === 'none') {
      return alert('Primero debes hacer clic en "1. Conectar por Puerto Serie / COM" o "2. Conectar por Cable USB Directo".');
    }
    const businessName = document.querySelector("input[name='config[MINIMARKET_NOMBRE]']")?.value || 'MINIMARKET';
    const rut = document.querySelector("input[name='config[MINIMARKET_RUT]']")?.value || '';
    await window.directPrinter.printTestTicket(businessName, rut);
  } catch (e) {
    alert('Error al imprimir ticket: ' + e.message);
  }
}

function guardarPrefImpresora(key, val) {
  if (key === 'autodrawer') {
    localStorage.setItem('pos_direct_printer_autodrawer', val ? 'true' : 'false');
    if (window.directPrinter) window.directPrinter.autoDrawer = val;
  } else if (key === 'autocut') {
    localStorage.setItem('pos_direct_printer_autocut', val ? 'true' : 'false');
    if (window.directPrinter) window.directPrinter.autoCut = val;
  }
}
function actualizarBordeTema(modo) {
  const lblDark = document.getElementById('lblTemaDark');
  const lblLight = document.getElementById('lblTemaLight');
  if (modo === 'dark') {
    lblDark.style.borderColor = 'var(--primary)';
    lblLight.style.borderColor = 'var(--border-dark)';
  } else {
    lblLight.style.borderColor = 'var(--primary)';
    lblDark.style.borderColor = 'var(--border-dark)';
  }
  document.documentElement.setAttribute('data-theme', modo);
}

function actualizarAcentoPreview(color, radioEl) {
  document.documentElement.setAttribute('data-accent', color);
  document.querySelectorAll('.accent-card').forEach(c => c.style.borderColor = 'var(--border-dark)');
  if (radioEl) {
    radioEl.closest('.accent-card').style.borderColor = 'var(--primary)';
  }
}

function actualizarBordeModoPos(modo) {
  const cards = {
    supermercado: document.getElementById('lblModoSupermercado'),
    tactil: document.getElementById('lblModoTactil'),
    clasico: document.getElementById('lblModoClasico')
  };
  Object.keys(cards).forEach(k => {
    if (cards[k]) {
      cards[k].style.borderColor = (k === modo) ? 'var(--primary)' : 'var(--border-dark)';
    }
  });
}

function previewLogoFile(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById('logoPreviewImg');
      const placeholder = document.getElementById('logoPreviewPlaceholder');
      img.src = e.target.result;
      img.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewLogoUrl(url) {
  const img = document.getElementById('logoPreviewImg');
  const placeholder = document.getElementById('logoPreviewPlaceholder');
  if (url && url.trim() !== '') {
    img.src = url.trim();
    img.style.display = 'block';
    placeholder.style.display = 'none';
  } else {
    img.style.display = 'none';
    placeholder.style.display = 'flex';
  }
}

function quitarLogo() {
  document.getElementById('logoUrlInput').value = '';
  document.getElementById('logoPreviewImg').style.display = 'none';
  document.getElementById('logoPreviewPlaceholder').style.display = 'flex';
}
function switchTab(tabId) {
  document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
  
  const selectedPane = document.getElementById(tabId);
  const selectedBtn = document.getElementById('btn-' + tabId);
  
  if (selectedPane) selectedPane.style.display = 'block';
  if (selectedBtn) selectedBtn.classList.add('active', 'btn-primary');
  try { localStorage.setItem('config_active_tab', tabId); } catch(e) {}
}

function toggleDteFields() {
  const provider = document.getElementById('dteProveedorSelect').value;
  const divOF = document.getElementById('divConfigOpenFactura');
  const divHaulmer = document.getElementById('divConfigHaulmer');
  const divSII = document.getElementById('divConfigSIICommon');
  const divMsg = document.getElementById('divConfigMsg');
  const txtHelp = document.getElementById('txtDteHelp');

  divOF.style.display = 'none';
  divHaulmer.style.display = 'none';
  divSII.style.display = 'none';
  divMsg.style.display = 'block';

  if (provider === 'ninguno') {
    txtHelp.innerHTML = "<strong>Modo Comprobante Local Activo:</strong> El sistema NO enviará información al SII. Solo se generarán los comprobantes de venta internos del local. Ideal para operar sin boleta electrónica.";
  } else if (provider === 'mock') {
    txtHelp.innerHTML = "<strong>Modo Simulador (Mock) Activo:</strong> El sistema simulará la emisión de DTEs (boletas, facturas, notas de crédito) de forma instantánea. Generará folios ficticios de prueba. Útil para verificar la interfaz y base de datos sin incurrir en costos ni conexión real.";
  } else if (provider === 'openfactura') {
    divOF.style.display = 'flex';
    divSII.style.display = 'flex';
    divMsg.style.display = 'none';
  } else if (provider === 'haulmer') {
    divHaulmer.style.display = 'flex';
    divSII.style.display = 'flex';
    divMsg.style.display = 'none';
  }
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
  } else {
    input.type = 'password';
    btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
  }
}

function probarConexionGemini() {
  const input = document.getElementById('inputGeminiApiKey');
  const btn = document.getElementById('btnTestGemini');
  const resDiv = document.getElementById('divResultadoTestGemini');
  if (!input || !btn || !resDiv) return;

  const key = input.value.trim();
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Probando...';
  resDiv.style.display = 'block';
  resDiv.innerHTML = '<div style="background: rgba(148, 163, 184, 0.1); border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem; color: #cbd5e1;"><i class="fa-solid fa-spinner fa-spin"></i> Contactando servidores de Google Gemini...</div>';

  fetch('api/probar_gemini.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ api_key: key })
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-vial"></i> Probar Clave';
    if (data.success) {
      resDiv.innerHTML = `
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 0.6rem 0.85rem; font-size: 0.82rem; color: #34d399;">
          <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem;">
            <i class="fa-solid fa-circle-check"></i> ${data.mensaje}
          </div>
          <small style="color: #a7f3d0;">Modelo en uso: <strong>${data.modelo}</strong>. Recuerda hacer clic en "Guardar Configuración de IA" abajo para persistir cualquier cambio.</small>
        </div>
      `;
    } else {
      resDiv.innerHTML = `
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 0.6rem 0.85rem; font-size: 0.82rem; color: #f87171;">
          <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.2rem;">
            <i class="fa-solid fa-circle-xmark"></i> ${data.mensaje || data.error}
          </div>
          ${data.ayuda ? `<small style="color: #fca5a5; display: block; margin-top: 0.2rem;">${data.ayuda}</small>` : ''}
        </div>
      `;
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-vial"></i> Probar Clave';
    resDiv.innerHTML = `
      <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 0.6rem 0.85rem; font-size: 0.82rem; color: #f87171;">
        <i class="fa-solid fa-triangle-exclamation"></i> Error al conectar con el servidor local para realizar la prueba.
      </div>
    `;
  });
}

// Ejecutar al cargar la página
document.addEventListener("DOMContentLoaded", () => {
  toggleDteFields();
  
  // Sincronizar estado de impresora directa
  if (window.directPrinter) {
    const savedBaud = localStorage.getItem('pos_direct_printer_baud');
    if (savedBaud && document.getElementById('cfgPrinterBaud')) {
      document.getElementById('cfgPrinterBaud').value = savedBaud;
    }
    const chkDrawer = document.getElementById('chkAutoDrawer');
    if (chkDrawer) chkDrawer.checked = window.directPrinter.autoDrawer;
    const chkCut = document.getElementById('chkAutoCut');
    if (chkCut) chkCut.checked = window.directPrinter.autoCut;
    
    if (window.directPrinter.type !== 'none') {
      window.directPrinter.updateUiStatus(true, window.directPrinter.portName);
    }
  }

  try {
    const savedTab = localStorage.getItem('config_active_tab');
    if (savedTab && document.getElementById(savedTab)) {
      switchTab(savedTab);
    }
  } catch(e) {}
});
</script>

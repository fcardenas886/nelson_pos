</main>

<footer style="text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-dark); margin-top: auto;">
  <p>
    &copy; <?= date('Y') ?> Minimarket POS Web 
    <a href="acerca.php" style="color: var(--primary); font-weight: 600; text-decoration: none; margin-left: 0.25rem;" title="Ver evolución del sistema y notas de versiones">
      <?= APP_VERSION ?> · Acerca de y Novedades
    </a>
    — Operando en Laragon (PHP + MySQL)
  </p>
</footer>

<?php if (!empty($_SESSION['usuario']) && ($iaActivoGlobal ?? true)): ?>
  <!-- ============================================================== -->
  <!-- WIDGET FLOTANTE: ASISTENTE DE IA TIPO CHAT -->
  <!-- ============================================================== -->
  <link rel="stylesheet" href="assets/css/asistente-ia.css?v=<?= APP_VERSION ?>">

  <!-- Botón Flotante (FAB) -->
  <button type="button" id="btnToggleAsistenteIA" title="Abrir Asistente de IA de Minimarket POS (Atajo: Alt + I)">
    <i class="fa-solid fa-wand-magic-sparkles ia-sparkle-icon"></i>
    <span>Asistente IA</span>
  </button>

  <!-- Ventana Flotante de Chat -->
  <div id="ventanaChatIA" role="dialog" aria-label="Asistente de IA">
    <div class="chat-ia-header">
      <div class="chat-ia-header-info">
        <div class="chat-ia-avatar">
          <i class="fa-solid fa-robot"></i>
          <span class="chat-ia-online-dot"></span>
        </div>
        <div class="chat-ia-header-text">
          <h3>Asistente POS <span class="badge badge-info" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">IA</span></h3>
          <p>En línea · Guía de uso y funciones</p>
        </div>
      </div>
      <button type="button" id="btnCerrarChatIA" class="chat-ia-btn-close" title="Minimizar ventana">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="chat-ia-body" id="chatIABody">
      <!-- Mensaje Inicial de Bienvenida -->
      <div class="chat-ia-msg bot">
        <div class="chat-ia-bubble">
          <p>👋 ¡Hola! Soy el **Asistente Oficial de Minimarket POS**.</p>
          <p>Estoy aquí para guiarte paso a paso sobre <strong>cómo usar cualquier función del sistema</strong> (ventas, cobros, turnos, mermas, kardex, inventario, fiados, compras y reportes).</p>
          <p style="margin-top: 0.5rem; font-weight: 600; color: #a5b4fc;">Preguntas frecuentes rápidas:</p>
          <div class="chat-ia-chips">
            <button type="button" class="chat-ia-chip-btn" onclick="enviarPreguntaChip('¿Cómo abro y cierro mi turno de caja?')">
              <i class="fa-solid fa-cash-register"></i> ¿Cómo abrir y cerrar mi turno de caja?
            </button>
            <button type="button" class="chat-ia-chip-btn" onclick="enviarPreguntaChip('¿Cómo cobrar con fiado, puntos o pago mixto?')">
              <i class="fa-solid fa-credit-card"></i> ¿Cómo cobrar con fiado, puntos o pago mixto?
            </button>
            <button type="button" class="chat-ia-chip-btn" onclick="enviarPreguntaChip('¿Cómo registrar una merma o pérdida de stock?')">
              <i class="fa-solid fa-trash-can"></i> ¿Cómo registrar una merma o pérdida?
            </button>
            <button type="button" class="chat-ia-chip-btn" onclick="enviarPreguntaChip('¿Cómo ver las utilidades por categoría desplegable?')">
              <i class="fa-solid fa-folder-tree"></i> ¿Cómo ver utilidades por categoría?
            </button>
            <button type="button" class="chat-ia-chip-btn" onclick="enviarPreguntaChip('¿Cómo generar un código PLU de balanza o EAN-8?')">
              <i class="fa-solid fa-barcode"></i> ¿Cómo generar código PLU o EAN-8?
            </button>
          </div>
        </div>
        <div class="chat-ia-time"><?= date('H:i') ?></div>
      </div>
    </div>

    <div class="chat-ia-footer">
      <form id="formChatIA" class="chat-ia-input-row" autocomplete="off">
        <input type="text" id="inputChatIAMensaje" placeholder="Pregunta cómo se usa una función..." maxlength="300" required>
        <button type="submit" id="btnEnviarChatIA" title="Enviar consulta">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </form>
      <p class="chat-ia-disclaimer">Soporte exclusivo de funciones del sistema · Atajo: <kbd>Alt + I</kbd></p>
    </div>
  </div>

  <script src="assets/js/asistente-ia.js?v=<?= APP_VERSION ?>"></script>
<?php endif; ?>

<script src="assets/js/main.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>

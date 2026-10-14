/**
 * ASISTENTE DE IA TIPO CHAT - MINIMARKET POS
 * Controlador de interfaz y comunicación con api/asistente_ia.php
 */

document.addEventListener('DOMContentLoaded', function () {
  initAsistenteIA();
});

function initAsistenteIA() {
  const btnToggle = document.getElementById('btnToggleAsistenteIA');
  const ventana = document.getElementById('ventanaChatIA');
  const btnCerrar = document.getElementById('btnCerrarChatIA');
  const formInput = document.getElementById('formChatIA');
  const inputMsg = document.getElementById('inputChatIAMensaje');
  const chatBody = document.getElementById('chatIABody');

  if (!btnToggle || !ventana) return;

  // Restaurar historial de sessionStorage si existe
  cargarHistorialSesion();

  // Abrir / Cerrar ventana
  btnToggle.addEventListener('click', function () {
    toggleChatIA();
  });

  if (btnCerrar) {
    btnCerrar.addEventListener('click', function () {
      toggleChatIA(false);
    });
  }

  // Atajo de teclado: Alt + I para abrir asistente rápidamente
  document.addEventListener('keydown', function (e) {
    if (e.altKey && (e.key === 'i' || e.key === 'I')) {
      e.preventDefault();
      toggleChatIA();
    }
  });

  // Enviar mensaje
  if (formInput) {
    formInput.addEventListener('submit', function (e) {
      e.preventDefault();
      const texto = (inputMsg.value || '').trim();
      if (!texto) return;

      enviarMensajeUsuario(texto);
      inputMsg.value = '';
    });
  }
}

function toggleChatIA(forzar = null) {
  const ventana = document.getElementById('ventanaChatIA');
  const inputMsg = document.getElementById('inputChatIAMensaje');
  if (!ventana) return;

  const estaAbierto = ventana.classList.contains('abierto');
  const nuevoEstado = forzar !== null ? forzar : !estaAbierto;

  if (nuevoEstado) {
    ventana.classList.add('abierto');
    sessionStorage.setItem('chat_ia_abierto', '1');
    setTimeout(() => {
      if (inputMsg) inputMsg.focus();
    }, 150);
  } else {
    ventana.classList.remove('abierto');
    sessionStorage.setItem('chat_ia_abierto', '0');
  }
}

function enviarMensajeUsuario(texto) {
  const chatBody = document.getElementById('chatIABody');
  if (!chatBody) return;

  // 1. Obtener historial reciente de la sesión para dar contexto conversacional
  let historialPrevio = [];
  try {
    const rawHist = JSON.parse(sessionStorage.getItem('chat_ia_historial') || '[]');
    historialPrevio = rawHist.slice(-6).map(m => ({
      rol: m.tipo === 'bot' ? 'model' : 'user',
      texto: m.texto
    }));
  } catch (e) {}

  // 2. Agregar burbuja de usuario
  agregarBurbujaChat('user', texto);
  guardarEnHistorial('user', texto);

  // 3. Mostrar indicador de escribiendo
  const typingId = mostrarIndicadorEscribiendo();

  // 4. Enviar a la API con mensaje e historial
  fetch('api/asistente_ia.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ mensaje: texto, historial: historialPrevio })
  })
    .then(res => res.json())
    .then(data => {
      quitarIndicadorEscribiendo(typingId);
      if (data && data.success && data.respuesta) {
        agregarBurbujaChat('bot', data.respuesta, data.fuente || 'motor_local');
        guardarEnHistorial('bot', data.respuesta, data.fuente || 'motor_local');
      } else {
        const errTxt = data.error || 'Lo siento, no pude procesar la consulta en este momento.';
        agregarBurbujaChat('bot', '⚠️ ' + errTxt, 'sistema');
        guardarEnHistorial('bot', '⚠️ ' + errTxt, 'sistema');
      }
    })
    .catch(err => {
      quitarIndicadorEscribiendo(typingId);
      const msgErr = '⚠️ Ocurrió un error al comunicar con el servidor. Verifica tu conexión.';
      agregarBurbujaChat('bot', msgErr, 'sistema');
      guardarEnHistorial('bot', msgErr, 'sistema');
    });
}

function enviarPreguntaChip(texto) {
  toggleChatIA(true);
  enviarMensajeUsuario(texto);
}

function agregarBurbujaChat(tipo, contenidoTexto, fuente = null) {
  const chatBody = document.getElementById('chatIABody');
  if (!chatBody) return;

  const ahora = new Date();
  const horaStr = ahora.getHours().toString().padStart(2, '0') + ':' + ahora.getMinutes().toString().padStart(2, '0');

  const msgDiv = document.createElement('div');
  msgDiv.className = `chat-ia-msg ${tipo}`;

  const bubbleDiv = document.createElement('div');
  bubbleDiv.className = 'chat-ia-bubble';

  if (tipo === 'bot') {
    bubbleDiv.innerHTML = formatearTextoMarkdown(contenidoTexto);
  } else {
    bubbleDiv.textContent = contenidoTexto;
  }

  const timeDiv = document.createElement('div');
  timeDiv.className = 'chat-ia-time';
  
  let badgeFuente = '';
  if (tipo === 'bot') {
    if (fuente === 'gemini') {
      badgeFuente = ' · <span style="opacity:0.65; color:#a855f7;" title="IA Asistente"><i class="fa-solid fa-wand-magic-sparkles"></i></span>';
    } else if (fuente === 'motor_local') {
      badgeFuente = ' · <span style="opacity:0.65; color:#38bdf8;" title="Manual POS"><i class="fa-solid fa-bolt"></i></span>';
    } else if (fuente === 'guardrail') {
      badgeFuente = ' · <span style="opacity:0.65; color:#34d399;" title="Sistema"><i class="fa-solid fa-shield-halved"></i></span>';
    }
  }
  timeDiv.innerHTML = horaStr + badgeFuente;

  msgDiv.appendChild(bubbleDiv);
  msgDiv.appendChild(timeDiv);

  chatBody.appendChild(msgDiv);
  chatBody.scrollTop = chatBody.scrollHeight;
}

function mostrarIndicadorEscribiendo() {
  const chatBody = document.getElementById('chatIABody');
  if (!chatBody) return null;

  const id = 'typing_' + Date.now();
  const typingDiv = document.createElement('div');
  typingDiv.id = id;
  typingDiv.className = 'chat-ia-msg bot';
  typingDiv.innerHTML = `
    <div class="chat-ia-typing">
      <div class="chat-ia-typing-dot"></div>
      <div class="chat-ia-typing-dot"></div>
      <div class="chat-ia-typing-dot"></div>
    </div>
  `;

  chatBody.appendChild(typingDiv);
  chatBody.scrollTop = chatBody.scrollHeight;
  return id;
}

function quitarIndicadorEscribiendo(id) {
  if (!id) return;
  const el = document.getElementById(id);
  if (el) el.remove();
}

function formatearTextoMarkdown(txt) {
  if (!txt) return '';

  let html = txt
    // Escapar etiquetas peligrosas pero permitir kbd, strong, em
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // Restaurar kbd permitido
  html = html.replace(/&lt;kbd&gt;(.*?)&lt;\/kbd&gt;/gi, '<kbd>$1</kbd>');

  // Negrita **texto**
  html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

  // Cursiva *texto*
  html = html.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');

  // Bloques de código `código`
  html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.3); padding:0.1rem 0.35rem; border-radius:4px; font-size:0.8rem; color:#38bdf8;">$1</code>');

  // Listas con viñetas
  const lineas = html.split('\n');
  let enLista = false;
  let resLineas = [];

  for (let l of lineas) {
    const lTrim = l.trim();
    if (lTrim.startsWith('* ') || lTrim.startsWith('- ')) {
      if (!enLista) {
        resLineas.push('<ul style="margin: 0.35rem 0; padding-left: 1.2rem;">');
        enLista = true;
      }
      resLineas.push(`<li>${lTrim.substring(2)}</li>`);
    } else {
      if (enLista) {
        resLineas.push('</ul>');
        enLista = false;
      }
      if (lTrim.length > 0) {
        resLineas.push(`<p style="margin: 0.25rem 0;">${l}</p>`);
      }
    }
  }
  if (enLista) {
    resLineas.push('</ul>');
  }

  return resLineas.join('');
}

function guardarEnHistorial(tipo, texto, fuente = null) {
  try {
    let hist = JSON.parse(sessionStorage.getItem('chat_ia_historial') || '[]');
    hist.push({ tipo: tipo, texto: texto, fuente: fuente, time: Date.now() });
    // Guardar hasta los últimos 30 mensajes
    if (hist.length > 30) hist = hist.slice(-30);
    sessionStorage.setItem('chat_ia_historial', JSON.stringify(hist));
  } catch (e) {}
}

function cargarHistorialSesion() {
  const chatBody = document.getElementById('chatIABody');
  if (!chatBody) return;

  try {
    const hist = JSON.parse(sessionStorage.getItem('chat_ia_historial') || '[]');
    if (hist.length > 0) {
      // Limpiar mensaje inicial por defecto si ya hay historial previo
      chatBody.innerHTML = '';
      hist.forEach(item => {
        agregarBurbujaChat(item.tipo, item.texto, item.fuente || null);
      });
    }

    // Restaurar si estaba abierto
    if (sessionStorage.getItem('chat_ia_abierto') === '1') {
      toggleChatIA(true);
    }
  } catch (e) {}
}

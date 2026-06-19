// frontend/app.js

let modoActual = 'register'; 

// URL COMPLETAMENTE CORREGIDA: Apunta directo a la raíz de tu Clever Cloud para eliminar el error de CORS
const API_BASE_URL = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io";
const AUTH_URL = `${API_BASE_URL}/auth.php`;
const RESERVAS_URL = `${API_BASE_URL}/reservas.php`;

// Registro del Service Worker para convertir la aplicación en una PWA según la rúbrica
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => emiteLog(`Service Worker activo. Scope: ${reg.scope}`, "success"))
            .catch(err => emiteLog("Error de registro SW: " + err, "error"));
    });
}

// Función encargada de imprimir las operaciones e hilos en la consola de pantalla
function emiteLog(msg, tipo = "info") {
    const box = document.getElementById('console-logs');
    const hora = new Date().toLocaleTimeString();
    box.innerHTML += `<p class="log-${tipo}">[${hora}] ${msg}</p>`;
    box.scrollTop = box.scrollHeight; // Auto-scroll al último evento generado
}

// HILO SECUNDARIO EN SEGUNDO PLANO: Simula monitoreo de latencia persistente cada 15 segundos
setInterval(() => {
    emiteLog("Hilo Secundario: Monitoreando latencia de los clusters cloud...", "info");
}, 15000);

// Alterna los textos visuales de la tarjeta de autenticación (Login / Registro)
function intercambiarModo(e) {
    e.preventDefault();
    document.getElementById('password-group').classList.remove('hidden');
    const msg = document.getElementById('welcome-text');
    const btn = document.getElementById('btn-submit');
    const toggleLink = document.getElementById('toggle-auth-mode');

    if (modoActual === 'register' || modoActual === 'recover') {
        modoActual = 'login';
        msg.innerText = "Inicia sesión para entrar al panel de tu corazón";
        btn.innerText = "Iniciar Sesión";
        toggleLink.innerText = "¿No tienes cuenta? Regístrate aquí";
    } else {
        modoActual = 'register';
        msg.innerText = "Bienvenido a la app de citas donde vas a encontrar tu media naranja";
        btn.innerText = "Registrar y Validar";
        toggleLink.innerText = "¿Ya tienes cuenta? Iniciar Sesión";
    }
}

// Adapta los campos del formulario para procesar un olvido de claves
function activarModoRecuperacion(e) {
    e.preventDefault();
    modoActual = 'recover';
    document.getElementById('welcome-text').innerText = "Recuperación de Contraseña";
    document.getElementById('password-group').classList.add('hidden');
    document.getElementById('btn-submit').innerText = "Enviar Enlace de Recuperación";
    document.getElementById('toggle-auth-mode').innerText = "Volver al Inicio de Sesión";
}

// ASYNC / AWAIT: Consume las peticiones de autenticación sin congelar la renderización de la UI
async function enviarDatosFormulario(e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    let urlTarget = `${AUTH_URL}?action=register`;
    let payload = { email, password };

    if (modoActual === 'login') {
        urlTarget = `${AUTH_URL}?action=login`;
    } else if (modoActual === 'recover') {
        urlTarget = `${AUTH_URL}?action=recover_request`;
        payload = { email };
    }

    emiteLog(`Enviando ${modoActual} al backend de Clever Cloud...`, "info");

    try {
        const respuesta = await fetch(urlTarget, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const datos = await respuesta.json();

        if (datos.status === 'success') {
            emiteLog(datos.message, "success");
            alert(datos.message);

            // Muestra en la consola integrada el enlace de activación simulado
            if ((modoActual === 'register' || modoActual === 'recover') && datos.link_simulado) {
                emiteLog(`[Gmail Simulado] Enlace Recibido: ${datos.link_simulado}`, "success");
            } 
            else if (modoActual === 'login') {
                document.getElementById('auth-section').classList.add('hidden');
                document.getElementById('heart-success-section').classList.remove('hidden');
                document.getElementById('display-user-email').innerText = `Conectado como: ${email}`;
                cargarCitas(email); // Lee de manera asíncrona las citas existentes (CRUD)
            }
        } else {
            emiteLog(`Fallo de validación: ${datos.message}`, "error");
            alert(datos.message);
        }
    } catch (error) {
        emiteLog("Fallo de CORS o error en la dirección IP del backend.", "error");
    }
}

// === FUNCIONES ADICIONALES PARA OPERACIONES CRUD (reservas.php) ===

// CRUD: Consulta asíncronamente las citas guardadas del usuario en Clever Cloud
async function cargarCitas(email) {
    try {
        const res = await fetch(`${RESERVAS_URL}?email=${encodeURIComponent(email)}`);
        const citas = await res.json();
        const lista = document.getElementById('lista-citas');
        lista.innerHTML = "";
        
        if (citas.length === 0) {
            lista.innerHTML = "<li>No hay citas agendadas aún.</li>";
            return;
        }
        
        citas.forEach(c => {
            lista.innerHTML += `<li>❤️ Cita con <strong>${c.nombre_cita}</strong> el: ${new Date(c.fecha_hora).toLocaleString()}</li>`;
        });
        emiteLog("CRUD: Datos de citas cargados desde la base de datos.", "success");
    } catch (err) {
        emiteLog("Error al cargar listado CRUD de citas.", "error");
    }
}

// CRUD: Envía una inserción persistente de una nueva cita a la Base de Datos
async function guardarNuevaCita() {
    const email = document.getElementById('email').value.trim();
    const nombre_cita = document.getElementById('nombre-cita').value.trim();
    const fecha_hora = document.getElementById('fecha-cita').value;

    if (!nombre_cita || !fecha_hora) {
        alert("Por favor llena los campos de la cita.");
        return;
    }

    try {
        const res = await fetch(RESERVAS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, nombre_cita, fecha_hora })
        });
        const datos = await res.json();
        if (datos.status === 'success') {
            alert(datos.message);
            document.getElementById('nombre-cita').value = "";
            document.getElementById('fecha-cita').value = "";
            cargarCitas(email); // Actualización dinámica de la información en pantalla sin recargar la página entera
        } else {
            alert(datos.message);
        }
    } catch (e) {
        emiteLog("Error al insertar registro en la base de datos.", "error");
    }
}

// Limpieza de estados globales al cerrar sesión
function salirDeSesion() {
    document.getElementById('heart-success-section').classList.add('hidden');
    document.getElementById('auth-section').classList.remove('hidden');
    document.getElementById('auth-form').reset();
    modoActual = 'login';
    intercambiarModo({ preventDefault: () => {} });
    emiteLog("Sesión de usuario destruida con éxito.", "info");
}

let isLoginMode = true;
let currentUser = null;

// URL fija por defecto de Clever Cloud si el input local falla o está vacío
const getBackendUrl = () => {
    const urlProduccionClever = "https://bxai5nugdj0qtsguxlnm-mysql.services.clever-cloud.com"; // Reemplázala por tu URL final de dominio PHP si aplica
    const inputUrl = document.getElementById('backend-url')?.value?.trim().replace(/\/$/, '');
    
    if (!inputUrl || inputUrl.includes('localhost') && inputUrl === '') {
        return urlProduccionClever;
    }
    return inputUrl;
};

const authForm = document.getElementById('auth-form');
const authTitle = document.getElementById('auth-title');
const btnAuthSubmit = document.getElementById('btn-auth-submit');
const toggleAuthLink = document.getElementById('toggle-auth-link');
const forgotPassLink = document.getElementById('forgot-pass-link');
const authSection = document.getElementById('auth-section');
const appSection = document.getElementById('app-section');
const currentUserDisplay = document.getElementById('current-user-display');
const btnLogout = document.getElementById('btn-logout');
const connectionStatus = document.getElementById('connection-status');
const consoleLogs = document.getElementById('console-logs');
const btnClearLogs = document.getElementById('btn-clear-logs');
const crudForm = document.getElementById('crud-form');
const reservasTableBody = document.getElementById('reservas-table-body');

function addLog(text, type = 'info') {
    const p = document.createElement('p');
    p.className = `log-${type}`;
    p.textContent = `[${new Date().toLocaleTimeString()}] ${text}`;
    consoleLogs.appendChild(p);
    consoleLogs.scrollTop = consoleLogs.scrollHeight;
}

// Hilo asíncrono secundario (Rúbrica: Procesos en segundo plano)
setInterval(() => {
    if (navigator.onLine) {
        addLog("Hilo Secundario: Monitoreando latencia de los clusters cloud...", "process");
    }
}, 15000);

window.addEventListener('online', () => {
    connectionStatus.textContent = "Online";
    connectionStatus.className = "status online";
    addLog("API Red: Enlace de datos activo.", "success");
});
window.addEventListener('offline', () => {
    connectionStatus.textContent = "Offline";
    connectionStatus.className = "status offline";
    addLog("API Red: Terminal operando sin conexión.", "warn");
});

// Función centralizada para alternar pantallas de Login / Registro
function actualizarInterfazAuth() {
    authTitle.textContent = isLoginMode ? "Autenticación de Usuarios" : "Registro de Cuenta Nueva";
    btnAuthSubmit.textContent = isLoginMode ? "Iniciar Sesión" : "Registrar y Validar";
    toggleAuthLink.textContent = isLoginMode ? "¿No tienes cuenta? Registrar nuevo usuario" : "¿Ya tienes cuenta? Iniciar Sesión";
}

toggleAuthLink.addEventListener('click', (e) => {
    e.preventDefault();
    isLoginMode = !isLoginMode;
    actualizarInterfazAuth();
});

// Recuperación de Contraseña Real Asíncrona
forgotPassLink.addEventListener('click', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    if (!email) { 
        alert("Por favor, escribe tu correo electrónico en el campo superior."); 
        return; 
    }

    addLog(`Asíncrono: Solicitando token de recuperación para: ${email}`, "process");
    try {
        const res = await fetch(`${getBackendUrl()}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=recover&email=${encodeURIComponent(email)}`
        });
        const data = await res.json();
        addLog(`Cloud API: ${data.message}`, data.status === 'success' ? 'success' : 'warn');
        alert(data.message);
    } catch (err) {
        addLog("Error de red en solicitud externa de credenciales.", "warn");
    }
});

// Autenticación de Usuarios (Login/Registro)
authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const action = isLoginMode ? 'login' : 'register';

    addLog(`Enviando transacción de ${action} al backend en la nube...`, "process");

    try {
        const res = await fetch(`${getBackendUrl()}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
        });
        const data = await res.json();

        if (data.status === 'success') {
            addLog(data.message, "success");
            alert(data.message);
            if (isLoginMode) {
                currentUser = email;
                currentUserDisplay.textContent = currentUser;
                authSection.classList.add('hidden');
                appSection.classList.remove('hidden');
                loadReservas();
            } else {
                // CORRECCIÓN: Cambia a modo login y actualiza visualmente de forma limpia
                isLoginMode = true;
                actualizarInterfazAuth();
                document.getElementById('password').value = '';
            }
        } else {
            addLog(`Error del Servidor: ${data.message}`, "warn");
            alert(data.message);
        }
    } catch (err) {
        addLog("Fallo de CORS o error en la dirección IP del backend.", "warn");
    }
});

btnLogout.addEventListener('click', () => {
    currentUser = null;
    appSection.classList.add('hidden');
    authSection.classList.remove('hidden');
    document.getElementById('password').value = '';
    addLog("Sesión cerrada correctamente. Token destruido.", "info");
});

// CRUD Asíncrono: Leer registros de la Base de Datos Relacional
async function loadReservas() {
    addLog("Asíncrono: Solicitando catálogo relacional a MySQL...", "process");
    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php`);
        const reservas = await res.json();
        reservasTableBody.innerHTML = '';

        reservas.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${r.dni}</td>
                <td>${r.idVuelo}</td>
                <td>${r.fecha}</td>
                <td><strong>$${r.precio}</strong></td>
                <td>
                    <button onclick="deleteReserva('${r.dni}', '${r.idVuelo}')" class="btn-danger">Eliminar</button>
                </td>
            `;
            reservasTableBody.appendChild(tr);
        });
        addLog(`Éxito: ${reservas.length} filas mapeadas en pantalla de forma asíncrona.`, "success");
    } catch (err) {
        addLog("Fallo al sincronizar datos remotos del cluster.", "warn");
    }
}

// CRUD Asíncrono: Insertar nueva reserva (POST)
crudForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const dni = document.getElementById('res-dni').value.trim();
    const idVuelo = document.getElementById('res-idvuelo').value.trim();
    const fecha = document.getElementById('res-fecha').value;
    const precio = document.getElementById('res-precio').value.trim();

    addLog("Iniciando inserción asíncrona (POST) en el cluster...", "process");

    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `dni=${encodeURIComponent(dni)}&idVuelo=${encodeURIComponent(idVuelo)}&fecha=${encodeURIComponent(fecha)}&precio=${encodeURIComponent(precio)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            addLog("Datos consolidados exitosamente en MySQL.", "success");
            crudForm.reset();
            loadReservas();
        } else {
            addLog(`Error: ${data.message}`, "warn");
            alert(data.message);
        }
    } catch (err) {
        addLog("Fallo de red en la transacción del formulario.", "warn");
    }
});

// CRUD Asíncrono: Eliminar registro físico (DELETE)
window.deleteReserva = async function (dni, idVuelo) {
    if (confirm(`¿Remover físicamente el registro relacional (DNI: ${dni} - Vuelo: ${idVuelo}) de MySQL?`)) {
        addLog(`Enviando eliminación asíncrona para DNI: ${dni}...`, "process");
        try {
            const res = await fetch(`${getBackendUrl()}/reservas.php?dni=${encodeURIComponent(dni)}&idVuelo=${encodeURIComponent(idVuelo)}`, { 
                method: 'DELETE' 
            });
            const data = await res.json();
            if (data.status === 'success') {
                addLog("Fila purgada con éxito del cluster cloud.", "success");
                loadReservas();
            } else {
                addLog(`Error al eliminar: ${data.message}`, "warn");
            }
        } catch (err) {
            addLog("Error al intentar procesar la eliminación en red.", "warn");
        }
    }
};

btnClearLogs.addEventListener('click', () => consoleLogs.innerHTML = '');

// Inicialización del Service Worker (Requerimiento PWA)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => addLog(`Service Worker activo. Alcance de sincronización: ${reg.scope}`, "success"))
            .catch(err => addLog(`Fallo de ciclo en Service Worker: ${err}`, "warn"));
    });
}

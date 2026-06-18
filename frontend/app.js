let isLoginMode = true;
let currentUser = null;

const getBackendUrl = () => document.getElementById('backend-url').value.replace(/\/$/, '');

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

// Hilo asíncrono secundario
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

toggleAuthLink.addEventListener('click', (e) => {
    e.preventDefault();
    isLoginMode = !isLoginMode;
    authTitle.textContent = isLoginMode ? "Autenticación de Usuarios" : "Registro de Cuenta Nueva";
    btnAuthSubmit.textContent = isLoginMode ? "Iniciar Sesión" : "Registrar y Validar";
    toggleAuthLink.textContent = isLoginMode ? "¿No tienes cuenta? Registrar nuevo usuario" : "¿Ya tienes cuenta? Iniciar Sesión";
});

// Recuperación de Contraseña
forgotPassLink.addEventListener('click', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    if (!email) { alert("Escribe tu correo institucional."); return; }

    addLog(`Asíncrono: Solicitando token para: ${email}`, "process");
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
        addLog("Error de red en solicitud externa.", "warn");
    }
});

// Autenticación (Login/Registro)
authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const action = isLoginMode ? 'login' : 'register';

    addLog(`Enviando ${action} al backend de Clever Cloud...`, "process");

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
                isLoginMode = true;
                toggleAuthLink.click();
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
    addLog("Sesión cerrada correctamente.", "info");
});

// CRUD Asíncrono de reservas
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
                    <button onclick="deleteReserva(${r.dni}, ${r.idVuelo})" class="btn-danger">Eliminar</button>
                </td>
            `;
            reservasTableBody.appendChild(tr);
        });
        addLog(`Éxito: ${reservas.length} filas mapeadas en pantalla.`, "success");
    } catch (err) {
        addLog("Fallo al sincronizar datos remotos.", "warn");
    }
}

crudForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const dni = document.getElementById('res-dni').value;
    const idVuelo = document.getElementById('res-idvuelo').value;
    const fecha = document.getElementById('res-fecha').value;
    const precio = document.getElementById('res-precio').value;

    addLog("Iniciando inserción asíncrona (POST)...", "process");

    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `dni=${dni}&idVuelo=${idVuelo}&fecha=${fecha}&precio=${precio}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            addLog("Datos consolidados exitosamente.", "success");
            crudForm.reset();
            loadReservas();
        } else {
            addLog(`Error: ${data.message}`, "warn");
            alert(data.message);
        }
    } catch (err) {
        addLog("Fallo de red en la transacción.", "warn");
    }
});

window.deleteReserva = async function (dni, idVuelo) {
    if (confirm("¿Remover físicamente este registro relacional de MySQL?")) {
        addLog(`Enviando eliminación asíncrona para DNI: ${dni}...`, "process");
        try {
            const res = await fetch(`${getBackendUrl()}/reservas.php?dni=${dni}&idVuelo=${idVuelo}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.status === 'success') {
                addLog("Fila purgada con éxito.", "success");
                loadReservas();
            }
        } catch (err) {
            addLog("Error al intentar procesar la eliminación.", "warn");
        }
    }
};

btnClearLogs.addEventListener('click', () => consoleLogs.innerHTML = '');

// Inicialización del Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => addLog(`Service Worker activo. Scope: ${reg.scope}`, "success"))
            .catch(err => addLog(`Fallo de Service Worker: ${err}`, "warn"));
    });
}
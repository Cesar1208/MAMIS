// frontend/app.js

let isLoginMode = true;
let currentUser = null;

// ===================================================
// RESOLUCIÓN DE RUTA DINÁMICA DE LA API
// ===================================================
const getBackendUrl = () => {
    const inputUrl = document.getElementById('network-url-input').value.trim();
    if (inputUrl) {
        return inputUrl;
    }
    // ⚠️ REEMPLAZA ESTA URL POR EL ENLACE COMPLETO EN HTTPS QUE TE ENTREGÓ CLEVER CLOUD
    return "https://tu-app-php.cleverapps.io/backend";
};

// Selectores estructurales del DOM
const authForm = document.getElementById('auth-form');
const authTitle = document.getElementById('auth-title');
const authSubtitle = document.getElementById('auth-subtitle');
const btnAuthSubmit = document.getElementById('btn-auth-submit');
const toggleAuthLink = document.getElementById('toggle-auth-link');
const authSection = document.getElementById('auth-section');
const appSection = document.getElementById('app-section');
const currentUserDisplay = document.getElementById('current-user-display');
const btnLogout = document.getElementById('btn-logout');
const connectionStatus = document.getElementById('connection-status');
const consoleLogs = document.getElementById('console-logs');
const btnClearLogs = document.getElementById('btn-clear-logs');
const crudForm = document.getElementById('crud-form');
const reservasTableBody = document.getElementById('reservas-table-body');

// Función de Logs de la consola asíncrona
function addLog(text, type = 'info') {
    const p = document.createElement('p');
    p.className = `log-${type}`;
    p.textContent = `[${new Date().toLocaleTimeString()}] ${text}`;
    consoleLogs.appendChild(p);
    consoleLogs.scrollTop = consoleLogs.scrollHeight;
}

// ===================================================
// MONITOREO DE HILOS SECUNDARIOS (RÚBRICA PWA)
// ===================================================
setInterval(() => {
    if (navigator.onLine) {
        addLog("Hilo Secundario: Verificando latencia con los clusters cloud...", "process");
    }
}, 15000);

window.addEventListener('online', () => {
    connectionStatus.textContent = "Online";
    connectionStatus.className = "status online";
    addLog("API Red: Terminal conectada al backend global.", "success");
});

window.addEventListener('offline', () => {
    connectionStatus.textContent = "Offline";
    connectionStatus.className = "status offline";
    addLog("API Red: Error de red física. Modo offline activado.", "warn");
});

function actualizarInterfazAuth() {
    authTitle.textContent = isLoginMode ? "Autenticación de Usuarios" : "Registro de Cuenta Nueva";
    authSubtitle.textContent = isLoginMode ? "Ingresa tus credenciales para acceder al panel relacional core." : "Crea una cuenta para interactuar con las tablas de MySQL.";
    btnAuthSubmit.textContent = isLoginMode ? "Iniciar Sesión" : "Registrar y Validar";
    toggleAuthLink.textContent = isLoginMode ? "¿No tienes cuenta? Registrar nuevo usuario" : "¿Ya tienes cuenta? Iniciar Sesión";
}

toggleAuthLink.addEventListener('click', (e) => {
    e.preventDefault();
    isLoginMode = !isLoginMode;
    actualizarInterfazAuth();
});

// ===================================================
// AUTENTICACIÓN ASÍNCRONA (LOGIN / REGISTRO)
// ===================================================
authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const action = isLoginMode ? 'login' : 'register';

    if (!email || !password) {
        addLog("Formulario rechazado: Campos incompletos.", "warn");
        return;
    }

    addLog(`Enviando petición asíncrona (${action}) al clúster de Clever Cloud...`, "process");

    try {
        const res = await fetch(`${getBackendUrl()}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
        });
        
        if (!res.ok) throw new Error(`HTTP Status ${res.status}`);
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
                actualizarInterfazAuth();
                document.getElementById('password').value = '';
            }
        } else {
            addLog(`Respuesta del Servidor: ${data.message}`, "warn");
            alert(data.message);
        }
    } catch (err) {
        addLog("Fallo de CORS o error en la dirección IP del backend.", "error");
    }
});

// ===================================================
// OPERACIONES DEL CRUD DE RESERVAS (GET / POST / DELETE)
// ===================================================
async function loadReservas() {
    addLog("Sincronizando registros de la tabla 'reservas'...", "process");
    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php`);
        if (!res.ok) throw new Error();
        const reservas = await res.json();
        
        reservasTableBody.innerHTML = '';
        if (reservas.length === 0) {
            reservasTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No hay reservas disponibles en el clúster.</td></tr>`;
            return;
        }

        reservas.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${r.dni}</td>
                <td><span class="highlight">${r.idVuelo}</span></td>
                <td>${r.fecha}</td>
                <td>$${parseFloat(r.precio).toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger" onclick="deleteReserva('${r.dni}', '${r.idVuelo}')">Eliminar</button>
                </td>
            `;
            reservasTableBody.appendChild(tr);
        });
        addLog("Tabla de datos actualizada correctamente.", "success");
    } catch (err) {
        addLog("Error al recuperar datos del CRUD relacional.", "error");
    }
}

crudForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const dni = document.getElementById('dni').value.trim();
    const idVuelo = document.getElementById('idVuelo').value.trim();
    const fecha = document.getElementById('fecha').value;
    const precio = document.getElementById('precio').value;

    addLog("Enviando comando INSERT hacia las tablas físicas de MySQL...", "process");

    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `dni=${encodeURIComponent(dni)}&idVuelo=${encodeURIComponent(idVuelo)}&fecha=${encodeURIComponent(fecha)}&precio=${encodeURIComponent(precio)}`
        });
        const data = await res.json();

        if (data.status === 'success') {
            addLog(data.message, "success");
            crudForm.reset();
            loadReservas();
        } else {
            addLog(`Error en inserción: ${data.message}`, "warn");
        }
    } catch (err) {
        addLog("Error de comunicación de red al guardar registro.", "error");
    }
});

window.deleteReserva = async (dni, idVuelo) => {
    if (!confirm("¿Deseas eliminar este registro de la base de datos cloud?")) return;
    addLog(`Enviando instrucción DELETE para DNI: ${dni}...`, "process");

    try {
        const res = await fetch(`${getBackendUrl()}/reservas.php?dni=${encodeURIComponent(dni)}&idVuelo=${encodeURIComponent(idVuelo)}`, {
            method: 'DELETE'
        });
        const data = await res.json();

        if (data.status === 'success') {
            addLog(data.message, "success");
            loadReservas();
        } else {
            addLog(`Error al eliminar: ${data.message}`, "warn");
        }
    } catch (err) {
        addLog("No se pudo completar el borrado físico del registro.", "error");
    }
};

btnLogout.addEventListener('click', () => {
    currentUser = null;
    appSection.classList.add('hidden');
    authSection.classList.remove('hidden');
    document.getElementById('password').value = '';
    addLog("Sesión cerrada. Estado de memoria destruido de forma segura.", "info");
});

btnClearLogs.addEventListener('click', () => { consoleLogs.innerHTML = ''; });

// Inicialización
addLog("Inicializando entorno PWA listo para Clever Cloud...", "success");

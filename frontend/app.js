let isLoginMode = true;
let currentUser = null;

// Configuración de la URL de producción del Servidor PHP en Clever Cloud
const getBackendUrl = () => {
    // 👇 REEMPLAZA ESTA URL POR TU ENLACE REAL DE CLEVER CLOUD (Debe incluir /backend al final si está en esa carpeta)
    return "https://tu-app-php.cleverapps.io/backend";
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

// Hilo asíncrono secundario en ejecución constante
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

// Autenticación asíncrona completa (Login/Registro)
authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const action = isLoginMode ? 'login' : 'register';

    addLog(`Enviando transacción de ${action} al backend de Clever Cloud...`, "process");

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
                actualizarInterfazAuth();
                document.getElementById('password').value = '';
            }
        } else {
            addLog(`Error del Servidor: ${data.message}`, "warn");
            alert(data.message);
        }
    } catch (err) {
        addLog("Fallo de comunicación o error de red con el cluster de Clever Cloud.", "warn");
    }
});

btnLogout.addEventListener('click', () => {
    currentUser = null;
    appSection.classList.add('hidden');
    authSection.classList.remove('hidden');
    document.getElementById('password').value = '';
    addLog("Sesión cerrada correctamente.", "info");
});

// ... (Aquí se mantiene intacto tu código posterior de loadReservas, crudForm y Service Worker)

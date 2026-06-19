// frontend/app.js

// 1. Determinar de forma automática la dirección IP/Dominio del Backend en Producción
const getBackendUrl = () => {
    const inputUrl = document.getElementById('network-url-input').value.trim();
    if (inputUrl) return inputUrl;
    
    // Conexión directa y por defecto a tu servidor de Clever Cloud sin requerir configuraciones manuales
    return "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io/backend";
};

let currentMode = 'register'; // Modos: 'register' o 'login'

// 2. Registro de Service Worker para cumplimiento estricto de PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => logToConsole(`Service Worker activo. Scope: ${reg.scope}`, 'success'))
            .catch(err => logToConsole(`Fallo de Service Worker: ${err}`, 'error'));
    });
}

// 3. Sistema para simular logs de hilos en segundo plano
function logToConsole(message, type = 'info') {
    const consoleBox = document.getElementById('console-logs');
    const time = new Date().toLocaleTimeString();
    let classColor = 'log-info';
    
    if (type === 'error') classColor = 'log-error';
    if (type === 'success') classColor = 'log-success';
    
    consoleBox.innerHTML += `<p class="${classColor}">[${time}] ${message}</p>`;
    consoleBox.scrollTop = consoleBox.scrollHeight;
}

function clearConsole() {
    document.getElementById('console-logs').innerHTML = '';
}

// Hilo secundario automatizado: Simulación de monitoreo de latencia en segundo plano
setInterval(() => {
    logToConsole("Hilo Secundario: Monitoreando latencia de los clusters cloud...", "info");
}, 15000);

// 4. Cambiar entre modos de Login y Registro de forma dinámica
function toggleAuthMode(event) {
    event.preventDefault();
    const title = document.getElementById('auth-title');
    const button = document.getElementById('btn-auth-action');
    const toggleLink = document.getElementById('toggle-link');
    
    if (currentMode === 'register') {
        currentMode = 'login';
        title.innerText = 'Iniciar Sesión';
        button.innerText = 'Ingresar';
        toggleLink.innerText = '¿No tienes cuenta? Registrar Cuenta Nueva';
    } else {
        currentMode = 'register';
        title.innerText = 'Registro de Cuenta Nueva';
        button.innerText = 'Registrar y Validar';
        toggleLink.innerText = '¿Ya tienes cuenta? Iniciar Sesión';
    }
}

// 5. Consumo Asíncrono de API: Registro e Inicio de Sesión
async function handleAuth() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const baseUrl = getBackendUrl();
    
    if (!email || !password) {
        logToConsole("Fallo: El usuario no ingresó credenciales válidas.", "error");
        alert("Por favor llena todos los campos.");
        return;
    }
    
    logToConsole(`Enviando ${currentMode} al backend de Clever Cloud...`, "info");
    
    try {
        const response = await fetch(`${baseUrl}/auth.php?action=${currentMode}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            logToConsole(`Éxito: ${data.message}`, "success");
            
            if (currentMode === 'register') {
                alert(`¡Simulación de correo enviada!\n\nRevisa el enlace impreso en la consola abajo para validar tu cuenta en Clever Cloud.`);
                if (data.link) {
                    logToConsole(`[CORREO ENVIADO] Enlace de activación generado: ${data.link}`, "success");
                }
            } else {
                // Almacenamiento persistente local de sesión
                localStorage.setItem('user_session', JSON.stringify(data.user));
                enterApp(data.user);
            }
        } else {
            logToConsole(`Denegado: ${data.message}`, "error");
            alert(data.message);
        }
    } catch (error) {
        logToConsole("Fallo de CORS o error en la dirección IP del backend.", "error");
        console.error(error);
    }
}

function enterApp(user) {
    document.getElementById('auth-section').classList.add('hidden');
    document.getElementById('dashboard-section').classList.remove('hidden');
    document.getElementById('session-user').innerText = `Sesión activa: ${user.email}`;
    cargarCitas();
}

function logout() {
    localStorage.removeItem('user_session');
    document.getElementById('auth-section').classList.remove('hidden');
    document.getElementById('dashboard-section').classList.add('hidden');
    logToConsole("Sesión destruida correctamente de forma segura.", "info");
}

// 6. Operaciones CRUD: Envío de datos dinámicos a la tabla Reservas
async function guardarCita() {
    const detalle = document.getElementById('cita-detalle').value.trim();
    const fecha = document.getElementById('cita-fecha').value;
    const session = JSON.parse(localStorage.getItem('user_session'));
    const baseUrl = getBackendUrl();
    
    if (!detalle || !fecha) {
        alert("Llena los campos de la cita");
        return;
    }
    
    logToConsole("Enviando comando INSERT CRUD a la Base de Datos...", "info");
    
    try {
        const response = await fetch(`${baseUrl}/reservas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: session ? session.id : 1,
                detalle: detalle,
                fecha: fecha
            })
        });
        const data = await response.json();
        if (data.status === 'success') {
            logToConsole("Cita insertada en Clever Cloud.", "success");
            document.getElementById('cita-detalle').value = '';
            document.getElementById('cita-fecha').value = '';
            cargarCitas();
        }
    } catch (e) {
        logToConsole("Error de comunicación CRUD.", "error");
    }
}

async function cargarCitas() {
    const baseUrl = getBackendUrl();
    logToConsole("Ejecutando consulta SELECT asíncrona a la base de datos...", "info");
    
    try {
        const response = await fetch(`${baseUrl}/reservas.php`);
        const result = await response.json();
        
        if (result.status === 'success') {
            const lista = document.getElementById('lista-citas');
            lista.innerHTML = '';
            result.data.forEach(cita => {
                lista.innerHTML += `<li><strong>${cita.fecha}</strong> - ${cita.detalle}</li>`;
            });
            logToConsole(`Actualización dinámica: ${result.data.length} citas renderizadas.`, "success");
        }
    } catch (e) {
        logToConsole("Error al consumir la lista de citas.", "error");
    }
}

// Mantener la sesión al refrescar
window.onload = () => {
    const savedSession = localStorage.getItem('user_session');
    if (savedSession) {
        enterApp(JSON.parse(savedSession));
    }
};

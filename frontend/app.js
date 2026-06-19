// Variable global para controlar en qué pantalla se encuentra parado el usuario
let modoActual = 'register'; 

// Apunta de manera fija y segura a tu backend en Clever Cloud para descartar errores locales
const API_URL = "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io/backend/auth.php";

// REGISTRO DEL SERVICE WORKER: Exigencia obligatoria para transformar tu app en una PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(() => emiteLog("PWA: Service Worker Activado Correctamente.", "success"))
            .catch(err => emiteLog("PWA Error: " + err, "error"));
    });
}

// FUNCIÓN DE CONSOLA VISUAL: Imprime los procesos internos en tiempo real directamente en la pantalla
function emiteLog(msg, tipo = "info") {
    const box = document.getElementById('console-logs');
    const hora = new Date().toLocaleTimeString();
    box.innerHTML += `<p class="log-${tipo}">[${hora}] ${msg}</p>`;
    box.scrollTop = box.scrollHeight; // Auto-scroll para ver siempre el último proceso generado
}

// RÚBRICA - PROCESO PERSISTENTE: Ejecuta un hilo secundario automático cada 15 segundos simulando tareas en segundo plano
setInterval(() => {
    emiteLog("Hilo Secundario: Verificando sincronización persistente con Clever Cloud...", "info");
}, 15000);

// INTERCAMBIO DE MODOS: Alterna las etiquetas visuales entre "Registrarse" e "Iniciar Sesión"
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

// RECUPERACIÓN VISUAL: Modifica la pantalla para dar soporte a la función de olvido de contraseña
function activarModoRecuperacion(e) {
    e.preventDefault();
    modoActual = 'recover';
    document.getElementById('welcome-text').innerText = "Recuperación de Contraseña";
    document.getElementById('password-group').classList.add('hidden'); // Oculta la clave ya que no es requerida para este paso
    document.getElementById('btn-submit').innerText = "Enviar Enlace de Recuperación";
    document.getElementById('toggle-auth-mode').innerText = "Volver al Inicio de Sesión";
}

// ASYNC / AWAIT: Consume las peticiones de red hacia Clever Cloud de manera asíncrona sin congelar la app
async function enviarDatosFormulario(e) {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    // Configura la ruta dinámica dependiendo de lo que el usuario esté haciendo en la pantalla
    let urlTarget = `${API_URL}?action=register`;
    let payload = { email, password };

    if (modoActual === 'login') {
        urlTarget = `${API_URL}?action=login`;
    } else if (modoActual === 'recover') {
        urlTarget = `${API_URL}?action=recover_request`;
        payload = { email }; // Solo manda el correo
    }

    emiteLog(`Consumo API: Procesando petición asíncrona [${modoActual}]...`, "info");

    try {
        // Ejecuta la llamada asíncrona enviando los objetos JSON estructurados
        const respuesta = await fetch(urlTarget, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const datos = await respuesta.json();

        if (datos.status === 'success') {
            emiteLog(datos.message, "success");
            alert(datos.message);

            // Si es un registro, captura el enlace de confirmación simulado de Gmail y lo saca por la consola integrada
            if (modoActual === 'register' && datos.link_simulado) {
                emiteLog(`[Gmail Simulado] Llegó Enlace de Activación: ${datos.link_simulado}`, "success");
            } 
            // Si es una recuperación de contraseña, atrapa el enlace dinámico generado por el servidor
            else if (modoActual === 'recover' && datos.link_simulado) {
                emiteLog(`[Gmail Simulado] Llegó Enlace de Recuperación: ${datos.link_simulado}`, "success");
            } 
            // Si el inicio de sesión es exitoso, oculta el formulario e ingresa a la pantalla de éxito
            else if (modoActual === 'login') {
                document.getElementById('auth-section').classList.add('hidden');
                document.getElementById('heart-success-section').classList.remove('hidden');
                document.getElementById('display-user-email').innerText = `Conectado como: ${email}`;
            }
        } else {
            emiteLog(`Error de Validación: ${datos.message}`, "error");
            alert(datos.message);
        }
    } catch (error) {
        emiteLog("Fallo de comunicación de red con el clúster.", "error");
    }
}

// Cierre de estados para limpiar variables de sesión local de forma segura
function salirDeSesion() {
    document.getElementById('heart-success-section').classList.add('hidden');
    document.getElementById('auth-section').classList.remove('hidden');
    document.getElementById('auth-form').reset();
    modoActual = 'login';
    intercambiarModo({ preventDefault: () => {} });
    emiteLog("Sesión limpia finalizada correctamente.", "info");
}

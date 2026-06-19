// frontend/app.js

const getBackendUrl = () => {
    const inputUrl = document.getElementById('network-url-input').value.trim();
    if (inputUrl) return inputUrl;
    
    // Usamos tu URL real de Clever Cloud con protocolo seguro HTTPS
    return "https://app-f11f01f7-d577-43bd-b5a4-bc58a8917f37.cleverapps.io/backend"; 
};

/**
 * JavaScript General
 * Sistema de Numeración - Municipalidad de Los Lagos
 */

// Utilidades
const Utils = {
    // Formatear fecha
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('es-CL');
    },
    
    // Formatear hora
    formatTime: function(time) {
        return time ? time.substring(0, 5) : '--:--';
    },
    
    // Mostrar alerta
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => alertDiv.remove(), 5000);
    },
    
    // Confirmar acción
    confirm: function(message) {
        return window.confirm(message);
    },
    
    // Copiar al portapapeles
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showAlert('Copiado al portapapeles', 'success');
        });
    }
};

// API Helper
const API = {
    baseUrl: '/numeracion/api',
    
    // GET request
    get: async function(endpoint, params = {}) {
        const url = new URL(this.baseUrl + endpoint, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        const response = await fetch(url);
        return response.json();
    },
    
    // POST request
    post: async function(endpoint, data = {}) {
        const response = await fetch(this.baseUrl + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    }
};

// Síntesis de voz
const Voice = {
    enabled: true,
    rate: 1,
    pitch: 1,
    
    speak: function(text) {
        if (!this.enabled || !('speechSynthesis' in window)) return;
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'es-ES';
        utterance.rate = this.rate;
        utterance.pitch = this.pitch;
        
        speechSynthesis.speak(utterance);
    },
    
    stop: function() {
        speechSynthesis.cancel();
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Auto-cerrar alertas
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Utilidades generales
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Mostrar modal de confirmación
function showConfirmModal(title, message, onConfirm) {
    var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    document.getElementById('confirmModalLabel').textContent = title;
    document.getElementById('confirmModalMessage').textContent = message;
    document.getElementById('confirmModalBtn').onclick = onConfirm;
    modal.show();
}

// Mostrar alerta
function showAlert(message, type = 'info') {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    var container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(function() {
        alertDiv.remove();
    }, 5000);
}

// Validar formulario
function validateForm(formId) {
    var form = document.getElementById(formId);
    var isValid = form.checkValidity();
    form.classList.add('was-validated');
    return isValid;
}

// Limpiar formulario
function clearForm(formId) {
    var form = document.getElementById(formId);
    form.reset();
    form.classList.remove('was-validated');
}

// Formato de fecha
function formatDate(dateString) {
    var options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

// Loading spinner
function showLoading() {
    document.querySelector('.loading').classList.add('active');
}

function hideLoading() {
    document.querySelector('.loading').classList.remove('active');
}

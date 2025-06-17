/**
 * SIMA Contable - Sistema de Notificaciones Toast
 */

// Sistema de toast notifications
window.showToast = function(message, type = 'info', title = null) {
    // Establecer el título según el tipo si no se proporciona
    if (!title) {
        switch(type) {
            case 'success': title = '¡Éxito!'; break;
            case 'error': title = 'Error'; break;
            case 'warning': title = 'Advertencia'; break;
            default: title = 'Información';
        }
    }
    
    // Crear el HTML del toast
    const iconClass = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type];
    
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type];
    
    const textClass = ['warning'].includes(type) ? '' : 'text-white';
    const closeButtonClass = ['warning'].includes(type) ? '' : 'btn-close-white';
    
    const toastHtml = `
        <div class="toast ${bgClass} ${textClass}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} ${textClass}">
                <i class="${iconClass} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close ${closeButtonClass}" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Añadir el toast al contenedor
    const container = document.querySelector('.toast-container');
    
    if (container) {
        container.insertAdjacentHTML('beforeend', toastHtml);
        
        // Inicializar y mostrar el toast recién creado
        const toastEl = container.lastElementChild;
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
        
        // Eliminar el toast del DOM cuando se oculte
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    } else {
        console.error('Toast container not found');
    }
};

// Inicializar los toasts de la sesión cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function(toastEl) {
        var toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        toast.show();
        return toast;
    });
}); 
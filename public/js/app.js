/**
 * SIMA Contable - JavaScript Global
 * Funciones y utilidades para toda la aplicación
 */

// Inicializar componentes de Bootstrap cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
    
    // Inicializar dropdowns explícitamente
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
    dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
    });
    
    // Solución alternativa para dropdowns usando jQuery
    if (window.jQuery) {
        // Manejar clic en elementos dropdown-toggle manualmente
        $(document).on('click', '.dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Cerrar otros dropdowns abiertos
            $('.dropdown-menu').not($(this).siblings('.dropdown-menu')).removeClass('show');
            
            // Alternar el estado del dropdown actual
            $(this).siblings('.dropdown-menu').toggleClass('show');
            $(this).parent('.dropdown, .nav-item').toggleClass('show');
            
            // Cerrar dropdown al hacer clic fuera
            $(document).one('click', function() {
                $('.dropdown-menu').removeClass('show');
                $('.dropdown, .nav-item').removeClass('show');
            });
            
            return false;
        });
    }
    
    // Inicializar alertas dismissibles
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        // Auto-ocultar después de 8 segundos si tiene la clase auto-dismiss
        if (alert.classList.contains('auto-dismiss')) {
            setTimeout(function() {
                var closeButton = alert.querySelector('.btn-close');
                if (closeButton) closeButton.click();
            }, 8000);
        }
    });
    
    // DataTables - Personalización de la apariencia (si jQuery está disponible)
    if (window.jQuery) {
        function applyCustomStyles() {
            $('.dataTables_filter label').each(function() {
                $(this).html('<i class="fas fa-search"></i> Buscar: <input type="search" class="form-control form-control-sm" placeholder="Filtrar registros..." aria-controls="tabla">');
            });
            
            $('.dataTables_length label').each(function() {
                var html = $(this).html();
                $(this).html(html.replace(/entries/g, 'filas').replace(/Show/g, 'Ver'));
            });
        }
        
        // Aplicar estilos y verificar periódicamente nuevas tablas
        applyCustomStyles();
        setInterval(applyCustomStyles, 500);
    }
});

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
} 
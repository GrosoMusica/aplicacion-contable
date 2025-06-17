<!-- Contenedor para notificaciones toast -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <!-- Los toasts se crearán dinámicamente por JavaScript -->
    @if (session('success'))
        <div class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">¡Éxito!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                {{ session('error') }}
            </div>
        </div>
    @endif
    
    @if (session('warning'))
        <div class="toast bg-warning" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong class="me-auto">Advertencia</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                {{ session('warning') }}
            </div>
        </div>
    @endif

    @if (session('info'))
        <div class="toast bg-info text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-info text-white">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Información</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                {{ session('info') }}
            </div>
        </div>
    @endif
</div> 
<div class="card" id="seccion-acreedores">
    <div class="card-header bg-success text-white">
        Acreedores
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success mb-3">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger mb-3">
                {{ session('error') }}
            </div>
        @endif
        
        @php
            // Buscar al administrador (ID 1)
            $admin = $acreedores->firstWhere('id', 1);
            $adminPorcentaje = 100;
            
            // Si el admin ya está asociado a esta financiación, obtener su porcentaje actual
            if ($admin && isset($comprador->financiacion)) {
                foreach($admin->financiaciones as $fin) {
                    if($fin->id == $comprador->financiacion->id) {
                        $adminPorcentaje = $fin->pivot->porcentaje;
                        break;
                    }
                }
            }
            
            // Obtener solo las cuotas pagadas o con pago parcial de esta financiación
            $cuotasPagadas = [];
            $totalPagado = 0;
            
            if (isset($comprador->financiacion)) {
                $cuotasPagadas = \App\Models\Cuota::where('financiacion_id', $comprador->financiacion->id)
                    ->whereIn('estado', ['pagada', 'parcial'])
                    ->get();
                
                // Obtener todos los pagos de estas cuotas
                $cuotasIds = $cuotasPagadas->pluck('id')->toArray();
                $pagos = \App\Models\Pago::whereIn('cuota_id', $cuotasIds)->get();
                
                // Calcular el monto total efectivamente pagado (en USD)
                $totalPagado = $pagos->sum('monto_usd');
            }
            
            // Inicializar array para acumular por acreedor
            $acumulados = [];
            foreach($acreedores as $acreedor) {
                $acumulados[$acreedor->id] = [
                    'debe' => 0,     // Lo que debería recibir según porcentaje de lo pagado
                    'haber' => 0,    // Lo que realmente ha recibido
                    'saldo' => 0     // La diferencia (haber - debe)
                ];
                
                // Si tenemos financiación, calcular el "debe" para cada acreedor
                if (isset($comprador->financiacion)) {
                    $porcentaje = 0;
                    foreach($acreedor->financiaciones as $fin) {
                        if($fin->id == $comprador->financiacion->id) {
                            $porcentaje = $fin->pivot->porcentaje;
                            break;
                        }
                    }
                    // El "debe" es el porcentaje del total efectivamente pagado hasta ahora
                    $acumulados[$acreedor->id]['debe'] = ($totalPagado * $porcentaje) / 100;
                }
            }
        @endphp
        
        <!-- Tabla de acreedores -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Fecha Creación</th>
                        <th>Acreedor</th>
                        <th>Porcentaje</th>
                        <th>Debe Recibir (de lo pagado)</th>
                        <th>Ha Recibido</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Admin siempre primero -->
                    @if(isset($acumulados[1]))
                    <tr class="table-light">
                        <td>{{ isset($comprador->financiacion) ? $comprador->financiacion->created_at->format('d/m/Y') : '-' }}</td>
                        <td><strong>{{ strtoupper('ADMIN') }}</strong></td>
                        <td><strong>{{ $adminPorcentaje }}%</strong></td>
                        <td class="text-end">U$D {{ number_format($acumulados[1]['debe'], 2) }}</td>
                        <td class="text-end">U$D {{ number_format($acumulados[1]['haber'], 2) }}</td>
                        <td class="text-end">
                            <span class="text-success">
                                U$D {{ number_format($acumulados[1]['saldo'], 2) }}
                                <i class="fas fa-plus-circle"></i>
                            </span>
                        </td>
                    </tr>
                    @endif
                    
                    <!-- Otros acreedores -->
                    @foreach($acreedores->where('id', '!=', 1) as $acreedor)
                        @php
                            $porcentaje = 0;
                            if (isset($comprador->financiacion)) {
                                foreach($acreedor->financiaciones as $fin) {
                                    if($fin->id == $comprador->financiacion->id) {
                                        $porcentaje = $fin->pivot->porcentaje;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $acreedor->created_at->format('d/m/Y') }}</td>
                            <td>{{ strtoupper($acreedor->nombre) }}</td>
                            <td>{{ $porcentaje }}%</td>
                            <td class="text-end">U$D {{ number_format($acumulados[$acreedor->id]['debe'], 2) }}</td>
                            <td class="text-end">U$D {{ number_format($acumulados[$acreedor->id]['haber'], 2) }}</td>
                            <td class="text-end">
                                <span class="text-success">
                                    U$D {{ number_format($acumulados[$acreedor->id]['saldo'], 2) }}
                                    <i class="fas fa-plus-circle"></i>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total Pagado:</strong></td>
                        <td class="text-end" colspan="3"><strong>U$D {{ number_format($totalPagado, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        @if(isset($comprador->financiacion))
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addAcreedorModal">Agregar Acreedor</button>
        @endif
    </div>
</div>

<!-- Modal para agregar acreedor -->
@if(isset($comprador->financiacion))
<div class="modal fade" id="addAcreedorModal" tabindex="-1" aria-labelledby="addAcreedorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('acreedores.store') }}" method="POST">
                @csrf
                <input type="hidden" name="financiacion_id" value="{{ $comprador->financiacion->id }}">
                <input type="hidden" name="redirect_to" value="acreedor-section">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAcreedorModalLabel">Agregar Acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="acreedor_id" class="form-label">Seleccionar Acreedor</label>
                        <select class="form-select" name="nombre" id="acreedor_select" required>
                            <option value="">Seleccione un acreedor</option>
                            @php
                                // Obtener los IDs de acreedores que ya están relacionados con esta financiación
                                $acreedoresRelacionadosIds = [];
                                
                                if (isset($comprador->financiacion)) {
                                    $acreedoresRelacionadosIds = \Illuminate\Support\Facades\DB::table('financiacion_acreedor')
                                        ->where('financiacion_id', $comprador->financiacion->id)
                                        ->pluck('acreedor_id')
                                        ->toArray();
                                }
                                
                                // Obtener acreedores que NO están relacionados todavía (excluir admin y relacionados)
                                $acreedoresDisponibles = \App\Models\Acreedor::where('id', '!=', 1)
                                    ->whereNotIn('id', $acreedoresRelacionadosIds)
                                    ->get();
                            @endphp
                            
                            @foreach($acreedoresDisponibles as $acreedor)
                                <option value="{{ $acreedor->nombre }}">{{ $acreedor->nombre }}</option>
                            @endforeach
                        </select>
                        
                        @if(count($acreedoresDisponibles) == 0)
                            <div class="form-text text-warning mt-1">
                                <i class="fas fa-exclamation-circle me-1"></i> No hay acreedores disponibles para agregar
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="porcentaje" class="form-label">Porcentaje</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="porcentaje" min="1" max="{{ $adminPorcentaje }}" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text text-success mt-1">
                            <i class="fas fa-info-circle me-1"></i> <strong>{{ $adminPorcentaje }}%</strong> disponible para asignar
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" {{ count($acreedoresDisponibles) == 0 ? 'disabled' : '' }}>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Script para hacer scroll a la sección de acreedores -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar si hay un hash en la URL
        if (window.location.hash === '#seccion-acreedores') {
            // Hacer scroll a la sección de acreedores
            document.getElementById('seccion-acreedores').scrollIntoView();
        }
    });
</script>

<!-- Mensajes de éxito y error -->
@if(session('success') && session('redirect_to') == 'acreedor-section')
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error') && session('redirect_to') == 'acreedor-section')
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Script para hacer scroll hasta la sección de acreedores -->
@if(session('redirect_to') == 'acreedor-section')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const acreedorSection = document.getElementById('acreedor-section');
        if (acreedorSection) {
            // Hacer scroll con un pequeño retraso para asegurar que todo está cargado
            setTimeout(function() {
                acreedorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            
            // Auto cerrar las alertas después de 5 segundos
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const closeBtn = new bootstrap.Alert(alert);
                    closeBtn.close();
                });
            }, 5000);
        }
    });
</script>
@endif 
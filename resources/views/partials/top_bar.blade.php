<nav class="navbar navbar-expand-lg navbar-light bg-white py-2 fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center py-0" href="{{ url('/') }}">
            <img src="{{ asset('logo-sima.png') }}" alt="SIMA" height="40" class="me-2">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{ ($active ?? '') == 'compradores' ? 'active' : '' }}" href="{{ route('compradores.index') }}">COMPRADORES</a>
                </li>
                
                <!-- PAGOS movido a la segunda posición -->
                <li class="nav-item">
                    <a class="nav-link {{ ($active ?? '') == 'pagos' ? 'active' : '' }}" href="#" data-bs-toggle="modal" data-bs-target="#buscarCompradorModal">
                        <i class="fas fa-search me-1"></i> PAGOS
                    </a>
                </li>

                {{-- Submenú Informes --}}
                @php
                    // Determinar si alguna ruta de informes está activa para resaltar el menú principal
                    $informesActive = request()->routeIs('informes.index') || request()->routeIs('morosos.index') || request()->routeIs('proximos.index');
                @endphp
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $informesActive ? 'active-nav' : '' }}" href="#" id="navbarDropdownInformes" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        INFORMES
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownInformes">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('informes.index') ? 'active' : '' }}" href="{{ route('informes.index') }}">
                                <i class="fas fa-calendar-day fa-fw me-2"></i> Mes Actual
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('morosos.index') ? 'active' : '' }}" href="{{ route('morosos.index') }}">
                                <i class="fas fa-exclamation-triangle fa-fw me-2 text-danger"></i> Morosos
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('proximos.index') ? 'active' : '' }}" href="{{ route('proximos.index') }}">
                                <i class="fas fa-flag-checkered fa-fw me-2 text-success"></i> Próximos a Finalizar
                            </a>
                        </li>
                    </ul>
                </li>

                @if(Auth::check() && Auth::user()->role == 'admin')
                <li class="nav-item">
                    <a class="nav-link {{ ($active ?? '') == 'acreedores' ? 'active' : '' }}" href="{{ route('gestion.acreedores.index') }}">ACREEDORES</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($active ?? '') == 'lotes' ? 'active' : '' }}" href="{{ route('lotes.index') }}">LOTES</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($active ?? '') == 'liquidaciones' ? 'active' : '' }}" href="{{ route('gestion.acreedores.pagos') }}">LIQUIDACIONES</a>
                </li>
                @endif
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3 text-muted">
                    <i class="far fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM') }}
                </li>
                <!-- Ícono de llave -->
                <li class="nav-item me-3">
                    <a href="{{ route('errors.index') }}" class="nav-link" title="Errores">
                        <i class="fas fa-key"></i>
                    </a>
                </li>
                @auth
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" style="border-radius: 20px; border: solid 2px #007bff;">
                            {{ Auth::user()->name }}
                            <i class="fas fa-sign-out-alt ms-2"></i>
                        </button>
                    </form>
                </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<!-- Añadir margen superior para compensar la barra fija -->
<div style="margin-top: 75px;"></div> 
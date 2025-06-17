@extends('layouts.app')

@section('title', 'SIMA - Contable | Aplicación Contable - Inicio')

@section('body-class', 'main-page')

@section('content')
    <div class="center-content">
        <div class="container-fluid py-3">
            <div class="row">
                <!-- Caja 1: Logo sin texto -->
                <div class="col-md-3">
                    <div class="logo-box">
                        <img src="{{ asset('logo-sima.png') }}" alt="SIMA Logo">
                    </div>
                </div>
                
                <!-- Caja 2: Compradores -->
                <div class="col-md-3">
                    <a href="{{ route('compradores.index') }}" class="text-decoration-none">
                        <div class="menu-button bg-primary text-white">
                            <i class="fas fa-users menu-icon"></i>
                            <span class="menu-title">COMPRADORES</span>
                        </div>
                    </a>
                </div>
                
                <!-- Caja 3: Pagos (movido) -->
                <div class="col-md-3">
                    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#buscarCompradorModal">
                        <div class="menu-button bg-dark text-white">
                            <i class="fas fa-search menu-icon"></i>
                            <span class="menu-title">PAGOS</span>
                        </div>
                    </a>
                </div>
                
                <!-- Caja 4: Informes -->
                <div class="col-md-3">
                    <a href="{{ route('informes.index') }}" class="text-decoration-none">
                        <div class="menu-button bg-secondary text-white">
                            <i class="fas fa-chart-bar menu-icon"></i>
                            <span class="menu-title">INFORMES</span>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="row mt-3">
                @auth
                    <!-- Caja 5: Crear Operación (todos los usuarios autenticados) -->
                    <div class="col-md-3">
                        <a href="{{ route('entries.create') }}" class="text-decoration-none">
                            <div class="menu-button bg-danger text-white">
                                <i class="fas fa-plus-circle menu-icon"></i>
                                <span class="menu-title">+ CREAR OPERACIÓN</span>
                            </div>
                        </a>
                    </div>
                @endauth
                @if(Auth::check() && Auth::user()->role == 'admin')
                    <!-- Caja 6: Acreedores (solo admin) -->
                    <div class="col-md-3">
                        <a href="{{ route('gestion.acreedores.index') }}" class="text-decoration-none">
                            <div class="menu-button bg-warning text-dark">
                                <i class="fas fa-handshake menu-icon"></i>
                                <span class="menu-title">ACREEDORES</span>
                            </div>
                        </a>
                    </div>
                    <!-- Caja 7: Lotes (solo admin) -->
                    <div class="col-md-3">
                        <a href="{{ route('lotes.index') }}" class="text-decoration-none">
                            <div class="menu-button bg-success text-white">
                                <i class="fas fa-map-marker-alt menu-icon"></i>
                                <span class="menu-title">LOTES</span>
                            </div>
                        </a>
                    </div>
                    <!-- Caja 8: Liquidaciones (solo admin) -->
                    <div class="col-md-3">
                        <a href="{{ route('gestion.acreedores.pagos') }}" class="text-decoration-none">
                            <div class="menu-button bg-purple text-white">
                                <i class="fas fa-file-invoice-dollar menu-icon"></i>
                                <span class="menu-title">LIQUIDACIONES</span>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
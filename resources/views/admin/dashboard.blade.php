@extends('layouts.admin')

@section('titulo', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-lg font-medium text-gray-900">
            Bienvenido, {{ app('negocio_actual')->nombre_comercial ?? 'RentSalon Pro' }}
        </h1>
        <p class="text-sm text-gray-500">Resumen general de tu negocio.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Salones activos</p>
            <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $salonesActivos }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Paquetes activos</p>
            <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $paquetesActivos }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Reservaciones pendientes</p>
            <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $reservacionesPendientes }}</p>
        </div>
    </div>

    @if (Route::has('admin.reservaciones.index'))
        <div class="mt-6">
            <a href="{{ route('admin.reservaciones.index') }}" class="text-sm text-emerald-700 hover:underline">
                Ver reservaciones &rarr;
            </a>
        </div>
    @endif
@endsection

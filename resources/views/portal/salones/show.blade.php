@extends('layouts.portal')

@section('titulo', $salon->nombre)

@push('vite-extra')
    @vite(['resources/js/portal-calendario.js'])
@endpush

@section('content')
    <div class="max-w-5xl mx-auto px-6 py-10">
        <a href="{{ route('portal.salones') }}" class="text-sm text-gray-500 hover:underline">&larr; Salones</a>

        <h1 class="text-2xl font-semibold text-gray-900 mt-2">{{ $salon->nombre }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            Capacidad: {{ $salon->capacidad_min ?? 0 }}–{{ $salon->capacidad_max }} personas
            @if ($salon->ubicacion) &middot; {{ $salon->ubicacion }} @endif
        </p>

        @if ($salon->imagenes->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                @foreach ($salon->imagenes as $imagen)
                    <img src="{{ $imagen->url }}" alt="{{ $salon->nombre }}" class="w-full aspect-square object-cover rounded-md border border-gray-200">
                @endforeach
            </div>
        @endif

        @if ($salon->descripcion)
            <p class="text-gray-600 mt-6 max-w-3xl">{{ $salon->descripcion }}</p>
        @endif

        @if ($paquetes->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-medium text-gray-900 mb-3">Paquetes disponibles en este salón</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($paquetes as $paquete)
                        <a href="{{ route('portal.paquete', $paquete) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-marca">
                            <p class="font-medium text-gray-900">{{ $paquete->nombre }}</p>
                            <p class="text-sm text-gray-500">Desde Bs. {{ number_format($paquete->precio_base, 2) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-medium text-gray-900">Disponibilidad</h2>
                <a href="{{ route('portal.solicitud.create', ['salon' => $salon->id]) }}"
                   class="btn-marca text-white text-sm px-4 py-2 rounded-md hover:opacity-90">
                    Solicitar esta fecha
                </a>
            </div>
            <div id="calendario-publico" class="bg-white border border-gray-200 rounded-lg p-3"
                 data-disponibilidad-url="{{ route('portal.disponibilidad', $salon) }}"
                 data-solicitar-url="{{ route('portal.solicitud.create') }}"
                 data-salon-id="{{ $salon->id }}"></div>
            <p class="text-xs text-gray-400 mt-2">Los días marcados "Ocupado" ya tienen una reserva o solicitud pendiente — el resto está disponible.</p>
        </div>
    </div>
@endsection

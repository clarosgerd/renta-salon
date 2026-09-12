@extends('layouts.portal')

@section('titulo', $paquete->nombre)

@php
    $etiquetasTipo = ['boda' => 'Boda', 'xv_anos' => 'XV Años', 'corporativo' => 'Corporativo', 'otro' => 'Otro'];
@endphp

@section('content')
    <div class="max-w-3xl mx-auto px-6 py-10">
        <a href="{{ route('portal.paquetes') }}" class="text-sm text-gray-500 hover:underline">&larr; Paquetes</a>

        <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 mt-3">
            {{ $etiquetasTipo[$paquete->tipo_evento] ?? $paquete->tipo_evento }}
        </span>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">{{ $paquete->nombre }}</h1>
        <p class="text-marca text-xl font-semibold mt-2">Bs. {{ number_format($paquete->precio_base, 2) }}</p>
        <p class="text-sm text-gray-500">{{ $paquete->duracion_horas }} horas de duración</p>

        @if ($paquete->descripcion)
            <p class="text-gray-600 mt-4">{{ $paquete->descripcion }}</p>
        @endif

        @if (!empty($paquete->servicios_incluidos))
            <div class="mt-6">
                <h2 class="text-sm font-medium text-gray-700 mb-2">Servicios incluidos</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($paquete->servicios_incluidos as $servicio)
                        <span class="text-sm bg-white border border-gray-200 px-3 py-1 rounded-full text-gray-600">{{ $servicio }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($salonesDelPaquete->isNotEmpty())
            <div class="mt-6">
                <h2 class="text-sm font-medium text-gray-700 mb-2">Disponible en</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($salonesDelPaquete as $salon)
                        <a href="{{ route('portal.salon', $salon) }}" class="text-sm text-marca hover:underline">{{ $salon->nombre }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        <a href="{{ route('portal.solicitud.create', ['paquete' => $paquete->id]) }}"
           class="btn-marca inline-block text-white text-sm px-6 py-3 rounded-md hover:opacity-90 mt-8">
            Solicitar esta fecha
        </a>
    </div>
@endsection

@extends('layouts.portal')

@section('titulo', 'Paquetes')

@php
    $etiquetasTipo = ['boda' => 'Boda', 'xv_anos' => 'XV Años', 'corporativo' => 'Corporativo', 'otro' => 'Otro'];
@endphp

@section('content')
    <div class="max-w-5xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Nuestros paquetes</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($paquetes as $paquete)
                <a href="{{ route('portal.paquete', $paquete) }}" class="bg-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow">
                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                        {{ $etiquetasTipo[$paquete->tipo_evento] ?? $paquete->tipo_evento }}
                    </span>
                    <h2 class="font-medium text-gray-900 mt-3">{{ $paquete->nombre }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $paquete->duracion_horas }} horas</p>
                    <p class="text-marca font-semibold mt-2">Desde Bs. {{ number_format($paquete->precio_base, 2) }}</p>
                </a>
            @empty
                <div class="col-span-full bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                    Todavía no hay paquetes publicados.
                </div>
            @endforelse
        </div>
    </div>
@endsection

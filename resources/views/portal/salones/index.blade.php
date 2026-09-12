@extends('layouts.portal')

@section('titulo', 'Salones')

@section('content')
    <div class="max-w-5xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Nuestros salones</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($salones as $salon)
                <a href="{{ route('portal.salon', $salon) }}" class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center">
                        @if ($salon->imagenes->first())
                            <img src="{{ $salon->imagenes->first()->url }}" alt="{{ $salon->nombre }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-gray-300 text-sm">Sin fotos</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <h2 class="font-medium text-gray-900">{{ $salon->nombre }}</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Capacidad: {{ $salon->capacidad_min ?? 0 }}–{{ $salon->capacidad_max }} personas
                        </p>
                        <span class="inline-block text-sm text-marca font-medium mt-3">Ver disponibilidad &rarr;</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                    Todavía no hay salones publicados.
                </div>
            @endforelse
        </div>
    </div>
@endsection

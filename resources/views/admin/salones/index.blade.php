@extends('layouts.admin')

@section('titulo', 'Salones')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Salones</h1>
            <p class="text-sm text-gray-500">El catálogo de salones que aparece en tu portal público.</p>
        </div>
        <a href="{{ route('admin.salones.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nuevo salón
        </a>
    </div>

    <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 flex gap-3">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre..."
               class="flex-1 rounded-md border-gray-300 text-sm">
        <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-md">Buscar</button>
        @if (request('buscar'))
            <a href="{{ route('admin.salones.index') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
        @endif
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($salones as $salon)
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="aspect-video bg-gray-100 flex items-center justify-center">
                    @if ($salon->imagenes->first())
                        <img src="{{ $salon->imagenes->first()->url }}" alt="{{ $salon->nombre }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-gray-300 text-sm">Sin fotos</span>
                    @endif
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-medium text-gray-900">{{ $salon->nombre }}</h2>
                        <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $salon->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $salon->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Capacidad: {{ $salon->capacidad_min ?? 0 }}–{{ $salon->capacidad_max }} personas
                    </p>
                    @if ($salon->ubicacion)
                        <p class="text-sm text-gray-500">{{ $salon->ubicacion }}</p>
                    @endif

                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('admin.salones.edit', $salon) }}" class="text-sm text-emerald-700 hover:underline">Editar</a>
                        <form method="POST" action="{{ route('admin.salones.toggle-activo', $salon) }}">
                            @csrf
                            @method('PATCH')
                            <button class="text-sm text-gray-500 hover:underline">
                                {{ $salon->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-gray-200 rounded-lg p-10 text-center text-gray-400">
                No hay salones que coincidan con la búsqueda.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $salones->links() }}
    </div>
@endsection

@extends('layouts.admin')

@section('titulo', 'Paquetes')

@php
    $tipoEventoLabels = ['boda' => 'Boda', 'xv_anos' => 'XV años', 'corporativo' => 'Corporativo', 'otro' => 'Otro'];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Paquetes</h1>
            <p class="text-sm text-gray-500">Cotizaciones estandarizadas por tipo de evento.</p>
        </div>
        <a href="{{ route('admin.paquetes.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nuevo paquete
        </a>
    </div>

    <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 flex gap-3">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre..."
               class="flex-1 rounded-md border-gray-300 text-sm">
        <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-md">Buscar</button>
        @if (request('buscar'))
            <a href="{{ route('admin.paquetes.index') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
        @endif
    </form>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Tipo de evento</th>
                    <th class="text-right px-4 py-3">Precio base</th>
                    <th class="text-left px-4 py-3">Salones</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($paquetes as $paquete)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $paquete->nombre }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $tipoEventoLabels[$paquete->tipo_evento] ?? $paquete->tipo_evento }}</td>
                        <td class="px-4 py-3 text-right">Bs {{ number_format($paquete->precio_base, 2) }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if ($paquete->aplicaATodosLosSalones())
                                <span class="text-gray-400">Todos</span>
                            @else
                                {{ $paquete->salones->pluck('nombre')->join(', ') }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $paquete->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $paquete->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.paquetes.edit', $paquete) }}" class="text-emerald-700 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('admin.paquetes.toggle-activo', $paquete) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-gray-500 hover:underline">
                                        {{ $paquete->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                            No hay paquetes que coincidan con la búsqueda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $paquetes->links() }}
    </div>
@endsection

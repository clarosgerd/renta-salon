@extends('layouts.admin')

@section('titulo', 'Calendario')

@section('content')
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Calendario</h1>
            <p class="text-sm text-gray-500">Vista maestra de reservaciones por salón.</p>
        </div>
        <a href="{{ route('admin.reservaciones.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nueva reservación
        </a>
    </div>

    {{-- Filtro de salones — todos marcados por default --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-5 flex flex-wrap gap-4">
        @forelse ($salones as $salon)
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" class="filtro-salon" value="{{ $salon->id }}" checked>
                {{ $salon->nombre }}
            </label>
        @empty
            <p class="text-sm text-gray-400">No hay salones activos para mostrar.</p>
        @endforelse
    </div>

    {{-- Leyenda de colores --}}
    <div class="flex flex-wrap gap-4 mb-3 text-xs text-gray-500">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#f59e0b"></span> Pendiente</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#10b981"></span> Confirmada</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#9ca3af"></span> Cancelada</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#3b82f6"></span> Finalizada</span>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div id="calendario"
             data-eventos-url="{{ route('admin.calendario.eventos') }}"
             data-crear-url="{{ route('admin.reservaciones.create') }}"></div>
    </div>

    {{-- Panel lateral — clic en un evento lo llena y lo muestra, sin navegar --}}
    <div id="panelLateral" hidden
         class="fixed top-0 right-0 h-full w-80 bg-white border-l border-gray-200 shadow-lg p-5 overflow-y-auto z-50">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-medium text-gray-900">Reservación</h2>
            <button id="panelCerrar" type="button" class="text-gray-400 hover:text-gray-700">&times;</button>
        </div>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-gray-500">Folio</dt>
                <dd id="panelFolio" class="font-medium text-gray-900"></dd>
            </div>
            <div>
                <dt class="text-gray-500">Cliente</dt>
                <dd id="panelCliente" class="font-medium text-gray-900"></dd>
            </div>
            <div>
                <dt class="text-gray-500">Salón</dt>
                <dd id="panelSalon"></dd>
            </div>
            <div>
                <dt class="text-gray-500">Paquete</dt>
                <dd id="panelPaquete"></dd>
            </div>
            <div>
                <dt class="text-gray-500">Saldo pendiente</dt>
                <dd id="panelSaldo" class="font-medium"></dd>
            </div>
            <div>
                <dt class="text-gray-500">Estado</dt>
                <dd id="panelEstado"></dd>
            </div>
        </dl>
        <a id="panelVerFicha" href="#"
           class="mt-6 block text-center bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            Ver ficha completa
        </a>
    </div>

    @vite(['resources/js/calendario.js'])
@endsection

@extends('layouts.admin')

@section('titulo', 'Reservaciones')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Reservaciones</h1>
            <p class="text-sm text-gray-500">Todas las rentas registradas en tu negocio.</p>
        </div>
        <a href="{{ route('admin.reservaciones.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nueva reservación
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-3">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Folio o cliente..."
               class="col-span-2 rounded-md border-gray-300 text-sm">

        <select name="estado" class="rounded-md border-gray-300 text-sm">
            <option value="">Todos los estados</option>
            @foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'cancelada' => 'Cancelada', 'finalizada' => 'Finalizada'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>

        <select name="salon_id" class="rounded-md border-gray-300 text-sm">
            <option value="">Todos los salones</option>
            @foreach ($salones ?? [] as $salon)
                <option value="{{ $salon->id }}" @selected(request('salon_id') == $salon->id)>{{ $salon->nombre }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <input type="date" name="desde" value="{{ request('desde') }}" class="rounded-md border-gray-300 text-sm w-full">
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="rounded-md border-gray-300 text-sm w-full">
        </div>

        <div class="col-span-2 md:col-span-5 flex justify-end gap-2">
            <a href="{{ route('admin.reservaciones.index') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
            <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-md">Filtrar</button>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Folio</th>
                    <th class="text-left px-4 py-3">Cliente</th>
                    <th class="text-left px-4 py-3">Salón</th>
                    <th class="text-left px-4 py-3">Fecha evento</th>
                    <th class="text-right px-4 py-3">Total</th>
                    <th class="text-right px-4 py-3">Saldo</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reservaciones as $reservacion)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $reservacion->folio }}</td>
                        <td class="px-4 py-3">{{ $reservacion->cliente_nombre }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $reservacion->salon->nombre }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $reservacion->fecha_evento->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">Bs {{ number_format($reservacion->precio_total, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $reservacion->saldo_pendiente > 0 ? 'text-red-600 font-medium' : 'text-emerald-600' }}">
                            Bs {{ number_format($reservacion->saldo_pendiente, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <x-badge-estado :estado="$reservacion->estado" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.reservaciones.show', $reservacion) }}"
                               class="text-emerald-700 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                            No hay reservaciones que coincidan con los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $reservaciones->links() }}
    </div>
@endsection

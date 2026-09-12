@extends('layouts.admin')

@section('titulo', 'Inventario — Existencias')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Inventario — Existencias</h1>
            <p class="text-sm text-gray-500">Stock actual por producto y salón.</p>
        </div>
        <a href="{{ route('admin.inventario.movimientos') }}" class="text-sm text-emerald-700 hover:underline">
            Ver movimientos &rarr;
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Producto</th>
                    <th class="text-left px-4 py-3">Almacén</th>
                    <th class="text-right px-4 py-3">Stock actual</th>
                    <th class="text-right px-4 py-3">Stock mínimo</th>
                    <th class="text-left px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($inventarios as $productoId => $filas)
                    @foreach ($filas as $fila)
                        <tr class="hover:bg-gray-50">
                            @if ($loop->first)
                                <td class="px-4 py-3 font-medium text-gray-900" rowspan="{{ $filas->count() }}">
                                    {{ $fila->producto->nombre }}
                                </td>
                            @endif
                            <td class="px-4 py-3 text-gray-600">{{ $fila->salon->nombre ?? 'Almacén general' }}</td>
                            <td class="px-4 py-3 text-right {{ $fila->stock_bajo ? 'text-red-600 font-medium' : '' }}">{{ $fila->stock_actual }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $fila->stock_minimo }}</td>
                            <td class="px-4 py-3">
                                @if ($fila->stock_bajo)
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-600">Stock bajo</span>
                                @else
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                            Todavía no hay productos con inventario cargado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

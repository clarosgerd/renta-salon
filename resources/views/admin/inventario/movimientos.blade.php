@extends('layouts.admin')

@section('titulo', 'Inventario — Movimientos')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Inventario — Movimientos</h1>
            <p class="text-sm text-gray-500">Entradas, salidas por venta y ajustes.</p>
        </div>
        <a href="{{ route('admin.inventario.index') }}" class="text-sm text-gray-500 hover:underline">
            &larr; Existencias
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 text-sm px-4 py-3 border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-5">
        <h2 class="text-sm font-medium text-gray-700 mb-3">Registrar entrada (reabasto)</h2>
        <form method="POST" action="{{ route('admin.inventario.movimientos.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Producto</label>
                <select name="producto_id" required class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Seleccionar...</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cantidad</label>
                <input type="number" name="cantidad" min="1" required value="{{ old('cantidad') }}"
                       class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Notas (opcional)</label>
                <input type="text" name="notas" value="{{ old('notas') }}" placeholder="Ej. compra a proveedor X"
                       class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-5 flex justify-end">
                <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Registrar entrada
                </button>
            </div>
        </form>
        @error('producto_id')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
        @error('cantidad')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Fecha</th>
                    <th class="text-left px-4 py-3">Producto</th>
                    <th class="text-left px-4 py-3">Tipo</th>
                    <th class="text-right px-4 py-3">Cantidad</th>
                    <th class="text-left px-4 py-3">Usuario</th>
                    <th class="text-left px-4 py-3">Notas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movimientos as $mov)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600">{{ $mov->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $mov->producto->nombre }}</td>
                        <td class="px-4 py-3">
                            @php
                                $etiquetas = ['entrada' => 'Entrada', 'salida_venta' => 'Salida (venta)', 'ajuste' => 'Ajuste'];
                                $colores = ['entrada' => 'bg-emerald-50 text-emerald-700', 'salida_venta' => 'bg-amber-50 text-amber-700', 'ajuste' => 'bg-gray-100 text-gray-600'];
                            @endphp
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $colores[$mov->tipo] }}">{{ $etiquetas[$mov->tipo] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right {{ $mov->cantidad < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $mov->usuario->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $mov->notas ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Todavía no hay movimientos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $movimientos->links() }}
    </div>
@endsection

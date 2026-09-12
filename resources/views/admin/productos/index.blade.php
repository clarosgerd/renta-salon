@extends('layouts.admin')

@section('titulo', 'Productos')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Productos</h1>
            <p class="text-sm text-gray-500">Catálogo de productos que se venden en el POS.</p>
        </div>
        <a href="{{ route('admin.productos.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nuevo producto
        </a>
    </div>

    <form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-5 flex flex-wrap gap-3">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre..."
               class="flex-1 min-w-[200px] rounded-md border-gray-300 text-sm">
        <select name="categoria" class="rounded-md border-gray-300 text-sm">
            <option value="">Todas las categorías</option>
            @foreach (['mobiliario' => 'Mobiliario', 'decoracion' => 'Decoración', 'bebidas' => 'Bebidas', 'alimentos' => 'Alimentos', 'otro' => 'Otro'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(request('categoria') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-md">Buscar</button>
        @if (request('buscar') || request('categoria'))
            <a href="{{ route('admin.productos.index') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
        @endif
    </form>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Categoría</th>
                    <th class="text-right px-4 py-3">Precio</th>
                    <th class="text-right px-4 py-3">Costo</th>
                    <th class="text-right px-4 py-3">Stock</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($productos as $producto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $producto->nombre }}</td>
                        <td class="px-4 py-3 text-gray-600 capitalize">{{ $producto->categoria }}</td>
                        <td class="px-4 py-3 text-right">Bs. {{ number_format($producto->precio_venta, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $producto->costo ? 'Bs. '.number_format($producto->costo, 2) : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="{{ (int) $producto->stock_actual_total <= 0 ? 'text-red-600 font-medium' : 'text-gray-700' }}">
                                {{ (int) $producto->stock_actual_total }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $producto->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.productos.edit', $producto) }}" class="text-emerald-700 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('admin.productos.toggle-activo', $producto) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-gray-500 hover:underline">
                                        {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">No hay productos que coincidan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $productos->links() }}
    </div>
@endsection

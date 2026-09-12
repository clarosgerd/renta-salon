@php
    $producto = $producto ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input type="text" name="nombre" required
               value="{{ old('nombre', $producto->nombre ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
        <select name="categoria" required class="w-full rounded-md border-gray-300 text-sm">
            @foreach (['mobiliario' => 'Mobiliario', 'decoracion' => 'Decoración', 'bebidas' => 'Bebidas', 'alimentos' => 'Alimentos', 'otro' => 'Otro'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('categoria', $producto->categoria ?? 'otro') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de medida</label>
        <input type="text" name="unidad_medida" placeholder="unidad"
               value="{{ old('unidad_medida', $producto->unidad_medida ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio de venta (Bs.)</label>
        <input type="number" step="0.01" min="0" name="precio_venta" required
               value="{{ old('precio_venta', $producto->precio_venta ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Costo (Bs., opcional)</label>
        <input type="number" step="0.01" min="0" name="costo"
               value="{{ old('costo', $producto->costo ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    @unless ($producto)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock inicial</label>
            <input type="number" min="0" name="stock_inicial" value="{{ old('stock_inicial', 0) }}"
                   class="w-full rounded-md border-gray-300 text-sm">
            <p class="text-xs text-gray-400 mt-1">Se carga en el almacén general. Se puede ajustar después desde Inventario.</p>
        </div>
    @endunless
</div>

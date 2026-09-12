@php
    $salon = $salon ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input type="text" name="nombre" required
               value="{{ old('nombre', $salon->nombre ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('descripcion', $salon->descripcion ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidad mínima</label>
        <input type="number" min="0" name="capacidad_min"
               value="{{ old('capacidad_min', $salon->capacidad_min ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidad máxima</label>
        <input type="number" min="1" name="capacidad_max" required
               value="{{ old('capacidad_max', $salon->capacidad_max ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
        <input type="text" name="ubicacion"
               value="{{ old('ubicacion', $salon->ubicacion ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    @if ($salon)
        <div class="md:col-span-2">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $salon->activo))>
                Salón activo (visible en el portal público)
            </label>
        </div>
    @endif

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $salon ? 'Agregar fotos' : 'Fotos' }}
        </label>
        <input type="file" name="imagenes[]" multiple accept="image/*" class="w-full text-sm">
        <p class="text-xs text-gray-400 mt-1">Hasta 10 fotos, 4 MB cada una.</p>
    </div>

    @if ($salon && $salon->imagenes->isNotEmpty())
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Galería actual</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($salon->imagenes as $imagen)
                    <div class="border border-gray-200 rounded-md overflow-hidden">
                        <img src="{{ $imagen->url }}" alt="" class="w-full aspect-square object-cover">
                        <div class="flex items-center justify-between px-1.5 py-1 bg-gray-50 text-xs">
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('admin.salones.imagenes.mover', [$salon, $imagen]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="direccion" value="arriba">
                                    <button type="submit" class="text-gray-500 hover:text-gray-800" title="Mover antes">&uarr;</button>
                                </form>
                                <form method="POST" action="{{ route('admin.salones.imagenes.mover', [$salon, $imagen]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="direccion" value="abajo">
                                    <button type="submit" class="text-gray-500 hover:text-gray-800" title="Mover después">&darr;</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('admin.salones.imagenes.destroy', [$salon, $imagen]) }}"
                                  onsubmit="return confirm('¿Eliminar esta foto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

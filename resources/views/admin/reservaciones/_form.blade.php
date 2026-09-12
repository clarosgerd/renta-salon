@php
    $reservacion = $reservacion ?? null;
    old_helper: // solo referencia mental, se usa old() abajo
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Salón</label>
        <select name="salon_id" required class="w-full rounded-md border-gray-300 text-sm">
            <option value="">Selecciona un salón</option>
            @foreach ($salones as $salon)
                <option value="{{ $salon->id }}"
                    @selected(old('salon_id', $reservacion->salon_id ?? null) == $salon->id)>
                    {{ $salon->nombre }} (cap. {{ $salon->capacidad_max }})
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Paquete</label>
        <select name="paquete_id" class="w-full rounded-md border-gray-300 text-sm">
            <option value="">Sin paquete / cotización libre</option>
            @foreach ($paquetes as $paquete)
                <option value="{{ $paquete->id }}"
                    data-precio="{{ $paquete->precio_base }}"
                    @selected(old('paquete_id', $reservacion->paquete_id ?? null) == $paquete->id)>
                    {{ $paquete->nombre }} — Bs {{ number_format($paquete->precio_base, 2) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del cliente</label>
        <input type="text" name="cliente_nombre" required
               value="{{ old('cliente_nombre', $reservacion->cliente_nombre ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="text" name="cliente_telefono" required
               value="{{ old('cliente_telefono', $reservacion->cliente_telefono ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email (opcional)</label>
        <input type="email" name="cliente_email"
               value="{{ old('cliente_email', $reservacion->cliente_email ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Número de invitados</label>
        <input type="number" min="1" name="num_invitados"
               value="{{ old('num_invitados', $reservacion->num_invitados ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del evento</label>
        {{-- Precarga desde ?fecha= (11/09/2026) — clic en un día vacío del
             Calendario maestro llega acá con la fecha ya elegida. --}}
        <input type="date" name="fecha_evento" required
               value="{{ old('fecha_evento', optional($reservacion?->fecha_evento)->format('Y-m-d') ?? request('fecha')) }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
            <input type="time" name="hora_inicio" required
                   value="{{ old('hora_inicio', $reservacion->hora_inicio ?? '') }}"
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
            <input type="time" name="hora_fin" required
                   value="{{ old('hora_fin', $reservacion->hora_fin ?? '') }}"
                   class="w-full rounded-md border-gray-300 text-sm">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio total (Bs)</label>
        <input type="number" step="0.01" min="0" name="precio_total" id="precio_total" required
               value="{{ old('precio_total', $reservacion->precio_total ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
        <p class="text-xs text-gray-400 mt-1">Se autocompleta con el precio base del paquete; puedes ajustarlo.</p>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('notas', $reservacion->notas ?? '') }}</textarea>
    </div>
</div>

@push('scripts')
<script>
    // Autocompleta el precio total al elegir un paquete (el admin puede sobreescribirlo).
    document.addEventListener('DOMContentLoaded', () => {
        const selectPaquete = document.querySelector('select[name="paquete_id"]');
        const inputPrecio = document.getElementById('precio_total');

        selectPaquete?.addEventListener('change', (e) => {
            const opcion = e.target.selectedOptions[0];
            const precio = opcion?.dataset?.precio;
            if (precio && !inputPrecio.value) {
                inputPrecio.value = precio;
            }
        });
    });
</script>
@endpush

@php
    $paquete = $paquete ?? null;
    $tipoEventoLabels = ['boda' => 'Boda', 'xv_anos' => 'XV años', 'corporativo' => 'Corporativo', 'otro' => 'Otro'];
    $serviciosViejos = old('servicios_incluidos', $paquete->servicios_incluidos ?? []);
    $salonesSeleccionados = old('salones', $paquete?->salones->pluck('id')->all() ?? []);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input type="text" name="nombre" required
               value="{{ old('nombre', $paquete->nombre ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de evento</label>
        <select name="tipo_evento" required class="w-full rounded-md border-gray-300 text-sm">
            @foreach ($tipoEventoLabels as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('tipo_evento', $paquete->tipo_evento ?? 'otro') === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Duración (horas)</label>
        <input type="number" min="1" name="duracion_horas" required
               value="{{ old('duracion_horas', $paquete->duracion_horas ?? 4) }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('descripcion', $paquete->descripcion ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio base (Bs)</label>
        <input type="number" step="0.01" min="0" name="precio_base" required
               value="{{ old('precio_base', $paquete->precio_base ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    @if ($paquete)
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $paquete->activo))>
                Paquete activo (visible en el portal público)
            </label>
        </div>
    @endif

    {{-- Servicios incluidos: chips agregables/removibles, sincronizados a
         inputs ocultos servicios_incluidos[] — mismo criterio vainilla que
         el resto del panel (sin Alpine, sin librerías nuevas). --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Servicios incluidos</label>
        <div id="serviciosChips" class="flex flex-wrap gap-2 mb-2"></div>
        <div class="flex gap-2">
            <input type="text" id="servicioNuevoInput" placeholder="Ej. Mantelería, Coordinador..."
                   class="flex-1 rounded-md border-gray-300 text-sm">
            <button type="button" id="servicioAgregarBtn"
                    class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                Agregar
            </button>
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Salones donde aplica</label>
        <p class="text-xs text-gray-400 mb-2">Si no seleccionas ninguno, el paquete aplica a todos los salones del negocio.</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach ($salones as $salonOpcion)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="salones[]" value="{{ $salonOpcion->id }}"
                           @checked(in_array($salonOpcion->id, $salonesSeleccionados))>
                    {{ $salonOpcion->nombre }}
                </label>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const contenedor = document.getElementById('serviciosChips');
        const input = document.getElementById('servicioNuevoInput');
        const boton = document.getElementById('servicioAgregarBtn');
        let servicios = @json($serviciosViejos);

        function render() {
            contenedor.innerHTML = '';
            servicios.forEach((servicio, indice) => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs font-medium px-2.5 py-1 rounded-full';
                chip.innerHTML = `${servicio.replace(/</g, '&lt;')} <button type="button" class="text-emerald-600 hover:text-emerald-900" aria-label="Quitar">&times;</button>
                    <input type="hidden" name="servicios_incluidos[]" value="${servicio.replace(/"/g, '&quot;')}">`;
                chip.querySelector('button').addEventListener('click', () => {
                    servicios.splice(indice, 1);
                    render();
                });
                contenedor.appendChild(chip);
            });
        }

        function agregar() {
            const valor = input.value.trim();
            if (valor && !servicios.includes(valor)) {
                servicios.push(valor);
                input.value = '';
                render();
            }
        }

        boton.addEventListener('click', agregar);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregar();
            }
        });

        render();
    });
</script>
@endpush

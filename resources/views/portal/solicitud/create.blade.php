@extends('layouts.portal')

@section('titulo', 'Solicitar una fecha')

@section('content')
    <div class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Solicitar una fecha</h1>
        <p class="text-sm text-gray-500 mb-6">
            Te contactaremos para confirmar disponibilidad — no es un cobro ni una reserva final todavía.
        </p>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 text-red-700 text-sm px-4 py-3 border border-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('portal.solicitud.store') }}" id="form-solicitud" class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Salón</label>
                    <select name="salon_id" id="select-salon" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach ($salones as $salon)
                            <option value="{{ $salon->id }}" @selected(old('salon_id', request('salon')) == $salon->id)>{{ $salon->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Paquete</label>
                    <select name="paquete_id" id="select-paquete" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach ($paquetes as $paquete)
                            <option value="{{ $paquete->id }}"
                                    data-salones="{{ $salonesPorPaquete[$paquete->id] ? $salonesPorPaquete[$paquete->id]->join(',') : '' }}"
                                    @selected(old('paquete_id', request('paquete')) == $paquete->id)>
                                {{ $paquete->nombre }} — Bs. {{ number_format($paquete->precio_base, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha deseada</label>
                    <input type="date" name="fecha_evento" required min="{{ now()->toDateString() }}"
                           value="{{ old('fecha_evento', request('fecha')) }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio</label>
                    <input type="time" name="hora_inicio" required value="{{ old('hora_inicio', '18:00') }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                <input type="text" name="cliente_nombre" required value="{{ old('cliente_nombre') }}"
                       class="w-full rounded-md border-gray-300 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="cliente_telefono" required value="{{ old('cliente_telefono') }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (opcional)</label>
                    <input type="email" name="cliente_email" value="{{ old('cliente_email') }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de invitados (opcional)</label>
                <input type="number" min="1" name="num_invitados" value="{{ old('num_invitados') }}"
                       class="w-full rounded-md border-gray-300 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                <textarea name="notas" rows="3" class="w-full rounded-md border-gray-300 text-sm">{{ old('notas') }}</textarea>
            </div>

            <button type="submit" class="btn-marca w-full text-white text-sm px-4 py-3 rounded-md hover:opacity-90">
                Enviar solicitud
            </button>
        </form>
    </div>

    <script>
        (function () {
            const selectSalon = document.getElementById('select-salon');
            const selectPaquete = document.getElementById('select-paquete');

            function filtrarPaquetes() {
                const salonId = selectSalon.value;
                Array.from(selectPaquete.options).forEach(function (opt) {
                    if (!opt.value) return; // "Seleccionar..."
                    const salones = opt.dataset.salones;
                    // Sin data-salones = aplica a todos los salones.
                    const aplica = !salones || salones.split(',').includes(salonId);
                    opt.hidden = !!salonId && !aplica;
                });
            }

            selectSalon.addEventListener('change', filtrarPaquetes);
            filtrarPaquetes();
        })();
    </script>
@endsection

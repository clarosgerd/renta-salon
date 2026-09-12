@extends('layouts.plataforma')

@section('titulo', 'Cobros (QR) — ' . $negocio->nombre_comercial)

@php
    $tieneCredenciales = ! empty($config?->credenciales_json_cifrado);
    $bancos = ['union' => 'Banco Unión', 'bnb' => 'BNB', 'bcp' => 'BCP'];
@endphp

@section('content')
    <div class="max-w-2xl">
        <div class="mb-5">
            <a href="{{ route('plataforma.negocios.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Negocios</a>
            <h1 class="text-lg font-medium text-gray-900 mt-1">Cobros (QR) — {{ $negocio->nombre_comercial }}</h1>
            <p class="text-sm text-gray-500">
                Banco y credenciales de cobro de este negocio. Solo visibles y editables desde acá —
                el negocio nunca ve sus propias credenciales.
            </p>
        </div>

        <form method="POST" action="{{ route('plataforma.negocios.config-pago.update', $negocio) }}"
              class="bg-white border border-gray-200 rounded-lg p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                    <select name="banco" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">— Sin configurar —</option>
                        @foreach ($bancos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('banco', $config?->banco) === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta destino</label>
                    <input type="text" name="cuenta_destino"
                           value="{{ old('cuenta_destino', $config?->cuenta_destino) }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <h2 class="text-sm font-medium text-gray-700 mb-3">Credenciales</h2>

                @if ($tieneCredenciales)
                    <div id="credenciales-actuales" class="flex items-center justify-between bg-gray-50 rounded-md px-4 py-3 text-sm">
                        <span class="text-gray-600">
                            •••• configuradas
                            @if ($config->updated_at)
                                — actualizadas el {{ $config->updated_at->format('d/m/Y H:i') }}
                            @endif
                        </span>
                        <label class="flex items-center gap-2 text-emerald-700 cursor-pointer">
                            <input type="checkbox" id="toggle-reemplazar" name="reemplazar_credenciales" value="1"
                                   onchange="document.getElementById('campos-credenciales').hidden = !this.checked">
                            Reemplazar
                        </label>
                    </div>
                @else
                    <input type="hidden" name="reemplazar_credenciales" value="1">
                @endif

                <div id="campos-credenciales" class="grid grid-cols-1 md:grid-cols-2 gap-5 {{ $tieneCredenciales ? 'mt-4' : '' }}"
                     @if ($tieneCredenciales) hidden @endif>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de comercio</label>
                        <input type="text" name="numero_comercio" autocomplete="off"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Llave API</label>
                        <input type="text" name="api_key" autocomplete="off"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $config?->activo))>
                    Activar pagos reales para este negocio
                </label>
                <p class="text-xs text-gray-400 mt-1">
                    Mientras esté desactivado, las reservaciones de este negocio generan QR de prueba (simulado),
                    sin cobrar de verdad.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('plataforma.negocios.index') }}"
                   class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Guardar cambios
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-lg p-6 mt-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-medium text-gray-700">Probar conexión</h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Genera un QR de prueba de Bs. 1 contra el banco configurado.
                        @if ($config?->ultima_prueba_conexion)
                            Última prueba: {{ $config->ultima_prueba_conexion->format('d/m/Y H:i') }}.
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('plataforma.negocios.config-pago.probar-conexion', $negocio) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                            @disabled(! $config?->banco)>
                        Probar conexión
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

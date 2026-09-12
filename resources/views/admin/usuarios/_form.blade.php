@php
    $usuario = $usuario ?? null;
    $rolActual = old('role', $usuario->role ?? '');
    $salonesAsignados = old('salones', $usuario?->salones->pluck('id')->all() ?? []);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input type="text" name="name" required
               value="{{ old('name', $usuario->name ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" required
               value="{{ old('email', $usuario->email ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $usuario ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
        </label>
        <input type="password" name="password" {{ $usuario ? '' : 'required' }} autocomplete="new-password"
               class="w-full rounded-md border-gray-300 text-sm">
        @if ($usuario)
            <p class="text-xs text-gray-400 mt-1">Dejar en blanco para no cambiarla.</p>
        @endif
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
        <select name="role" id="select-rol" required class="w-full rounded-md border-gray-300 text-sm">
            <option value="">Seleccionar...</option>
            @foreach (['admin_negocio' => 'Admin Negocio', 'admin_salon' => 'Admin Salón', 'cajero' => 'Cajero'] as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected($rolActual === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div id="bloque-salones" class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">Salones asignados</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach ($salones as $salon)
                <label class="flex items-center gap-2 text-sm border border-gray-200 rounded-md px-3 py-2">
                    <input type="checkbox" name="salones[]" value="{{ $salon->id }}"
                           @checked(in_array($salon->id, $salonesAsignados))>
                    {{ $salon->nombre }}
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-1">Solo aplica a Admin Salón / Cajero — un Admin Negocio ve todos los salones automáticamente.</p>
    </div>
</div>

<script>
    (function () {
        const selectRol = document.getElementById('select-rol');
        const bloqueSalones = document.getElementById('bloque-salones');

        function actualizar() {
            bloqueSalones.hidden = selectRol.value === 'admin_negocio' || selectRol.value === '';
        }

        selectRol.addEventListener('change', actualizar);
        actualizar();
    })();
</script>

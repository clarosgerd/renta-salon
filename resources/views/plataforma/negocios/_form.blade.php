@php
    $negocio = $negocio ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre comercial</label>
        <input type="text" name="nombre_comercial" required
               value="{{ old('nombre_comercial', $negocio->nombre_comercial ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Subdominio</label>
        <div class="flex items-center gap-1">
            <input type="text" name="subdominio" required
                   value="{{ old('subdominio', $negocio->subdominio ?? '') }}"
                   class="w-full rounded-md border-gray-300 text-sm font-mono" placeholder="saloneslapaz">
            <span class="text-sm text-gray-400 whitespace-nowrap">.rentsalon-pro.test</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">Solo minúsculas, números y guiones.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Dominio propio (opcional)</label>
        <input type="text" name="dominio_personalizado"
               value="{{ old('dominio_personalizado', $negocio->dominio_personalizado ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm" placeholder="www.saloneselegantes.com">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Logo (URL)</label>
        <input type="url" name="logo_url"
               value="{{ old('logo_url', $negocio->logo_url ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Color primario</label>
        <div class="flex items-center gap-2">
            <input type="color"
                   value="{{ old('color_primario', $negocio->color_primario ?? '#1D9E75') }}"
                   onchange="document.getElementById('color_primario_text').value = this.value"
                   class="h-9 w-12 rounded border-gray-300">
            <input type="text" name="color_primario" id="color_primario_text"
                   value="{{ old('color_primario', $negocio->color_primario ?? '#1D9E75') }}"
                   class="w-full rounded-md border-gray-300 text-sm font-mono">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de contacto</label>
        <input type="text" name="telefono_contacto"
               value="{{ old('telefono_contacto', $negocio->telefono_contacto ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email de contacto</label>
        <input type="email" name="email_contacto"
               value="{{ old('email_contacto', $negocio->email_contacto ?? '') }}"
               class="w-full rounded-md border-gray-300 text-sm">
    </div>
</div>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', app('negocio_actual')->nombre_comercial ?? 'RentSalon Pro')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('vite-extra')
    <style>
        .btn-marca { background-color: var(--brand); }
        .btn-marca:hover { filter: brightness(0.92); }
        .text-marca { color: var(--brand); }
        .border-marca { border-color: var(--brand); }
    </style>
</head>
{{-- Branding dinámico por negocio (Fase 6, 11/09/2026) — a diferencia del
     resto del panel (clases Tailwind fijas), acá el color SÍ tiene que
     salir de la BD: Tailwind JIT no genera clases desde un hex arbitrario
     en runtime, así que se usa una custom property + estilo inline en los
     acentos. --}}
<body class="bg-gray-50 text-gray-800" style="--brand: {{ app('negocio_actual')->color_primario ?? '#1D9E75' }}">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('portal.home') }}" class="flex items-center gap-2">
                @if (app('negocio_actual')->logo_url)
                    <img src="{{ app('negocio_actual')->logo_url }}" alt="{{ app('negocio_actual')->nombre_comercial }}" class="h-9">
                @else
                    <span class="font-semibold text-gray-900">{{ app('negocio_actual')->nombre_comercial }}</span>
                @endif
            </a>
            <nav class="flex items-center gap-5 text-sm text-gray-600">
                <a href="{{ route('portal.salones') }}" class="hover:text-gray-900">Salones</a>
                <a href="{{ route('portal.paquetes') }}" class="hover:text-gray-900">Paquetes</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 mt-16">
        <div class="max-w-5xl mx-auto px-6 py-8 flex items-center justify-between text-xs text-gray-400">
            <span>&copy; {{ now()->year }} {{ app('negocio_actual')->nombre_comercial }}</span>
            <a href="{{ route('login') }}" class="hover:text-gray-600">Acceso administradores</a>
        </div>
    </footer>
</body>
</html>

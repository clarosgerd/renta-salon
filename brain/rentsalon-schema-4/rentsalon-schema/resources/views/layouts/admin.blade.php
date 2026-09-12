<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Panel') — {{ app('negocio_actual')->nombre_comercial ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-60 bg-white border-r border-gray-200 flex-shrink-0">
            <div class="px-5 py-4 border-b border-gray-100">
                <p class="font-semibold text-gray-900">
                    {{ app('negocio_actual')->nombre_comercial ?? 'RentSalon Pro' }}
                </p>
            </div>
            <nav class="p-3 space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Dashboard</a>
                <a href="{{ route('admin.calendario.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Calendario</a>
                <a href="{{ route('admin.reservaciones.index') }}"
                   class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.reservaciones.*') ? 'bg-emerald-50 text-emerald-700 font-medium' : 'hover:bg-gray-100' }}">
                    Reservaciones
                </a>
                <a href="{{ route('admin.salones.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Salones</a>
                <a href="{{ route('admin.paquetes.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Paquetes</a>
                <a href="{{ route('admin.pos.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Punto de venta</a>
                <a href="{{ route('admin.productos.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Productos</a>
                <a href="{{ route('admin.usuarios.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-100">Usuarios</a>
            </nav>
        </aside>

        {{-- Contenido --}}
        <main class="flex-1 p-6">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 text-sm px-4 py-3 border border-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 text-red-700 text-sm px-4 py-3 border border-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>

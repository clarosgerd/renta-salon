<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Plataforma') — RentSalon Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800">

    {{-- Sin sidebar de módulos tenant (esto vive FUERA de layouts.admin a
         propósito — no hay app('negocio_actual') en este dominio). --}}
    <header class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <span class="font-semibold">RentSalon Pro — Plataforma</span>
            <a href="{{ route('plataforma.negocios.index') }}" class="text-sm text-gray-300 hover:text-white">Negocios</a>
        </div>
        <form method="POST" action="{{ route('plataforma.logout') }}">
            @csrf
            <button type="submit" class="text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded-md">
                Salir ({{ auth()->user()->email ?? '' }})
            </button>
        </form>
    </header>

    <main class="max-w-5xl mx-auto p-6">
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
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentSalon Pro — Software para salones de eventos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 antialiased">

    <header class="border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="font-semibold text-gray-900">RentSalon <span class="text-emerald-600">Pro</span></span>
            <nav class="hidden sm:flex items-center gap-6 text-sm text-gray-600">
                <a href="#caracteristicas" class="hover:text-gray-900">Características</a>
                <a href="#como-funciona" class="hover:text-gray-900">Cómo funciona</a>
                <a href="#contacto" class="hover:text-gray-900">Contacto</a>
            </nav>
            <a href="{{ route('plataforma.login') }}" class="text-sm border border-gray-300 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-50">
                Ingresar
            </a>
        </div>
    </header>

    <section class="max-w-6xl mx-auto px-6 py-20 text-center">
        <h1 class="text-3xl sm:text-5xl font-semibold text-gray-900 max-w-3xl mx-auto leading-tight">
            El software para administrar tu salón de eventos, de punta a punta
        </h1>
        <p class="text-gray-500 mt-5 max-w-xl mx-auto text-lg">
            Calendario, reservaciones, cobros con QR, punto de venta e inventario — todo en un solo
            panel, con un portal público para que tus clientes soliciten su fecha sin llamarte.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
            <a href="mailto:hola@rentsalonpro.test?subject=Quiero%20una%20demo%20de%20RentSalon%20Pro"
               class="bg-emerald-600 text-white text-sm px-6 py-3 rounded-md hover:bg-emerald-700">
                Solicitar una demo
            </a>
            <a href="http://saloneslapaz.rentsalon-pro.test:8080/" target="_blank" rel="noopener"
               class="border border-gray-300 text-gray-700 text-sm px-6 py-3 rounded-md hover:bg-gray-50">
                Ver un negocio de ejemplo &rarr;
            </a>
        </div>
    </section>

    <section id="caracteristicas" class="bg-gray-50 border-y border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl font-semibold text-gray-900 text-center mb-10">Todo lo que necesitás, en un solo lugar</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['📅', 'Calendario y reservaciones', 'Vista maestra de todos tus salones, con control de disponibilidad y estado de cuenta por evento.'],
                    ['🌐', 'Portal público', 'Tus clientes ven disponibilidad real y solicitan su fecha sin necesidad de llamarte — vos confirmás o rechazás desde el panel.'],
                    ['💳', 'Cobros con QR o efectivo', 'Abonos de reservaciones y ventas del punto de venta, con confirmación automática del banco.'],
                    ['🛒', 'POS e inventario', 'Vendé productos el día del evento — el stock se descuenta solo, con corte de caja diario por cajero.'],
                    ['🏢', 'Multi-negocio real', 'Cada negocio con su propio subdominio, marca, usuarios y datos — completamente aislados entre sí.'],
                    ['👥', 'Roles para tu equipo', 'Admin Salón y Cajero con acceso solo a lo que les corresponde, sin dar acceso total a todo el negocio.'],
                ] as [$emoji, $titulo, $texto])
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <span class="text-2xl">{{ $emoji }}</span>
                        <h3 class="font-medium text-gray-900 mt-3">{{ $titulo }}</h3>
                        <p class="text-sm text-gray-500 mt-1.5">{{ $texto }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="como-funciona" class="max-w-6xl mx-auto px-6 py-16">
        <h2 class="text-2xl font-semibold text-gray-900 text-center mb-10">Cómo funciona</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            @foreach ([
                ['1', 'Cargá tus salones y paquetes', 'Fotos, capacidad, precios y servicios incluidos — listo para publicarse en tu portal.'],
                ['2', 'Recibís solicitudes solas', 'Tus clientes ven el calendario real y piden su fecha desde tu propio subdominio.'],
                ['3', 'Cobrás y controlás todo', 'Confirmá reservaciones, registrá abonos, vendé en el POS — un solo panel para tu equipo.'],
            ] as [$numero, $titulo, $texto])
                <div class="text-center sm:text-left">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600 text-white text-sm font-medium">{{ $numero }}</span>
                    <h3 class="font-medium text-gray-900 mt-3">{{ $titulo }}</h3>
                    <p class="text-sm text-gray-500 mt-1.5">{{ $texto }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="contacto" class="bg-emerald-600">
        <div class="max-w-3xl mx-auto px-6 py-16 text-center">
            <h2 class="text-2xl font-semibold text-white">¿Listo para probarlo con tu negocio?</h2>
            <p class="text-emerald-50 mt-2">Escribinos y te damos acceso a una demo con tus propios salones.</p>
            <a href="mailto:hola@rentsalonpro.test?subject=Quiero%20una%20demo%20de%20RentSalon%20Pro"
               class="inline-block bg-white text-emerald-700 text-sm px-6 py-3 rounded-md hover:bg-emerald-50 mt-6">
                hola@rentsalonpro.test
            </a>
        </div>
    </section>

    <footer class="border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-8 text-center text-xs text-gray-400">
            &copy; {{ now()->year }} RentSalon Pro
        </div>
    </footer>
</body>
</html>

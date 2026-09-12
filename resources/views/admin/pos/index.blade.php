@extends('layouts.admin')

@section('titulo', 'Punto de Venta')

@php
    $iconosCategoria = ['bebidas' => '🥤', 'decoracion' => '💐', 'mobiliario' => '🪑', 'alimentos' => '🍽️', 'otro' => '📦'];
    $etiquetasCategoria = ['bebidas' => 'Bebidas', 'decoracion' => 'Decoración', 'mobiliario' => 'Mobiliario', 'alimentos' => 'Alimentos', 'otro' => 'Otro'];
    $categoriasPresentes = $productos->pluck('categoria')->unique()->values();
    $etiquetasTipo = ['boda' => 'Boda', 'xv_anos' => 'XV Años', 'corporativo' => 'Corporativo', 'otro' => 'Evento'];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Punto de Venta</h1>
            @if ($reservacionVinculada)
                <p class="text-sm text-gray-500 mt-0.5">
                    📅 Vinculado a:
                    <a href="{{ route('admin.reservaciones.show', $reservacionVinculada) }}" class="text-emerald-700 hover:underline">
                        {{ $reservacionVinculada->folio }} —
                        {{ $reservacionVinculada->paquete ? ($etiquetasTipo[$reservacionVinculada->paquete->tipo_evento] ?? 'Evento') : 'Evento' }}
                        {{ $reservacionVinculada->cliente_nombre }}
                    </a>
                </p>
            @else
                <p class="text-sm text-gray-500">Agrega productos al carrito y cobra en efectivo o QR.</p>
            @endif
        </div>
        <a href="{{ route('admin.pos.corte') }}" class="text-sm text-emerald-700 hover:underline">Ver mi corte de caja &rarr;</a>
    </div>

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

    {{-- QR pendiente de una venta recién generada (venido del store()) —
         mismo criterio que el placeholder de Reservaciones, pero acá sí se
         muestra la imagen real: PagoQr.payload_respuesta la guarda porque
         el gateway no la persiste en ninguna otra columna (ver
         VentaPosService::generarQrParaVenta). --}}
    @if ($ventaQr && $ventaQr->pagoQr)
        <div id="qr-pendiente" class="bg-white border border-amber-300 rounded-lg p-6 mb-5 flex flex-col sm:flex-row items-center gap-6"
             data-estado-url="{{ route('admin.ventas-pos.estado-qr', $ventaQr) }}"
             data-confirmar-url="{{ route('admin.ventas-pos.confirmar-manual', $ventaQr) }}">
            <img src="data:image/png;base64,{{ $ventaQr->pagoQr->payload_respuesta['imagen_base64'] ?? '' }}"
                 alt="QR de pago" class="w-40 h-40 border border-gray-200 rounded-md">
            <div class="flex-1">
                <p class="font-medium text-gray-900">Venta {{ $ventaQr->folio }} — Bs. {{ number_format($ventaQr->total, 2) }}</p>
                <p id="qr-estado-texto" class="text-sm text-amber-700 mt-1">Esperando confirmación del pago...</p>
                <form method="POST" action="{{ route('admin.ventas-pos.confirmar-manual', $ventaQr) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button class="text-sm text-gray-500 hover:underline">Confirmar manualmente</button>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Panel izquierdo: buscador + filtro de categoría + grid de productos --}}
        <div class="lg:col-span-2">
            <input type="text" id="buscador-productos" placeholder="Buscar producto..."
                   class="w-full rounded-md border-gray-300 text-sm mb-3">

            <div id="filtro-categorias" class="flex flex-wrap gap-2 mb-4">
                <button type="button" data-categoria="" class="btn-categoria activa text-sm px-3 py-1.5 rounded-full border">
                    Todas
                </button>
                @foreach ($categoriasPresentes as $categoria)
                    <button type="button" data-categoria="{{ $categoria }}" class="btn-categoria text-sm px-3 py-1.5 rounded-full border">
                        {{ $etiquetasCategoria[$categoria] ?? ucfirst($categoria) }}
                    </button>
                @endforeach
            </div>

            <div id="grid-productos" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @forelse ($productos as $producto)
                    <button type="button" class="btn-agregar text-left bg-white border border-gray-200 rounded-lg p-3 hover:border-emerald-400"
                            data-nombre="{{ strtolower($producto->nombre) }}" data-categoria="{{ $producto->categoria }}"
                            data-id="{{ $producto->id }}" data-precio="{{ $producto->precio_venta }}">
                        <span class="text-2xl">{{ $iconosCategoria[$producto->categoria] ?? '📦' }}</span>
                        <p class="text-sm font-medium text-gray-900 mt-2">{{ $producto->nombre }}</p>
                        <p class="text-sm text-gray-500">Bs {{ number_format($producto->precio_venta, 2) }}</p>
                    </button>
                @empty
                    <p class="col-span-full text-gray-400 text-sm">No hay productos activos. Crea uno en Productos.</p>
                @endforelse
            </div>
        </div>

        {{-- Panel derecho: carrito --}}
        <div>
            <form method="POST" action="{{ route('admin.pos.store') }}" id="form-venta" class="bg-white border border-gray-200 rounded-lg p-4">
                @csrf
                <h2 class="text-sm font-medium text-gray-700 mb-3">Carrito</h2>

                <div id="carrito-vacio" class="text-sm text-gray-400 py-6 text-center">Sin productos agregados.</div>
                <div id="carrito-filas" class="hidden space-y-3"></div>

                <div class="border-t border-gray-100 mt-3 pt-3 flex items-center justify-between font-medium">
                    <span>Total</span>
                    <span id="carrito-total">Bs 0.00</span>
                </div>

                @if ($reservacionVinculada)
                    <input type="hidden" name="reservacion_id" value="{{ $reservacionVinculada->id }}">
                @else
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vincular a reservación (opcional)</label>
                        <select name="reservacion_id" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Venta de mostrador</option>
                            @foreach ($reservacionesActivas as $reservacion)
                                <option value="{{ $reservacion->id }}">{{ $reservacion->folio }} — {{ $reservacion->cliente_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <input type="hidden" name="metodo_pago" id="metodo-pago" value="efectivo">

                <div class="grid grid-cols-2 gap-2 mt-5">
                    <button type="submit" id="btn-efectivo" disabled
                            class="text-sm border border-gray-300 text-gray-700 px-4 py-2.5 rounded-md hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                        💵 Efectivo
                    </button>
                    <button type="submit" id="btn-qr" disabled
                            class="text-sm bg-gray-900 text-white px-4 py-2.5 rounded-md hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed">
                        📲 Cobrar QR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const carrito = {}; // { producto_id: { nombre, precio, cantidad } }
            const filas = document.getElementById('carrito-filas');
            const vacio = document.getElementById('carrito-vacio');
            const totalEl = document.getElementById('carrito-total');
            const btnEfectivo = document.getElementById('btn-efectivo');
            const btnQr = document.getElementById('btn-qr');
            const metodoPagoInput = document.getElementById('metodo-pago');
            const form = document.getElementById('form-venta');

            function render() {
                const ids = Object.keys(carrito);
                filas.innerHTML = '';
                let total = 0;

                ids.forEach(function (id) {
                    const item = carrito[id];
                    const subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    const fila = document.createElement('div');
                    fila.className = 'flex items-start justify-between text-sm';
                    fila.innerHTML =
                        '<div>' +
                            '<p class="text-gray-900">' + item.nombre + '</p>' +
                            '<div class="flex items-center gap-1 text-gray-400">' +
                                '<span>x</span>' +
                                '<input type="number" min="1" value="' + item.cantidad + '" data-id="' + id + '" class="input-cantidad w-12 rounded-md border-gray-300 text-xs py-0.5">' +
                            '</div>' +
                        '</div>' +
                        '<div class="flex items-center gap-2">' +
                            '<span class="font-medium text-gray-900">Bs ' + subtotal.toFixed(2) + '</span>' +
                            '<button type="button" data-id="' + id + '" class="btn-quitar text-red-400">&times;</button>' +
                        '</div>';
                    filas.appendChild(fila);
                });

                vacio.classList.toggle('hidden', ids.length > 0);
                filas.classList.toggle('hidden', ids.length === 0);
                totalEl.textContent = 'Bs ' + total.toFixed(2);
                btnEfectivo.disabled = ids.length === 0;
                btnQr.disabled = ids.length === 0;
            }

            document.getElementById('grid-productos').addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-agregar');
                if (!btn) return;
                const id = btn.dataset.id;
                if (carrito[id]) {
                    carrito[id].cantidad++;
                } else {
                    carrito[id] = { nombre: btn.querySelector('p').textContent, precio: parseFloat(btn.dataset.precio), cantidad: 1 };
                }
                render();
            });

            filas.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-quitar');
                if (!btn) return;
                delete carrito[btn.dataset.id];
                render();
            });

            filas.addEventListener('input', function (e) {
                if (!e.target.classList.contains('input-cantidad')) return;
                const cantidad = parseInt(e.target.value, 10);
                carrito[e.target.dataset.id].cantidad = cantidad > 0 ? cantidad : 1;
                render();
            });

            function aplicarFiltros() {
                const texto = document.getElementById('buscador-productos').value.toLowerCase();
                const categoriaActiva = document.querySelector('.btn-categoria.activa').dataset.categoria;
                document.querySelectorAll('.btn-agregar').forEach(function (btn) {
                    const coincideTexto = btn.dataset.nombre.includes(texto);
                    const coincideCategoria = !categoriaActiva || btn.dataset.categoria === categoriaActiva;
                    btn.style.display = (coincideTexto && coincideCategoria) ? '' : 'none';
                });
            }

            document.getElementById('buscador-productos').addEventListener('input', aplicarFiltros);

            document.getElementById('filtro-categorias').addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-categoria');
                if (!btn) return;
                document.querySelectorAll('.btn-categoria').forEach((b) => b.classList.remove('activa'));
                btn.classList.add('activa');
                aplicarFiltros();
            });

            btnEfectivo.addEventListener('click', function () { metodoPagoInput.value = 'efectivo'; });
            btnQr.addEventListener('click', function () { metodoPagoInput.value = 'qr'; });

            form.addEventListener('submit', function () {
                Object.keys(carrito).forEach(function (id, i) {
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'items[' + i + '][producto_id]';
                    inputId.value = id;
                    form.appendChild(inputId);

                    const inputCant = document.createElement('input');
                    inputCant.type = 'hidden';
                    inputCant.name = 'items[' + i + '][cantidad]';
                    inputCant.value = carrito[id].cantidad;
                    form.appendChild(inputCant);
                });
            });

            // Polling del QR pendiente, si venimos de una venta recién generada.
            const qrBox = document.getElementById('qr-pendiente');
            if (qrBox) {
                const estadoTexto = document.getElementById('qr-estado-texto');
                const intervalo = setInterval(function () {
                    fetch(qrBox.dataset.estadoUrl)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.estado === 'pagado') {
                                estadoTexto.textContent = 'Pago confirmado ✓';
                                estadoTexto.classList.remove('text-amber-700');
                                estadoTexto.classList.add('text-emerald-700');
                                clearInterval(intervalo);
                            }
                        });
                }, 3000);
            }
        })();
    </script>

    <style>
        .btn-categoria { border-color: #d1d5db; color: #374151; }
        .btn-categoria.activa { background-color: #111827; color: #fff; border-color: #111827; }
    </style>
@endsection

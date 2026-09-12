@extends('layouts.admin')

@section('titulo', 'Punto de Venta')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Punto de Venta</h1>
            <p class="text-sm text-gray-500">Agrega productos al carrito y cobra en efectivo o QR.</p>
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

    {{-- QR pendiente de un venta recién generada (venido del store()) —
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
        {{-- Panel izquierdo: buscador + grid de productos --}}
        <div class="lg:col-span-2">
            <input type="text" id="buscador-productos" placeholder="Buscar producto por nombre o categoría..."
                   class="w-full rounded-md border-gray-300 text-sm mb-4">

            <div id="grid-productos" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @forelse ($productos as $producto)
                    <button type="button" class="btn-agregar text-left bg-white border border-gray-200 rounded-lg p-3 hover:border-emerald-400"
                            data-nombre="{{ strtolower($producto->nombre) }}" data-categoria="{{ strtolower($producto->categoria) }}"
                            data-id="{{ $producto->id }}" data-precio="{{ $producto->precio_venta }}">
                        <p class="text-sm font-medium text-gray-900">{{ $producto->nombre }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ $producto->categoria }}</p>
                        <p class="text-sm text-emerald-700 mt-1">Bs. {{ number_format($producto->precio_venta, 2) }}</p>
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
                <table id="carrito-tabla" class="w-full text-sm hidden">
                    <tbody id="carrito-filas"></tbody>
                </table>

                <div class="border-t border-gray-100 mt-3 pt-3 flex items-center justify-between font-medium">
                    <span>Total</span>
                    <span id="carrito-total">Bs. 0.00</span>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Vincular a reservación (opcional)</label>
                    <select name="reservacion_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Venta de mostrador</option>
                        @foreach ($reservacionesActivas as $reservacion)
                            <option value="{{ $reservacion->id }}">{{ $reservacion->folio }} — {{ $reservacion->cliente_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-500 mb-2">Método de pago</label>
                    <div class="flex gap-2">
                        <label class="flex-1 text-center text-sm py-2 rounded-md border border-gray-300 cursor-pointer has-[:checked]:bg-gray-900 has-[:checked]:text-white">
                            <input type="radio" name="metodo_pago" value="efectivo" checked class="sr-only"> Efectivo
                        </label>
                        <label class="flex-1 text-center text-sm py-2 rounded-md border border-gray-300 cursor-pointer has-[:checked]:bg-gray-900 has-[:checked]:text-white">
                            <input type="radio" name="metodo_pago" value="qr" class="sr-only"> QR
                        </label>
                    </div>
                </div>

                <button type="submit" id="btn-cobrar" disabled
                        class="w-full mt-5 px-4 py-2.5 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    Cobrar
                </button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const carrito = {}; // { producto_id: { nombre, precio, cantidad } }
            const filas = document.getElementById('carrito-filas');
            const vacio = document.getElementById('carrito-vacio');
            const tabla = document.getElementById('carrito-tabla');
            const totalEl = document.getElementById('carrito-total');
            const btnCobrar = document.getElementById('btn-cobrar');
            const form = document.getElementById('form-venta');

            function render() {
                const ids = Object.keys(carrito);
                filas.innerHTML = '';
                let total = 0;

                ids.forEach(function (id) {
                    const item = carrito[id];
                    const subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td class="py-1.5 pr-2">' + item.nombre + '</td>' +
                        '<td class="py-1.5 pr-2 w-16">' +
                            '<input type="number" min="1" value="' + item.cantidad + '" data-id="' + id + '" class="input-cantidad w-14 rounded-md border-gray-300 text-sm">' +
                        '</td>' +
                        '<td class="py-1.5 text-right pr-2">Bs. ' + subtotal.toFixed(2) + '</td>' +
                        '<td class="py-1.5 text-right"><button type="button" data-id="' + id + '" class="btn-quitar text-red-500">&times;</button></td>';
                    filas.appendChild(tr);
                });

                vacio.classList.toggle('hidden', ids.length > 0);
                tabla.classList.toggle('hidden', ids.length === 0);
                totalEl.textContent = 'Bs. ' + total.toFixed(2);
                btnCobrar.disabled = ids.length === 0;
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

            document.getElementById('buscador-productos').addEventListener('input', function (e) {
                const texto = e.target.value.toLowerCase();
                document.querySelectorAll('.btn-agregar').forEach(function (btn) {
                    const coincide = btn.dataset.nombre.includes(texto) || btn.dataset.categoria.includes(texto);
                    btn.style.display = coincide ? '' : 'none';
                });
            });

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
@endsection

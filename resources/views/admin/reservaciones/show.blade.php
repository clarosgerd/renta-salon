@extends('layouts.admin')

@section('titulo', $reservacion->folio)

@section('content')
<div x-data="{ tab: 'cuenta', modalAbono: false, metodoPago: 'efectivo' }" class="max-w-5xl">

    <a href="{{ route('admin.reservaciones.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Reservaciones</a>

    {{-- Encabezado --}}
    <div class="flex items-start justify-between mt-2 mb-5">
        <div>
            <p class="text-xs text-gray-400">Folio {{ $reservacion->folio }}</p>
            <h1 class="text-lg font-medium text-gray-900 mt-0.5">{{ $reservacion->cliente_nombre }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $reservacion->salon->nombre }} ·
                {{ $reservacion->paquete->nombre ?? 'Sin paquete' }} ·
                {{ $reservacion->fecha_evento->format('d M Y') }},
                {{ \Carbon\Carbon::parse($reservacion->hora_inicio)->format('g:i A') }}
                – {{ \Carbon\Carbon::parse($reservacion->hora_fin)->format('g:i A') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-badge-estado :estado="$reservacion->estado" />
            <a href="{{ route('admin.reservaciones.edit', $reservacion) }}"
               class="text-sm text-gray-500 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">
                Editar
            </a>
        </div>
    </div>

    {{-- Acciones de estado --}}
    @if ($reservacion->estado === 'pendiente')
        <div class="flex gap-2 mb-5">
            <form method="POST" action="{{ route('admin.reservaciones.confirmar', $reservacion) }}">
                @csrf @method('PATCH')
                <button class="text-sm bg-emerald-600 text-white px-4 py-2 rounded-md hover:bg-emerald-700">
                    Confirmar reservación
                </button>
            </form>
            <form method="POST" action="{{ route('admin.reservaciones.rechazar', $reservacion) }}"
                  onsubmit="return confirm('¿Rechazar esta solicitud? La fecha quedará liberada.')">
                @csrf @method('PATCH')
                <button class="text-sm border border-gray-300 text-gray-600 px-4 py-2 rounded-md hover:bg-gray-50">
                    Rechazar
                </button>
            </form>
        </div>
    @elseif ($reservacion->estado === 'confirmada')
        <div class="flex gap-2 mb-5">
            <form method="POST" action="{{ route('admin.reservaciones.finalizar', $reservacion) }}">
                @csrf @method('PATCH')
                <button class="text-sm bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Marcar como finalizada
                </button>
            </form>
            <form method="POST" action="{{ route('admin.reservaciones.cancelar', $reservacion) }}"
                  onsubmit="return confirm('¿Cancelar esta reservación?')">
                @csrf @method('PATCH')
                <button class="text-sm border border-gray-300 text-gray-600 px-4 py-2 rounded-md hover:bg-gray-50">
                    Cancelar
                </button>
            </form>
        </div>
    @endif

    {{-- Tarjetas de resumen financiero --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-1">Total del evento</p>
            <p class="text-xl font-medium">Bs {{ number_format($reservacion->precio_total, 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-1">Abonado</p>
            <p class="text-xl font-medium text-emerald-600">Bs {{ number_format($reservacion->total_abonado, 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-1">Saldo pendiente</p>
            <p class="text-xl font-medium {{ $reservacion->esta_liquidada ? 'text-emerald-600' : 'text-red-600' }}">
                Bs {{ number_format($reservacion->saldo_pendiente, 2) }}
            </p>
        </div>
    </div>

    {{-- Pestañas --}}
    <div class="flex gap-1 border-b border-gray-200 mb-5 text-sm">
        <button @click="tab = 'cliente'" :class="tab === 'cliente' ? 'border-b-2 border-emerald-600 text-gray-900 font-medium' : 'text-gray-500'"
                class="px-3 py-2">Cliente</button>
        <button @click="tab = 'cuenta'" :class="tab === 'cuenta' ? 'border-b-2 border-emerald-600 text-gray-900 font-medium' : 'text-gray-500'"
                class="px-3 py-2">Estado de cuenta</button>
        <button @click="tab = 'pos'" :class="tab === 'pos' ? 'border-b-2 border-emerald-600 text-gray-900 font-medium' : 'text-gray-500'"
                class="px-3 py-2">Ventas POS</button>
        <button @click="tab = 'historial'" :class="tab === 'historial' ? 'border-b-2 border-emerald-600 text-gray-900 font-medium' : 'text-gray-500'"
                class="px-3 py-2">Historial</button>
    </div>

    {{-- Pestaña: Cliente --}}
    <div x-show="tab === 'cliente'" class="bg-white border border-gray-200 rounded-lg p-5 text-sm space-y-3">
        <div class="grid grid-cols-2 gap-4">
            <div><p class="text-gray-500">Nombre</p><p class="font-medium">{{ $reservacion->cliente_nombre }}</p></div>
            <div><p class="text-gray-500">Teléfono</p><p class="font-medium">{{ $reservacion->cliente_telefono }}</p></div>
            <div><p class="text-gray-500">Email</p><p class="font-medium">{{ $reservacion->cliente_email ?? '—' }}</p></div>
            <div><p class="text-gray-500">Invitados</p><p class="font-medium">{{ $reservacion->num_invitados ?? '—' }}</p></div>
        </div>
        @if ($reservacion->notas)
            <div class="pt-3 border-t border-gray-100">
                <p class="text-gray-500 mb-1">Notas</p>
                <p>{{ $reservacion->notas }}</p>
            </div>
        @endif
    </div>

    {{-- Pestaña: Estado de cuenta --}}
    <div x-show="tab === 'cuenta'" class="bg-white border border-gray-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-900">Abonos registrados</p>
            <div class="flex gap-2">
                <a href="{{ route('admin.reservaciones.estado-cuenta.pdf', $reservacion) }}"
                   class="text-sm border border-gray-300 text-gray-600 px-3 py-1.5 rounded-md hover:bg-gray-50">
                    Descargar PDF
                </a>
                <button @click="modalAbono = true"
                        class="text-sm bg-emerald-600 text-white px-3 py-1.5 rounded-md hover:bg-emerald-700">
                    + Registrar abono
                </button>
            </div>
        </div>

        <table class="w-full text-sm">
            <thead class="text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left py-2">Fecha</th>
                    <th class="text-left py-2">Método</th>
                    <th class="text-left py-2">Registrado por</th>
                    <th class="text-left py-2">Estado</th>
                    <th class="text-right py-2">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reservacion->pagosAbonos as $abono)
                    <tr>
                        <td class="py-2.5">{{ $abono->fecha_pago->format('d M Y') }}</td>
                        <td class="py-2.5">
                            {{ $abono->metodo_pago === 'qr' ? 'QR' : 'Efectivo' }}
                        </td>
                        <td class="py-2.5 text-gray-600">{{ $abono->registradoPor->name ?? '—' }}</td>
                        <td class="py-2.5"><x-badge-estado :estado="$abono->estado_pago === 'confirmado' ? 'confirmada' : $abono->estado_pago" /></td>
                        <td class="py-2.5 text-right font-medium">Bs {{ number_format($abono->monto, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-400">Aún no hay abonos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pestaña: Ventas POS --}}
    <div x-show="tab === 'pos'" class="bg-white border border-gray-200 rounded-lg p-5">
        @forelse ($reservacion->ventasPos as $venta)
            <div class="border border-gray-100 rounded-md p-3 mb-3">
                <div class="flex justify-between text-sm mb-2">
                    <p class="font-medium">{{ $venta->folio }} · {{ $venta->fecha->format('d M Y') }}</p>
                    <p class="font-medium">Bs {{ number_format($venta->total, 2) }}</p>
                </div>
                <ul class="text-sm text-gray-600 space-y-0.5">
                    @foreach ($venta->detalles as $detalle)
                        <li>{{ $detalle->cantidad }} x {{ $detalle->producto->nombre }} — Bs {{ number_format($detalle->subtotal, 2) }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-6">No hay ventas del POS vinculadas a este evento.</p>
        @endforelse
    </div>

    {{-- Pestaña: Historial --}}
    <div x-show="tab === 'historial'" class="bg-white border border-gray-200 rounded-lg p-5 text-sm">
        <p class="text-gray-500">
            Creada el {{ $reservacion->created_at->format('d M Y, g:i A') }}
            @if ($reservacion->creadoPor) por {{ $reservacion->creadoPor->name }} @endif
            · Origen: {{ $reservacion->origen === 'portal_publico' ? 'Solicitud del portal público' : 'Alta manual' }}
        </p>
        {{-- Nota: bitácora detallada de cambios de estado queda para cuando se integre
             spatie/laravel-activitylog (ver backlog, Fase 8). --}}
    </div>

    {{-- Modal: Registrar abono --}}
    <div x-show="modalAbono" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" style="display:none">
        <div @click.outside="modalAbono = false" class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-base font-medium mb-4">Registrar abono</h2>

            <form method="POST" action="{{ route('admin.reservaciones.pagos.store', $reservacion) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto (Bs)</label>
                    <input type="number" step="0.01" min="0.01" name="monto" required
                           max="{{ $reservacion->saldo_pendiente }}"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago</label>
                    <div class="flex gap-2">
                        <button type="button" @click="metodoPago = 'efectivo'"
                                :class="metodoPago === 'efectivo' ? 'bg-gray-900 text-white' : 'border border-gray-300 text-gray-600'"
                                class="flex-1 text-sm py-2 rounded-md">Efectivo</button>
                        <button type="button" @click="metodoPago = 'qr'"
                                :class="metodoPago === 'qr' ? 'bg-gray-900 text-white' : 'border border-gray-300 text-gray-600'"
                                class="flex-1 text-sm py-2 rounded-md">QR</button>
                    </div>
                    <input type="hidden" name="metodo_pago" :value="metodoPago">
                </div>

                <div x-show="metodoPago === 'qr'" class="text-xs text-gray-500 bg-gray-50 rounded-md p-3">
                    Al guardar, el sistema generará el QR con el banco configurado para tu negocio
                    y esperará confirmación (ver módulo de Pagos QR, Fase 5).
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                    <input type="text" name="notas" class="w-full rounded-md border-gray-300 text-sm">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modalAbono = false"
                            class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600">Cancelar</button>
                    <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                        Guardar abono
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

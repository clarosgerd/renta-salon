@extends('layouts.admin')

@section('titulo', 'Corte de caja')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Mi corte de caja de hoy</h1>
            <p class="text-sm text-gray-500">{{ now()->format('d/m/Y') }} — ventas y pagos que registraste tú.</p>
        </div>
        <a href="{{ route('admin.pos.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Volver al POS</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">Efectivo recibido</p>
            <p class="text-xl font-semibold text-gray-900 mt-1">Bs. {{ number_format($efectivo, 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500">QR confirmado</p>
            <p class="text-xl font-semibold text-gray-900 mt-1">Bs. {{ number_format($qrConfirmado, 2) }}</p>
        </div>
        <div class="bg-white border {{ $qrPendiente > 0 ? 'border-amber-300' : 'border-gray-200' }} rounded-lg p-4">
            <p class="text-xs text-gray-500">QR pendiente de confirmar</p>
            <p class="text-xl font-semibold {{ $qrPendiente > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-1">
                Bs. {{ number_format($qrPendiente, 2) }}
            </p>
            @if ($qrPendiente > 0)
                <p class="text-xs text-amber-600 mt-1">Hay pagos QR sin confirmar todavía.</p>
            @endif
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <p class="text-xs text-emerald-700">Total a entregar/reportar</p>
            <p class="text-xl font-semibold text-emerald-800 mt-1">Bs. {{ number_format($totalAEntregar, 2) }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">Ventas POS de hoy</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2">Folio</th>
                    <th class="text-left px-4 py-2">Reservación</th>
                    <th class="text-left px-4 py-2">Método</th>
                    <th class="text-right px-4 py-2">Total</th>
                    <th class="text-left px-4 py-2">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($ventas as $venta)
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $venta->folio }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $venta->reservacion->folio ?? 'Mostrador' }}</td>
                        <td class="px-4 py-2 uppercase text-gray-600">{{ $venta->metodo_pago }}</td>
                        <td class="px-4 py-2 text-right">Bs. {{ number_format($venta->total, 2) }}</td>
                        <td class="px-4 py-2">
                            @if ($venta->metodo_pago === 'qr')
                                <span class="text-xs {{ $venta->pagoQr?->estaPagado() ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $venta->pagoQr?->estaPagado() ? 'Pagado' : 'Pendiente' }}
                                </span>
                            @else
                                <span class="text-xs text-emerald-700">Pagado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin ventas hoy todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">Abonos de reservación registrados hoy</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-2">Reservación</th>
                    <th class="text-left px-4 py-2">Método</th>
                    <th class="text-right px-4 py-2">Monto</th>
                    <th class="text-left px-4 py-2">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($abonos as $abono)
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $abono->reservacion->folio ?? '—' }}</td>
                        <td class="px-4 py-2 uppercase text-gray-600">{{ $abono->metodo_pago }}</td>
                        <td class="px-4 py-2 text-right">Bs. {{ number_format($abono->monto, 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs {{ $abono->estado_pago === 'confirmado' ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $abono->estado_pago === 'confirmado' ? 'Confirmado' : 'Pendiente' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Sin abonos registrados hoy.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 0; }
        .muted { color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f7f7f7; font-size: 10px; text-transform: uppercase; color: #888; }
        .totales td { font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Estado de cuenta — {{ $reservacion->folio }}</h1>
    <p class="muted">{{ $reservacion->salon->negocio->nombre_comercial ?? '' }}</p>

    <p>
        <strong>Cliente:</strong> {{ $reservacion->cliente_nombre }}<br>
        <strong>Salón:</strong> {{ $reservacion->salon->nombre }}<br>
        <strong>Paquete:</strong> {{ $reservacion->paquete->nombre ?? '—' }}<br>
        <strong>Fecha del evento:</strong> {{ $reservacion->fecha_evento->format('d/m/Y') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Método</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reservacion->pagosAbonos->where('estado_pago', 'confirmado') as $abono)
                <tr>
                    <td>{{ $abono->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $abono->metodo_pago === 'qr' ? 'QR' : 'Efectivo' }}</td>
                    <td class="text-right">Bs {{ number_format($abono->monto, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totales">
                <td colspan="2">Total del evento</td>
                <td class="text-right">Bs {{ number_format($reservacion->precio_total, 2) }}</td>
            </tr>
            <tr class="totales">
                <td colspan="2">Total abonado</td>
                <td class="text-right">Bs {{ number_format($reservacion->total_abonado, 2) }}</td>
            </tr>
            <tr class="totales">
                <td colspan="2">Saldo pendiente</td>
                <td class="text-right">Bs {{ number_format($reservacion->saldo_pendiente, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

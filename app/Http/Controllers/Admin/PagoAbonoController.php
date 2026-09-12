<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoAbonoRequest;
use App\Models\PagoAbono;
use App\Models\Reservacion;
use App\Services\PagoAbonoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PagoAbonoController extends Controller
{
    public function __construct(private readonly PagoAbonoService $pagos)
    {
    }

    /**
     * POST /admin/reservaciones/{reservacion}/pagos
     * Registra el abono. Si es QR, la vista se queda escuchando el
     * polling de `estadoQr()` hasta que se confirme.
     */
    public function store(StorePagoAbonoRequest $request, Reservacion $reservacion): RedirectResponse
    {
        try {
            $abono = $this->pagos->registrar($reservacion, [
                ...$request->validated(),
                'registrado_por' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['monto' => $e->getMessage()]);
        }

        if ($abono->esQr()) {
            return back()->with([
                'success' => 'QR generado. Esperando confirmación del pago...',
                'abono_qr_id' => $abono->id,
            ]);
        }

        return back()->with('success', 'Abono registrado correctamente.');
    }

    /**
     * GET /admin/pagos/{pagoAbono}/estado-qr
     * Endpoint de polling que consulta el modal mientras el QR está en pantalla.
     * Devuelve JSON para no recargar la página.
     */
    public function estadoQr(Request $request, PagoAbono $pagoAbono): JsonResponse
    {
        $this->authorize('cambiarEstado', $pagoAbono->reservacion);

        $estado = $this->pagos->consultarEstadoQr($pagoAbono);

        return response()->json([
            'estado' => $estado,
            'saldo_pendiente' => $pagoAbono->reservacion->fresh()->saldo_pendiente,
        ]);
    }

    /**
     * PATCH /admin/pagos/{pagoAbono}/confirmar-manual
     * Respaldo cuando la consulta automática al banco falla o tarda.
     */
    public function confirmarManual(Request $request, PagoAbono $pagoAbono): RedirectResponse
    {
        $this->authorize('cambiarEstado', $pagoAbono->reservacion);

        $request->validate([
            'comprobante_url' => ['nullable', 'string', 'max:255'],
        ]);

        $this->pagos->confirmarManualmente($pagoAbono, $request->comprobante_url);

        return back()->with('success', 'Pago confirmado manualmente.');
    }
}

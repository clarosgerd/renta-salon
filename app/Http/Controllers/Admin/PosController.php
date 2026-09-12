<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVentaPosRequest;
use App\Models\PagoAbono;
use App\Models\Producto;
use App\Models\Reservacion;
use App\Models\VentaPos;
use App\Services\VentaPosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * POS (Fase 7, 11/09/2026) — ver CJ-1..CJ-4/§3.8-§3.9. Mismo patrón que
 * PagoAbonoController: registrar → si es QR, generarQr ya quedó adentro
 * del Service → polling estadoQr() → confirmarManual() de respaldo.
 */
class PosController extends Controller
{
    public function __construct(private readonly VentaPosService $ventas)
    {
    }

    /**
     * GET /admin/pos — pantalla de venta
     */
    public function index(): View
    {
        $this->authorize('create', VentaPos::class);

        $productos = Producto::activos()->with('inventarios')
            ->orderBy('categoria')->orderBy('nombre')->get();

        // Selector "Vincular a reservación" — solo activas (pendiente/
        // confirmada), venta de mostrador si se deja vacío (§3.8).
        $reservacionesActivas = Reservacion::whereIn('estado', ['pendiente', 'confirmada'])
            ->orderByDesc('fecha_evento')
            ->limit(50)
            ->get(['id', 'folio', 'cliente_nombre']);

        // Si venimos de un store() con QR recién generado, la vista
        // muestra el código + arranca el polling automáticamente.
        $ventaQr = session('venta_qr_id') ? VentaPos::with('pagoQr')->find(session('venta_qr_id')) : null;

        return view('admin.pos.index', compact('productos', 'reservacionesActivas', 'ventaQr'));
    }

    /**
     * POST /admin/pos
     */
    public function store(StoreVentaPosRequest $request): RedirectResponse
    {
        try {
            $venta = $this->ventas->registrar($request->validated(), $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        if ($venta->esQr()) {
            return back()->with([
                'success' => 'QR generado. Esperando confirmación del pago...',
                'venta_qr_id' => $venta->id,
            ]);
        }

        return back()->with('success', "Venta {$venta->folio} registrada correctamente. Total: Bs. {$venta->total}.");
    }

    /**
     * GET /admin/ventas-pos/{venta}/estado-qr — polling
     */
    public function estadoQr(VentaPos $venta): JsonResponse
    {
        $this->authorize('view', $venta);

        $estado = $this->ventas->consultarEstadoQr($venta);

        return response()->json(['estado' => $estado]);
    }

    /**
     * PATCH /admin/ventas-pos/{venta}/confirmar-manual
     */
    public function confirmarManual(VentaPos $venta): RedirectResponse
    {
        $this->authorize('view', $venta);

        $this->ventas->confirmarManualmente($venta);

        return back()->with('success', 'Pago confirmado manualmente.');
    }

    /**
     * GET /admin/pos/corte — "mi corte de caja del día" (CJ-4). Reporte
     * en vivo, sin bloqueo de turno (decisión confirmada con el usuario).
     */
    public function corte(Request $request): View
    {
        $this->authorize('viewAny', VentaPos::class);

        $cajero = $request->user();
        $hoy = now()->toDateString();

        $ventas = VentaPos::with(['pagoQr', 'reservacion'])
            ->where('cajero_id', $cajero->id)
            ->whereDate('fecha', $hoy)
            ->where('estado', 'pagada')
            ->get();

        $abonos = PagoAbono::with(['pagoQr', 'reservacion'])
            ->where('registrado_por', $cajero->id)
            ->whereDate('fecha_pago', $hoy)
            ->get();

        $efectivo = $ventas->where('metodo_pago', 'efectivo')->sum('total')
            + $abonos->where('metodo_pago', 'efectivo')->where('estado_pago', 'confirmado')->sum('monto');

        $qrConfirmado = $ventas->where('metodo_pago', 'qr')->filter(fn (VentaPos $v) => $v->pagoQr?->estaPagado())->sum('total')
            + $abonos->where('metodo_pago', 'qr')->where('estado_pago', 'confirmado')->sum('monto');

        $qrPendiente = $ventas->where('metodo_pago', 'qr')->filter(fn (VentaPos $v) => ! $v->pagoQr?->estaPagado())->sum('total')
            + $abonos->where('metodo_pago', 'qr')->where('estado_pago', 'pendiente')->sum('monto');

        $totalAEntregar = $efectivo + $qrConfirmado;

        return view('admin.pos.corte', compact(
            'ventas', 'abonos', 'efectivo', 'qrConfirmado', 'qrPendiente', 'totalAEntregar'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class EstadoCuentaController extends Controller
{
    /**
     * GET /admin/reservaciones/{reservacion}/estado-cuenta.pdf
     */
    public function pdf(Reservacion $reservacion): Response
    {
        $this->authorize('view', $reservacion);

        $reservacion->load(['salon', 'paquete', 'pagosAbonos' => fn ($q) => $q->orderBy('fecha_pago')]);

        $pdf = Pdf::loadView('pdf.estado-cuenta', compact('reservacion'));

        return $pdf->stream("estado-cuenta-{$reservacion->folio}.pdf");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paquete;
use App\Models\Reservacion;
use App\Models\Salon;
use Illuminate\View\View;

/**
 * Stub mínimo (no el dashboard completo de Fase 2, ver plan de
 * activación multi-tenant) — existe porque el login ya redirige a
 * route('dashboard') y el sidebar de layouts.admin necesita algo real
 * detrás de ese link. NegocioScope filtra automáticamente por el negocio
 * actual (Salon/Paquete/Reservacion usan BelongsToNegocio), así que estos
 * conteos ya son correctos sin filtrar negocio_id a mano acá.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'salonesActivos' => Salon::activos()->count(),
            'paquetesActivos' => Paquete::activos()->count(),
            'reservacionesPendientes' => Reservacion::where('estado', 'pendiente')->count(),
        ]);
    }
}

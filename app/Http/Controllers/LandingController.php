<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Landing del producto RentSalon Pro (11/09/2026) — vive en el dominio
 * PELADO (rentsalon-pro.test, sin subdominio), fuera del middleware
 * 'negocio' — no hay "negocio actual" acá, es la página del producto en
 * sí, distinta de cualquier portal público de un negocio cliente (ver
 * PortalController, Fase 6). Sin CRM/captura de leads: el CTA es un
 * mailto, no hay tabla ni formulario propio todavía.
 */
class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing');
    }
}

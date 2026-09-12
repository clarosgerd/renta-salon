<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IdentificarNegocio;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('negocio', [
        IdentificarNegocio::class,
    ]);

        // Bug real encontrado en Fase 6 (11/09/2026): sin esto,
        // IdentificarNegocio podía correr DESPUÉS de SubstituteBindings en
        // rutas sin 'auth' (ej. el portal público) — Laravel ordena el
        // middleware de una request según $middlewarePriority, y como
        // IdentificarNegocio no estaba en esa lista, su posición relativa a
        // SubstituteBindings dependía de qué OTRO middleware hubiera en la
        // ruta. En /admin/* (con 'auth', que sí está priorizado antes que
        // SubstituteBindings) el orden salía bien por casualidad; en el
        // portal público (sin 'auth') el route model binding de {salon}/
        // {paquete} se resolvía ANTES de que app('negocio_actual') existiera
        // — NegocioScope no filtraba nada todavía, y un salón de OTRO
        // negocio pasaba route model binding sin dar 404. Encontrado por un
        // test de aislamiento multi-tenant en el portal, no manualmente.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: IdentificarNegocio::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

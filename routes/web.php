<?php

use App\Http\Controllers\Admin\CalendarioController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EstadoCuentaController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\PagoAbonoController;
use App\Http\Controllers\Admin\PaqueteController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\ReservacionController;
use App\Http\Controllers\Admin\SalonController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ImpersonacionEntradaController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Plataforma\ImpersonacionController;
use App\Http\Controllers\Plataforma\NegocioConfigPagoController;
use App\Http\Controllers\Plataforma\NegocioController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SolicitudController;
use Illuminate\Support\Facades\Route;

/**
 * Panel Super Admin Plataforma (11/09/2026) — Fase 1, ver
 * brain/Plan_Implementacion_RentSalon_Pro_v2.md y SA-1/SA-2/SA-3 en
 * brain/Historias_Usuario_Pantallas_RentSalon_Pro.md. Este dominio queda
 * deliberadamente FUERA del middleware 'negocio' (ver docblock de
 * IdentificarNegocio: así el Super Admin ve todos los negocios, sin que
 * ninguno se resuelva como "el negocio actual"). Login separado del lado
 * tenant (mismo controller/vista, nombres de ruta distintos: 'login'/
 * 'logout' ya los usa auth.php de abajo) — sin registro ni reset de
 * password acá, cuentas de Super Admin se crean por seeder/tinker.
 *
 * IMPORTANTE — orden de registro: este grupo tiene que ir ANTES del grupo
 * 'negocio' de abajo. Laravel matchea rutas en el orden en que se
 * registran, no por especificidad — el `POST /login` de auth.php (dentro
 * del grupo 'negocio', SIN restricción de dominio) matchea CUALQUIER host,
 * incluido admin.rentsalon-pro.test. Si ese grupo se registrara primero,
 * "atraparía" las requests de la plataforma antes de llegar nunca a este
 * `Route::domain(...)` (bug real encontrado con un test: el login de
 * plataforma daba 404 en vez de redirigir).
 */
Route::domain('admin.rentsalon-pro.test')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('plataforma.login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('plataforma.logout');

        Route::get('/', fn () => redirect()->route('plataforma.negocios.index'));

        Route::prefix('plataforma')->name('plataforma.')->group(function () {
            // Sin show/destroy — "suspender" (toggle-estado) cubre la
            // baja, mismo criterio que Salon/Paquete (SA-2, sin borrar
            // historial).
            Route::prefix('negocios')->name('negocios.')->group(function () {
                Route::get('/', [NegocioController::class, 'index'])->name('index');
                Route::get('/crear', [NegocioController::class, 'create'])->name('create');
                Route::post('/', [NegocioController::class, 'store'])->name('store');
                Route::get('/{negocio}/editar', [NegocioController::class, 'edit'])->name('edit');
                Route::put('/{negocio}', [NegocioController::class, 'update'])->name('update');
                Route::patch('/{negocio}/toggle-estado', [NegocioController::class, 'toggleEstado'])->name('toggle-estado');

                // Cobros (SA-4, 11/09/2026) — banco QR + credenciales
                // cifradas, ver Historias_Usuario_Pantallas §1.1. Solo
                // editable desde acá, nunca desde el panel del negocio.
                Route::prefix('{negocio}/config-pago')->name('config-pago.')->group(function () {
                    Route::get('/', [NegocioConfigPagoController::class, 'edit'])->name('edit');
                    Route::put('/', [NegocioConfigPagoController::class, 'update'])->name('update');
                    Route::post('/probar-conexion', [NegocioConfigPagoController::class, 'probarConexion'])->name('probar-conexion');
                });

                // Impersonar (SA-5, 11/09/2026) — entra automáticamente
                // como el primer admin_negocio del negocio, sin selector.
                Route::post('/{negocio}/impersonar', [ImpersonacionController::class, 'iniciar'])->name('impersonar');
            });

            // Log de auditoría de impersonación (SA-5) — solo lectura.
            Route::get('/impersonaciones', [ImpersonacionController::class, 'historial'])->name('impersonaciones.index');
        });
    });
});

/**
 * Landing del producto RentSalon Pro (11/09/2026) — dominio PELADO
 * (rentsalon-pro.test, sin subdominio ni dominio propio de ningún
 * negocio). Antes de esto, ese host daba 404 real: IdentificarNegocio no
 * encontraba ningún Negocio con subdominio='rentsalon-pro' (no existe
 * ninguno con ese nombre) — comportamiento correcto para un tenant
 * inexistente, pero el dominio raíz merece su propia página de producto,
 * no un 404. Mismo criterio de orden de registro que el bloque de
 * Plataforma de arriba: este Route::domain() va ANTES del grupo 'negocio'.
 */
Route::domain('rentsalon-pro.test')->get('/', [LandingController::class, 'index'])->name('landing');

/**
 * Activación del ruteo multi-tenant (10/09/2026) — hasta ahora este
 * archivo tenía TODO el bloque de abajo comentado (incluido /dashboard,
 * del que depende el login) y las rutas de Reservaciones/Pagos activas
 * SIN prefijo /admin ni middleware auth/negocio — la app no funcionaba de
 * punta a punta (ni el login).
 *
 * Todo lo de acá abajo cuelga del middleware 'negocio' (IdentificarNegocio,
 * registrado en bootstrap/app.php) — resuelve el tenant por
 * subdominio/dominio propio, 404 si no existe, 403 si está suspendido.
 */
Route::middleware('negocio')->group(function () {
    // Portal público (Fase 6, 11/09/2026) — ver CL-1/CL-2/CL-3/§3.14. Sin
    // 'auth': cualquiera navega el subdominio sin login. NegocioScope ya
    // filtra Salon/Paquete/Reservacion por el negocio resuelto acá arriba.
    Route::name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'home'])->name('home');
        Route::get('/salones', [PortalController::class, 'salones'])->name('salones');
        Route::get('/salones/{salon}', [PortalController::class, 'salon'])->name('salon');
        Route::get('/salones/{salon}/disponibilidad', [PortalController::class, 'disponibilidad'])->name('disponibilidad');
        Route::get('/paquetes', [PortalController::class, 'paquetes'])->name('paquetes');
        Route::get('/paquetes/{paquete}', [PortalController::class, 'paquete'])->name('paquete');

        Route::get('/solicitar', [SolicitudController::class, 'create'])->name('solicitud.create');
        // Throttle: formulario público sin login, expuesto a spam/abuso.
        Route::post('/solicitar', [SolicitudController::class, 'store'])
            ->middleware('throttle:10,1')->name('solicitud.store');
    });

    require __DIR__.'/auth.php';

    // Impersonar — recepción del lado tenant (SA-5, 11/09/2026). 'entrar'
    // va fuera de 'auth' (todavía no hay sesión en este dominio) pero
    // protegida por firma de un solo uso de 60s — ver
    // Plataforma\ImpersonacionController::iniciar.
    Route::get('/impersonar/entrar/{log}', [ImpersonacionEntradaController::class, 'entrar'])
        ->middleware('signed')->name('impersonar.entrar');
    Route::post('/impersonar/salir', [ImpersonacionEntradaController::class, 'salir'])
        ->middleware('auth')->name('impersonar.salir');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::middleware('auth')->prefix('admin')->group(function () {
        Route::prefix('reservaciones')->name('admin.reservaciones.')->group(function () {
            Route::get('/', [ReservacionController::class, 'index'])->name('index');
            Route::get('/crear', [ReservacionController::class, 'create'])->name('create');
            Route::post('/', [ReservacionController::class, 'store'])->name('store');
            Route::get('/{reservacion}', [ReservacionController::class, 'show'])->name('show');
            Route::get('/{reservacion}/editar', [ReservacionController::class, 'edit'])->name('edit');
            Route::put('/{reservacion}', [ReservacionController::class, 'update'])->name('update');

            Route::patch('/{reservacion}/confirmar', [ReservacionController::class, 'confirmar'])->name('confirmar');
            Route::patch('/{reservacion}/rechazar', [ReservacionController::class, 'rechazar'])->name('rechazar');
            Route::patch('/{reservacion}/cancelar', [ReservacionController::class, 'cancelar'])->name('cancelar');
            Route::patch('/{reservacion}/finalizar', [ReservacionController::class, 'finalizar'])->name('finalizar');

            // Pagos y abonos de esta reservación específica
            Route::post('/{reservacion}/pagos', [PagoAbonoController::class, 'store'])->name('pagos.store');

            // Estado de cuenta en PDF
            Route::get('/{reservacion}/estado-cuenta.pdf', [EstadoCuentaController::class, 'pdf'])
                ->name('estado-cuenta.pdf');
        });

        // Acciones sobre un abono puntual (no anidadas bajo reservación,
        // porque el PagoAbono ya sabe a qué reservación pertenece vía
        // route model binding).
        Route::prefix('pagos')->name('admin.pagos.')->group(function () {
            Route::get('/{pagoAbono}/estado-qr', [PagoAbonoController::class, 'estadoQr'])->name('estado-qr');
            Route::patch('/{pagoAbono}/confirmar-manual', [PagoAbonoController::class, 'confirmarManual'])->name('confirmar-manual');
        });

        // Salones (10/09/2026) — ver AN-1 / Historias_Usuario_Pantallas §3.6.
        // Sin show/destroy: el "toggle-activo" cubre "ocultar del portal
        // sin borrar historial", pedido explícito en vez de un delete real.
        Route::prefix('salones')->name('admin.salones.')->group(function () {
            Route::get('/', [SalonController::class, 'index'])->name('index');
            Route::get('/crear', [SalonController::class, 'create'])->name('create');
            Route::post('/', [SalonController::class, 'store'])->name('store');
            Route::get('/{salon}/editar', [SalonController::class, 'edit'])->name('edit');
            Route::put('/{salon}', [SalonController::class, 'update'])->name('update');
            Route::patch('/{salon}/toggle-activo', [SalonController::class, 'toggleActivo'])->name('toggle-activo');
            Route::patch('/{salon}/imagenes/{imagen}/mover', [SalonController::class, 'moverImagen'])->name('imagenes.mover');
            Route::delete('/{salon}/imagenes/{imagen}', [SalonController::class, 'eliminarImagen'])->name('imagenes.destroy');
        });

        // Paquetes (10/09/2026) — ver AN-2 / Historias_Usuario_Pantallas §3.7.
        Route::prefix('paquetes')->name('admin.paquetes.')->group(function () {
            Route::get('/', [PaqueteController::class, 'index'])->name('index');
            Route::get('/crear', [PaqueteController::class, 'create'])->name('create');
            Route::post('/', [PaqueteController::class, 'store'])->name('store');
            Route::get('/{paquete}/editar', [PaqueteController::class, 'edit'])->name('edit');
            Route::put('/{paquete}', [PaqueteController::class, 'update'])->name('update');
            Route::patch('/{paquete}/toggle-activo', [PaqueteController::class, 'toggleActivo'])->name('toggle-activo');
        });

        // Calendario maestro (11/09/2026) — ver AN-3 / Historias_Usuario_Pantallas §3.2.
        Route::prefix('calendario')->name('admin.calendario.')->group(function () {
            Route::get('/', [CalendarioController::class, 'index'])->name('index');
            Route::get('/eventos', [CalendarioController::class, 'eventos'])->name('eventos');
        });

        // POS (Fase 7, 11/09/2026) — ver CJ-1..CJ-4 / §3.8-§3.9. El corte
        // de caja es un reporte del día, sin bloqueo de "cerrar turno"
        // (decisión confirmada con el usuario — no hay tabla turnos).
        Route::prefix('pos')->name('admin.pos.')->group(function () {
            Route::get('/', [PosController::class, 'index'])->name('index');
            Route::post('/', [PosController::class, 'store'])->name('store');
            Route::get('/corte', [PosController::class, 'corte'])->name('corte');
        });

        // Acciones sobre una venta puntual (no anidadas bajo pos, mismo
        // criterio que admin.pagos.* con PagoAbono).
        Route::prefix('ventas-pos')->name('admin.ventas-pos.')->group(function () {
            Route::get('/{venta}/estado-qr', [PosController::class, 'estadoQr'])->name('estado-qr');
            Route::patch('/{venta}/confirmar-manual', [PosController::class, 'confirmarManual'])->name('confirmar-manual');
        });

        // Productos (Fase 7, 11/09/2026) — ver AN-7 / §3.10. Sin destroy,
        // mismo criterio "no borrar historial" que Salon/Paquete.
        Route::prefix('productos')->name('admin.productos.')->group(function () {
            Route::get('/', [ProductoController::class, 'index'])->name('index');
            Route::get('/crear', [ProductoController::class, 'create'])->name('create');
            Route::post('/', [ProductoController::class, 'store'])->name('store');
            Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->name('edit');
            Route::put('/{producto}', [ProductoController::class, 'update'])->name('update');
            Route::patch('/{producto}/toggle-activo', [ProductoController::class, 'toggleActivo'])->name('toggle-activo');
        });

        // Inventario (Fase 7, 11/09/2026) — ver §3.10. Solo "entrada
        // manual" (reabasto); las salidas por venta se generan solas
        // desde VentaPosService.
        Route::prefix('inventario')->name('admin.inventario.')->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::get('/movimientos', [InventarioController::class, 'movimientos'])->name('movimientos');
            Route::post('/movimientos', [InventarioController::class, 'registrarEntrada'])->name('movimientos.store');
        });

        // Usuarios (Fase 8, 11/09/2026) — ver AN-6/§3.11. Sin destroy,
        // mismo criterio "no borrar historial" que el resto del panel.
        Route::prefix('usuarios')->name('admin.usuarios.')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('index');
            Route::get('/crear', [UsuarioController::class, 'create'])->name('create');
            Route::post('/', [UsuarioController::class, 'store'])->name('store');
            Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
            Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
            Route::patch('/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('toggle-activo');
        });
    });
});

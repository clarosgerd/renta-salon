<?php

use App\Http\Controllers\Admin\EstadoCuentaController;
use App\Http\Controllers\Admin\PagoAbonoController;
use App\Http\Controllers\Admin\ReservacionController;
use Illuminate\Support\Facades\Route;

/**
 * Incluir dentro del grupo con middleware ['negocio', 'auth'] y prefix('admin'),
 * tal como se definió en la guía de configuración del proyecto.
 */
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

// Acciones sobre un abono puntual (no anidadas bajo reservación, porque el
// PagoAbono ya sabe a qué reservación pertenece vía route model binding).
Route::prefix('pagos')->name('admin.pagos.')->group(function () {
    Route::get('/{pagoAbono}/estado-qr', [PagoAbonoController::class, 'estadoQr'])->name('estado-qr');
    Route::patch('/{pagoAbono}/confirmar-manual', [PagoAbonoController::class, 'confirmarManual'])->name('confirmar-manual');
});

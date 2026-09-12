<?php

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
});

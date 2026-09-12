<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('reservacion_id')->constrained('reservaciones')->cascadeOnDelete();

            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->enum('metodo_pago', ['efectivo', 'qr'])->default('efectivo');
            $table->enum('estado_pago', ['pendiente', 'confirmado', 'rechazado'])->default('confirmado');
            $table->string('referencia_transaccion')->nullable();
            $table->string('comprobante_url')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['reservacion_id', 'estado_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_abonos');
    }
};

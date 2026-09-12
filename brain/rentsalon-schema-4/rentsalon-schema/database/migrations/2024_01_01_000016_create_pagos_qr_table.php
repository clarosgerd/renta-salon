<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_abono_id')->nullable()->constrained('pagos_abonos')->cascadeOnDelete();
            $table->foreignId('venta_pos_id')->nullable()->constrained('ventas_pos')->cascadeOnDelete();

            $table->enum('proveedor_qr', ['union', 'bnb', 'bcp'])->nullable();
            $table->string('qr_id_externo')->nullable();
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('BOB');
            $table->enum('estado', ['generado', 'pagado', 'expirado', 'fallido'])->default('generado');

            $table->timestamp('fecha_generacion')->useCurrent();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->json('payload_respuesta')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_qr');
    }
};

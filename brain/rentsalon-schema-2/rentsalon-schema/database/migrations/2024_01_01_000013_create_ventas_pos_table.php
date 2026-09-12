<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('reservacion_id')->nullable()->constrained('reservaciones')->nullOnDelete();
            $table->string('folio')->unique();

            $table->date('fecha');
            $table->decimal('total', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'qr'])->default('efectivo');
            $table->enum('estado', ['pagada', 'cancelada'])->default('pagada');

            $table->foreignId('cajero_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['negocio_id', 'fecha', 'cajero_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_pos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();

            $table->enum('tipo', ['entrada', 'salida_venta', 'ajuste']);
            $table->integer('cantidad'); // positivo entrada/ajuste+, negativo salida/ajuste-
            $table->foreignId('referencia_venta_id')->nullable()->constrained('ventas_pos')->nullOnDelete();

            $table->date('fecha');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['producto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};

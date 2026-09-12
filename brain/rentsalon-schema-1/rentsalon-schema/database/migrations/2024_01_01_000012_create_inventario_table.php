<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            // null = almacén general del negocio; con salon_id = stock específico de ese salón
            $table->foreignId('salon_id')->nullable()->constrained('salones')->cascadeOnDelete();

            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->timestamp('ultima_actualizacion')->nullable();
            $table->timestamps();

            $table->unique(['producto_id', 'salon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};

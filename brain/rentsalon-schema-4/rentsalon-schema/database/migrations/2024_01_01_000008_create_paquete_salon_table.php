<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Un paquete puede aplicar a uno, varios o todos los salones del negocio.
    // Si un paquete no tiene filas aquí, la app lo interpreta como "aplica a todos".
    public function up(): void
    {
        Schema::create('paquete_salon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paquete_id')->constrained('paquetes')->cascadeOnDelete();
            $table->foreignId('salon_id')->constrained('salones')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['paquete_id', 'salon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquete_salon');
    }
};

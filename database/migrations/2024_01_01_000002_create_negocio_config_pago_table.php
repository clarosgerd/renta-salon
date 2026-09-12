<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocio_config_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->enum('banco', ['union', 'bnb', 'bcp'])->nullable();
            $table->text('credenciales_json_cifrado')->nullable();
            $table->string('cuenta_destino')->nullable();
            $table->boolean('activo')->default(false);
            $table->timestamp('ultima_prueba_conexion')->nullable();
            $table->timestamps();

            $table->unique('negocio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocio_config_pago');
    }
};

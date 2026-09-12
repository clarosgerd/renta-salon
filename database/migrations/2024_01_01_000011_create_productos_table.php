<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre');
            $table->enum('categoria', ['mobiliario', 'decoracion', 'bebidas', 'alimentos', 'otro'])
                ->default('otro');
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('costo', 10, 2)->nullable();
            $table->string('unidad_medida')->default('unidad');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['negocio_id', 'activo', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

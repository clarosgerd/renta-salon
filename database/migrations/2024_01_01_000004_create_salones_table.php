<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('capacidad_min')->nullable();
            $table->unsignedInteger('capacidad_max');
            $table->string('ubicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['negocio_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre');
            $table->enum('tipo_evento', ['boda', 'xv_anos', 'corporativo', 'otro'])->default('otro');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_base', 10, 2);
            $table->unsignedInteger('duracion_horas')->default(4);
            $table->json('servicios_incluidos')->nullable(); // ["Mantelería","Coordinador",...]
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['negocio_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquetes');
    }
};

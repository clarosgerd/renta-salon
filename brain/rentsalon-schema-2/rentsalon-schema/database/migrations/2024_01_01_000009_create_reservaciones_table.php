<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('salon_id')->constrained('salones')->restrictOnDelete();
            $table->foreignId('paquete_id')->nullable()->constrained('paquetes')->nullOnDelete();
            $table->string('folio')->unique();

            $table->string('cliente_nombre');
            $table->string('cliente_telefono');
            $table->string('cliente_email')->nullable();
            $table->unsignedInteger('num_invitados')->nullable();

            $table->date('fecha_evento');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->decimal('precio_total', 10, 2);
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'finalizada'])
                ->default('pendiente');
            $table->enum('origen', ['portal_publico', 'manual'])->default('manual');

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            // Consulta de disponibilidad: salon + fecha es el filtro más usado
            $table->index(['negocio_id', 'salon_id', 'fecha_evento', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservaciones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('subdominio')->unique();
            $table->string('dominio_personalizado')->nullable()->unique();
            $table->string('logo_url')->nullable();
            $table->string('color_primario', 7)->default('#1D9E75');
            $table->string('telefono_contacto')->nullable();
            $table->string('email_contacto')->nullable();
            $table->enum('estado', ['activo', 'suspendido'])->default('activo');
            $table->string('plan')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};

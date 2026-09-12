<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pivote: qué salones puede administrar un Admin Salón / Cajero
    public function up(): void
    {
        Schema::create('usuario_salon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('salon_id')->constrained('salones')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'salon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_salon');
    }
};

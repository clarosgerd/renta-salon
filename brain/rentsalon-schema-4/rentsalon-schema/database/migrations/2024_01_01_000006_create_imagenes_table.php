<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tabla polimórfica reutilizable para galerías: salones, productos, etc.
    public function up(): void
    {
        Schema::create('imagenes', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable'); // imageable_id, imageable_type
            $table->string('url');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes');
    }
};

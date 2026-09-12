<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonacion_logs', function (Blueprint $table) {
            $table->id();
            // nullOnDelete en los 2 FKs a users: el log de auditoría (SA-5)
            // sobrevive aunque el usuario se borre después — negocio_id sí
            // cascadea, mismo criterio que el resto de tablas tenant.
            $table->foreignId('super_admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('usuario_impersonado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_origen')->nullable();
            $table->timestamp('iniciada_en');
            $table->timestamp('finalizada_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonacion_logs');
    }
};

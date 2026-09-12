<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('negocio_id')->nullable()->after('id')
                ->constrained('negocios')->nullOnDelete();
            $table->enum('role', ['super_admin_plataforma', 'admin_negocio', 'admin_salon', 'cajero'])
                ->default('cajero')->after('negocio_id');
            $table->boolean('activo')->default(true)->after('role');

            $table->index(['negocio_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('negocio_id');
            $table->dropColumn(['role', 'activo']);
        });
    }
};

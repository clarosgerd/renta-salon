<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Panel Super Admin Plataforma (11/09/2026) — las cuentas de este rol se
 * crean por seeder/tinker, no hay autoservicio (ver routes/web.php, grupo
 * admin.rentsalon-pro.test sin registro/reset de password). Idempotente
 * (firstOrCreate), mismo criterio que NegocioDemoSeeder.
 */
class SuperAdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@rentsalonpro.test'],
            [
                'negocio_id' => null,
                'name' => 'Super Admin Demo',
                'password' => bcrypt('password'),
                'role' => 'super_admin_plataforma',
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Negocio demo + salones/paquetes de ejemplo (10/09/2026) — antes
        // no se invocaba acá, así que `php artisan db:seed` nunca sembraba
        // nada probable contra un subdominio real.
        $this->call(NegocioDemoSeeder::class);

        // Panel Super Admin Plataforma (11/09/2026) — usuario de prueba
        // para admin.rentsalon-pro.test/login.
        $this->call(SuperAdminDemoSeeder::class);
    }
}

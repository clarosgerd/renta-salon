<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\Paquete;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Seeder;

class NegocioDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotente (firstOrCreate en vez de create) — ya se corrió una vez
     * a mano contra la BD real antes de conectarse a DatabaseSeeder, un
     * create() directo fallaría por el `subdominio` unique. Extendido
     * (10/09/2026) con salones/paquetes de ejemplo para poder probar esos
     * módulos contra saloneslapaz.rentsalon-pro.test sin cargar datos a
     * mano primero.
     */
    public function run(): void
    {
        $negocio = Negocio::firstOrCreate(
            ['subdominio' => 'saloneslapaz'],
            [
                'nombre_comercial' => 'Salones La Paz',
                'color_primario' => '#1D9E75',
                'estado' => 'activo',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@saloneslapaz.test'],
            [
                'negocio_id' => $negocio->id,
                'name' => 'Admin Demo',
                'password' => bcrypt('password'),
                'role' => 'admin_negocio',
            ]
        );

        if ($negocio->salones()->count() === 0) {
            $salonJardin = Salon::create([
                'negocio_id' => $negocio->id,
                'nombre' => 'Salón Jardín',
                'descripcion' => 'Amplio salón con jardín exterior, ideal para bodas.',
                'capacidad_min' => 50,
                'capacidad_max' => 250,
                'ubicacion' => 'Av. Principal 123, La Paz',
            ]);

            $salonEjecutivo = Salon::create([
                'negocio_id' => $negocio->id,
                'nombre' => 'Salón Ejecutivo',
                'descripcion' => 'Salón más pequeño, equipado para eventos corporativos.',
                'capacidad_min' => 10,
                'capacidad_max' => 80,
                'ubicacion' => 'Av. Principal 123, La Paz',
            ]);

            Paquete::create([
                'negocio_id' => $negocio->id,
                'nombre' => 'Boda Clásica',
                'tipo_evento' => 'boda',
                'descripcion' => 'Paquete completo para bodas de hasta 200 invitados.',
                'precio_base' => 15000,
                'duracion_horas' => 8,
                'servicios_incluidos' => ['Mantelería', 'Coordinador de evento', 'Sonido básico'],
            ]);

            $paqueteCorporativo = Paquete::create([
                'negocio_id' => $negocio->id,
                'nombre' => 'Evento Corporativo',
                'tipo_evento' => 'corporativo',
                'descripcion' => 'Ideal para conferencias y capacitaciones.',
                'precio_base' => 4000,
                'duracion_horas' => 4,
                'servicios_incluidos' => ['Proyector', 'Internet', 'Coffee break'],
            ]);
            // Este paquete solo aplica al Salón Ejecutivo (no a todos) —
            // ejemplo de la regla Paquete::aplicaATodosLosSalones().
            $paqueteCorporativo->salones()->sync([$salonEjecutivo->id]);
        }
    }
}

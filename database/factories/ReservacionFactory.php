<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Reservacion;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservacion>
 */
class ReservacionFactory extends Factory
{
    protected $model = Reservacion::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'salon_id' => Salon::factory(),
            'cliente_nombre' => fake()->name(),
            'cliente_telefono' => fake()->phoneNumber(),
            'fecha_evento' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'hora_inicio' => '18:00',
            'hora_fin' => '23:00',
            'precio_total' => fake()->randomFloat(2, 2000, 15000),
            'estado' => 'confirmada',
            'origen' => 'manual',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Paquete;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paquete>
 */
class PaqueteFactory extends Factory
{
    protected $model = Paquete::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->words(3, true),
            'tipo_evento' => fake()->randomElement(['boda', 'xv_anos', 'corporativo', 'otro']),
            'descripcion' => fake()->sentence(),
            'precio_base' => fake()->randomFloat(2, 1000, 20000),
            'duracion_horas' => 4,
            'servicios_incluidos' => ['Mantelería', 'Coordinador'],
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

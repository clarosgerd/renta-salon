<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Salon>
 */
class SalonFactory extends Factory
{
    protected $model = Salon::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => 'Salón ' . fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'capacidad_min' => 20,
            'capacidad_max' => 150,
            'ubicacion' => fake()->address(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

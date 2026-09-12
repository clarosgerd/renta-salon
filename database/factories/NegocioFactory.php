<?php

namespace Database\Factories;

use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    protected $model = Negocio::class;

    public function definition(): array
    {
        return [
            'nombre_comercial' => fake()->company(),
            'subdominio' => fake()->unique()->slug(2),
            'color_primario' => '#1D9E75',
            'estado' => 'activo',
        ];
    }

    public function suspendido(): static
    {
        return $this->state(fn () => ['estado' => 'suspendido']);
    }
}

<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->words(2, true),
            'categoria' => fake()->randomElement(['mobiliario', 'decoracion', 'bebidas', 'alimentos', 'otro']),
            'precio_venta' => fake()->randomFloat(2, 5, 200),
            'costo' => fake()->randomFloat(2, 2, 100),
            'unidad_medida' => 'unidad',
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
